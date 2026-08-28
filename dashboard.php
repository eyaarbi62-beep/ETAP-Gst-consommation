<?php
require "config.php";
if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: login.php");
    exit;
}

// Totaux de consommation par type (toutes périodes confondues)
$totaux = ["electricite" => 0, "gaz" => 0, "eau" => 0];
$res = $conn->query("
    SELECT c.type, SUM(cc.consommation) AS total
    FROM concerne cc
    JOIN compteur c ON cc.id_compteur = c.id_compteur
    GROUP BY c.type
");
while ($row = $res->fetch_assoc()) {
    $totaux[$row["type"]] = $row["total"];
}

$nb_locaux = $conn->query("SELECT COUNT(*) AS n FROM local")->fetch_assoc()["n"];
$nb_compteurs = $conn->query("SELECT COUNT(*) AS n FROM compteur")->fetch_assoc()["n"];
$nb_factures = $conn->query("SELECT COUNT(*) AS n FROM facture")->fetch_assoc()["n"];

$mois_labels = [];
$mois_data = ["electricite" => [], "gaz" => [], "eau" => []];
$date_mois = new DateTime("first day of this month");
$date_mois->modify("-35 months");
for ($i = 0; $i < 36; $i++) {
    $mois_labels[] = $date_mois->format("Y-m");
    $date_mois->modify("+1 month");
}

$res_mois = $conn->query("
    SELECT DATE_FORMAT(fa.periode, '%Y-%m') AS mois, c.type, SUM(cc.consommation) AS total
    FROM concerne cc
    JOIN compteur c ON cc.id_compteur = c.id_compteur
    JOIN facture fa ON cc.id_facture = fa.id_facture
    WHERE fa.periode >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 35 MONTH)
    GROUP BY mois, c.type
    ORDER BY mois
");
$totaux_mois = [];
while ($row = $res_mois->fetch_assoc()) {
    $totaux_mois[$row["mois"]][$row["type"]] = (float) $row["total"];
}
foreach ($mois_labels as $mois) {
    foreach ($mois_data as $type => $_) {
        $mois_data[$type][] = $totaux_mois[$mois][$type] ?? 0;
    }
}

$regions_labels = [];
$regions_data = [];
$res_regions = $conn->query("
    SELECT r.nom AS region, SUM(cc.consommation) AS total
    FROM concerne cc
    JOIN compteur c ON cc.id_compteur = c.id_compteur
    JOIN facture fa ON cc.id_facture = fa.id_facture
    JOIN local l ON c.id_local = l.id_local
    JOIN region r ON l.id_region = r.id_region
    GROUP BY r.id_region, r.nom
    ORDER BY total DESC
");
while ($row = $res_regions->fetch_assoc()) {
    $regions_labels[] = $row["region"];
    $regions_data[] = (float) $row["total"];
}

$types_labels = [];
$types_data = [];
$res_types = $conn->query("
    SELECT c.type, SUM(cc.consommation) AS total
    FROM concerne cc
    JOIN compteur c ON cc.id_compteur = c.id_compteur
    GROUP BY c.type
    ORDER BY total DESC
");
while ($row = $res_types->fetch_assoc()) {
    $types_labels[] = $row["type"];
    $types_data[] = (float) $row["total"];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <h3 class="mb-1">Tableau de bord</h3>
    <p class="text-muted">Bonjour <?= htmlspecialchars($_SESSION["prenom"]) ?>, voici un aperçu de la consommation ETAP.</p>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card bg-elec">
                <div class="label"><i class="bi bi-lightning-charge-fill"></i> Électricité</div>
                <div class="value"><?= number_format($totaux["electricite"], 0, ',', ' ') ?> kWh</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-gaz">
                <div class="label"><i class="bi bi-fire"></i> Gaz</div>
                <div class="value"><?= number_format($totaux["gaz"], 0, ',', ' ') ?> m³</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card bg-eau">
                <div class="label"><i class="bi bi-droplet-fill"></i> Eau</div>
                <div class="value"><?= number_format($totaux["eau"], 0, ',', ' ') ?> L</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="mini-card">
                <div class="text-muted small">Locaux enregistrés</div>
                <div class="fs-3 fw-bold"><?= $nb_locaux ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mini-card">
                <div class="text-muted small">Compteurs enregistrés</div>
                <div class="fs-3 fw-bold"><?= $nb_compteurs ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mini-card">
                <div class="text-muted small">Factures enregistrées</div>
                <div class="fs-3 fw-bold"><?= $nb_factures ?></div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <div class="col-12">
            <div class="mini-card chart-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="mb-1">Consommation par mois</h5>
                        <p class="text-muted small mb-0">Evolution des 36 derniers mois</p>
                    </div>
                    <i class="bi bi-graph-up-arrow chart-icon"></i>
                </div>
                <div class="chart-container chart-container-large"><canvas id="monthlyConsumptionChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="mini-card chart-card h-100">
                <h5 class="mb-3">Consommation par region</h5>
                <div class="chart-container"><canvas id="regionConsumptionChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="mini-card chart-card h-100">
                <h5 class="mb-3">Consommation par type</h5>
                <div class="chart-container"><canvas id="typeConsumptionChart"></canvas></div>
            </div>
        </div>
    </div>
</div>

<style>
    .chart-card h5 { font-weight: 700; }
    .chart-container { position: relative; height: 280px; }
    .chart-container-large { height: 340px; }
    .chart-icon { color: #2979ff; font-size: 1.4rem; }
    @media (max-width: 767px) {
        .chart-container { height: 240px; }
        .chart-container-large { height: 280px; }
    }
</style>

<script>
const chartColors = { electricite: '#f57c00', gaz: '#8e44ad', eau: '#0097a7' };

new Chart(document.getElementById('monthlyConsumptionChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($mois_labels) ?>,
        datasets: [
            { label: 'Electricite', data: <?= json_encode($mois_data['electricite']) ?>, borderColor: chartColors.electricite, backgroundColor: 'rgba(245,124,0,0.12)', tension: 0.3, fill: true },
            { label: 'Gaz', data: <?= json_encode($mois_data['gaz']) ?>, borderColor: chartColors.gaz, backgroundColor: 'rgba(142,68,173,0.10)', tension: 0.3, fill: true },
            { label: 'Eau', data: <?= json_encode($mois_data['eau']) ?>, borderColor: chartColors.eau, backgroundColor: 'rgba(0,151,167,0.10)', tension: 0.3, fill: true }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('regionConsumptionChart'), {
    type: 'bar',
    data: { labels: <?= json_encode($regions_labels) ?>, datasets: [{ label: 'Consommation totale', data: <?= json_encode($regions_data) ?>, backgroundColor: '#2979ff', borderRadius: 5 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('typeConsumptionChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode($types_labels) ?>, datasets: [{ data: <?= json_encode($types_data) ?>, backgroundColor: [chartColors.electricite, chartColors.gaz, chartColors.eau], borderWidth: 0 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>

</body>
</html>

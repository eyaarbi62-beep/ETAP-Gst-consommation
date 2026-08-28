<?php
require "config.php";
if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: login.php");
    exit;
}

// --- Filtres communs ---
$f_region = $_GET["region"] ?? "";
$f_famille = $_GET["famille"] ?? "";
$f_sous_famille = $_GET["sous_famille"] ?? "";
$f_local = $_GET["local"] ?? "";
$f_type = $_GET["type"] ?? "";

$regions = $conn->query("SELECT * FROM region ORDER BY nom");
$familles = $conn->query("SELECT * FROM famille ORDER BY nom");
$sous_familles = $conn->query("SELECT * FROM sous_famille ORDER BY nom");
$locaux_list = $conn->query("SELECT * FROM local ORDER BY nom");

// --- 1) Etat liste des compteurs (avec filtres) ---
$sql_compteurs = "
    SELECT c.*, l.nom AS nom_local, r.nom AS nom_region, f.nom AS nom_famille, sf.nom AS nom_sous_famille
    FROM compteur c
    JOIN local l ON c.id_local = l.id_local
    JOIN region r ON l.id_region = r.id_region
    JOIN famille f ON l.id_famille = f.id_famille
    JOIN sous_famille sf ON l.id_sous_famille = sf.id_sous_famille
    WHERE 1=1
";
if ($f_type !== "") $sql_compteurs .= " AND c.type = '" . $conn->real_escape_string($f_type) . "'";
if ($f_region !== "") $sql_compteurs .= " AND r.id_region = " . intval($f_region);
if ($f_famille !== "") $sql_compteurs .= " AND f.id_famille = " . intval($f_famille);
if ($f_sous_famille !== "") $sql_compteurs .= " AND sf.id_sous_famille = " . intval($f_sous_famille);
if ($f_local !== "") $sql_compteurs .= " AND l.id_local = " . intval($f_local);
$sql_compteurs .= " ORDER BY c.numero";
$etat_compteurs = $conn->query($sql_compteurs);

// --- 2) Etat liste des factures (avec filtres) ---
$sql_factures = "
    SELECT f.*, GROUP_CONCAT(DISTINCT l.nom SEPARATOR ', ') AS locaux_lies,
           GROUP_CONCAT(DISTINCT r.nom SEPARATOR ', ') AS regions_liees
    FROM facture f
    LEFT JOIN concerne cc ON cc.id_facture = f.id_facture
    LEFT JOIN compteur c ON c.id_compteur = cc.id_compteur
    LEFT JOIN local l ON c.id_local = l.id_local
    LEFT JOIN region r ON l.id_region = r.id_region
    LEFT JOIN famille fa ON l.id_famille = fa.id_famille
    LEFT JOIN sous_famille sf ON l.id_sous_famille = sf.id_sous_famille
    WHERE 1=1
";
if ($f_type !== "") $sql_factures .= " AND f.type = '" . $conn->real_escape_string($f_type) . "'";
if ($f_region !== "") $sql_factures .= " AND r.id_region = " . intval($f_region);
if ($f_famille !== "") $sql_factures .= " AND fa.id_famille = " . intval($f_famille);
if ($f_sous_famille !== "") $sql_factures .= " AND sf.id_sous_famille = " . intval($f_sous_famille);
if ($f_local !== "") $sql_factures .= " AND l.id_local = " . intval($f_local);
$sql_factures .= " GROUP BY f.id_facture ORDER BY f.periode DESC";
$etat_factures = $conn->query($sql_factures);

// --- 3) Etat des dépassements (consommation réelle > moyenne du compteur) ---
$sql_depassements = "
    SELECT c.numero, c.type, c.moyenne_consommation, cc.consommation, fa.periode,
           l.nom AS nom_local, r.nom AS nom_region, fam.nom AS nom_famille, sf.nom AS nom_sous_famille
    FROM concerne cc
    JOIN compteur c ON cc.id_compteur = c.id_compteur
    JOIN facture fa ON cc.id_facture = fa.id_facture
    JOIN local l ON c.id_local = l.id_local
    JOIN region r ON l.id_region = r.id_region
    JOIN famille fam ON l.id_famille = fam.id_famille
    JOIN sous_famille sf ON l.id_sous_famille = sf.id_sous_famille
    WHERE cc.consommation > c.moyenne_consommation AND c.moyenne_consommation > 0
";
if ($f_type !== "") $sql_depassements .= " AND c.type = '" . $conn->real_escape_string($f_type) . "'";
if ($f_region !== "") $sql_depassements .= " AND r.id_region = " . intval($f_region);
if ($f_famille !== "") $sql_depassements .= " AND fam.id_famille = " . intval($f_famille);
if ($f_sous_famille !== "") $sql_depassements .= " AND sf.id_sous_famille = " . intval($f_sous_famille);
if ($f_local !== "") $sql_depassements .= " AND l.id_local = " . intval($f_local);
$sql_depassements .= " ORDER BY fa.periode DESC";
$etat_depassements = $conn->query($sql_depassements);

// --- 4) Données pour le graphique (consommation totale par période et par type) ---
$sql_graphe = "
    SELECT fa.periode, c.type, SUM(cc.consommation) AS total
    FROM concerne cc
    JOIN compteur c ON cc.id_compteur = c.id_compteur
    JOIN facture fa ON cc.id_facture = fa.id_facture
    JOIN local l ON c.id_local = l.id_local
    JOIN region r ON l.id_region = r.id_region
    JOIN famille fam ON l.id_famille = fam.id_famille
    JOIN sous_famille sf ON l.id_sous_famille = sf.id_sous_famille
    WHERE 1=1
";
if ($f_region !== "") $sql_graphe .= " AND r.id_region = " . intval($f_region);
if ($f_famille !== "") $sql_graphe .= " AND fam.id_famille = " . intval($f_famille);
if ($f_sous_famille !== "") $sql_graphe .= " AND sf.id_sous_famille = " . intval($f_sous_famille);
if ($f_local !== "") $sql_graphe .= " AND l.id_local = " . intval($f_local);
$sql_graphe .= " GROUP BY fa.periode, c.type ORDER BY fa.periode";
$res_graphe = $conn->query($sql_graphe);

$labels_periodes = [];
$data_graphe = ["electricite" => [], "gaz" => [], "eau" => []];
$temp = [];
while ($row = $res_graphe->fetch_assoc()) {
    $temp[$row["periode"]][$row["type"]] = $row["total"];
}
ksort($temp);
foreach ($temp as $periode => $valeurs) {
    $labels_periodes[] = $periode;
    $data_graphe["electricite"][] = $valeurs["electricite"] ?? 0;
    $data_graphe["gaz"][] = $valeurs["gaz"] ?? 0;
    $data_graphe["eau"][] = $valeurs["eau"] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>États et graphes - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">États et graphes</h2>
        <span class="text-muted">Bonjour <?= htmlspecialchars($_SESSION["prenom"]) ?></span>
    </div>

    <!-- Filtres communs -->
    <form method="GET" class="card p-3 mb-4">
        <div class="row g-2">
            <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                    <option value="">Tous</option>
                    <option value="electricite" <?= $f_type === "electricite" ? "selected" : "" ?>>Électricité</option>
                    <option value="gaz" <?= $f_type === "gaz" ? "selected" : "" ?>>Gaz</option>
                    <option value="eau" <?= $f_type === "eau" ? "selected" : "" ?>>Eau</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Région</label>
                <select name="region" class="form-select">
                    <option value="">Toutes</option>
                    <?php $regions->data_seek(0); while ($r = $regions->fetch_assoc()): ?>
                        <option value="<?= $r["id_region"] ?>" <?= $f_region == $r["id_region"] ? "selected" : "" ?>><?= htmlspecialchars($r["nom"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Famille</label>
                <select name="famille" class="form-select">
                    <option value="">Toutes</option>
                    <?php $familles->data_seek(0); while ($f = $familles->fetch_assoc()): ?>
                        <option value="<?= $f["id_famille"] ?>" <?= $f_famille == $f["id_famille"] ? "selected" : "" ?>><?= htmlspecialchars($f["nom"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Sous-famille</label>
                <select name="sous_famille" class="form-select">
                    <option value="">Toutes</option>
                    <?php $sous_familles->data_seek(0); while ($sf = $sous_familles->fetch_assoc()): ?>
                        <option value="<?= $sf["id_sous_famille"] ?>" <?= $f_sous_famille == $sf["id_sous_famille"] ? "selected" : "" ?>><?= htmlspecialchars($sf["nom"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Local</label>
                <select name="local" class="form-select">
                    <option value="">Tous</option>
                    <?php $locaux_list->data_seek(0); while ($l = $locaux_list->fetch_assoc()): ?>
                        <option value="<?= $l["id_local"] ?>" <?= $f_local == $l["id_local"] ? "selected" : "" ?>><?= htmlspecialchars($l["nom"]) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Filtrer</button>
            </div>
        </div>
    </form>

    <!-- Onglets -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-compteurs">Compteurs</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-factures">Factures</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-depassements">Dépassements</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-graphe">Graphique</button></li>
    </ul>

    <div class="tab-content border border-top-0 p-3 mb-4">
        <!-- Onglet Compteurs -->
        <div class="tab-pane fade show active" id="tab-compteurs">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr><th>Numéro</th><th>Type</th><th>Région</th><th>Famille</th><th>Sous-famille</th><th>Local</th></tr>
                </thead>
                <tbody>
                    <?php while ($c = $etat_compteurs->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($c["numero"]) ?></td>
                            <td><?= htmlspecialchars($c["type"]) ?></td>
                            <td><?= htmlspecialchars($c["nom_region"]) ?></td>
                            <td><?= htmlspecialchars($c["nom_famille"]) ?></td>
                            <td><?= htmlspecialchars($c["nom_sous_famille"]) ?></td>
                            <td><?= htmlspecialchars($c["nom_local"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Onglet Factures -->
        <div class="tab-pane fade" id="tab-factures">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr><th>Type</th><th>Période</th><th>Montant</th><th>Locaux</th><th>Régions</th></tr>
                </thead>
                <tbody>
                    <?php while ($f = $etat_factures->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($f["type"]) ?></td>
                            <td><?= htmlspecialchars($f["periode"]) ?></td>
                            <td><?= htmlspecialchars($f["montant"]) ?> DT</td>
                            <td><?= htmlspecialchars($f["locaux_lies"] ?? "-") ?></td>
                            <td><?= htmlspecialchars($f["regions_liees"] ?? "-") ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Onglet Dépassements -->
        <div class="tab-pane fade" id="tab-depassements">
            <p class="text-muted">Compteurs et locaux dont la consommation dépasse la moyenne habituelle du compteur.</p>
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr><th>Numéro</th><th>Type</th><th>Période</th><th>Consommation</th><th>Moyenne</th><th>Local</th><th>Région</th></tr>
                </thead>
                <tbody>
                    <?php while ($d = $etat_depassements->fetch_assoc()): ?>
                        <tr class="table-warning">
                            <td><?= htmlspecialchars($d["numero"]) ?></td>
                            <td><?= htmlspecialchars($d["type"]) ?></td>
                            <td><?= htmlspecialchars($d["periode"]) ?></td>
                            <td><strong><?= htmlspecialchars($d["consommation"]) ?></strong></td>
                            <td><?= htmlspecialchars($d["moyenne_consommation"]) ?></td>
                            <td><?= htmlspecialchars($d["nom_local"]) ?></td>
                            <td><?= htmlspecialchars($d["nom_region"]) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Onglet Graphique -->
        <div class="tab-pane fade" id="tab-graphe">
            <canvas id="graphConso" height="100"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('graphConso');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels_periodes) ?>,
        datasets: [
            {
                label: 'Électricité (kWh)',
                data: <?= json_encode($data_graphe["electricite"]) ?>,
                borderColor: '#ff9800',
                backgroundColor: 'rgba(255,152,0,0.15)',
                tension: 0.3
            },
            {
                label: 'Gaz (m³)',
                data: <?= json_encode($data_graphe["gaz"]) ?>,
                borderColor: '#9c27b0',
                backgroundColor: 'rgba(156,39,176,0.15)',
                tension: 0.3
            },
            {
                label: 'Eau (L)',
                data: <?= json_encode($data_graphe["eau"]) ?>,
                borderColor: '#00bcd4',
                backgroundColor: 'rgba(0,188,212,0.15)',
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } }
    }
});
</script>

</body>
</html>

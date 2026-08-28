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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
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
</div>

</body>
</html>

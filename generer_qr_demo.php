<?php
require "config.php";
if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: login.php");
    exit;
}

$compteurs = $conn->query("SELECT id_compteur, numero, type FROM compteur ORDER BY numero");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générateur QR démo - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Générateur QR (démo)</h2>
        <span class="text-muted">Bonjour <?= htmlspecialchars($_SESSION["prenom"]) ?></span>
    </div>

    <div class="alert alert-info">
        Cet outil sert uniquement à <strong>créer un QR code de test</strong>, pour simuler une vraie facture avec QR code
        et démontrer le scanner pendant ta soutenance.
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card p-3">
                <h5>Données de la facture (test)</h5>

                <label class="form-label">Compteur</label>
                <select id="sel_compteur" class="form-select mb-2">
                    <?php while ($c = $compteurs->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($c["numero"]) ?>" data-type="<?= $c["type"] ?>">
                            <?= htmlspecialchars($c["numero"]) ?> (<?= $c["type"] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>

                <label class="form-label">Période</label>
                <input type="date" id="periode" class="form-control mb-2" value="<?= date('Y-m-d') ?>">

                <label class="form-label">Montant (DT)</label>
                <input type="number" step="0.01" id="montant" class="form-control mb-2" value="45.500">

                <label class="form-label">Consommation</label>
                <input type="number" step="0.01" id="consommation" class="form-control mb-2" value="1200">

                <label class="form-label">Date d'échéance</label>
                <input type="date" id="date_echeance" class="form-control mb-3">

                <button class="btn btn-primary" onclick="genererQR()">Générer le QR code</button>
            </div>
        </div>

        <div class="col-md-6 text-center">
            <div id="qrcode" class="d-flex justify-content-center align-items-center" style="min-height:260px;"></div>
            <p class="text-muted mt-2">Scanne ce QR code avec le bouton "Scanner un QR Code" sur la page Factures.</p>
        </div>
    </div>
</div>

<script>
function genererQR() {
    const select = document.getElementById("sel_compteur");
    const numero = select.value;
    const type = select.options[select.selectedIndex].dataset.type;

    const data = {
        numero_compteur: numero,
        type: type,
        periode: document.getElementById("periode").value,
        montant: parseFloat(document.getElementById("montant").value),
        consommation: parseFloat(document.getElementById("consommation").value),
        date_echeance: document.getElementById("date_echeance").value
    };

    document.getElementById("qrcode").innerHTML = "";
    new QRCode(document.getElementById("qrcode"), {
        text: JSON.stringify(data),
        width: 220,
        height: 220
    });
}
</script>

</body>
</html>

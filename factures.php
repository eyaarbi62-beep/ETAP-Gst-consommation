<?php
require "config.php";
if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: login.php");
    exit;
}

$erreur = "";
$mode_edition = false;
$facture_edite = null;
$lignes_existantes = [];

// --- Suppression ---
if (isset($_GET["supprimer"])) {
    $id = intval($_GET["supprimer"]);
    $stmt = $conn->prepare("DELETE FROM concerne WHERE id_facture = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt = $conn->prepare("DELETE FROM facture WHERE id_facture = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: factures.php");
    exit;
}

// --- Charger une facture pour modification ---
if (isset($_GET["modifier"])) {
    $id = intval($_GET["modifier"]);
    $stmt = $conn->prepare("SELECT * FROM facture WHERE id_facture = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $facture_edite = $stmt->get_result()->fetch_assoc();
    $mode_edition = true;

    $stmt2 = $conn->prepare("
        SELECT cc.id_compteur, cc.consommation, c.numero
        FROM concerne cc JOIN compteur c ON cc.id_compteur = c.id_compteur
        WHERE cc.id_facture = ?
    ");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $lignes_existantes = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
}

// --- Ajout ou modification (soumission du formulaire) ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = $_POST["type"];
    $periode = $_POST["periode"];
    $montant = floatval($_POST["montant"]);
    $date_echeance = $_POST["date_echeance"] !== "" ? $_POST["date_echeance"] : null;
    $compteurs_ids = $_POST["compteur"] ?? [];
    $consommations = $_POST["consommation"] ?? [];

    if ($periode === "" || count($compteurs_ids) === 0) {
        $erreur = "La période et au moins un compteur sont obligatoires.";
    } else {
        if (isset($_POST["id_facture"]) && $_POST["id_facture"] !== "") {
            $id_facture = intval($_POST["id_facture"]);
            $stmt = $conn->prepare("UPDATE facture SET type=?, periode=?, montant=?, date_echeance=? WHERE id_facture=?");
            $stmt->bind_param("ssdsi", $type, $periode, $montant, $date_echeance, $id_facture);
            $stmt->execute();

            $del = $conn->prepare("DELETE FROM concerne WHERE id_facture = ?");
            $del->bind_param("i", $id_facture);
            $del->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO facture (type, periode, montant, date_echeance) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssds", $type, $periode, $montant, $date_echeance);
            $stmt->execute();
            $id_facture = $conn->insert_id;
        }

        for ($i = 0; $i < count($compteurs_ids); $i++) {
            $id_cpt = intval($compteurs_ids[$i]);
            $conso = floatval($consommations[$i]);
            if ($id_cpt > 0) {
                $ins = $conn->prepare("INSERT INTO concerne (id_compteur, id_facture, consommation) VALUES (?, ?, ?)");
                $ins->bind_param("iid", $id_cpt, $id_facture, $conso);
                $ins->execute();
            }
        }

        header("Location: factures.php");
        exit;
    }
}

$compteurs = $conn->query("SELECT id_compteur, numero, type FROM compteur ORDER BY numero");
$liste_compteurs = [];
$compteurs->data_seek(0);
while ($c = $compteurs->fetch_assoc()) {
    $liste_compteurs[] = $c;
}

$factures = $conn->query("
    SELECT f.*, GROUP_CONCAT(c.numero SEPARATOR ', ') AS compteurs_lies
    FROM facture f
    LEFT JOIN concerne cc ON cc.id_facture = f.id_facture
    LEFT JOIN compteur c ON c.id_compteur = cc.id_compteur
    GROUP BY f.id_facture
    ORDER BY f.periode DESC
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des factures - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Gestion des factures</h2>
        <span class="text-muted">Bonjour <?= htmlspecialchars($_SESSION["prenom"]) ?></span>
    </div>

    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h5><?= $mode_edition ? "Modifier la facture" : "Ajouter une facture" ?></h5>
            <div>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalScan">
                    📷 Scanner avec la caméra
                </button>
            </div>
        </div>
        <small class="text-muted d-block mt-2">
            <i class="bi bi-upc-scan"></i> Tu peux aussi scanner directement avec une douchette USB : le formulaire se remplira automatiquement.
        </small>

        <form method="POST" id="formFacture">
            <?php if ($mode_edition): ?>
                <input type="hidden" name="id_facture" value="<?= $facture_edite["id_facture"] ?>">
            <?php endif; ?>

            <div class="row g-2 mt-2">
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" id="type_facture" class="form-select" required>
                        <option value="electricite" <?= ($mode_edition && $facture_edite["type"] === "electricite") ? "selected" : "" ?>>Électricité</option>
                        <option value="gaz" <?= ($mode_edition && $facture_edite["type"] === "gaz") ? "selected" : "" ?>>Gaz</option>
                        <option value="eau" <?= ($mode_edition && $facture_edite["type"] === "eau") ? "selected" : "" ?>>Eau</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Période</label>
                    <input type="date" name="periode" id="periode_facture" class="form-control" required
                           value="<?= $mode_edition ? $facture_edite["periode"] : "" ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Montant (DT)</label>
                    <input type="number" step="0.01" name="montant" id="montant_facture" class="form-control" required
                           value="<?= $mode_edition ? $facture_edite["montant"] : "" ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date d'échéance</label>
                    <input type="date" name="date_echeance" id="echeance_facture" class="form-control"
                           value="<?= $mode_edition ? $facture_edite["date_echeance"] : "" ?>">
                </div>
            </div>

            <hr>
            <h6>Compteurs concernés par cette facture</h6>
            <div id="lignes_container"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="ajouterLigne()">+ Ajouter un compteur</button>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <?= $mode_edition ? "Enregistrer les modifications" : "Ajouter" ?>
                </button>
                <?php if ($mode_edition): ?>
                    <a href="factures.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Type</th>
                <th>Période</th>
                <th>Montant (DT)</th>
                <th>Échéance</th>
                <th>Compteurs liés</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($f = $factures->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($f["type"]) ?></td>
                    <td><?= htmlspecialchars($f["periode"]) ?></td>
                    <td><?= htmlspecialchars($f["montant"]) ?></td>
                    <td><?= htmlspecialchars($f["date_echeance"] ?? "-") ?></td>
                    <td><?= htmlspecialchars($f["compteurs_lies"] ?? "-") ?></td>
                    <td>
                        <a href="factures.php?modifier=<?= $f["id_facture"] ?>" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="factures.php?supprimer=<?= $f["id_facture"] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Supprimer cette facture ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Modal Scanner QR -->
<div class="modal fade" id="modalScan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scanner le QR Code de la facture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="reader" style="width:100%;"></div>
                <div id="scan_resultat" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Liste des compteurs disponibles, injectée depuis PHP
const compteursDisponibles = <?= json_encode($liste_compteurs) ?>;
const lignesExistantes = <?= json_encode($lignes_existantes) ?>;

function creerLigne(idCompteurSelectionne = "", consommation = "") {
    const div = document.createElement("div");
    div.className = "row g-2 mb-2 ligne-compteur";

    let options = '<option value="">-- Choisir un compteur --</option>';
    compteursDisponibles.forEach(c => {
        const selected = (String(c.id_compteur) === String(idCompteurSelectionne)) ? "selected" : "";
        options += `<option value="${c.id_compteur}" ${selected}>${c.numero} (${c.type})</option>`;
    });

    div.innerHTML = `
        <div class="col-md-6">
            <select name="compteur[]" class="form-select">${options}</select>
        </div>
        <div class="col-md-4">
            <input type="number" step="0.01" name="consommation[]" class="form-control" placeholder="Consommation" value="${consommation}">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.ligne-compteur').remove()">Suppr.</button>
        </div>
    `;
    document.getElementById("lignes_container").appendChild(div);
}

function ajouterLigne() {
    creerLigne();
}

// Pré-remplissage en mode édition, ou une ligne vide par défaut
if (lignesExistantes.length > 0) {
    lignesExistantes.forEach(l => creerLigne(l.id_compteur, l.consommation));
} else {
    creerLigne();
}

// --- Scanner QR ---
let html5QrCode;
const modalScan = document.getElementById('modalScan');

modalScan.addEventListener('shown.bs.modal', () => {
    html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 220 },
        (decodedText) => {
            traiterQRCode(decodedText);
            html5QrCode.stop();
            bootstrap.Modal.getInstance(modalScan).hide();
        },
        (errorMessage) => { /* ignoré, scan en continu */ }
    ).catch(err => {
        document.getElementById("scan_resultat").innerHTML =
            '<div class="alert alert-danger">Impossible d\'accéder à la caméra : ' + err + '</div>';
    });
});

modalScan.addEventListener('hidden.bs.modal', () => {
    if (html5QrCode) {
        html5QrCode.stop().catch(() => {});
    }
});

function traiterQRCode(decodedText) {
    let data;
    try {
        data = JSON.parse(decodedText);
    } catch (e) {
        alert("QR Code non reconnu (format invalide).");
        return;
    }

    if (data.type) document.getElementById("type_facture").value = data.type;
    if (data.periode) document.getElementById("periode_facture").value = data.periode;
    if (data.montant) document.getElementById("montant_facture").value = data.montant;
    if (data.date_echeance) document.getElementById("echeance_facture").value = data.date_echeance;

    if (data.numero_compteur) {
        fetch("chercher_compteur.php?numero=" + encodeURIComponent(data.numero_compteur))
            .then(r => r.json())
            .then(res => {
                document.getElementById("lignes_container").innerHTML = "";
                if (res.trouve) {
                    creerLigne(res.compteur.id_compteur, data.consommation || "");
                } else {
                    creerLigne("", data.consommation || "");
                    alert("Compteur '" + data.numero_compteur + "' non trouvé dans la base. Sélectionne-le manuellement.");
                }
            });
    }
}

// --- Scanner physique (douchette USB en mode "clavier"), capté sur toute la page ---
let bufferScan = "";
let dernierTemps = 0;

document.addEventListener("keydown", function(e) {
    const maintenant = Date.now();
    // Une douchette tape les caractères très vite (quelques ms entre chaque touche).
    // Si le délai est trop long, on suppose qu'il s'agit d'une frappe humaine normale -> on réinitialise.
    if (maintenant - dernierTemps > 50) {
        bufferScan = "";
    }
    dernierTemps = maintenant;

    if (e.key === "Enter") {
        if (bufferScan.length > 15) {
            e.preventDefault();
            traiterQRCode(bufferScan);
        }
        bufferScan = "";
    } else if (e.key.length === 1) {
        bufferScan += e.key;
    }
});
</script>

</body>
</html>

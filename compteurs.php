<?php
require "config.php";
if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: login.php");
    exit;
}

$erreur = "";
$mode_edition = false;
$compteur_edite = null;

// --- Suppression ---
if (isset($_GET["supprimer"])) {
    $id = intval($_GET["supprimer"]);
    $stmt = $conn->prepare("DELETE FROM compteur WHERE id_compteur = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: compteurs.php");
    exit;
}

// --- Charger un compteur pour modification ---
if (isset($_GET["modifier"])) {
    $id = intval($_GET["modifier"]);
    $stmt = $conn->prepare("SELECT * FROM compteur WHERE id_compteur = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $compteur_edite = $stmt->get_result()->fetch_assoc();
    $mode_edition = true;
}

// --- Ajout ou modification (soumission du formulaire) ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $numero = trim($_POST["numero"]);
    $type = $_POST["type"];
    $tension = ($type === "electricite" && $_POST["tension"] !== "") ? $_POST["tension"] : null;
    $moyenne = ($_POST["moyenne_consommation"] !== "") ? floatval($_POST["moyenne_consommation"]) : 0;
    $date_installation = $_POST["date_installation"] !== "" ? $_POST["date_installation"] : null;
    $id_local = intval($_POST["id_local"]);

    if ($numero === "" || $id_local === 0) {
        $erreur = "Le numéro et le local sont obligatoires.";
    } elseif ($moyenne <= 0) {
        $erreur = "La moyenne de consommation est obligatoire et doit être supérieure à 0 (elle sert de référence pour détecter les dépassements).";
    } else {
        if (isset($_POST["id_compteur"]) && $_POST["id_compteur"] !== "") {
            $id_compteur = intval($_POST["id_compteur"]);
            $stmt = $conn->prepare("UPDATE compteur SET numero=?, type=?, tension=?, moyenne_consommation=?, date_installation=?, id_local=? WHERE id_compteur=?");
            $stmt->bind_param("sssdsii", $numero, $type, $tension, $moyenne, $date_installation, $id_local, $id_compteur);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO compteur (numero, type, tension, moyenne_consommation, date_installation, id_local) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdsi", $numero, $type, $tension, $moyenne, $date_installation, $id_local);
            $stmt->execute();
        }
        header("Location: compteurs.php");
        exit;
    }
}

// --- Filtre optionnel par type ---
$filtre_type = isset($_GET["type"]) ? $_GET["type"] : "";

$locaux = $conn->query("SELECT * FROM local ORDER BY nom");

$sql = "SELECT c.*, l.nom AS nom_local FROM compteur c JOIN local l ON c.id_local = l.id_local";
if ($filtre_type !== "") {
    $sql .= " WHERE c.type = '" . $conn->real_escape_string($filtre_type) . "'";
}
$sql .= " ORDER BY c.numero";
$compteurs = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des compteurs - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Gestion des compteurs</h2>
        <span class="text-muted">Bonjour <?= htmlspecialchars($_SESSION["prenom"]) ?></span>
    </div>

    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <!-- Formulaire ajout / modification -->
    <div class="card p-3 mb-4">
        <h5><?= $mode_edition ? "Modifier le compteur" : "Ajouter un compteur" ?></h5>
        <form method="POST">
            <?php if ($mode_edition): ?>
                <input type="hidden" name="id_compteur" value="<?= $compteur_edite["id_compteur"] ?>">
            <?php endif; ?>

            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Numéro du compteur</label>
                    <input type="text" name="numero" class="form-control" required
                           value="<?= $mode_edition ? htmlspecialchars($compteur_edite["numero"]) : "" ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" id="type_select" class="form-select" required onchange="toggleTension()">
                        <?php foreach (["electricite" => "Électricité", "gaz" => "Gaz", "eau" => "Eau"] as $val => $label): ?>
                            <option value="<?= $val ?>" <?= ($mode_edition && $compteur_edite["type"] === $val) ? "selected" : "" ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2" id="tension_div">
                    <label class="form-label">Tension</label>
                    <select name="tension" class="form-select">
                        <option value="">--</option>
                        <option value="haute" <?= ($mode_edition && $compteur_edite["tension"] === "haute") ? "selected" : "" ?>>Haute</option>
                        <option value="basse" <?= ($mode_edition && $compteur_edite["tension"] === "basse") ? "selected" : "" ?>>Basse</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Moyenne conso. <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="moyenne_consommation" class="form-control" required
                           value="<?= $mode_edition ? htmlspecialchars($compteur_edite["moyenne_consommation"]) : "" ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Date d'installation</label>
                    <input type="date" name="date_installation" class="form-control"
                           value="<?= $mode_edition ? htmlspecialchars($compteur_edite["date_installation"]) : "" ?>">
                </div>

                <div class="col-md-4 mt-2">
                    <label class="form-label">Local</label>
                    <select name="id_local" class="form-select" required>
                        <option value="">-- Choisir --</option>
                        <?php $locaux->data_seek(0); while ($l = $locaux->fetch_assoc()): ?>
                            <option value="<?= $l["id_local"] ?>"
                                <?= ($mode_edition && (int)$compteur_edite["id_local"] === (int)$l["id_local"]) ? "selected" : "" ?>>
                                <?= htmlspecialchars($l["nom"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">
                <?= $mode_edition ? "Enregistrer les modifications" : "Ajouter" ?>
            </button>
            <?php if ($mode_edition): ?>
                <a href="compteurs.php" class="btn btn-secondary mt-3">Annuler</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Filtre par type -->
    <div class="mb-3">
        <a href="compteurs.php" class="btn btn-sm btn-outline-dark <?= $filtre_type === "" ? "active" : "" ?>">Tous</a>
        <a href="compteurs.php?type=electricite" class="btn btn-sm btn-outline-warning <?= $filtre_type === "electricite" ? "active" : "" ?>">Électricité</a>
        <a href="compteurs.php?type=gaz" class="btn btn-sm btn-outline-info <?= $filtre_type === "gaz" ? "active" : "" ?>">Gaz</a>
        <a href="compteurs.php?type=eau" class="btn btn-sm btn-outline-primary <?= $filtre_type === "eau" ? "active" : "" ?>">Eau</a>
    </div>

    <!-- Liste des compteurs -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Numéro</th>
                <th>Type</th>
                <th>Tension</th>
                <th>Moyenne conso.</th>
                <th>Date installation</th>
                <th>Local</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($c = $compteurs->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($c["numero"]) ?></td>
                    <td><?= htmlspecialchars($c["type"]) ?></td>
                    <td><?= htmlspecialchars($c["tension"] ?? "-") ?></td>
                    <td><?= htmlspecialchars($c["moyenne_consommation"]) ?></td>
                    <td><?= htmlspecialchars($c["date_installation"] ?? "-") ?></td>
                    <td><?= htmlspecialchars($c["nom_local"]) ?></td>
                    <td>
                        <a href="compteurs.php?modifier=<?= $c["id_compteur"] ?>" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="compteurs.php?supprimer=<?= $c["id_compteur"] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Supprimer ce compteur ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function toggleTension() {
    const type = document.getElementById("type_select").value;
    const tensionDiv = document.getElementById("tension_div");
    tensionDiv.style.display = (type === "electricite") ? "block" : "none";
}
window.onload = toggleTension;
</script>

</body>
</html>

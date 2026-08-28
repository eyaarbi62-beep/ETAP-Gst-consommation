<?php
require "config.php";
if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: login.php");
    exit;
}

$erreur = "";
$mode_edition = false;
$local_edite = null;

// --- Suppression ---
if (isset($_GET["supprimer"])) {
    $id = intval($_GET["supprimer"]);
    $stmt = $conn->prepare("DELETE FROM local WHERE id_local = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: locaux.php");
    exit;
}

// --- Charger un local pour modification ---
if (isset($_GET["modifier"])) {
    $id = intval($_GET["modifier"]);
    $stmt = $conn->prepare("SELECT * FROM local WHERE id_local = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $local_edite = $stmt->get_result()->fetch_assoc();
    $mode_edition = true;
}

// --- Ajout ou modification (soumission du formulaire) ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST["nom"]);
    $adresse = trim($_POST["adresse"]);
    $id_region = intval($_POST["id_region"]);
    $id_famille = intval($_POST["id_famille"]);
    $id_sous_famille = intval($_POST["id_sous_famille"]);

    if ($nom === "") {
        $erreur = "Le nom du local est obligatoire.";
    } else {
        if (isset($_POST["id_local"]) && $_POST["id_local"] !== "") {
            // Modification
            $id_local = intval($_POST["id_local"]);
            $stmt = $conn->prepare("UPDATE local SET nom=?, adresse=?, id_region=?, id_famille=?, id_sous_famille=? WHERE id_local=?");
            $stmt->bind_param("ssiiii", $nom, $adresse, $id_region, $id_famille, $id_sous_famille, $id_local);
            $stmt->execute();
        } else {
            // Ajout
            $stmt = $conn->prepare("INSERT INTO local (nom, adresse, id_region, id_famille, id_sous_famille) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiii", $nom, $adresse, $id_region, $id_famille, $id_sous_famille);
            $stmt->execute();
        }
        header("Location: locaux.php");
        exit;
    }
}

// --- Listes pour les menus déroulants ---
$regions = $conn->query("SELECT * FROM region ORDER BY nom");
$familles = $conn->query("SELECT * FROM famille ORDER BY nom");
$sous_familles = $conn->query("SELECT * FROM sous_famille ORDER BY nom");

// --- Liste des locaux avec noms de région/famille/sous-famille ---
$locaux = $conn->query("
    SELECT l.*, r.nom AS nom_region, f.nom AS nom_famille, sf.nom AS nom_sous_famille
    FROM local l
    JOIN region r ON l.id_region = r.id_region
    JOIN famille f ON l.id_famille = f.id_famille
    JOIN sous_famille sf ON l.id_sous_famille = sf.id_sous_famille
    ORDER BY l.nom
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des locaux - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Gestion des locaux</h2>
        <span class="text-muted">Bonjour <?= htmlspecialchars($_SESSION["prenom"]) ?></span>
    </div>

    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <!-- Formulaire ajout / modification -->
    <div class="card p-3 mb-4">
        <h5><?= $mode_edition ? "Modifier le local" : "Ajouter un local" ?></h5>
        <form method="POST">
            <?php if ($mode_edition): ?>
                <input type="hidden" name="id_local" value="<?= $local_edite["id_local"] ?>">
            <?php endif; ?>

            <div class="row g-2">
                <div class="col-md-4">
                    <label class="form-label">Nom du local</label>
                    <input type="text" name="nom" class="form-control" required
                           value="<?= $mode_edition ? htmlspecialchars($local_edite["nom"]) : "" ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Adresse</label>
                    <input type="text" name="adresse" class="form-control"
                           value="<?= $mode_edition ? htmlspecialchars($local_edite["adresse"]) : "" ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Région</label>
                    <select name="id_region" class="form-select" required>
                        <option value="">-- Choisir --</option>
                        <?php $regions->data_seek(0); while ($r = $regions->fetch_assoc()): ?>
                            <option value="<?= $r["id_region"] ?>"
                                <?= ($mode_edition && (int)$local_edite["id_region"] === (int)$r["id_region"]) ? "selected" : "" ?>>
                                <?= htmlspecialchars($r["nom"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Famille</label>
                    <select name="id_famille" class="form-select" required>
                        <option value="">-- Choisir --</option>
                        <?php $familles->data_seek(0); while ($f = $familles->fetch_assoc()): ?>
                            <option value="<?= $f["id_famille"] ?>"
                                <?= ($mode_edition && (int)$local_edite["id_famille"] === (int)$f["id_famille"]) ? "selected" : "" ?>>
                                <?= htmlspecialchars($f["nom"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Sous-famille</label>
                    <select name="id_sous_famille" class="form-select" required>
                        <option value="">-- Choisir --</option>
                        <?php $sous_familles->data_seek(0); while ($sf = $sous_familles->fetch_assoc()): ?>
                            <option value="<?= $sf["id_sous_famille"] ?>"
                                <?= ($mode_edition && (int)$local_edite["id_sous_famille"] === (int)$sf["id_sous_famille"]) ? "selected" : "" ?>>
                                <?= htmlspecialchars($sf["nom"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-3">
                <?= $mode_edition ? "Enregistrer les modifications" : "Ajouter" ?>
            </button>
            <?php if ($mode_edition): ?>
                <a href="locaux.php" class="btn btn-secondary mt-3">Annuler</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Liste des locaux -->
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Nom</th>
                <th>Adresse</th>
                <th>Région</th>
                <th>Famille</th>
                <th>Sous-famille</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($local = $locaux->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($local["nom"]) ?></td>
                    <td><?= htmlspecialchars($local["adresse"]) ?></td>
                    <td><?= htmlspecialchars($local["nom_region"]) ?></td>
                    <td><?= htmlspecialchars($local["nom_famille"]) ?></td>
                    <td><?= htmlspecialchars($local["nom_sous_famille"]) ?></td>
                    <td>
                        <a href="locaux.php?modifier=<?= $local["id_local"] ?>" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="locaux.php?supprimer=<?= $local["id_local"] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Supprimer ce local ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>

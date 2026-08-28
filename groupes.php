<?php
require "config.php";
if (!isset($_SESSION["id_utilisateur"])) {
    header("Location: login.php");
    exit;
}

$erreur = "";
$mode_edition = false;
$item_edite = null;

if (isset($_GET["supprimer"])) {
    $id = intval($_GET["supprimer"]);
    $stmt = $conn->prepare("DELETE FROM groupe WHERE id_groupe = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: groupes.php");
    exit;
}

if (isset($_GET["modifier"])) {
    $id = intval($_GET["modifier"]);
    $stmt = $conn->prepare("SELECT * FROM groupe WHERE id_groupe = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item_edite = $stmt->get_result()->fetch_assoc();
    $mode_edition = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST["nom"]);
    $droits = trim($_POST["droits"]);
    if ($nom === "") {
        $erreur = "Le nom est obligatoire.";
    } else {
        if (isset($_POST["id_groupe"]) && $_POST["id_groupe"] !== "") {
            $id = intval($_POST["id_groupe"]);
            $stmt = $conn->prepare("UPDATE groupe SET nom=?, droits=? WHERE id_groupe=?");
            $stmt->bind_param("ssi", $nom, $droits, $id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO groupe (nom, droits) VALUES (?, ?)");
            $stmt->bind_param("ss", $nom, $droits);
            $stmt->execute();
        }
        header("Location: groupes.php");
        exit;
    }
}

$groupes = $conn->query("SELECT * FROM groupe ORDER BY nom");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des groupes - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Gestion des groupes d'utilisateurs</h2>
        <span class="text-muted">Bonjour <?= htmlspecialchars($_SESSION["prenom"]) ?></span>
    </div>

    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="card p-3 mb-4">
        <h5><?= $mode_edition ? "Modifier le groupe" : "Ajouter un groupe" ?></h5>
        <form method="POST" class="row g-2">
            <?php if ($mode_edition): ?>
                <input type="hidden" name="id_groupe" value="<?= $item_edite["id_groupe"] ?>">
            <?php endif; ?>
            <div class="col-md-4">
                <input type="text" name="nom" class="form-control" placeholder="Nom du groupe" required
                       value="<?= $mode_edition ? htmlspecialchars($item_edite["nom"]) : "" ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="droits" class="form-control" placeholder="Droits (ex: tout, limite)"
                       value="<?= $mode_edition ? htmlspecialchars($item_edite["droits"]) : "" ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary"><?= $mode_edition ? "Enregistrer" : "Ajouter" ?></button>
                <?php if ($mode_edition): ?>
                    <a href="groupes.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark"><tr><th>Nom</th><th>Droits</th><th>Actions</th></tr></thead>
        <tbody>
            <?php while ($g = $groupes->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($g["nom"]) ?></td>
                    <td><?= htmlspecialchars($g["droits"]) ?></td>
                    <td>
                        <a href="groupes.php?modifier=<?= $g["id_groupe"] ?>" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="groupes.php?supprimer=<?= $g["id_groupe"] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Supprimer ce groupe ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>

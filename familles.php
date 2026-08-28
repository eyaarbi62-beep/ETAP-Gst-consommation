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
    $stmt = $conn->prepare("DELETE FROM famille WHERE id_famille = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: familles.php");
    exit;
}

if (isset($_GET["modifier"])) {
    $id = intval($_GET["modifier"]);
    $stmt = $conn->prepare("SELECT * FROM famille WHERE id_famille = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item_edite = $stmt->get_result()->fetch_assoc();
    $mode_edition = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST["nom"]);
    if ($nom === "") {
        $erreur = "Le nom est obligatoire.";
    } else {
        if (isset($_POST["id_famille"]) && $_POST["id_famille"] !== "") {
            $id = intval($_POST["id_famille"]);
            $stmt = $conn->prepare("UPDATE famille SET nom=? WHERE id_famille=?");
            $stmt->bind_param("si", $nom, $id);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("INSERT INTO famille (nom) VALUES (?)");
            $stmt->bind_param("s", $nom);
            $stmt->execute();
        }
        header("Location: familles.php");
        exit;
    }
}

$familles = $conn->query("SELECT * FROM famille ORDER BY nom");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des familles - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Gestion des familles</h2>
        <span class="text-muted">Bonjour <?= htmlspecialchars($_SESSION["prenom"]) ?></span>
    </div>

    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="card p-3 mb-4">
        <h5><?= $mode_edition ? "Modifier la famille" : "Ajouter une famille" ?></h5>
        <form method="POST" class="row g-2">
            <?php if ($mode_edition): ?>
                <input type="hidden" name="id_famille" value="<?= $item_edite["id_famille"] ?>">
            <?php endif; ?>
            <div class="col-md-6">
                <input type="text" name="nom" class="form-control" placeholder="Nom de la famille" required
                       value="<?= $mode_edition ? htmlspecialchars($item_edite["nom"]) : "" ?>">
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary"><?= $mode_edition ? "Enregistrer" : "Ajouter" ?></button>
                <?php if ($mode_edition): ?>
                    <a href="familles.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark"><tr><th>Nom</th><th>Actions</th></tr></thead>
        <tbody>
            <?php while ($f = $familles->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($f["nom"]) ?></td>
                    <td>
                        <a href="familles.php?modifier=<?= $f["id_famille"] ?>" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="familles.php?supprimer=<?= $f["id_famille"] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Supprimer cette famille ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>

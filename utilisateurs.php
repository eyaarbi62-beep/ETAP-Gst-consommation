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
    $stmt = $conn->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: utilisateurs.php");
    exit;
}

if (isset($_GET["modifier"])) {
    $id = intval($_GET["modifier"]);
    $stmt = $conn->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $item_edite = $stmt->get_result()->fetch_assoc();
    $mode_edition = true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST["nom"]);
    $prenom = trim($_POST["prenom"]);
    $login = trim($_POST["login"]);
    $mot_de_passe = $_POST["mot_de_passe"];
    $id_groupe = intval($_POST["id_groupe"]);

    if ($nom === "" || $login === "" || $id_groupe === 0) {
        $erreur = "Nom, identifiant et groupe sont obligatoires.";
    } else {
        if (isset($_POST["id_utilisateur"]) && $_POST["id_utilisateur"] !== "") {
            $id = intval($_POST["id_utilisateur"]);
            if ($mot_de_passe !== "") {
                $stmt = $conn->prepare("UPDATE utilisateur SET nom=?, prenom=?, login=?, mot_de_passe=?, id_groupe=? WHERE id_utilisateur=?");
                $stmt->bind_param("ssssii", $nom, $prenom, $login, $mot_de_passe, $id_groupe, $id);
            } else {
                // On ne modifie pas le mot de passe si le champ est laissé vide
                $stmt = $conn->prepare("UPDATE utilisateur SET nom=?, prenom=?, login=?, id_groupe=? WHERE id_utilisateur=?");
                $stmt->bind_param("sssii", $nom, $prenom, $login, $id_groupe, $id);
            }
            $stmt->execute();
        } else {
            if ($mot_de_passe === "") {
                $erreur = "Le mot de passe est obligatoire pour un nouvel utilisateur.";
            } else {
                $stmt = $conn->prepare("INSERT INTO utilisateur (nom, prenom, login, mot_de_passe, id_groupe) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $nom, $prenom, $login, $mot_de_passe, $id_groupe);
                $stmt->execute();
            }
        }
        if ($erreur === "") {
            header("Location: utilisateurs.php");
            exit;
        }
    }
}

$groupes = $conn->query("SELECT * FROM groupe ORDER BY nom");
$liste_groupes = [];
$groupes->data_seek(0);
while ($g = $groupes->fetch_assoc()) { $liste_groupes[] = $g; }

$utilisateurs = $conn->query("
    SELECT u.*, g.nom AS nom_groupe
    FROM utilisateur u
    JOIN groupe g ON u.id_groupe = g.id_groupe
    ORDER BY u.nom
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des utilisateurs - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Gestion des utilisateurs</h2>
        <span class="text-muted">Bonjour <?= htmlspecialchars($_SESSION["prenom"]) ?></span>
    </div>

    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <div class="card p-3 mb-4">
        <h5><?= $mode_edition ? "Modifier l'utilisateur" : "Ajouter un utilisateur" ?></h5>
        <form method="POST" class="row g-2">
            <?php if ($mode_edition): ?>
                <input type="hidden" name="id_utilisateur" value="<?= $item_edite["id_utilisateur"] ?>">
            <?php endif; ?>
            <div class="col-md-3">
                <input type="text" name="nom" class="form-control" placeholder="Nom" required
                       value="<?= $mode_edition ? htmlspecialchars($item_edite["nom"]) : "" ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="prenom" class="form-control" placeholder="Prénom"
                       value="<?= $mode_edition ? htmlspecialchars($item_edite["prenom"]) : "" ?>">
            </div>
            <div class="col-md-3">
                <input type="text" name="login" class="form-control" placeholder="Identifiant" required
                       value="<?= $mode_edition ? htmlspecialchars($item_edite["login"]) : "" ?>">
            </div>
            <div class="col-md-3">
                <input type="password" name="mot_de_passe" class="form-control"
                       placeholder="<?= $mode_edition ? "Laisser vide pour ne pas changer" : "Mot de passe" ?>">
            </div>
            <div class="col-md-4 mt-2">
                <select name="id_groupe" class="form-select" required>
                    <option value="">-- Choisir un groupe --</option>
                    <?php foreach ($liste_groupes as $g): ?>
                        <option value="<?= $g["id_groupe"] ?>"
                            <?= ($mode_edition && (int)$item_edite["id_groupe"] === (int)$g["id_groupe"]) ? "selected" : "" ?>>
                            <?= htmlspecialchars($g["nom"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-8 mt-2">
                <button type="submit" class="btn btn-primary"><?= $mode_edition ? "Enregistrer" : "Ajouter" ?></button>
                <?php if ($mode_edition): ?>
                    <a href="utilisateurs.php" class="btn btn-secondary">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark"><tr><th>Nom</th><th>Prénom</th><th>Identifiant</th><th>Groupe</th><th>Actions</th></tr></thead>
        <tbody>
            <?php while ($u = $utilisateurs->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($u["nom"]) ?></td>
                    <td><?= htmlspecialchars($u["prenom"]) ?></td>
                    <td><?= htmlspecialchars($u["login"]) ?></td>
                    <td><?= htmlspecialchars($u["nom_groupe"]) ?></td>
                    <td>
                        <a href="utilisateurs.php?modifier=<?= $u["id_utilisateur"] ?>" class="btn btn-sm btn-warning">Modifier</a>
                        <a href="utilisateurs.php?supprimer=<?= $u["id_utilisateur"] ?>" class="btn btn-sm btn-danger"
                           onclick="return confirm('Supprimer cet utilisateur ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>

<?php
require "config.php";

$erreur = "";
$succes = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST["nom"]);
    $prenom = trim($_POST["prenom"]);
    $email = trim($_POST["email"]);
    $mot_de_passe = $_POST["mot_de_passe"];
    $confirmation = $_POST["confirmation"];

    if ($nom === "" || $email === "" || $mot_de_passe === "") {
        $erreur = "Nom, email et mot de passe sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Adresse email invalide.";
    } elseif ($mot_de_passe !== $confirmation) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($mot_de_passe) < 6) {
        $erreur = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        // Vérifie que l'email n'est pas déjà utilisé (stocké dans la colonne "login")
        $stmt = $conn->prepare("SELECT id_utilisateur FROM utilisateur WHERE login = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $erreur = "Un compte existe déjà avec cet email.";
        } else {
            // Groupe par défaut : "Utilisateurs" si trouvé, sinon le premier groupe existant
            $res_groupe = $conn->query("SELECT id_groupe FROM groupe WHERE nom LIKE '%Utilisateur%' LIMIT 1");
            if ($res_groupe->num_rows === 0) {
                $res_groupe = $conn->query("SELECT id_groupe FROM groupe ORDER BY id_groupe LIMIT 1");
            }
            $groupe_defaut = $res_groupe->fetch_assoc();
            $id_groupe = $groupe_defaut ? $groupe_defaut["id_groupe"] : 1;

            $stmt = $conn->prepare("INSERT INTO utilisateur (nom, prenom, login, mot_de_passe, id_groupe) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $nom, $prenom, $email, $mot_de_passe, $id_groupe);
            $stmt->execute();

            $succes = "Compte créé avec succès ! Tu peux maintenant te connecter.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        html, body { height: 100%; margin: 0; }
        .split-container { display: flex; min-height: 100vh; }
        .side-brand {
            flex: 1; background: linear-gradient(160deg, #0d1b2a 0%, #16283b 100%);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: #fff; padding: 40px; text-align: center;
        }
        .side-brand img { max-width: 160px; margin-bottom: 25px; }
        .side-brand p { color: #90a4ae; max-width: 320px; margin-top: 10px; font-size: 0.95rem; }
        .side-form { flex: 1; display: flex; align-items: center; justify-content: center; background: #f4f6f9; padding: 30px; }
        .form-box { width: 100%; max-width: 400px; }
        .input-icon { position: relative; }
        .input-icon i { position: absolute; top: 50%; left: 14px; transform: translateY(-50%); color: #90a4ae; }
        .input-icon input { padding-left: 40px; }
        .btn-etap { background: #0d1b2a; color: #fff; border: none; font-weight: 600; padding: 10px; }
        .btn-etap:hover { background: #16283b; color: #fff; }
        @media (max-width: 767px) { .split-container { flex-direction: column; } .side-brand { padding: 30px; } }
    </style>
</head>
<body>

<div class="split-container">
    <div class="side-brand">
        <img src="images/logo_etap.png" alt="Logo ETAP">
        <h4>ETAP — Suivi consommation</h4>
        <p>Crée un compte pour accéder à la plateforme de gestion de la consommation d'électricité, de gaz et d'eau.</p>
    </div>

    <div class="side-form">
        <div class="form-box">
            <h3>Créer un compte</h3>
            <p class="text-muted mb-4">Renseigne tes informations ci-dessous.</p>

            <?php if ($erreur): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>
            <?php if ($succes): ?>
                <div class="alert alert-success py-2">
                    <?= htmlspecialchars($succes) ?>
                    <a href="login.php" class="fw-bold">Se connecter →</a>
                </div>
            <?php endif; ?>

            <?php if (!$succes): ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Nom</label>
                    <div class="input-icon">
                        <i class="bi bi-person"></i>
                        <input type="text" name="nom" class="form-control" required
                               value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prénom</label>
                    <div class="input-icon">
                        <i class="bi bi-person"></i>
                        <input type="text" name="prenom" class="form-control"
                               value="<?= isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-icon">
                        <i class="bi bi-envelope"></i>
                        <input type="email" name="email" class="form-control" required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="mot_de_passe" class="form-control" required minlength="6">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="confirmation" class="form-control" required minlength="6">
                    </div>
                </div>
                <button type="submit" class="btn btn-etap w-100 py-2">Créer le compte</button>
            </form>
            <p class="text-center text-muted mt-3">
                Déjà un compte ? <a href="login.php">Se connecter</a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>

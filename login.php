<?php
require "config.php";

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = $_POST["login"];
    $motDePasse = $_POST["mot_de_passe"];

    $stmt = $conn->prepare("SELECT id_utilisateur, nom, prenom, mot_de_passe, id_groupe FROM utilisateur WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $utilisateur = $result->fetch_assoc();

        if ($motDePasse === $utilisateur["mot_de_passe"]) {
            $_SESSION["id_utilisateur"] = $utilisateur["id_utilisateur"];
            $_SESSION["nom"] = $utilisateur["nom"];
            $_SESSION["prenom"] = $utilisateur["prenom"];
            $_SESSION["id_groupe"] = $utilisateur["id_groupe"];

            header("Location: dashboard.php");
            exit;
        } else {
            $erreur = "Mot de passe incorrect.";
        }
    } else {
        $erreur = "Utilisateur introuvable.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - ETAP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        html, body { height: 100%; margin: 0; }
        .split-container { display: flex; min-height: 100vh; }

        .side-brand {
            flex: 1;
            background: linear-gradient(160deg, #0d1b2a 0%, #16283b 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #fff;
            padding: 40px;
            text-align: center;
        }
        .side-brand img { max-width: 160px; margin-bottom: 25px; }
        .side-brand h4 { font-weight: 700; letter-spacing: 0.5px; }
        .side-brand p { color: #90a4ae; max-width: 320px; margin-top: 10px; font-size: 0.95rem; }

        .side-form {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f6f9;
            padding: 30px;
        }
        .form-box { width: 100%; max-width: 380px; }
        .form-box h3 { font-weight: 700; margin-bottom: 5px; }
        .form-box p.subtitle { color: #78909c; margin-bottom: 30px; }

        .input-icon { position: relative; }
        .input-icon i {
            position: absolute; top: 50%; left: 14px; transform: translateY(-50%); color: #90a4ae;
        }
        .input-icon input { padding-left: 40px; }

        .btn-etap {
            background: #0d1b2a; color: #fff; border: none; font-weight: 600; padding: 10px;
        }
        .btn-etap:hover { background: #16283b; color: #fff; }

        @media (max-width: 767px) {
            .split-container { flex-direction: column; }
            .side-brand { padding: 30px; }
        }
    </style>
</head>
<body>

<div class="split-container">

    <div class="side-brand">
        <img src="images/logo_etap.png" alt="Logo ETAP">
        <h4>ETAP — Suivi consommation</h4>
        <p>Plateforme de gestion et de suivi de la consommation d'électricité, de gaz et d'eau à travers les factures.</p>
    </div>

    <div class="side-form">
        <div class="form-box">
            <h3>Connexion</h3>
            <p class="subtitle">Connecte-toi pour accéder à ton espace.</p>

            <?php if ($erreur): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Identifiant</label>
                    <div class="input-icon">
                        <i class="bi bi-person"></i>
                        <input type="text" name="login" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Mot de passe</label>
                    <div class="input-icon">
                        <i class="bi bi-lock"></i>
                        <input type="password" name="mot_de_passe" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-etap w-100 py-2">Se connecter</button>
            </form>

            <p class="text-center text-muted mt-3">
                Pas encore de compte ? <a href="inscription.php">Créer un compte</a>
            </p>

            <p class="text-center text-muted mt-4" style="font-size:0.8rem;">© <?= date("Y") ?> ETAP - Entreprise Tunisienne d'Activités Pétrolières</p>
        </div>
    </div>

</div>

</body>
</html>

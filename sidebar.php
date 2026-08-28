<?php $page_actuelle = basename($_SERVER['PHP_SELF']); ?>
<div class="sidebar">
    <div class="brand">
        <img src="images/logo_etap.png" alt="Logo ETAP">
    </div>

    <a href="dashboard.php" class="<?= $page_actuelle === 'dashboard.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
    </a>

    <div class="section-label">Administration</div>
    <a href="locaux.php" class="<?= $page_actuelle === 'locaux.php' ? 'active' : '' ?>">
        <i class="bi bi-geo-alt me-2"></i> Locaux
    </a>
    <a href="regions.php" class="<?= $page_actuelle === 'regions.php' ? 'active' : '' ?>">
        <i class="bi bi-map me-2"></i> Régions
    </a>
    <a href="familles.php" class="<?= $page_actuelle === 'familles.php' ? 'active' : '' ?>">
        <i class="bi bi-diagram-3 me-2"></i> Familles
    </a>
    <a href="sous_familles.php" class="<?= $page_actuelle === 'sous_familles.php' ? 'active' : '' ?>">
        <i class="bi bi-diagram-2 me-2"></i> Sous-familles
    </a>
    <a href="groupes.php" class="<?= $page_actuelle === 'groupes.php' ? 'active' : '' ?>">
        <i class="bi bi-people me-2"></i> Groupes
    </a>
    <a href="utilisateurs.php" class="<?= $page_actuelle === 'utilisateurs.php' ? 'active' : '' ?>">
        <i class="bi bi-person-badge me-2"></i> Utilisateurs
    </a>

    <div class="section-label">Consommation</div>
    <a href="compteurs.php" class="<?= $page_actuelle === 'compteurs.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer me-2"></i> Compteurs
    </a>
    <a href="factures.php" class="<?= $page_actuelle === 'factures.php' ? 'active' : '' ?>">
        <i class="bi bi-receipt me-2"></i> Factures
    </a>
    <a href="generer_qr_demo.php" class="<?= $page_actuelle === 'generer_qr_demo.php' ? 'active' : '' ?>">
        <i class="bi bi-qr-code me-2"></i> Générer QR (démo)
    </a>
    <a href="etats.php" class="<?= $page_actuelle === 'etats.php' ? 'active' : '' ?>">
        <i class="bi bi-bar-chart-line me-2"></i> États & Graphes
    </a>

    <div class="section-label">Compte</div>
    <a href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a>
</div>

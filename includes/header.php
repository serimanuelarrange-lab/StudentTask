<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fonctions.php';
require_once __DIR__ . '/auth.php';

// Toutes les pages qui utilisent ce en-tête sont réservées aux utilisateurs connectés.
exigerConnexion();

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/statistiques.php';

$titrePage  = $titrePage  ?? 'StudentTask';
$pageActive = $pageActive ?? '';

// Nombre de tâches en retard (pastille du menu et de la cloche).
$nbRetard = calculerIndicateurs(getPDO(), idUtilisateurConnecte())['en_retard'];

// Initiales affichées dans l'avatar.
$initiales = '';
foreach (array_slice(preg_split('/\s+/', trim($_SESSION['nom'] ?? '')), 0, 2) as $mot) {
    $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titrePage) ?> — StudentTask</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo-icone.svg">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app">

    <aside class="sidebar" id="sidebar">
        <a class="logo" href="<?= BASE_URL ?>/dashboard.php">
            <img class="logo-img" src="<?= BASE_URL ?>/assets/images/logo.svg" alt="StudentTask">
        </a>
        <div class="utilisateur">Connecté : <?= e($_SESSION['nom'] ?? '') ?></div>

        <nav>
            <a class="lien <?= $pageActive === 'dashboard' ? 'actif' : '' ?>" href="<?= BASE_URL ?>/dashboard.php">
                <svg class="icone" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Tableau de bord
            </a>
            <a class="lien <?= $pageActive === 'projets' ? 'actif' : '' ?>" href="<?= BASE_URL ?>/projets/index.php">
                <svg class="icone" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Projets
            </a>
            <a class="lien <?= $pageActive === 'taches' ? 'actif' : '' ?>" href="<?= BASE_URL ?>/taches/index.php">
                <svg class="icone" viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Tâches
                <?php if ($nbRetard > 0): ?><span class="pastille-menu" title="Tâches en retard"><?= e($nbRetard) ?></span><?php endif; ?>
            </a>
            <a class="lien <?= $pageActive === 'membres' ? 'actif' : '' ?>" href="<?= BASE_URL ?>/membres/index.php">
                <svg class="icone" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Membres
            </a>
        </nav>

        <a class="lien" href="<?= BASE_URL ?>/logout.php">
            <svg class="icone" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Déconnexion
        </a>
    </aside>

    <main class="contenu">
        <div class="barre-haut">
            <button class="menu-toggle" id="menu-toggle" aria-label="Ouvrir le menu" aria-expanded="false">☰ Menu</button>

            <form class="recherche-globale" action="<?= BASE_URL ?>/taches/index.php" method="get" role="search">
                <svg class="icone" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="search" name="q" placeholder="Rechercher une tâche..." aria-label="Rechercher une tâche" maxlength="100">
            </form>

            <div class="barre-haut-droite">
                <a class="icone-bouton" href="<?= BASE_URL ?>/taches/index.php" title="Tâches en retard : <?= e($nbRetard) ?>" aria-label="Tâches en retard : <?= e($nbRetard) ?>">
                    <svg class="icone" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <?php if ($nbRetard > 0): ?><span class="pastille"><?= e($nbRetard) ?></span><?php endif; ?>
                </a>
                <span class="avatar" title="<?= e($_SESSION['nom'] ?? '') ?>"><?= e($initiales) ?></span>
            </div>
        </div>

        <?php afficherFlash(); ?>
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projets.php';
require_once __DIR__ . '/../includes/taches.php';

exigerConnexion();

// Page de retour : la liste des tâches par défaut, le tableau de bord si demandé.
$retour = (($_POST['retour'] ?? '') === 'dashboard') ? '/dashboard.php' : '/taches/index.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('/taches/index.php');
}

if (!verifierCsrf()) {
    definirFlash('erreur', 'Votre session a expiré. Veuillez réessayer.');
    rediriger($retour);
}

$pdo    = getPDO();
$idUser = idUtilisateurConnecte();
$id     = (int) ($_POST['id_tache'] ?? 0);
$statut = $_POST['statut'] ?? '';
$tache  = trouverTachePourUtilisateur($pdo, $id, $idUser);

if (!in_array($statut, STATUTS_TACHE, true)) {
    definirFlash('erreur', 'Statut invalide.');
} elseif (!$tache) {
    definirFlash('erreur', 'Tâche introuvable.');
} elseif ($tache['role'] !== 'chef_projet' && (int) $tache['id_assigne'] !== $idUser) {
    definirFlash('erreur', 'Vous ne pouvez modifier que le statut des tâches qui vous sont attribuées.');
} else {
    try {
        changerStatutTache($pdo, $id, $statut);
        definirFlash('succes', 'Statut mis à jour : ' . libelleStatut($statut) . '.');
    } catch (PDOException $e) {
        error_log('StudentTask - changement de statut : ' . $e->getMessage());
        definirFlash('erreur', 'Impossible de changer le statut pour le moment.');
    }
}

rediriger($retour);
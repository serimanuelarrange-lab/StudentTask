<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projets.php';
require_once __DIR__ . '/../includes/taches.php';

exigerConnexion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rediriger('/taches/index.php');
}

if (!verifierCsrf()) {
    definirFlash('erreur', 'Votre session a expiré. Veuillez réessayer.');
    rediriger('/taches/index.php');
}

$pdo   = getPDO();
$id    = (int) ($_POST['id_tache'] ?? 0);
$tache = trouverTachePourUtilisateur($pdo, $id, idUtilisateurConnecte());

if (!$tache) {
    definirFlash('erreur', 'Tâche introuvable.');
} elseif ($tache['role'] !== 'chef_projet') {
    definirFlash('erreur', 'Seul le chef de projet peut supprimer une tâche.');
} else {
    try {
        supprimerTache($pdo, $id);
        definirFlash('succes', 'Tâche supprimée.');
    } catch (PDOException $e) {
        error_log('StudentTask - suppression de tâche : ' . $e->getMessage());
        definirFlash('erreur', 'Impossible de supprimer la tâche pour le moment.');
    }
}

rediriger('/taches/index.php');
<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projets.php';
require_once __DIR__ . '/../includes/taches.php';

exigerConnexion();

$pdo     = getPDO();
$id      = (int) ($_GET['id'] ?? 0);
$tache   = trouverTachePourUtilisateur($pdo, $id, idUtilisateurConnecte());
$erreurs = [];
$message = '';

if (!$tache) {
    http_response_code(404);
    $message = 'Tâche introuvable.';
} elseif ($tache['role'] !== 'chef_projet') {
    http_response_code(403);
    $message = 'Seul le chef de projet peut modifier cette tâche.';
} else {
    $membres = listerMembresProjet($pdo, (int) $tache['id_projet']);
    $projet  = ['titre' => $tache['titre_projet'], 'date_debut' => $tache['date_debut_projet']];
    $valeurs = [
        'titre'       => $tache['titre'],
        'description' => (string) $tache['description'],
        'id_assigne'  => $tache['id_assigne'],
        'priorite'    => $tache['priorite'],
        'statut'      => $tache['statut'],
        'date_limite' => $tache['date_limite'],
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifierCsrf()) {
            $erreurs[] = 'Votre session a expiré. Veuillez réessayer.';
        } else {
            [$erreurs, $valeurs] = validerTache($_POST, $projet, $membres);

            if (empty($erreurs)) {
                try {
                    modifierTache($pdo, $id, $valeurs);
                    definirFlash('succes', 'Tâche mise à jour.');
                    rediriger('/taches/index.php');
                } catch (PDOException $e) {
                    error_log('StudentTask - modification de tâche : ' . $e->getMessage());
                    $erreurs[] = 'Impossible de modifier la tâche pour le moment. Veuillez réessayer.';
                }
            }
        }
    }
}

$titrePage     = 'Modifier la tâche';
$pageActive    = 'taches';
$libelleBouton = 'Enregistrer les modifications';
$lienAnnuler   = BASE_URL . '/taches/index.php';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($message !== ''): ?>
    <div class="carte"><p class="etat-vide"><?= e($message) ?></p></div>
    <p><a href="<?= BASE_URL ?>/taches/index.php">← Retour aux tâches</a></p>
<?php else: ?>
    <div class="entete-page">
        <div>
            <h1>Modifier la tâche</h1>
            <p class="sous-titre"><?= e($tache['titre']) ?></p>
        </div>
    </div>

    <?php if (!empty($erreurs)): ?>
        <div class="alerte alerte-erreur">
            <ul>
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= e($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="carte">
        <?php require __DIR__ . '/../includes/formulaire_tache.php'; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
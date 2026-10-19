<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projets.php';
require_once __DIR__ . '/../includes/taches.php';

exigerConnexion();

$pdo           = getPDO();
$idUtilisateur = idUtilisateurConnecte();
$idProjet      = (int) ($_GET['projet'] ?? 0);
$erreurs       = [];
$message       = '';
$projet        = null;
$choixProjets  = [];
$membres       = [];
$valeurs       = [
    'titre' => '', 'description' => '', 'id_assigne' => null,
    'priorite' => 'moyenne', 'statut' => 'a_faire', 'date_limite' => '',
];

if ($idProjet === 0) {
    // Aucun projet choisi : on propose les projets où l'utilisateur est chef et qui ne sont pas archivés.
    $choixProjets = array_filter(
        listerProjetsUtilisateur($pdo, $idUtilisateur),
        fn($p) => $p['role'] === 'chef_projet' && !$p['archive']
    );
} else {
    $projet = trouverProjetPourUtilisateur($pdo, $idProjet, $idUtilisateur);

    if (!$projet) {
        http_response_code(404);
        $message = 'Projet introuvable.';
    } elseif ($projet['role'] !== 'chef_projet') {
        http_response_code(403);
        $message = 'Seul le chef de projet peut créer des tâches.';
    } elseif ($projet['archive']) {
        http_response_code(403);
        $message = "Ce projet est archivé : il n'accepte plus de nouvelles tâches.";
    } else {
        $membres = listerMembresProjet($pdo, $idProjet);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifierCsrf()) {
                $erreurs[] = 'Votre session a expiré. Veuillez réessayer.';
            } else {
                [$erreurs, $valeurs] = validerTache($_POST, $projet, $membres);

                if (empty($erreurs)) {
                    try {
                        creerTache($pdo, $idProjet, $valeurs);
                        definirFlash('succes', 'Tâche créée avec succès.');
                        rediriger('/projets/detail.php?id=' . $idProjet);
                    } catch (PDOException $e) {
                        error_log('StudentTask - création de tâche : ' . $e->getMessage());
                        $erreurs[] = 'Impossible de créer la tâche pour le moment. Veuillez réessayer.';
                    }
                }
            }
        }
    }
}

$titrePage     = 'Nouvelle tâche';
$pageActive    = 'taches';
$libelleBouton = 'Créer la tâche';
$lienAnnuler   = BASE_URL . '/projets/detail.php?id=' . $idProjet;
require __DIR__ . '/../includes/header.php';
?>

<?php if ($idProjet === 0): ?>
    <div class="entete-page">
        <div>
            <h1>Nouvelle tâche</h1>
            <p class="sous-titre">Choisissez le projet auquel rattacher la tâche.</p>
        </div>
    </div>
    <?php if (empty($choixProjets)): ?>
        <div class="carte"><p class="etat-vide">Aucun projet actif dont vous êtes le chef. Créez d'abord un projet.</p></div>
    <?php else: ?>
        <section class="carte">
            <ul class="liste-simple">
                <?php foreach ($choixProjets as $p): ?>
                    <li>
                        <strong><?= e($p['titre']) ?></strong>
                        <a href="<?= BASE_URL ?>/taches/creer.php?projet=<?= e($p['id_projet']) ?>">Ajouter une tâche</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

<?php elseif ($message !== ''): ?>
    <div class="carte"><p class="etat-vide"><?= e($message) ?></p></div>
    <p><a href="<?= BASE_URL ?>/projets/index.php">← Retour aux projets</a></p>

<?php else: ?>
    <div class="entete-page">
        <div>
            <h1>Nouvelle tâche</h1>
            <p class="sous-titre">Projet : <strong><?= e($projet['titre']) ?></strong></p>
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
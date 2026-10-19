<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projets.php';

exigerConnexion();

$pdo           = getPDO();
$idUtilisateur = idUtilisateurConnecte();
$id            = (int) ($_GET['id'] ?? 0);
$erreurs       = [];
$projet        = null;
$choixProjets  = [];

if ($id === 0) {
    // Aucun projet choisi : on propose la liste des projets de l'utilisateur.
    $choixProjets = listerProjetsUtilisateur($pdo, $idUtilisateur);
} else {
    $projet = trouverProjetPourUtilisateur($pdo, $id, $idUtilisateur);
    if (!$projet) {
        http_response_code(404);
    }
}

$estChef = $projet && $projet['role'] === 'chef_projet';

// ---------- Traitement des actions (ajout / retrait) ----------
if ($projet && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$estChef) {
        http_response_code(403);
        $erreurs[] = 'Seul le chef de projet peut gérer les membres.';
    } elseif (!verifierCsrf()) {
        $erreurs[] = 'Votre session a expiré. Veuillez réessayer.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'ajouter') {
                $email = mb_strtolower(trim($_POST['email'] ?? ''));

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $erreurs[] = "L'adresse e-mail n'est pas valide.";
                } else {
                    $cible = trouverUtilisateurParEmail($pdo, $email);
                    if (!$cible) {
                        $erreurs[] = "Aucun compte StudentTask n'existe avec cette adresse e-mail.";
                    } elseif (estMembreProjet($pdo, $id, (int) $cible['id_utilisateur'])) {
                        $erreurs[] = 'Cette personne fait déjà partie du projet.';
                    } else {
                        ajouterMembreProjet($pdo, $id, (int) $cible['id_utilisateur']);
                        definirFlash('succes', $cible['nom'] . ' a été ajouté(e) au projet.');
                        rediriger('/membres/index.php?id=' . $id);
                    }
                }
            } elseif ($action === 'retirer') {
                $cible = (int) ($_POST['id_utilisateur'] ?? 0);

                if ($cible === $idUtilisateur) {
                    $erreurs[] = 'Le chef de projet ne peut pas être retiré du projet.';
                } elseif (retirerMembreProjet($pdo, $id, $cible)) {
                    definirFlash('succes', 'Le membre a été retiré. Ses tâches sont maintenant non attribuées.');
                    rediriger('/membres/index.php?id=' . $id);
                } else {
                    $erreurs[] = 'Ce membre ne fait pas partie du projet.';
                }
            } else {
                $erreurs[] = 'Action inconnue.';
            }
        } catch (PDOException $e) {
            error_log('StudentTask - gestion des membres : ' . $e->getMessage());
            $erreurs[] = "L'opération a échoué. Veuillez réessayer.";
        }
    }
}

$membres = $projet ? listerMembresProjet($pdo, $id) : [];

$titrePage  = 'Membres';
$pageActive = 'membres';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($id === 0): ?>
    <div class="entete-page">
        <div>
            <h1>Membres</h1>
            <p class="sous-titre">Choisissez un projet pour voir et gérer ses membres.</p>
        </div>
    </div>

    <?php if (empty($choixProjets)): ?>
        <div class="carte"><p class="etat-vide">Vous n'avez encore aucun projet.</p></div>
    <?php else: ?>
        <section class="carte">
            <ul class="liste-simple">
                <?php foreach ($choixProjets as $p): ?>
                    <li>
                        <strong><?= e($p['titre']) ?></strong>
                        <a href="<?= BASE_URL ?>/membres/index.php?id=<?= e($p['id_projet']) ?>">Voir les membres</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

<?php elseif (!$projet): ?>
    <div class="carte"><p class="etat-vide">Projet introuvable.</p></div>
    <p><a href="<?= BASE_URL ?>/projets/index.php">← Retour aux projets</a></p>

<?php else: ?>
    <a class="lien-retour" href="<?= BASE_URL ?>/projets/detail.php?id=<?= e($id) ?>">← Retour au projet</a>

    <div class="entete-page">
        <div>
            <h1>Membres</h1>
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

    <div class="grille-2">
        <section class="carte">
            <h2>Membres du projet (<?= e(count($membres)) ?>)</h2>
            <ul class="liste-simple">
                <?php foreach ($membres as $m): ?>
                    <li>
                        <div><strong><?= e($m['nom']) ?></strong><br><span class="petit"><?= e($m['email']) ?></span></div>
                        <div class="actions-ligne">
                            <span class="badge badge-role"><?= $m['role'] === 'chef_projet' ? 'Chef de projet' : 'Membre' ?></span>
                            <?php if ($estChef && $m['role'] !== 'chef_projet'): ?>
                                <form class="form-inline" method="post" action=""
                                      onsubmit="return confirm('Retirer ce membre du projet ? Ses tâches deviendront non attribuées.');">
                                    <?= champCsrf() ?>
                                    <input type="hidden" name="action" value="retirer">
                                    <input type="hidden" name="id_utilisateur" value="<?= e($m['id_utilisateur']) ?>">
                                    <button class="bouton bouton-danger bouton-petit" type="submit">Retirer</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="carte">
            <h2>Ajouter un membre</h2>
            <?php if ($estChef): ?>
                <form method="post" action="">
                    <?= champCsrf() ?>
                    <input type="hidden" name="action" value="ajouter">
                    <div class="champ">
                        <label for="email">Adresse e-mail du membre *</label>
                        <input type="email" id="email" name="email" required maxlength="150">
                        <p class="aide">La personne doit déjà avoir un compte StudentTask.</p>
                    </div>
                    <button class="bouton" type="submit">Ajouter au projet</button>
                </form>
            <?php else: ?>
                <p class="etat-vide">Seul le chef de projet peut ajouter ou retirer des membres.</p>
            <?php endif; ?>
        </section>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
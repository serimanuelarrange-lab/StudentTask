<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projets.php';

exigerConnexion();

$titrePage  = 'Projets';
$pageActive = 'projets';

$projets = listerProjetsUtilisateur(getPDO(), idUtilisateurConnecte());

require __DIR__ . '/../includes/header.php';
?>

<div class="entete-page">
    <div>
        <h1>Projets</h1>
        <p class="sous-titre">Tous les projets auxquels vous participez.</p>
    </div>
    <a class="bouton" href="<?= BASE_URL ?>/projets/creer.php">+ Nouveau projet</a>
</div>

<?php if (empty($projets)): ?>
    <div class="carte"><p class="etat-vide">Vous n'avez encore aucun projet. Créez le premier !</p></div>
<?php else: ?>
    <div class="grille-projets">
        <?php foreach ($projets as $projet): ?>
            <?php $pct = calculerProgression((int) $projet['termine'], (int) $projet['total']); ?>
            <article class="carte carte-projet">
                <div class="meta-projet">
                    <?php if ($projet['archive']): ?>
                        <span class="badge badge-archive">Archivé</span>
                    <?php else: ?>
                        <span class="badge badge-actif">Actif</span>
                    <?php endif; ?>
                    <span class="petit">Échéance : <?= e(formaterDate($projet['date_limite'])) ?></span>
                </div>
                <h2><?= e($projet['titre']) ?></h2>
                <p class="description"><?= e($projet['description']) ?></p>
                <div class="haut-progression">
                    <span class="petit"><?= e($projet['termine']) ?> / <?= e($projet['total']) ?> tâches terminées</span>
                    <strong><?= $pct ?> %</strong>
                </div>
                <div class="barre"><span style="width: <?= $pct ?>%"></span></div>
                <a class="bouton bouton-secondaire" href="<?= BASE_URL ?>/projets/detail.php?id=<?= e($projet['id_projet']) ?>">Voir le projet</a>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
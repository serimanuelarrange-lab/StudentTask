<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projets.php';

exigerConnexion();

$pdo    = getPDO();
$id     = (int) ($_GET['id'] ?? 0);
$projet = trouverProjetPourUtilisateur($pdo, $id, idUtilisateurConnecte());

if (!$projet) {
    http_response_code(404); // introuvable, ou accès refusé : même réponse pour ne rien révéler
} else {
    $membres   = listerMembresProjet($pdo, $id);
    $taches    = listerTachesProjet($pdo, $id);
    $terminees = count(array_filter($taches, fn($t) => $t['statut'] === 'termine'));
    $pct       = calculerProgression($terminees, count($taches));
    $estChef   = $projet['role'] === 'chef_projet';
}

$titrePage  = 'Détail du projet';
$pageActive = 'projets';
require __DIR__ . '/../includes/header.php';
?>

<?php if (!$projet): ?>
    <div class="carte"><p class="etat-vide">Projet introuvable.</p></div>
    <p><a href="<?= BASE_URL ?>/projets/index.php">← Retour aux projets</a></p>
<?php else: ?>
    <a class="lien-retour" href="<?= BASE_URL ?>/projets/index.php">← Retour aux projets</a>

    <div class="entete-page">
        <div>
            <h1><?= e($projet['titre']) ?></h1>
            <p class="sous-titre"><?= e($projet['description']) ?></p>
        </div>
        <div class="boutons-entete">
            <?php if ($estChef): ?>
                <a class="bouton bouton-secondaire" href="<?= BASE_URL ?>/projets/modifier.php?id=<?= e($id) ?>">Modifier</a>
            <?php endif; ?>
            <?php if (!$projet['archive'] && $estChef): ?>
                <a class="bouton" href="<?= BASE_URL ?>/taches/creer.php?projet=<?= e($id) ?>">+ Nouvelle tâche</a>
            <?php endif; ?>
        </div>
    </div>

    <section class="carte" style="margin-bottom: 18px;">
        <div class="infos-projet">
            <div><span class="petit">Début</span><br><strong><?= e(formaterDate($projet['date_debut'])) ?></strong></div>
            <div><span class="petit">Échéance</span><br><strong><?= e(formaterDate($projet['date_limite'])) ?></strong></div>
            <div><span class="petit">Statut</span><br>
                <?php if ($projet['archive']): ?>
                    <span class="badge badge-archive">Archivé</span>
                <?php else: ?>
                    <span class="badge badge-actif">Actif</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="haut-progression">
            <span class="petit"><?= e($terminees) ?> / <?= e(count($taches)) ?> tâches terminées</span>
            <strong><?= $pct ?> %</strong>
        </div>
        <div class="barre"><span style="width: <?= $pct ?>%"></span></div>
    </section>

    <div class="grille-2">
        <section class="carte">
            <h2>Membres</h2>
            <ul class="liste-simple">
                <?php foreach ($membres as $m): ?>
                    <li>
                        <div><strong><?= e($m['nom']) ?></strong><br><span class="petit"><?= e($m['email']) ?></span></div>
                        <span class="badge badge-role"><?= $m['role'] === 'chef_projet' ? 'Chef de projet' : 'Membre' ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p style="margin-bottom: 0;"><a href="<?= BASE_URL ?>/membres/index.php?id=<?= e($id) ?>">Gérer les membres</a></p>
        </section>

        <section class="carte">
            <h2>Tâches du projet</h2>
            <?php if (empty($taches)): ?>
                <p class="etat-vide">Ce projet n'a encore aucune tâche.</p>
            <?php else: ?>
                <ul class="liste-simple">
                    <?php foreach ($taches as $t): ?>
                        <li>
                            <div>
                                <strong><?= e($t['titre']) ?></strong><br>
                                <span class="petit">
                                    <?= e($t['nom_assigne'] ?? 'Non attribuée') ?>
                                    · <?= e(formaterDate($t['date_limite'])) ?>
                                    <?php if (estEnRetard($t['date_limite'], $t['statut'])): ?>
                                        <span class="badge badge-retard">En retard</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <span class="badge badge-statut-<?= e($t['statut']) ?>"><?= e(libelleStatut($t['statut'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
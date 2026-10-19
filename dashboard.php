<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/projets.php';
require_once __DIR__ . '/includes/taches.php';
require_once __DIR__ . '/includes/statistiques.php';

exigerConnexion();

$pdo           = getPDO();
$idUtilisateur = idUtilisateurConnecte();
$aujourdhui    = date('Y-m-d');

// ---------- Données ----------
$indicateurs   = calculerIndicateurs($pdo, $idUtilisateur);
$tousProjets   = listerProjetsUtilisateur($pdo, $idUtilisateur);
$toutesTaches  = listerTachesUtilisateur($pdo, $idUtilisateur);

$projetsActifs = array_values(array_filter($tousProjets, fn($p) => !$p['archive']));

$tachesEnRetard = array_values(array_filter(
    $toutesTaches,
    fn($t) => estEnRetard($t['date_limite'], $t['statut'])
));
$retardAffichees = array_slice($tachesEnRetard, 0, 6);

$mesTaches = array_values(array_filter(
    $toutesTaches,
    fn($t) => (int) $t['id_assigne'] === $idUtilisateur && $t['statut'] !== 'termine'
));
$mesTachesAffichees = array_slice($mesTaches, 0, 5);

$progressionGlobale = calculerProgression($indicateurs['termine'], $indicateurs['total']);

// ---------- Graphique des échéances (14 jours) ----------
$echeances = echeancesProchainsJours($toutesTaches, 14);
$valeurs   = array_values($echeances);
$dates     = array_keys($echeances);
$nbPoints  = count($valeurs);
$max       = max(1, max($valeurs));
$totalEcheances = array_sum($valeurs);

$largeurSvg = 640;
$hauteurSvg = 230;
$margeG = 34; $margeD = 14; $margeH = 14; $margeB = 34;
$largeur = $largeurSvg - $margeG - $margeD;
$hauteur = $hauteurSvg - $margeH - $margeB;
$yBas    = $margeH + $hauteur;

$points = [];
foreach ($valeurs as $i => $v) {
    $x = $margeG + $largeur * $i / ($nbPoints - 1);
    $y = $margeH + $hauteur - $hauteur * $v / $max;
    $points[] = [round($x, 1), round($y, 1)];
}
$ligne   = implode(' ', array_map(fn($p) => $p[0] . ',' . $p[1], $points));
$aire    = $margeG . ',' . $yBas . ' ' . $ligne . ' ' . ($margeG + $largeur) . ',' . $yBas;
$niveaux = array_unique([0, (int) ceil($max / 2), $max]);

// ---------- Calendrier (mois choisi avec ?mois=AAAA-MM) ----------
$moisDemande = $_GET['mois'] ?? '';
if (!preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $moisDemande, $m) || (int) $m[1] < 2000 || (int) $m[1] > 2100) {
    $moisDemande = date('Y-m');
}
[$annee, $mois] = array_map('intval', explode('-', $moisDemande));
$calendrier     = construireCalendrier($annee, $mois, $toutesTaches);
$moisPrecedent  = (clone $calendrier['premier'])->modify('-1 month')->format('Y-m');
$moisSuivant    = (clone $calendrier['premier'])->modify('+1 month')->format('Y-m');
$moisCourant    = date('Y-m');

$titrePage  = 'Tableau de bord';
$pageActive = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="entete-page">
    <div>
        <h1>Tableau de bord</h1>
        <p class="sous-titre">
            Bonjour <?= e($_SESSION['nom'] ?? '') ?>, voici la situation de vos projets au
            <?= e(date('d/m/Y')) ?>.
        </p>
    </div>
</div>

<div class="dashboard">

    <!-- ===== Colonne principale ===== -->
    <div class="colonne-principale">

        <div class="cartes-couleur">
            <div class="carte-couleur menthe">
                <div class="icone-carte"><svg class="icone" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
                <div class="libelle">Avancement global</div>
                <div class="grand"><?= e($progressionGlobale) ?> %</div>
                <div class="detail"><?= e($indicateurs['termine']) ?> / <?= e($indicateurs['total']) ?> tâches terminées</div>
                <div class="barre"><span style="width: <?= e($progressionGlobale) ?>%"></span></div>
            </div>

            <div class="carte-couleur bleu">
                <div class="icone-carte"><svg class="icone" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg></div>
                <div class="libelle">Projets actifs</div>
                <div class="grand"><?= e(count($projetsActifs)) ?></div>
                <div class="detail">sur <?= e(count($tousProjets)) ?> projet(s) au total</div>
            </div>

            <div class="carte-couleur peche">
                <div class="icone-carte"><svg class="icone" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div class="libelle">Tâches en retard</div>
                <div class="grand"><?= e($indicateurs['en_retard']) ?></div>
                <div class="detail"><?= $indicateurs['en_retard'] > 0 ? 'À traiter en priorité' : 'Tout est à jour' ?></div>
            </div>
        </div>

        <section class="carte">
            <h2>Répartition des tâches</h2>
            <div class="repartition">
                <div><div class="valeur"><?= e($indicateurs['total']) ?></div><div class="petit">Au total</div></div>
                <div><div class="valeur"><?= e($indicateurs['a_faire']) ?></div><div class="petit">À faire</div></div>
                <div><div class="valeur"><?= e($indicateurs['en_cours']) ?></div><div class="petit">En cours</div></div>
                <div><div class="valeur vert"><?= e($indicateurs['termine']) ?></div><div class="petit">Terminées</div></div>
            </div>
        </section>

        <section class="carte graphique">
            <h2>Échéances des 14 prochains jours</h2>
            <svg viewBox="0 0 <?= $largeurSvg ?> <?= $hauteurSvg ?>" role="img"
                 aria-label="Nombre de tâches non terminées à échéance chaque jour pendant 14 jours">
                <?php foreach ($niveaux as $niveau): ?>
                    <?php $yNiveau = round($margeH + $hauteur - $hauteur * $niveau / $max, 1); ?>
                    <line class="graphe-grille" x1="<?= $margeG ?>" y1="<?= $yNiveau ?>" x2="<?= $margeG + $largeur ?>" y2="<?= $yNiveau ?>"/>
                    <text class="graphe-axe" x="<?= $margeG - 8 ?>" y="<?= $yNiveau + 4 ?>" text-anchor="end"><?= e($niveau) ?></text>
                <?php endforeach; ?>

                <polygon class="graphe-aire" points="<?= $aire ?>"/>
                <polyline class="graphe-ligne" points="<?= $ligne ?>"/>

                <?php foreach ($points as $i => $p): ?>
                    <circle class="graphe-point <?= $valeurs[$i] > 0 ? 'plein' : '' ?>" cx="<?= $p[0] ?>" cy="<?= $p[1] ?>" r="4">
                        <title><?= e(formaterDate($dates[$i])) ?> : <?= e($valeurs[$i]) ?> tâche(s)</title>
                    </circle>
                    <?php if ($i % 2 === 0): ?>
                        <text class="graphe-axe" x="<?= $p[0] ?>" y="<?= $hauteurSvg - 10 ?>" text-anchor="middle">
                            <?= $i === 0 ? 'Auj.' : e(substr($dates[$i], 8, 2) . '/' . substr($dates[$i], 5, 2)) ?>
                        </text>
                    <?php endif; ?>
                <?php endforeach; ?>
            </svg>
            <?php if ($totalEcheances === 0): ?>
                <p class="petit" style="text-align: center; margin: 4px 0 0;">Aucune tâche non terminée n'arrive à échéance dans les 14 prochains jours.</p>
            <?php endif; ?>
        </section>

        <div class="grille-2">
            <section class="carte">
                <h2>Avancement des projets</h2>
                <?php if (empty($projetsActifs)): ?>
                    <p class="etat-vide">
                        Aucun projet actif pour le moment.<br>
                        <a href="<?= BASE_URL ?>/projets/creer.php">Créer un projet</a>
                    </p>
                <?php else: ?>
                    <?php foreach ($projetsActifs as $projet): ?>
                        <?php $pct = calculerProgression((int) $projet['termine'], (int) $projet['total']); ?>
                        <div class="ligne-projet">
                            <div class="haut">
                                <a href="<?= BASE_URL ?>/projets/detail.php?id=<?= e($projet['id_projet']) ?>"><strong><?= e($projet['titre']) ?></strong></a>
                                <span><?= $pct ?> %</span>
                            </div>
                            <div class="barre"><span style="width: <?= $pct ?>%"></span></div>
                            <span class="petit"><?= e($projet['termine']) ?> / <?= e($projet['total']) ?> tâches terminées</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <section class="carte">
                <h2>Mes tâches à venir</h2>
                <?php if (empty($mesTaches)): ?>
                    <p class="etat-vide">Aucune tâche ne vous est attribuée pour le moment.</p>
                <?php else: ?>
                    <ul class="liste-simple">
                        <?php foreach ($mesTachesAffichees as $t): ?>
                            <li>
                                <div>
                                    <strong><?= e($t['titre']) ?></strong><br>
                                    <span class="petit">
                                        <?= e($t['titre_projet']) ?> · <?= e(formaterDate($t['date_limite'])) ?>
                                        <?php if (estEnRetard($t['date_limite'], $t['statut'])): ?>
                                            <span class="badge badge-retard">En retard</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="actions-ligne">
                                    <span class="badge badge-statut-<?= e($t['statut']) ?>"><?= e(libelleStatut($t['statut'])) ?></span>
                                    <form class="form-inline" method="post" action="<?= BASE_URL ?>/taches/statut.php">
                                        <?= champCsrf() ?>
                                        <input type="hidden" name="id_tache" value="<?= e($t['id_tache']) ?>">
                                        <input type="hidden" name="statut" value="termine">
                                        <input type="hidden" name="retour" value="dashboard">
                                        <button class="bouton bouton-petit" type="submit">✓ Terminer</button>
                                    </form>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="petit" style="margin-bottom: 0;"><a href="<?= BASE_URL ?>/taches/index.php">Voir toutes les tâches</a></p>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- ===== Colonne latérale ===== -->
    <aside class="colonne-laterale">

        <section class="carte calendrier">
            <div class="calendrier-entete">
                <a class="nav-mois" href="<?= BASE_URL ?>/dashboard.php?mois=<?= e($moisPrecedent) ?>" aria-label="Mois précédent">&lsaquo;</a>
                <strong><?= e(NOMS_MOIS[$mois]) ?> <?= e($annee) ?></strong>
                <a class="nav-mois" href="<?= BASE_URL ?>/dashboard.php?mois=<?= e($moisSuivant) ?>" aria-label="Mois suivant">&rsaquo;</a>
            </div>

            <div class="calendrier-grille">
                <?php foreach (['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di'] as $nomJour): ?>
                    <span class="entete-jour"><?= e($nomJour) ?></span>
                <?php endforeach; ?>

                <?php for ($i = 0; $i < $calendrier['decalage']; $i++): ?>
                    <span class="jour vide"></span>
                <?php endfor; ?>

                <?php for ($jour = 1; $jour <= $calendrier['nbJours']; $jour++): ?>
                    <?php
                    $dateJour = sprintf('%04d-%02d-%02d', $annee, $mois, $jour);
                    $nbJour   = $calendrier['parJour'][$jour] ?? 0;
                    $classes  = 'jour';
                    if ($dateJour === $aujourdhui) {
                        $classes .= ' aujourdhui';
                    }
                    if ($nbJour > 0) {
                        $classes .= $dateJour < $aujourdhui ? ' avec-retard' : ' avec-tache';
                    }
                    ?>
                    <span class="<?= e($classes) ?>" <?= $nbJour > 0 ? 'title="' . e($nbJour) . ' tâche(s) à échéance"' : '' ?>><?= $jour ?></span>
                <?php endfor; ?>
            </div>

            <div class="legende-calendrier">
                <span><i class="point point-vert"></i> Échéance à venir</span>
                <span><i class="point point-rouge"></i> En retard</span>
            </div>
            <?php if ($moisDemande !== $moisCourant): ?>
                <p class="petit" style="margin: 10px 0 0;"><a href="<?= BASE_URL ?>/dashboard.php">Revenir au mois en cours</a></p>
            <?php endif; ?>
        </section>

        <section class="carte">
            <h2>Tâches en retard</h2>
            <?php if (empty($tachesEnRetard)): ?>
                <p class="etat-vide">Aucune tâche en retard. Bravo !</p>
            <?php else: ?>
                <ul class="liste-simple">
                    <?php foreach ($retardAffichees as $t): ?>
                        <li>
                            <div>
                                <strong><?= e($t['titre']) ?></strong><br>
                                <span class="petit"><?= e($t['titre_projet']) ?> · <?= e($t['nom_assigne'] ?? 'Non attribuée') ?></span>
                            </div>
                            <span class="badge badge-retard"><?= e(formaterDate($t['date_limite'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($tachesEnRetard) > count($retardAffichees)): ?>
                    <p class="petit" style="margin-bottom: 0;">
                        + <?= e(count($tachesEnRetard) - count($retardAffichees)) ?> autre(s) :
                        <a href="<?= BASE_URL ?>/taches/index.php">voir toutes les tâches</a>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </aside>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
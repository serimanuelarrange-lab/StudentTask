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
$taches        = listerTachesUtilisateur($pdo, $idUtilisateur);
$projets       = listerProjetsUtilisateur($pdo, $idUtilisateur);

// Membres proposés dans le filtre : ceux à qui au moins une tâche est attribuée.
$membresFiltre = [];
foreach ($taches as $t) {
    if ($t['id_assigne'] !== null) {
        $membresFiltre[(int) $t['id_assigne']] = $t['nom_assigne'];
    }
}
asort($membresFiltre);

// Valeurs initiales des indicateurs (JavaScript les met à jour en direct).
$compte = ['a_faire' => 0, 'en_cours' => 0, 'termine' => 0, 'retard' => 0];
foreach ($taches as $t) {
    $compte[$t['statut']]++;
    if (estEnRetard($t['date_limite'], $t['statut'])) {
        $compte['retard']++;
    }
}

$titrePage  = 'Tâches';
$pageActive = 'taches';
require __DIR__ . '/../includes/header.php';
?>

<div class="entete-page">
    <div>
        <h1>Tâches</h1>
        <p class="sous-titre">Toutes les tâches de vos projets.</p>
    </div>
    <a class="bouton" href="<?= BASE_URL ?>/taches/creer.php">+ Nouvelle tâche</a>
</div>

<section class="carte">
    <div class="barre-filtres">
        <div class="champ">
            <label for="recherche">Recherche</label>
            <input type="search" id="recherche" placeholder="Titre, projet, personne..." autocomplete="off">
        </div>
        <div class="champ">
            <label for="filtre-projet">Projet</label>
            <select id="filtre-projet">
                <option value="">Tous</option>
                <?php foreach ($projets as $p): ?>
                    <option value="<?= e($p['id_projet']) ?>"><?= e($p['titre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="champ">
            <label for="filtre-membre">Membre</label>
            <select id="filtre-membre">
                <option value="">Tous</option>
                <option value="0">Non attribuée</option>
                <?php foreach ($membresFiltre as $idMembre => $nomMembre): ?>
                    <option value="<?= e($idMembre) ?>"><?= e($nomMembre) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="champ">
            <label for="filtre-statut">Statut</label>
            <select id="filtre-statut">
                <option value="">Tous</option>
                <?php foreach (STATUTS_TACHE as $s): ?>
                    <option value="<?= e($s) ?>"><?= e(libelleStatut($s)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="champ">
            <label for="filtre-priorite">Priorité</label>
            <select id="filtre-priorite">
                <option value="">Toutes</option>
                <?php foreach (PRIORITES_TACHE as $pr): ?>
                    <option value="<?= e($pr) ?>"><?= e(libellePriorite($pr)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="champ">
            <label for="tri">Trier par</label>
            <select id="tri">
                <option value="date">Date limite</option>
                <option value="priorite">Priorité</option>
            </select>
        </div>
    </div>

    <?php if (empty($taches)): ?>
        <p class="etat-vide">Aucune tâche pour le moment.</p>
    <?php else: ?>
        <div class="resume-filtres" aria-live="polite">
            <span><strong id="compteur-affichees"><?= e(count($taches)) ?></strong> / <?= e(count($taches)) ?> tâche(s) affichée(s)</span>
            <span class="badge badge-statut-a_faire">À faire : <span id="res-a_faire"><?= e($compte['a_faire']) ?></span></span>
            <span class="badge badge-statut-en_cours">En cours : <span id="res-en_cours"><?= e($compte['en_cours']) ?></span></span>
            <span class="badge badge-statut-termine">Terminées : <span id="res-termine"><?= e($compte['termine']) ?></span></span>
            <span class="badge badge-retard">En retard : <span id="res-retard"><?= e($compte['retard']) ?></span></span>
        </div>

        <div class="table-scroll">
            <table class="tableau">
                <thead>
                    <tr>
                        <th>Tâche</th><th>Projet</th><th>Attribuée à</th>
                        <th>Priorité</th><th>Date limite</th><th>Statut</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody id="corps-taches">
                    <?php foreach ($taches as $t): ?>
                        <?php
                        $retard     = estEnRetard($t['date_limite'], $t['statut']);
                        $estChef    = $t['role'] === 'chef_projet';
                        $peutStatut = $estChef || (int) $t['id_assigne'] === $idUtilisateur;
                        $texteRecherche = mb_strtolower($t['titre'] . ' ' . $t['titre_projet'] . ' ' . ($t['nom_assigne'] ?? ''));
                        ?>
                        <tr class="<?= $retard ? 'ligne-retard' : '' ?>"
                            data-id="<?= e($t['id_tache']) ?>"
                            data-projet="<?= e($t['id_projet']) ?>"
                            data-assigne="<?= e($t['id_assigne']) ?>"
                            data-statut="<?= e($t['statut']) ?>"
                            data-priorite="<?= e($t['priorite']) ?>"
                            data-date="<?= e($t['date_limite']) ?>"
                            data-retard="<?= $retard ? '1' : '0' ?>"
                            data-recherche="<?= e($texteRecherche) ?>">
                            <td><strong><?= e($t['titre']) ?></strong></td>
                            <td><?= e($t['titre_projet']) ?></td>
                            <td><?= e($t['nom_assigne'] ?? 'Non attribuée') ?></td>
                            <td><span class="badge badge-priorite-<?= e($t['priorite']) ?>"><?= e(libellePriorite($t['priorite'])) ?></span></td>
                            <td>
                                <?= e(formaterDate($t['date_limite'])) ?>
                                <?php if ($retard): ?><span class="badge badge-retard">En retard</span><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($peutStatut): ?>
                                    <form class="form-statut" method="post" action="<?= BASE_URL ?>/taches/statut.php">
                                        <?= champCsrf() ?>
                                        <input type="hidden" name="id_tache" value="<?= e($t['id_tache']) ?>">
                                        <select name="statut" aria-label="Statut de la tâche">
                                            <?php foreach (STATUTS_TACHE as $s): ?>
                                                <option value="<?= e($s) ?>" <?= $t['statut'] === $s ? 'selected' : '' ?>><?= e(libelleStatut($s)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="bouton bouton-secondaire bouton-petit" type="submit">OK</button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge badge-statut-<?= e($t['statut']) ?>"><?= e(libelleStatut($t['statut'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($estChef): ?>
                                    <div class="actions-tableau">
                                        <a href="<?= BASE_URL ?>/taches/modifier.php?id=<?= e($t['id_tache']) ?>">Modifier</a>
                                        <form class="form-inline" method="post" action="<?= BASE_URL ?>/taches/supprimer.php"
                                              onsubmit="return confirm('Supprimer définitivement cette tâche ?');">
                                            <?= champCsrf() ?>
                                            <input type="hidden" name="id_tache" value="<?= e($t['id_tache']) ?>">
                                            <button class="lien-danger" type="submit">Supprimer</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div id="etat-vide-filtre" class="etat-vide" hidden>
            Aucune tâche ne correspond à votre recherche.<br>
            <button type="button" id="reinitialiser-filtres" class="bouton-lien">Réinitialiser les filtres</button>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
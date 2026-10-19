<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projets.php';

exigerConnexion();

$pdo     = getPDO();
$id      = (int) ($_GET['id'] ?? 0);
$projet  = trouverProjetPourUtilisateur($pdo, $id, idUtilisateurConnecte());
$erreurs = [];
$message = ''; // message affiché à la place du formulaire si l'accès est refusé

if (!$projet) {
    http_response_code(404);
    $message = 'Projet introuvable.';
} elseif ($projet['role'] !== 'chef_projet') {
    http_response_code(403);
    $message = 'Seul le chef de projet peut modifier ce projet.';
} else {
    $valeurs = [
        'titre'       => $projet['titre'],
        'description' => (string) $projet['description'],
        'date_debut'  => $projet['date_debut'],
        'date_limite' => $projet['date_limite'],
    ];
    $archive = (int) $projet['archive'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifierCsrf()) {
            $erreurs[] = 'Votre session a expiré. Veuillez réessayer.';
        } else {
            [$erreurs, $valeurs] = validerProjet($_POST);
            $archive = isset($_POST['archive']) ? 1 : 0;

            if (empty($erreurs)) {
                try {
                    $maj = $pdo->prepare(
                        'UPDATE projets
                         SET titre = :titre, description = :description, date_debut = :debut,
                             date_limite = :limite, archive = :archive
                         WHERE id_projet = :id'
                    );
                    $maj->execute([
                        ':titre'       => $valeurs['titre'],
                        ':description' => $valeurs['description'] === '' ? null : $valeurs['description'],
                        ':debut'       => $valeurs['date_debut'],
                        ':limite'      => $valeurs['date_limite'],
                        ':archive'     => $archive,
                        ':id'          => $id,
                    ]);
                    definirFlash('succes', 'Projet mis à jour.');
                    rediriger('/projets/detail.php?id=' . $id);
                } catch (PDOException $e) {
                    error_log('StudentTask - modification de projet : ' . $e->getMessage());
                    $erreurs[] = 'Impossible de modifier le projet pour le moment. Veuillez réessayer.';
                }
            }
        }
    }
}

$titrePage  = 'Modifier le projet';
$pageActive = 'projets';
require __DIR__ . '/../includes/header.php';
?>

<?php if ($message !== ''): ?>
    <div class="carte"><p class="etat-vide"><?= e($message) ?></p></div>
    <p><a href="<?= BASE_URL ?>/projets/index.php">← Retour aux projets</a></p>
<?php else: ?>
    <div class="entete-page">
        <div>
            <h1>Modifier le projet</h1>
            <p class="sous-titre"><?= e($projet['titre']) ?></p>
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
        <form method="post" action="">
            <?= champCsrf() ?>
            <div class="champ">
                <label for="titre">Titre du projet *</label>
                <input type="text" id="titre" name="titre" required maxlength="150" value="<?= e($valeurs['titre']) ?>">
            </div>
            <div class="champ">
                <label for="description">Description</label>
                <textarea id="description" name="description" maxlength="1000"><?= e($valeurs['description']) ?></textarea>
            </div>
            <div class="ligne-champs">
                <div class="champ">
                    <label for="date_debut">Date de début *</label>
                    <input type="date" id="date_debut" name="date_debut" required value="<?= e($valeurs['date_debut']) ?>">
                </div>
                <div class="champ">
                    <label for="date_limite">Date limite *</label>
                    <input type="date" id="date_limite" name="date_limite" required value="<?= e($valeurs['date_limite']) ?>">
                </div>
            </div>
            <div class="champ case">
                <input type="checkbox" id="archive" name="archive" value="1" <?= $archive ? 'checked' : '' ?>>
                <label for="archive" style="margin: 0;">Archiver ce projet (il restera consultable, mais sans nouvelles tâches)</label>
            </div>
            <div class="actions-formulaire">
                <button class="bouton" type="submit">Enregistrer les modifications</button>
                <a class="bouton bouton-secondaire" href="<?= BASE_URL ?>/projets/detail.php?id=<?= e($id) ?>">Annuler</a>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
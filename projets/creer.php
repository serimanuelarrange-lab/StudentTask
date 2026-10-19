<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/projets.php';

exigerConnexion();

$erreurs = [];
$valeurs = ['titre' => '', 'description' => '', 'date_debut' => '', 'date_limite' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreurs[] = 'Votre session a expiré. Veuillez réessayer.';
    } else {
        [$erreurs, $valeurs] = validerProjet($_POST);

        if (empty($erreurs)) {
            $pdo = getPDO();
            try {
                // Transaction : le projet ET son chef sont créés ensemble, ou rien du tout.
                $pdo->beginTransaction();

                $ajout = $pdo->prepare(
                    'INSERT INTO projets (titre, description, date_debut, date_limite, id_createur)
                     VALUES (:titre, :description, :debut, :limite, :createur)'
                );
                $ajout->execute([
                    ':titre'       => $valeurs['titre'],
                    ':description' => $valeurs['description'] === '' ? null : $valeurs['description'],
                    ':debut'       => $valeurs['date_debut'],
                    ':limite'      => $valeurs['date_limite'],
                    ':createur'    => idUtilisateurConnecte(),
                ]);
                $idProjet = (int) $pdo->lastInsertId();

                $membre = $pdo->prepare(
                    'INSERT INTO membres_projet (id_projet, id_utilisateur, role)
                     VALUES (:projet, :utilisateur, \'chef_projet\')'
                );
                $membre->execute([':projet' => $idProjet, ':utilisateur' => idUtilisateurConnecte()]);

                $pdo->commit();

                definirFlash('succes', 'Projet créé avec succès.');
                rediriger('/projets/detail.php?id=' . $idProjet);
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('StudentTask - création de projet : ' . $e->getMessage());
                $erreurs[] = 'Impossible de créer le projet pour le moment. Veuillez réessayer.';
            }
        }
    }
}

$titrePage  = 'Nouveau projet';
$pageActive = 'projets';
require __DIR__ . '/../includes/header.php';
?>

<div class="entete-page">
    <div>
        <h1>Nouveau projet</h1>
        <p class="sous-titre">Renseignez les informations de base. Vous deviendrez chef de ce projet.</p>
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
        <div class="actions-formulaire">
            <button class="bouton" type="submit">Créer le projet</button>
            <a class="bouton bouton-secondaire" href="<?= BASE_URL ?>/projets/index.php">Annuler</a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/reinitialisation.php';

if (utilisateurConnecte()) {
    rediriger('/dashboard.php');
}

$erreurs = [];
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreurs[] = 'Votre session a expiré. Veuillez réessayer.';
    } else {
        $email = mb_strtolower(trim($_POST['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $erreurs[] = "L'adresse e-mail n'est pas valide.";
        } else {
            try {
                [$envoye, $raison] = demanderReinitialisation(getPDO(), $email);

                // Message identique que le compte existe ou non.
                $message = "Si un compte existe avec cette adresse, un code à 6 chiffres vient d'être envoyé. "
                         . 'Il est valable ' . DUREE_CODE_MINUTES . ' minutes.';
                if (APP_DEBUG && $raison !== '') {
                    $message .= ' (Mode développement : ' . $raison . ')';
                }

                $_SESSION['reinit_email'] = $email;
                definirFlash('succes', $message);
                rediriger('/reinitialiser.php');
            } catch (PDOException $e) {
                error_log('StudentTask - demande de réinitialisation : ' . $e->getMessage());
                $erreurs[] = 'Impossible de traiter la demande pour le moment. Veuillez réessayer.';
                if (APP_DEBUG) {
                    $erreurs[] = 'Mode développement : ' . $e->getMessage();
                }
            }
        }
    }
}

$titrePage = 'Mot de passe oublié';
require __DIR__ . '/includes/header_auth.php';
?>

<div class="carte carte-auth">
    <h1>Mot de passe oublié</h1>
    <p class="sous-titre">Saisissez votre adresse e-mail : nous vous envoyons un code à 6 chiffres pour choisir un nouveau mot de passe.</p>

    <?php if (!empty($erreurs)): ?>
        <div class="alerte alerte-erreur">
            <ul>
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= e($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?= champCsrf() ?>
        <div class="champ">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" required maxlength="150" autocomplete="email" value="<?= e($email) ?>">
        </div>
        <button class="bouton bouton-large" type="submit">Recevoir le code</button>
    </form>

    <p class="lien-bas"><a href="<?= BASE_URL ?>/reinitialiser.php">J'ai déjà un code</a> · <a href="<?= BASE_URL ?>/login.php">Retour à la connexion</a></p>
</div>

<?php require __DIR__ . '/includes/footer_auth.php'; ?>
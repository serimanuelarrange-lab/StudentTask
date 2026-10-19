<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

if (utilisateurConnecte()) {
    rediriger('/dashboard.php');
}

$erreur = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreur = 'Votre session a expiré. Veuillez réessayer.';
    } else {
        $email      = mb_strtolower(trim($_POST['email'] ?? ''));
        $motDePasse = $_POST['mot_de_passe'] ?? '';

        if ($email === '' || $motDePasse === '') {
            $erreur = 'Veuillez renseigner votre e-mail et votre mot de passe.';
        } else {
            $requete = getPDO()->prepare(
                'SELECT id_utilisateur, nom, mot_de_passe_hash FROM utilisateurs WHERE email = :email'
            );
            $requete->execute([':email' => $email]);
            $utilisateur = $requete->fetch();

            if ($utilisateur && password_verify($motDePasse, $utilisateur['mot_de_passe_hash'])) {
                connecterUtilisateur($utilisateur);
                rediriger('/dashboard.php');
            } else {
                // Message volontairement identique dans les deux cas (e-mail inconnu / mauvais mot de passe).
                $erreur = 'E-mail ou mot de passe incorrect.';
            }
        }
    }
}

$titrePage = 'Connexion';
require __DIR__ . '/includes/header_auth.php';
?>

<div class="carte carte-auth">
    <h1>Connexion</h1>
    <p class="sous-titre">Accédez à vos projets et à vos tâches.</p>

    <?php afficherFlash(); ?>

    <?php if ($erreur !== ''): ?>
        <div class="alerte alerte-erreur"><?= e($erreur) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <?= champCsrf() ?>
        <div class="champ">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" required autocomplete="email" value="<?= e($email) ?>">
        </div>
        <div class="champ">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required autocomplete="current-password">
        </div>
        <button class="bouton bouton-large" type="submit">Se connecter</button>
    </form>

    <p class="lien-bas"><a href="<?= BASE_URL ?>/mot-de-passe-oublie.php">Mot de passe oublié ?</a></p>
    <p class="lien-bas">Pas encore de compte ? <a href="<?= BASE_URL ?>/inscription.php">Créer un compte</a></p>
</div>

<?php require __DIR__ . '/includes/footer_auth.php'; ?>
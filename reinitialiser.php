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
$email   = $_SESSION['reinit_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreurs[] = 'Votre session a expiré. Veuillez réessayer.';
    } else {
        $email        = mb_strtolower(trim($_POST['email'] ?? ''));
        $code         = preg_replace('/\s+/', '', $_POST['code'] ?? '');
        $motDePasse   = $_POST['mot_de_passe'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $erreurs[] = "L'adresse e-mail n'est pas valide.";
        }
        if (!preg_match('/^\d{6}$/', $code)) {
            $erreurs[] = 'Le code doit contenir exactement 6 chiffres.';
        }
        if (strlen($motDePasse) < 8) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if ($motDePasse !== $confirmation) {
            $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        }

        if (empty($erreurs)) {
            try {
                $pdo      = getPDO();
                $resultat = verifierCodeReinitialisation($pdo, $email, $code);

                if (!$resultat) {
                    // Même message pour : code faux, expiré, ou adresse inconnue.
                    $erreurs[] = 'Code invalide ou expiré. Vérifiez le code ou demandez-en un nouveau.';
                } else {
                    changerMotDePasse($pdo, $resultat['id_utilisateur'], $motDePasse);
                    unset($_SESSION['reinit_email']);
                    definirFlash('succes', 'Mot de passe modifié. Vous pouvez maintenant vous connecter.');
                    rediriger('/login.php');
                }
            } catch (PDOException $e) {
                error_log('StudentTask - réinitialisation : ' . $e->getMessage());
                $erreurs[] = 'Impossible de modifier le mot de passe pour le moment. Veuillez réessayer.';
            }
        }
    }
}

$titrePage = 'Nouveau mot de passe';
require __DIR__ . '/includes/header_auth.php';
?>

<div class="carte carte-auth">
    <h1>Nouveau mot de passe</h1>
    <p class="sous-titre">Saisissez le code reçu par e-mail, puis votre nouveau mot de passe.</p>

    <?php afficherFlash(); ?>

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
        <div class="champ">
            <label for="code">Code à 6 chiffres</label>
            <input type="text" id="code" name="code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="123456">
        </div>
        <div class="champ">
            <label for="mot_de_passe">Nouveau mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="8" autocomplete="new-password">
            <p class="aide">8 caractères minimum.</p>
        </div>
        <div class="champ">
            <label for="confirmation">Confirmer le mot de passe</label>
            <input type="password" id="confirmation" name="confirmation" required minlength="8" autocomplete="new-password">
        </div>
        <button class="bouton bouton-large" type="submit">Modifier mon mot de passe</button>
    </form>

    <p class="lien-bas"><a href="<?= BASE_URL ?>/mot-de-passe-oublie.php">Demander un nouveau code</a> · <a href="<?= BASE_URL ?>/login.php">Retour à la connexion</a></p>
</div>

<?php require __DIR__ . '/includes/footer_auth.php'; ?>
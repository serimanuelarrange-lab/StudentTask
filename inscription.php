<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/fonctions.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/email.php';

if (utilisateurConnecte()) {
    rediriger('/dashboard.php');
}

$erreurs = [];
$nom     = '';
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierCsrf()) {
        $erreurs[] = 'Votre session a expiré. Veuillez réessayer.';
    } else {
        $nom          = trim($_POST['nom'] ?? '');
        $email        = mb_strtolower(trim($_POST['email'] ?? ''));
        $motDePasse   = $_POST['mot_de_passe'] ?? '';
        $confirmation = $_POST['confirmation'] ?? '';

        if ($nom === '' || mb_strlen($nom) > 100) {
            $erreurs[] = 'Le nom est obligatoire (100 caractères maximum).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $erreurs[] = "L'adresse e-mail n'est pas valide.";
        }
        if (strlen($motDePasse) < 8) {
            $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        if ($motDePasse !== $confirmation) {
            $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
        }

        if (empty($erreurs)) {
            $pdo   = getPDO();
            $verif = $pdo->prepare('SELECT 1 FROM utilisateurs WHERE email = :email');
            $verif->execute([':email' => $email]);

            if ($verif->fetch()) {
                $erreurs[] = 'Un compte existe déjà avec cette adresse e-mail.';
            } else {
                try {
                    $ajout = $pdo->prepare(
                        'INSERT INTO utilisateurs (nom, email, mot_de_passe_hash) VALUES (:nom, :email, :hash)'
                    );
                    $ajout->execute([
                        ':nom'   => $nom,
                        ':email' => $email,
                        ':hash'  => password_hash($motDePasse, PASSWORD_DEFAULT),
                    ]);

                    // Le compte est créé : on tente d'envoyer l'e-mail de bienvenue.
                    // Un échec d'envoi n'annule jamais l'inscription.
                    [$envoye, $raison] = envoyerEmailBienvenue($nom, $email);

                    $message = 'Compte créé avec succès. Vous pouvez maintenant vous connecter.';
                    if ($envoye) {
                        $message .= ' Un e-mail de bienvenue vous a été envoyé.';
                    } elseif (APP_DEBUG && $raison !== '') {
                        $message .= ' (E-mail non envoyé : ' . $raison . ')';
                    }
                    definirFlash('succes', $message);
                    rediriger('/login.php');
                } catch (PDOException $e) {
                    error_log('StudentTask - inscription : ' . $e->getMessage());
                    $erreurs[] = 'Impossible de créer le compte pour le moment. Veuillez réessayer.';
                }
            }
        }
    }
}

$titrePage = 'Inscription';
require __DIR__ . '/includes/header_auth.php';
?>

<div class="carte carte-auth">
    <h1>Créer un compte</h1>
    <p class="sous-titre">Rejoignez StudentTask pour organiser vos travaux de groupe.</p>

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
            <label for="nom">Nom complet</label>
            <input type="text" id="nom" name="nom" required maxlength="100" autocomplete="name" value="<?= e($nom) ?>">
        </div>
        <div class="champ">
            <label for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" required maxlength="150" autocomplete="email" value="<?= e($email) ?>">
        </div>
        <div class="champ">
            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="8" autocomplete="new-password">
            <p class="aide">8 caractères minimum.</p>
        </div>
        <div class="champ">
            <label for="confirmation">Confirmer le mot de passe</label>
            <input type="password" id="confirmation" name="confirmation" required minlength="8" autocomplete="new-password">
        </div>
        <button class="bouton bouton-large" type="submit">Créer mon compte</button>
    </form>

    <p class="lien-bas">Déjà inscrit ? <a href="<?= BASE_URL ?>/login.php">Se connecter</a></p>
</div>

<?php require __DIR__ . '/includes/footer_auth.php'; ?>
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fonctions.php';

// Démarre la session une seule fois.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Redirige vers une page de l'application, puis arrête le script.
function rediriger($chemin)
{
    header('Location: ' . BASE_URL . $chemin);
    exit;
}

function utilisateurConnecte()
{
    return !empty($_SESSION['id_utilisateur']);
}

function idUtilisateurConnecte()
{
    return (int) ($_SESSION['id_utilisateur'] ?? 0);
}

// À appeler en haut des pages réservées aux utilisateurs connectés.
function exigerConnexion()
{
    if (!utilisateurConnecte()) {
        rediriger('/login.php');
    }
}

function connecterUtilisateur($utilisateur)
{
    session_regenerate_id(true); // protège contre le vol de session
    $_SESSION['id_utilisateur'] = (int) $utilisateur['id_utilisateur'];
    $_SESSION['nom']            = $utilisateur['nom'];
}

function deconnecterUtilisateur()
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// ---------- Protection CSRF ----------
function tokenCsrf()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

// Champ caché à placer dans chaque formulaire.
function champCsrf()
{
    return '<input type="hidden" name="csrf_token" value="' . e(tokenCsrf()) . '">';
}

function verifierCsrf()
{
    $envoye = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $envoye);
}

// ---------- Messages temporaires (affichés une seule fois) ----------
function definirFlash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function afficherFlash()
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        echo '<div class="alerte alerte-' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
    }
}
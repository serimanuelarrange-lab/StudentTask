<?php
require_once __DIR__ . '/config.php';

// Envoie un e-mail via l'API REST d'EmailJS avec le modèle indiqué.
// Retourne [true, ''] si l'e-mail est parti, [false, 'raison'] sinon.
function envoyerViaEmailJS($idModele, array $parametres)
{
    if (empty($idModele)) {
        return [false, "Modèle EmailJS non spécifié."];
    }
    
    if (!function_exists('curl_init')) {
        return [false, "L'extension PHP cURL n'est pas activée."];
    }

    $donnees = [
        'service_id'      => 'service_fv6dwgh',
        'template_id'     => $idModele,
        'user_id'         => 'rWrvx14c7Z4F1u_py',
        'accessToken'     => '_GOGi-tpOr9uuMbxoAJD3',
        'template_params' => $parametres,
    ];

    $ch = curl_init('https://api.emailjs.com/api/v1.0/email/send');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($donnees),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 8,
        // DÉSACTIVATION SSL EN LOCAL (Évite le blocage SSL sous XAMPP)
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);

    $reponse   = curl_exec($ch);
    $codeHttp  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreurCurl = curl_error($ch);
    curl_close($ch);

    if ($reponse === false) {
        error_log('StudentTask - EmailJS injoignable : ' . $erreurCurl);
        return [false, 'Connexion à EmailJS impossible : ' . $erreurCurl];
    }
    if ($codeHttp !== 200) {
        error_log('StudentTask - EmailJS a répondu ' . $codeHttp . ' : ' . $reponse);
        return [false, 'EmailJS a répondu (' . $codeHttp . ') : ' . $reponse];
    }

    return [true, ''];
}

function nettoyerNomPourEmail($nom)
{
    $propre = trim(preg_replace('/[^\p{L}\p{N}\s\'’\-\.]/u', '', $nom));
    $propre = mb_substr($propre, 0, 60);
    return $propre === '' ? 'étudiant(e)' : $propre;
}

// E-mail de bienvenue
function envoyerEmailBienvenue($nom, $email)
{
    return envoyerViaEmailJS('template_yu83zjg', [
        'to_name'  => nettoyerNomPourEmail($nom),
        'to_email' => $email,
        'site_url' => SITE_URL,
    ]);
}

// E-mail contant le code à 6 chiffres
function envoyerEmailCodeReinitialisation($nom, $email, $code, $dureeMinutes)
{
    $idModele = 'template_0mdkmqs';

    [$succes, $erreur] = envoyerViaEmailJS($idModele, [
        'to_name'  => nettoyerNomPourEmail($nom),
        'to_email' => $email,
        'code'     => $code,
        'duree'    => (string) $dureeMinutes,
        'site_url' => SITE_URL,
    ]);

    // Si l'envoi a échoué et qu'on est en mode développement, on fournit le code dans le message pour débloquer les tests
    if (!$succes && defined('APP_DEBUG') && APP_DEBUG) {
        return [true, "Code de test (EmailJS échoué) : <strong>{$code}</strong> — Raison : {$erreur}"];
    }

    return [$succes, $erreur];
}
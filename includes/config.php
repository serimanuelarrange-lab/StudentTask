<?php
// Réglages généraux de StudentTask.

// Chemin de l'application dans le navigateur.
define('BASE_URL', '/studenttask');

// Adresse complète du site, utilisée dans l'e-mail de bienvenue.
define('SITE_URL', 'http://localhost/studenttask');

// Mode développement : affiche des détails techniques en cas d'erreur.
// À passer à false avant la présentation finale.
define('APP_DEBUG', true);

// Connexion à la base de données (Supabase PostgreSQL)
define('DB_HOST', 'db.tzccubchxzayjgjjvowb.supabase.co');
define('DB_PORT', '5432');
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres');
define('DB_PASS', 'Manuel1020@@@DieuDIEU%%%'); // Remplacez par votre vrai mot de passe Supabase
define('DB_CHARSET', 'utf8');

// E-mail de bienvenue via EmailJS (https://www.emailjs.com).

define('EMAILJS_SERVICE_ID', 'service_fv6dwgh');
define('EMAILJS_TEMPLATE_WELCOME', 'template_yu83zjg');
define('EMAILJS_TEMPLATE_RESET', 'template_0mdkmqs');
define('EMAILJS_PUBLIC_KEY', 'rWrvx14c7Z4F1u_py');
define('EMAILJS_PRIVATE_KEY', '_GOGi-tpOr9uuMbxoAJD3');

// Fuseau horaire unique pour toute l'application (important pour les dates limites).
date_default_timezone_set('Africa/Abidjan');
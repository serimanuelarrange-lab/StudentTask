<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/fonctions.php';

$titrePage = $titrePage ?? 'StudentTask';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titrePage) ?> — StudentTask</title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/logo-icone.svg">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="page-auth">
<main class="boite-auth">
    <img class="logo-auth-img" src="<?= BASE_URL ?>/assets/images/logo.svg" alt="StudentTask">
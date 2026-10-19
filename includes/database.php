<?php
require_once __DIR__ . '/config.php';

// Retourne la connexion PDO (créée une seule fois, puis réutilisée).
function getPDO()
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('StudentTask - connexion à la base impossible : ' . $e->getMessage());
            http_response_code(500);
            echo '<p>Le service est momentanément indisponible. Veuillez réessayer plus tard.</p>';
            if (APP_DEBUG) {
                echo '<p><small>Détail (mode développement) : ' . htmlspecialchars($e->getMessage()) . '</small></p>';
            }
            exit;
        }
    }

    return $pdo;
}
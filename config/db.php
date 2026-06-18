<?php
/**
 * Database configuration (PDO + prepared statements).
 * Edit the constants below to match your server.
 */
declare(strict_types=1);

define('DB_HOST', 'sql210.infinityfree.com');
define('DB_NAME', 'if0_42182947_pg_rent_db');
define('DB_USER', 'if0_42182947');
define('DB_PASS', 'H9pn0oC05k3RC');
define('DB_CHARSET', 'utf8mb4');

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $opts = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
    }
    return $pdo;
}

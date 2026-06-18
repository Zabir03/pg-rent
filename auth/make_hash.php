<?php
/**
 * Run from CLI:  php auth/make_hash.php "YourPassword"
 * Prints a bcrypt hash you can paste into the DB.
 *
 * Also: visit /auth/seed_demo.php once in the browser to create a demo
 * PG owner login (owner@pgrent.local / Owner@123).
 */
if (PHP_SAPI === 'cli') {
    $pw = $argv[1] ?? 'Admin@123';
    echo password_hash($pw, PASSWORD_DEFAULT), PHP_EOL;
    exit;
}

// Browser seed mode
require_once __DIR__ . '/../config/db.php';

$email = 'owner@pgrent.local';
$pdo = db();
$exists = $pdo->prepare('SELECT id FROM users WHERE email=?');
$exists->execute([$email]);
if ($exists->fetch()) {
    exit('Demo owner already exists.');
}
$hash = password_hash('Owner@123', PASSWORD_DEFAULT);
$pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)')
    ->execute(['Demo Owner', $email, $hash, 'pg_owner']);
$uid = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO pg_owners (user_id,pg_name,city) VALUES (?,?,?)')
    ->execute([$uid, 'Sunrise PG', 'Pune']);

echo 'Demo owner created: owner@pgrent.local / Owner@123';

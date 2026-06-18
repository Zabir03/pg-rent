<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('pg_owner');
$oid = current_owner_id();
$id = (int)($_GET['id'] ?? 0);
db()->prepare("DELETE FROM students WHERE id=? AND owner_id=?")->execute([$id, $oid]);
log_activity("delete student #$id");
flash('Student deleted.');
header('Location: /students/index.php');
exit;

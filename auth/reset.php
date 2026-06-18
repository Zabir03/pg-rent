<?php
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$error = $done = '';

$stmt = db()->prepare(
    'SELECT id FROM users WHERE reset_token=? AND reset_expires > NOW() LIMIT 1'
);
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    $error = 'This reset link is invalid or has expired.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $p1 = $_POST['password'] ?? '';
    $p2 = $_POST['password2'] ?? '';
    if (strlen($p1) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($p1 !== $p2) {
        $error = 'Passwords do not match.';
    } else {
        $hash = password_hash($p1, PASSWORD_DEFAULT);
        $up = db()->prepare(
            'UPDATE users SET password_hash=?, reset_token=NULL, reset_expires=NULL WHERE id=?'
        );
        $up->execute([$hash, $user['id']]);
        $done = 'Password updated. You can now log in.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Set New Password</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-body">
<div class="auth-card">
  <h1 class="auth-logo">New Password</h1>
  <?php if ($error): ?><div class="toast toast-error"><?= e($error) ?></div><?php endif; ?>
  <?php if ($done): ?>
    <div class="toast toast-success"><?= e($done) ?></div>
    <a class="btn btn-primary btn-block" href="/auth/login.php">Go to Login</a>
  <?php elseif (!$error || $user): ?>
    <form method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <label>New Password <input type="password" name="password" required></label>
      <label>Confirm Password <input type="password" name="password2" required></label>
      <button class="btn btn-primary btn-block">Update Password</button>
    </form>
  <?php endif; ?>
</div>
</body>
</html>

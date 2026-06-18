<?php
require_once __DIR__ . '/../includes/functions.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($row = $stmt->fetch()) {
        $token = bin2hex(random_bytes(32));
        $exp = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');
        $up = db()->prepare('UPDATE users SET reset_token=?, reset_expires=? WHERE id=?');
        $up->execute([$token, $exp, $row['id']]);
        // In production: email this link. For now we surface it (dev convenience).
        $link = '/auth/reset.php?token=' . $token;
        $msg = 'Reset link generated (dev mode): <a href="' . e($link) . '">' . e($link) . '</a>';
    } else {
        // Do not reveal whether the email exists (security).
        $msg = 'If that email exists, a reset link has been generated.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot Password</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-body">
<div class="auth-card">
  <h1 class="auth-logo">Reset Password</h1>
  <?php if ($msg): ?><div class="toast toast-success"><?= $msg ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>Account Email <input type="email" name="email" required></label>
    <button class="btn btn-primary btn-block">Send Reset Link</button>
  </form>
  <a class="auth-link" href="/auth/login.php">Back to login</a>
</div>
</body>
</html>

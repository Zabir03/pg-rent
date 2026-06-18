<?php
require_once __DIR__ . '/../includes/otp.php';

if (current_user()) {
    header('Location: /dashboard/index.php');
    exit;
}

$error = '';
$stage = 'password';            // 'password' -> 'otp'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? 'password';

    // ---- STEP 1: verify password, then email an OTP ----
    if ($action === 'password') {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';

        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active' &&
            password_verify($pass, $user['password_hash'])) {
            $_SESSION['pending_login'] = (int)$user['id'];
            $_SESSION['pending_email'] = $user['email'];
            if (otp_send($user['email'], 'login')) {
                $stage = 'otp';
            } else {
                $error = 'Could not send the verification email. Please try again shortly.';
            }
        } else {
            $error = 'Invalid credentials or inactive account.';
        }
    }

    // ---- STEP 2: verify the emailed OTP, then log in ----
    elseif ($action === 'otp') {
        $email = $_SESSION['pending_email'] ?? '';
        $code  = trim($_POST['otp'] ?? '');
        if (!$email || empty($_SESSION['pending_login'])) {
            $error = 'Session expired. Please log in again.';
        } elseif (otp_verify($email, 'login', $code)) {
            $uid = (int)$_SESSION['pending_login'];
            $row = db()->prepare('SELECT id,name,role FROM users WHERE id=?');
            $row->execute([$uid]); $user = $row->fetch();
            unset($_SESSION['pending_login'], $_SESSION['pending_email']);
            session_regenerate_id(true);
            $_SESSION['user'] = ['id'=>(int)$user['id'],'name'=>$user['name'],'role'=>$user['role']];
            db()->prepare('UPDATE users SET email_verified=1 WHERE id=?')->execute([$user['id']]);
            log_activity('login (otp verified)');
            header('Location: /dashboard/index.php'); exit;
        } else {
            $error = 'Incorrect or expired code. Please try again.';
            $stage = 'otp';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — PG Rent Manager</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-body">
<div class="auth-card">
  <h1 class="auth-logo">PG&nbsp;Rent <span>Manager</span></h1>
  <?php if ($error): ?><div class="toast toast-error"><?= e($error) ?></div><?php endif; ?>
  <?php if (isset($_GET['timeout'])): ?><div class="toast toast-warn">Session timed out. Please log in again.</div><?php endif; ?>

  <?php if ($stage === 'password'): ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <label>Email <input type="email" name="email" required autofocus></label>
      <label>Password <input type="password" name="password" required></label>
      <button type="submit" class="btn btn-primary btn-block">Continue</button>
    </form>
    <a class="auth-link" href="/auth/forgot.php">Forgot password?</a>
  <?php else: ?>
    <p style="color:var(--slate);font-size:.9rem">
      We emailed a 6-digit code to <strong><?= e($_SESSION['pending_email'] ?? '') ?></strong>.
      Enter it below to finish signing in.
    </p>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="action" value="otp">
      <label>Verification Code
        <input name="otp" required autofocus inputmode="numeric" pattern="[0-9]{6}"
               maxlength="6" placeholder="######" style="letter-spacing:.3em;text-align:center;font-size:1.2rem">
      </label>
      <button type="submit" class="btn btn-primary btn-block">Verify &amp; Sign In</button>
    </form>
    <a class="auth-link" href="/auth/login.php">← Start over</a>
  <?php endif; ?>
</div>
</body>
</html>

<?php
$page_title = 'Add PG Owner';
require_once __DIR__ . '/../includes/otp.php';
require_once __DIR__ . '/../includes/header.php';
require_role('super_admin');

$error = '';
$old = ['name'=>'','email'=>'','phone'=>'','pg_name'=>'','city'=>'','address'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = fn($k) => trim($_POST[$k] ?? '');
    $old = [
      'name'=>$f('name'),'email'=>$f('email'),'phone'=>$f('phone'),
      'pg_name'=>$f('pg_name'),'city'=>$f('city'),'address'=>$f('address'),
    ];
    $pass  = $_POST['password'] ?? '';

    // validation
    if ($old['name'] === '' || $old['email'] === '' || $old['pg_name'] === '') {
        $error = 'Name, email and PG name are required.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($pass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        // check email not taken
        $chk = db()->prepare("SELECT id FROM users WHERE email=?");
        $chk->execute([$old['email']]);
        if ($chk->fetch()) {
            $error = 'That email is already registered.';
        } else {
            try {
                db()->beginTransaction();
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                db()->prepare("INSERT INTO users (name,email,phone,password_hash,role,status,email_verified)
                  VALUES (?,?,?,?, 'pg_owner', 'active', 0)")
                  ->execute([$old['name'], $old['email'], $old['phone'], $hash]);
                $uid = (int)db()->lastInsertId();
                db()->prepare("INSERT INTO pg_owners (user_id,pg_name,address,city,is_active)
                  VALUES (?,?,?,?,1)")
                  ->execute([$uid, $old['pg_name'], $old['address'], $old['city']]);
                db()->commit();
                log_activity("add pg owner: {$old['email']}");
                notify($uid, 'Welcome to PG Rent Manager',
                       'Your account has been created. Update your PG details in Settings.', 'info');
                // send a verification code to the new owner's email
                $sent = otp_send($old['email'], 'signup');
                flash($sent
                    ? 'PG owner created. A verification code was emailed to them.'
                    : 'PG owner created, but the verification email could not be sent (check mail settings).',
                    $sent ? 'success' : 'warn');
                header('Location: /dashboard/owners.php'); exit;
            } catch (Throwable $ex) {
                if (db()->inTransaction()) db()->rollBack();
                $error = 'Could not create the owner. Please try again.';
            }
        }
    }
}
?>
<p><a href="/dashboard/owners.php">← Back to PG Owners</a></p>
<div class="panel"><div class="panel-head"><h3>Add New PG Owner</h3></div><div class="panel-body">
<?php if ($error): ?><div class="toast toast-error"><?= e($error) ?></div><?php endif; ?>
<form method="post" autocomplete="off">
  <?= csrf_field() ?>
  <h4 style="color:var(--slate);margin:0 0 10px">Login Details</h4>
  <div class="form-grid">
    <label>Owner Name <input name="name" required value="<?= e($old['name']) ?>"></label>
    <label>Email <input type="email" name="email" required value="<?= e($old['email']) ?>"></label>
    <label>Phone <input name="phone" value="<?= e($old['phone']) ?>"></label>
    <label>Password <input type="password" name="password" required placeholder="min 8 characters"></label>
  </div>
  <h4 style="color:var(--slate);margin:18px 0 10px">PG Details</h4>
  <div class="form-grid">
    <label>PG Name <input name="pg_name" required value="<?= e($old['pg_name']) ?>"></label>
    <label>City <input name="city" value="<?= e($old['city']) ?>"></label>
    <label class="full">Address <textarea name="address" rows="2"><?= e($old['address']) ?></textarea></label>
  </div>
  <div style="margin-top:14px;display:flex;gap:10px">
    <button class="btn btn-primary">Create Owner</button>
    <a class="btn btn-ghost" href="/dashboard/owners.php">Cancel</a>
  </div>
</form>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

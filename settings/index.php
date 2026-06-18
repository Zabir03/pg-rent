<?php
$page_title = 'Settings';
require_once __DIR__ . '/../includes/header.php';
require_role('pg_owner');
$u = current_user();
$oid = current_owner_id();

// load current pg info
$st = db()->prepare("SELECT * FROM pg_owners WHERE id=?");
$st->execute([$oid]); $pg = $st->fetch();

function save_logo(): ?string {
    if (empty($_FILES['logo']['name']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) return null;
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) return null;
    $name = 'logo_' . bin2hex(random_bytes(6)) . '.' . $ext;
    move_uploaded_file($_FILES['logo']['tmp_name'], "$dir/$name");
    return 'uploads/' . $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sec = $_POST['section'] ?? '';

    if ($sec === 'pg') {
        $f = fn($k) => trim($_POST[$k] ?? '');
        $logo = save_logo() ?? ($pg['logo'] ?: null);
        db()->prepare("UPDATE pg_owners SET pg_name=?, address=?, city=?, logo=? WHERE id=?")
          ->execute([$f('pg_name'), $f('address'), $f('city'), $logo, $oid]);
        log_activity('update pg settings');
        flash('PG information updated.');
        header('Location: /settings/index.php'); exit;
    }

    if ($sec === 'account') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        db()->prepare("UPDATE users SET name=?, phone=? WHERE id=?")->execute([$name, $phone, $u['id']]);
        $_SESSION['user']['name'] = $name; // reflect immediately
        log_activity('update account');
        flash('Account details updated.');
        header('Location: /settings/index.php'); exit;
    }

    if ($sec === 'password') {
        $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? ''; $conf = $_POST['confirm'] ?? '';
        $row = db()->prepare("SELECT password_hash FROM users WHERE id=?");
        $row->execute([$u['id']]); $hash = $row->fetch()['password_hash'];
        if (!password_verify($cur, $hash)) {
            flash('Current password is incorrect.', 'error');
        } elseif (strlen($new) < 8) {
            flash('New password must be at least 8 characters.', 'error');
        } elseif ($new !== $conf) {
            flash('New passwords do not match.', 'error');
        } else {
            db()->prepare("UPDATE users SET password_hash=? WHERE id=?")
              ->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            log_activity('change password');
            flash('Password changed successfully.');
        }
        header('Location: /settings/index.php'); exit;
    }
}

// reload account row for display
$acc = db()->prepare("SELECT name, email, phone FROM users WHERE id=?");
$acc->execute([$u['id']]); $account = $acc->fetch();
?>
<div class="panel"><div class="panel-head"><h3>PG Information</h3></div><div class="panel-body">
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="section" value="pg">
  <?php if (!empty($pg['logo'])): ?>
    <div style="margin-bottom:14px">
      <img src="/<?= e($pg['logo']) ?>" alt="PG logo" style="height:64px;border-radius:8px;border:1px solid var(--line)">
    </div>
  <?php endif; ?>
  <div class="form-grid">
    <label>PG Name <input name="pg_name" required value="<?= e($pg['pg_name']) ?>"></label>
    <label>City <input name="city" value="<?= e($pg['city']) ?>"></label>
    <label class="full">Address <textarea name="address" rows="2"><?= e($pg['address']) ?></textarea></label>
    <label>Logo <input type="file" name="logo" accept="image/*"></label>
  </div>
  <div style="margin-top:12px"><button class="btn btn-primary">Save PG Info</button></div>
</form>
</div></div>

<div class="panel"><div class="panel-head"><h3>Account Details</h3></div><div class="panel-body">
<form method="post">
  <?= csrf_field() ?><input type="hidden" name="section" value="account">
  <div class="form-grid">
    <label>Your Name <input name="name" required value="<?= e($account['name']) ?>"></label>
    <label>Phone <input name="phone" value="<?= e($account['phone']) ?>"></label>
    <label>Email <input value="<?= e($account['email']) ?>" disabled style="opacity:.6"></label>
  </div>
  <p style="color:var(--muted);font-size:.82rem;margin:6px 0 0">Email is your login and can't be changed here.</p>
  <div style="margin-top:12px"><button class="btn btn-primary">Save Account</button></div>
</form>
</div></div>

<div class="panel"><div class="panel-head"><h3>Change Password</h3></div><div class="panel-body">
<form method="post" autocomplete="off">
  <?= csrf_field() ?><input type="hidden" name="section" value="password">
  <div class="form-grid">
    <label>Current Password <input type="password" name="current" required></label>
    <label></label>
    <label>New Password <input type="password" name="new" required></label>
    <label>Confirm New Password <input type="password" name="confirm" required></label>
  </div>
  <div style="margin-top:12px"><button class="btn btn-primary">Update Password</button></div>
</form>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

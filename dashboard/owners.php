<?php
$page_title = 'PG Owners';
require_once __DIR__ . '/../includes/header.php';
require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'toggle') {
        $uid = (int)$_POST['user_id'];
        db()->prepare("UPDATE users SET status = IF(status='active','inactive','active')
          WHERE id=? AND role='pg_owner'")->execute([$uid]);
        db()->prepare("UPDATE pg_owners SET is_active = IF(is_active=1,0,1) WHERE user_id=?")->execute([$uid]);
        log_activity("toggle owner #$uid");
        flash('Owner status updated.');
    }
    header('Location: /dashboard/owners.php'); exit;
}

$rows = db()->query("SELECT u.id user_id, u.name, u.email, u.status, o.pg_name, o.city,
  (SELECT COUNT(*) FROM students s WHERE s.owner_id=o.id) students
  FROM users u JOIN pg_owners o ON o.user_id=u.id
  WHERE u.role='pg_owner' ORDER BY u.created_at DESC")->fetchAll();
?>
<div class="panel"><div class="panel-head"><h3>PG Owners (<?= count($rows) ?>)</h3>
  <a class="btn btn-primary btn-sm" href="/dashboard/owner_add.php" style="margin-left:auto">+ Add PG Owner</a>
</div>
<div class="table-wrap"><table class="data">
  <thead><tr><th>Owner</th><th>PG</th><th>City</th><th>Email</th><th>Students</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px">No PG owners yet.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr>
      <td><strong><?= e($r['name']) ?></strong></td>
      <td><?= e($r['pg_name']) ?></td>
      <td><?= e($r['city']) ?></td>
      <td><?= e($r['email']) ?></td>
      <td><?= (int)$r['students'] ?></td>
      <td><span class="badge <?= $r['status']==='active'?'green':'red' ?>"><?= e($r['status']) ?></span></td>
      <td><form method="post" style="margin:0"><?= csrf_field() ?>
        <input type="hidden" name="action" value="toggle"><input type="hidden" name="user_id" value="<?= (int)$r['user_id'] ?>">
        <button class="btn btn-ghost btn-sm"><?= $r['status']==='active'?'Deactivate':'Activate' ?></button>
      </form></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

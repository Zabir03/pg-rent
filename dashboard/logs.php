<?php
$page_title = 'Activity Logs';
require_once __DIR__ . '/../includes/header.php';
require_role('super_admin');

$rows = db()->query("SELECT l.*, u.name, u.email FROM activity_logs l
  LEFT JOIN users u ON u.id=l.user_id
  ORDER BY l.created_at DESC LIMIT 300")->fetchAll();
?>
<div class="panel"><div class="panel-head"><h3>Recent Activity (last 300)</h3></div>
<div class="table-wrap"><table class="data">
  <thead><tr><th>When</th><th>User</th><th>Action</th><th>IP</th></tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:24px">No activity logged yet.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['created_at']) ?></td>
      <td><?= e($r['name'] ?? 'System') ?><?php if ($r['email']): ?><br><span style="color:var(--muted);font-size:.8rem"><?= e($r['email']) ?></span><?php endif; ?></td>
      <td><?= e($r['action']) ?></td>
      <td><?= e($r['ip_address'] ?? '—') ?></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

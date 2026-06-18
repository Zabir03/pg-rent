<?php
$page_title = 'Notifications';
require_once __DIR__ . '/../includes/header.php';
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'read_all') {
        db()->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$u['id']]);
        flash('All notifications marked as read.');
    } elseif ($act === 'read_one') {
        db()->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")
          ->execute([(int)$_POST['id'], $u['id']]);
    } elseif ($act === 'clear') {
        db()->prepare("DELETE FROM notifications WHERE user_id=?")->execute([$u['id']]);
        flash('Notifications cleared.');
    }
    header('Location: /notifications/index.php'); exit;
}

$rows = db()->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 100");
$rows->execute([$u['id']]); $items = $rows->fetchAll();
?>
<div class="panel">
  <div class="panel-head"><h3>Notifications</h3>
    <form method="post" style="margin-left:auto;display:flex;gap:8px"><?= csrf_field() ?>
      <button class="btn btn-ghost btn-sm" name="action" value="read_all">Mark all read</button>
      <button class="btn btn-danger btn-sm" name="action" value="clear" data-confirm="Delete all notifications?">Clear all</button>
    </form>
  </div>
  <div class="panel-body" style="padding:0">
  <?php if (!$items): ?>
    <p style="text-align:center;color:var(--muted);padding:30px">No notifications yet.</p>
  <?php else: foreach ($items as $n):
    $typeBadge = ['rent_due'=>'amber','overdue'=>'red','sub_expiry'=>'amber','info'=>'blue'][$n['type']] ?? 'gray'; ?>
    <div class="notif <?= $n['is_read'] ? '' : 'unread' ?>">
      <div style="flex:1">
        <div style="display:flex;align-items:center;gap:8px">
          <strong><?= e($n['title']) ?></strong>
          <span class="badge <?= $typeBadge ?>"><?= e(str_replace('_',' ',$n['type'])) ?></span>
        </div>
        <?php if ($n['body']): ?><div style="color:var(--slate);margin-top:3px"><?= e($n['body']) ?></div><?php endif; ?>
        <div style="color:var(--muted);font-size:.78rem;margin-top:4px"><?= e($n['created_at']) ?></div>
      </div>
      <?php if (!$n['is_read']): ?>
        <form method="post" style="margin:0"><?= csrf_field() ?>
          <input type="hidden" name="action" value="read_one">
          <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
          <button class="btn btn-ghost btn-sm">Mark read</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endforeach; endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

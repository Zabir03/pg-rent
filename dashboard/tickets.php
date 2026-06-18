<?php
$page_title = 'Support';
require_once __DIR__ . '/../includes/header.php';
require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'set_status') {
        $status = in_array($_POST['status'] ?? '', ['open','in_progress','closed'], true) ? $_POST['status'] : 'open';
        $tid = (int)$_POST['id'];
        db()->prepare("UPDATE support_tickets SET status=? WHERE id=?")
          ->execute([$status, $tid]);
        // notify the owner who raised it
        $own = db()->prepare("SELECT user_id, subject FROM support_tickets WHERE id=?");
        $own->execute([$tid]); $t = $own->fetch();
        if ($t) {
            notify((int)$t['user_id'], 'Support ticket updated',
                   'Your ticket "' . $t['subject'] . '" is now ' . str_replace('_',' ',$status) . '.', 'info');
        }
        log_activity("ticket #$tid -> $status");
        flash('Ticket updated.');
    }
    header('Location: /dashboard/tickets.php'); exit;
}

$rows = db()->query("SELECT t.*, u.name, u.email FROM support_tickets t
  JOIN users u ON u.id=t.user_id ORDER BY
  FIELD(t.status,'open','in_progress','closed'), t.created_at DESC LIMIT 200")->fetchAll();

$open = db()->query("SELECT COUNT(*) c FROM support_tickets WHERE status='open'")->fetch()['c'];
$prog = db()->query("SELECT COUNT(*) c FROM support_tickets WHERE status='in_progress'")->fetch()['c'];
?>
<div class="stat-grid">
  <div class="stat"><div class="label">Open Tickets</div><div class="value red"><?= (int)$open ?></div></div>
  <div class="stat"><div class="label">In Progress</div><div class="value amber"><?= (int)$prog ?></div></div>
</div>

<div class="panel"><div class="panel-head"><h3>Support Tickets</h3></div>
<div class="table-wrap"><table class="data">
  <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Created</th><th>Status</th><th>Update</th></tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">No support tickets.</td></tr>
  <?php else: foreach ($rows as $r):
    $b = $r['status']==='open'?'red':($r['status']==='in_progress'?'amber':'green'); ?>
    <tr>
      <td><strong><?= e($r['name']) ?></strong><br><span style="color:var(--muted);font-size:.8rem"><?= e($r['email']) ?></span></td>
      <td><?= e($r['subject']) ?></td>
      <td style="max-width:280px"><?= e($r['message']) ?></td>
      <td><?= e($r['created_at']) ?></td>
      <td><span class="badge <?= $b ?>"><?= e(str_replace('_',' ',$r['status'])) ?></span></td>
      <td>
        <form method="post" style="display:flex;gap:6px;margin:0"><?= csrf_field() ?>
          <input type="hidden" name="action" value="set_status">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <select name="status" style="margin:0;max-width:140px">
            <option value="open" <?= $r['status']==='open'?'selected':'' ?>>Open</option>
            <option value="in_progress" <?= $r['status']==='in_progress'?'selected':'' ?>>In Progress</option>
            <option value="closed" <?= $r['status']==='closed'?'selected':'' ?>>Closed</option>
          </select>
          <button class="btn btn-ghost btn-sm">Save</button>
        </form>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

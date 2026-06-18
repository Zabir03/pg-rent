<?php
$page_title = 'Support';
require_once __DIR__ . '/../includes/header.php';
require_role('pg_owner');
$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($subject === '') {
        flash('Please enter a subject.', 'error');
    } else {
        db()->prepare("INSERT INTO support_tickets (user_id, subject, message, status)
          VALUES (?,?,?, 'open')")->execute([$u['id'], $subject, $message]);
        log_activity('raise support ticket');
        flash('Ticket submitted. Our team will get back to you.');
    }
    header('Location: /support/index.php'); exit;
}

$rows = db()->prepare("SELECT * FROM support_tickets WHERE user_id=? ORDER BY created_at DESC LIMIT 100");
$rows->execute([$u['id']]); $tickets = $rows->fetchAll();
?>
<div class="panel"><div class="panel-head"><h3>Raise a Support Ticket</h3></div><div class="panel-body">
<form method="post">
  <?= csrf_field() ?>
  <label>Subject <input name="subject" required placeholder="Briefly, what's the issue?"></label>
  <label>Message <textarea name="message" rows="4" placeholder="Describe the problem in detail…"></textarea></label>
  <div style="margin-top:6px"><button class="btn btn-primary">Submit Ticket</button></div>
</form>
</div></div>

<div class="panel"><div class="panel-head"><h3>My Tickets</h3></div>
<div class="table-wrap"><table class="data">
  <thead><tr><th>Subject</th><th>Message</th><th>Raised</th><th>Status</th></tr></thead>
  <tbody>
  <?php if (!$tickets): ?>
    <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:24px">You haven't raised any tickets yet.</td></tr>
  <?php else: foreach ($tickets as $t):
    $b = $t['status']==='open'?'red':($t['status']==='in_progress'?'amber':'green'); ?>
    <tr>
      <td><strong><?= e($t['subject']) ?></strong></td>
      <td style="max-width:320px"><?= e($t['message']) ?></td>
      <td><?= e($t['created_at']) ?></td>
      <td><span class="badge <?= $b ?>"><?= e(str_replace('_',' ',$t['status'])) ?></span></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

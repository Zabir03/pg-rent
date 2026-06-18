<?php
$page_title = 'Subscriptions';
require_once __DIR__ . '/../includes/header.php';
require_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $ownerId = (int)$_POST['owner_id'];
        $plan    = in_array($_POST['plan'] ?? '', ['monthly','yearly'], true) ? $_POST['plan'] : 'monthly';
        $amount  = (float)$_POST['amount'];
        $start   = $_POST['start_date'] ?: date('Y-m-d');
        $renewal = $plan === 'yearly'
            ? date('Y-m-d', strtotime($start . ' +1 year'))
            : date('Y-m-d', strtotime($start . ' +1 month'));
        db()->prepare("INSERT INTO subscriptions (owner_id,plan,amount,start_date,renewal_date,status)
          VALUES (?,?,?,?,?, 'active')")->execute([$ownerId,$plan,$amount,$start,$renewal]);
        $subId = (int)db()->lastInsertId();
        // auto-generate invoice
        $invNo = 'INV-' . date('Ymd') . '-' . str_pad((string)$subId, 4, '0', STR_PAD_LEFT);
        db()->prepare("INSERT INTO invoices (subscription_id,invoice_number,amount,issued_on,status)
          VALUES (?,?,?,?, 'paid')")->execute([$subId,$invNo,$amount,$start]);
        log_activity("add subscription for owner #$ownerId");
        flash('Subscription added and invoice generated.');
    } elseif ($act === 'expire') {
        db()->prepare("UPDATE subscriptions SET status='expired' WHERE id=?")->execute([(int)$_POST['id']]);
        flash('Subscription marked expired.');
    }
    header('Location: /dashboard/subscriptions.php'); exit;
}

// owners for the dropdown
$owners = db()->query("SELECT o.id, o.pg_name, u.name FROM pg_owners o
  JOIN users u ON u.id=o.user_id ORDER BY o.pg_name")->fetchAll();

$rows = db()->query("SELECT s.*, o.pg_name, u.name owner_name
  FROM subscriptions s
  JOIN pg_owners o ON o.id=s.owner_id
  JOIN users u ON u.id=o.user_id
  ORDER BY s.created_at DESC LIMIT 200")->fetchAll();

$activeCount = db()->query("SELECT COUNT(*) c FROM subscriptions WHERE status='active'")->fetch()['c'];
$revenue = db()->query("SELECT COALESCE(SUM(amount),0) t FROM subscriptions WHERE status='active'")->fetch()['t'];
?>
<div class="stat-grid">
  <div class="stat"><div class="label">Active Subscriptions</div><div class="value green"><?= (int)$activeCount ?></div></div>
  <div class="stat"><div class="label">Active Plan Revenue</div><div class="value">₹<?= number_format((float)$revenue) ?></div></div>
</div>

<div class="panel"><div class="panel-head"><h3>Add / Renew Subscription</h3></div><div class="panel-body">
<form method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div class="form-grid">
    <label>PG Owner
      <select name="owner_id" required>
        <option value="">Select…</option>
        <?php foreach ($owners as $o): ?>
          <option value="<?= (int)$o['id'] ?>"><?= e($o['pg_name']) ?> — <?= e($o['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Plan
      <select name="plan">
        <option value="monthly">Monthly Maintenance</option>
        <option value="yearly">Yearly Maintenance</option>
      </select>
    </label>
    <label>Amount (₹) <input type="number" step="0.01" name="amount" required></label>
    <label>Start Date <input type="date" name="start_date" value="<?= date('Y-m-d') ?>"></label>
  </div>
  <div style="margin-top:12px"><button class="btn btn-primary">Add Subscription</button></div>
</form>
</div></div>

<div class="panel"><div class="panel-head"><h3>All Subscriptions</h3></div>
<div class="table-wrap"><table class="data">
  <thead><tr><th>PG</th><th>Owner</th><th>Plan</th><th>Amount</th><th>Start</th><th>Renewal</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">No subscriptions yet.</td></tr>
  <?php else: foreach ($rows as $r):
    $b = $r['status']==='active'?'green':($r['status']==='expired'?'red':'amber'); ?>
    <tr>
      <td><strong><?= e($r['pg_name']) ?></strong></td>
      <td><?= e($r['owner_name']) ?></td>
      <td><?= e(ucfirst($r['plan'])) ?></td>
      <td>₹<?= number_format((float)$r['amount']) ?></td>
      <td><?= e($r['start_date']) ?></td>
      <td><?= e($r['renewal_date']) ?></td>
      <td><span class="badge <?= $b ?>"><?= e($r['status']) ?></span></td>
      <td>
        <?php if ($r['status']==='active'): ?>
          <form method="post" style="margin:0"><?= csrf_field() ?>
            <input type="hidden" name="action" value="expire">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-ghost btn-sm" data-confirm="Mark this subscription expired?">Expire</button>
          </form>
        <?php else: ?>—<?php endif; ?>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

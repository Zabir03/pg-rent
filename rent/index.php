<?php
$page_title = 'Rent';
require_once __DIR__ . '/../includes/header.php';
require_role('pg_owner');
$oid = current_owner_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'collect') {
        $studentId = (int)$_POST['student_id'];
        $amount    = (float)$_POST['amount'];
        $lateFee   = (float)($_POST['late_fee'] ?? 0);
        $month     = preg_match('/^\d{4}-\d{2}$/', $_POST['rent_month'] ?? '') ? $_POST['rent_month'] : date('Y-m');
        $mode      = in_array($_POST['payment_mode'] ?? '', ['cash','upi','bank_transfer','card'], true) ? $_POST['payment_mode'] : 'cash';
        $txn       = trim($_POST['transaction_id'] ?? '');
        db()->prepare("INSERT INTO rent_payments
          (owner_id,student_id,rent_month,amount,late_fee,due_date,payment_date,payment_mode,transaction_id,status)
          VALUES (?,?,?,?,?,?,CURDATE(),?,?, 'paid')")
          ->execute([$oid,$studentId,$month,$amount,$lateFee,date('Y-m-05'),$mode,$txn]);
        $rid = db()->lastInsertId();
        log_activity('collect rent');
        notify($u['id'], 'Rent collected',
               '₹' . number_format($amount + $lateFee) . ' recorded for ' . $month . '.', 'info');
        flash('Payment recorded.');
        header("Location: /rent/receipt.php?id=$rid"); exit;
    }
    header('Location: /rent/index.php'); exit;
}

$students = db()->prepare("SELECT id,full_name,monthly_rent FROM students WHERE owner_id=? AND status='active' ORDER BY full_name");
$students->execute([$oid]); $students = $students->fetchAll();

$pay = db()->prepare("SELECT p.*, s.full_name FROM rent_payments p
  JOIN students s ON s.id=p.student_id WHERE p.owner_id=? ORDER BY p.created_at DESC LIMIT 100");
$pay->execute([$oid]); $payments = $pay->fetchAll();
?>
<div class="panel"><div class="panel-head"><h3>Collect Rent</h3>
  <a class="btn btn-ghost btn-sm" href="/rent/reminders.php" style="margin-left:auto">💬 Send Reminders</a>
</div><div class="panel-body">
<form method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="collect">
  <div class="form-grid">
    <label>Student
      <select name="student_id" required>
        <option value="">Select…</option>
        <?php foreach ($students as $s): ?>
          <option value="<?= (int)$s['id'] ?>" data-rent="<?= (float)$s['monthly_rent'] ?>">
            <?= e($s['full_name']) ?> (₹<?= number_format((float)$s['monthly_rent']) ?>)
          </option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Rent Month <input type="month" name="rent_month" value="<?= date('Y-m') ?>"></label>
    <label>Amount <input type="number" step="0.01" name="amount" required></label>
    <label>Late Fee <input type="number" step="0.01" name="late_fee" value="0"></label>
    <label>Payment Mode <select name="payment_mode">
      <option value="cash">Cash</option><option value="upi">UPI</option>
      <option value="bank_transfer">Bank Transfer</option><option value="card">Card</option>
    </select></label>
    <label>Transaction ID <input name="transaction_id" placeholder="optional"></label>
  </div>
  <div style="margin-top:12px"><button class="btn btn-primary">Record Payment</button></div>
</form>
</div></div>

<div class="panel"><div class="panel-head"><h3>Payment History</h3></div>
<div class="table-wrap"><table class="data">
  <thead><tr><th>Student</th><th>Month</th><th>Amount</th><th>Late</th><th>Mode</th><th>Date</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$payments): ?><tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">No payments yet.</td></tr>
  <?php else: foreach ($payments as $p):
    $b = $p['status']==='paid'?'green':($p['status']==='overdue'?'red':'amber'); ?>
    <tr>
      <td><?= e($p['full_name']) ?></td>
      <td><?= e($p['rent_month']) ?></td>
      <td>₹<?= number_format((float)$p['amount']) ?></td>
      <td>₹<?= number_format((float)$p['late_fee']) ?></td>
      <td><?= e($p['payment_mode']) ?></td>
      <td><?= e($p['payment_date'] ?? '—') ?></td>
      <td><span class="badge <?= $b ?>"><?= e($p['status']) ?></span></td>
      <td><a class="btn btn-ghost btn-sm" href="/rent/receipt.php?id=<?= (int)$p['id'] ?>">Receipt</a></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div></div>

<script>
// auto-fill amount from selected student's monthly rent
document.querySelector('select[name=student_id]').addEventListener('change', e => {
  const rent = e.target.selectedOptions[0]?.dataset.rent;
  if (rent) document.querySelector('input[name=amount]').value = rent;
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

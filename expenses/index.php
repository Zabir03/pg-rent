<?php
$page_title = 'Expenses';
require_once __DIR__ . '/../includes/header.php';
require_role('pg_owner');
$oid = current_owner_id();

$cats = ['Electricity','Water','Internet','Maintenance','Staff Salary','Food','Repairs','Miscellaneous'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        db()->prepare("INSERT INTO expenses (owner_id,category,amount,expense_date,note) VALUES (?,?,?,?,?)")
          ->execute([$oid, $_POST['category'], (float)$_POST['amount'],
                     $_POST['expense_date'] ?: date('Y-m-d'), trim($_POST['note'] ?? '')]);
        flash('Expense added.');
    } elseif ($act === 'delete') {
        db()->prepare("DELETE FROM expenses WHERE id=? AND owner_id=?")->execute([(int)$_POST['id'], $oid]);
        flash('Expense deleted.');
    }
    header('Location: /expenses/index.php'); exit;
}

$rows = db()->prepare("SELECT * FROM expenses WHERE owner_id=? ORDER BY expense_date DESC LIMIT 200");
$rows->execute([$oid]); $rows = $rows->fetchAll();

$sum = db()->prepare("SELECT category, SUM(amount) t FROM expenses
  WHERE owner_id=? AND DATE_FORMAT(expense_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')
  GROUP BY category ORDER BY t DESC");
$sum->execute([$oid]); $summary = $sum->fetchAll();
$monthTotal = array_sum(array_map(fn($s)=>(float)$s['t'], $summary));
?>
<div class="stat-grid">
  <div class="stat"><div class="label">This Month Total</div><div class="value amber">₹<?= number_format($monthTotal) ?></div></div>
  <?php foreach (array_slice($summary,0,3) as $s): ?>
    <div class="stat"><div class="label"><?= e($s['category']) ?></div><div class="value">₹<?= number_format((float)$s['t']) ?></div></div>
  <?php endforeach; ?>
</div>

<div class="panel"><div class="panel-head"><h3>Add Expense</h3></div><div class="panel-body">
<form method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="add">
  <div class="form-grid">
    <label>Category <select name="category">
      <?php foreach ($cats as $c): ?><option><?= e($c) ?></option><?php endforeach; ?>
    </select></label>
    <label>Amount <input type="number" step="0.01" name="amount" required></label>
    <label>Date <input type="date" name="expense_date" value="<?= date('Y-m-d') ?>"></label>
    <label>Note <input name="note"></label>
  </div>
  <div style="margin-top:12px"><button class="btn btn-primary">Add Expense</button></div>
</form>
</div></div>

<div class="panel"><div class="panel-head"><h3>Expense History</h3></div>
<div class="table-wrap"><table class="data">
  <thead><tr><th>Date</th><th>Category</th><th>Amount</th><th>Note</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?><tr><td colspan="5" style="text-align:center;color:var(--muted);padding:24px">No expenses yet.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['expense_date']) ?></td>
      <td><span class="badge gray"><?= e($r['category']) ?></span></td>
      <td>₹<?= number_format((float)$r['amount']) ?></td>
      <td><?= e($r['note']) ?></td>
      <td><form method="post" style="margin:0"><?= csrf_field() ?>
        <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
        <button class="btn btn-danger btn-sm" data-confirm="Delete expense?">Del</button>
      </form></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

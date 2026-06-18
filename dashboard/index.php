<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';

if ($role === 'super_admin') {
    $owners   = db()->query("SELECT COUNT(*) c FROM pg_owners")->fetch()['c'];
    $active   = db()->query("SELECT COUNT(*) c FROM pg_owners WHERE is_active=1")->fetch()['c'];
    $subs     = db()->query("SELECT COUNT(*) c FROM subscriptions WHERE status='active'")->fetch()['c'];
    $tickets  = db()->query("SELECT COUNT(*) c FROM support_tickets WHERE status='open'")->fetch()['c'];
    ?>
    <div class="stat-grid">
      <div class="stat"><div class="label">Total PG Owners</div><div class="value"><?= (int)$owners ?></div></div>
      <div class="stat"><div class="label">Active Owners</div><div class="value green"><?= (int)$active ?></div></div>
      <div class="stat"><div class="label">Active Subscriptions</div><div class="value"><?= (int)$subs ?></div></div>
      <div class="stat"><div class="label">Open Tickets</div><div class="value amber"><?= (int)$tickets ?></div></div>
    </div>
    <div class="panel"><div class="panel-head"><h3>Welcome, Super Admin</h3></div>
      <div class="panel-body">Use the sidebar to manage PG owners, subscriptions and support.</div></div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    return;
}

// ---- PG Owner dashboard ----
$oid = current_owner_id();
$q = fn($sql) => (function($s){ $st=db()->prepare($s); $st->execute([$GLOBALS['oid']]); return $st->fetch(); });

// ---- Subscription expiry check (notify once per day) ----
$subChk = db()->prepare("SELECT * FROM subscriptions WHERE owner_id=? ORDER BY renewal_date DESC, id DESC LIMIT 1");
$subChk->execute([$oid]); $subRow = $subChk->fetch();
if ($subRow) {
    $today  = new DateTime('today');
    $renew  = new DateTime($subRow['renewal_date']);
    $days   = (int)$today->diff($renew)->format('%r%a');   // signed days until renewal
    $isExpired = $subRow['status'] === 'expired' || $days < 0;

    if ($isExpired || $days <= 7) {
        // avoid duplicate notifications the same day
        $dupe = db()->prepare("SELECT COUNT(*) c FROM notifications
          WHERE user_id=? AND type='sub_expiry' AND DATE(created_at)=CURDATE()");
        $dupe->execute([$u['id']]);
        if ((int)$dupe->fetch()['c'] === 0) {
            if ($isExpired) {
                notify($u['id'], 'Subscription expired',
                       'Your subscription has expired. Visit the Subscription tab to renew.', 'sub_expiry');
            } else {
                notify($u['id'], 'Subscription expiring soon',
                       "Your subscription expires in {$days} day" . ($days === 1 ? '' : 's') .
                       ' (' . $renew->format('d M Y') . '). Please renew.', 'sub_expiry');
            }
        }
    }
}

$st = db()->prepare("SELECT COUNT(*) c FROM students WHERE owner_id=? AND status='active'");
$st->execute([$oid]); $activeStudents = (int)$st->fetch()['c'];

$st = db()->prepare("SELECT COUNT(*) c FROM rooms WHERE owner_id=?");
$st->execute([$oid]); $totalRooms = (int)$st->fetch()['c'];

$st = db()->prepare("SELECT COUNT(*) c FROM rooms WHERE owner_id=? AND status='occupied'");
$st->execute([$oid]); $occupied = (int)$st->fetch()['c'];
$vacant = max(0, $totalRooms - $occupied);

$st = db()->prepare("SELECT COALESCE(SUM(amount+late_fee),0) s FROM rent_payments
  WHERE owner_id=? AND status='paid' AND rent_month=DATE_FORMAT(CURDATE(),'%Y-%m')");
$st->execute([$oid]); $monthRevenue = (float)$st->fetch()['s'];

$st = db()->prepare("SELECT COALESCE(SUM(amount+late_fee),0) s FROM rent_payments
  WHERE owner_id=? AND status IN('pending','overdue')");
$st->execute([$oid]); $pending = (float)$st->fetch()['s'];

$st = db()->prepare("SELECT COALESCE(SUM(amount),0) s FROM expenses
  WHERE owner_id=? AND DATE_FORMAT(expense_date,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')");
$st->execute([$oid]); $monthExpense = (float)$st->fetch()['s'];

$occPct = $totalRooms ? round($occupied / $totalRooms * 100) : 0;

// last 6 months revenue for the chart
$st = db()->prepare("SELECT rent_month, SUM(amount+late_fee) t FROM rent_payments
  WHERE owner_id=? AND status='paid'
  GROUP BY rent_month ORDER BY rent_month DESC LIMIT 6");
$st->execute([$oid]);
$series = array_reverse($st->fetchAll());
$maxRev = max(1, ...array_map(fn($r)=>(float)$r['t'], $series ?: [['t'=>1]]));
?>
<?php if ($subRow && ($isExpired || $days <= 7)): ?>
  <div class="toast <?= $isExpired ? 'toast-error' : 'toast-warn' ?>" style="margin-bottom:18px">
    <?php if ($isExpired): ?>
      ⚠ Your subscription has <strong>expired</strong>.
    <?php else: ?>
      ⏳ Your subscription expires in <strong><?= $days ?> day<?= $days === 1 ? '' : 's' ?></strong> (<?= e($renew->format('d M Y')) ?>).
    <?php endif; ?>
    <a href="/subscription/index.php" style="margin-left:6px;font-weight:700">Renew now →</a>
  </div>
<?php endif; ?>
<div class="stat-grid">
  <div class="stat"><div class="label">Active Students</div><div class="value"><?= $activeStudents ?></div></div>
  <div class="stat"><div class="label">Occupied Rooms</div><div class="value"><?= $occupied ?>/<?= $totalRooms ?></div></div>
  <div class="stat"><div class="label">Vacant Rooms</div><div class="value blue"><?= $vacant ?></div></div>
  <div class="stat"><div class="label">Occupancy</div><div class="value"><?= $occPct ?>%</div></div>
  <div class="stat"><div class="label">This Month Revenue</div><div class="value green">₹<?= number_format($monthRevenue) ?></div></div>
  <div class="stat"><div class="label">Pending / Due Rent</div><div class="value red">₹<?= number_format($pending) ?></div></div>
  <div class="stat"><div class="label">This Month Expenses</div><div class="value amber">₹<?= number_format($monthExpense) ?></div></div>
  <div class="stat"><div class="label">Net (Rev − Exp)</div><div class="value">₹<?= number_format($monthRevenue - $monthExpense) ?></div></div>
</div>

<div class="panel">
  <div class="panel-head"><h3>Revenue — last 6 months</h3></div>
  <div class="panel-body">
    <?php if ($series): ?>
      <div class="bars">
        <?php foreach ($series as $r): $h = round((float)$r['t'] / $maxRev * 100); ?>
          <div class="bar" style="height:<?= $h ?>%" title="₹<?= number_format((float)$r['t']) ?>">
            <span><?= e(date('M', strtotime($r['rent_month'].'-01'))) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color:var(--muted)">No paid rent recorded yet.</p>
    <?php endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

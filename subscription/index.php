<?php
$page_title = 'Subscription';
require_once __DIR__ . '/../includes/header.php';
require_role('pg_owner');
$oid = current_owner_id();

// latest subscription for this owner
$st = db()->prepare("SELECT * FROM subscriptions WHERE owner_id=? ORDER BY renewal_date DESC, id DESC LIMIT 1");
$st->execute([$oid]); $sub = $st->fetch();

// contact details (admin-configured)
$email = get_setting('renew_email', 'support@myrentify.page.gd');
$wa    = get_setting('renew_whatsapp', '+919999999999');
$waDigits = preg_replace('/\D+/', '', $wa);
$waMsg = rawurlencode('Hello, I would like to renew my PG Rent Manager subscription.');

// work out days remaining
$daysLeft = null; $expired = false;
if ($sub) {
    $today = new DateTime('today');
    $renew = new DateTime($sub['renewal_date']);
    $daysLeft = (int)$today->diff($renew)->format('%r%a'); // signed
    $expired = $sub['status'] === 'expired' || $daysLeft < 0;
}
?>
<div class="panel"><div class="panel-head"><h3>My Subscription</h3></div><div class="panel-body">
<?php if (!$sub): ?>
  <div class="toast toast-warn" style="margin:0 0 16px">You don't have an active subscription yet.</div>
<?php else: ?>
  <div class="stat-grid" style="margin-bottom:8px">
    <div class="stat"><div class="label">Plan</div><div class="value"><?= e(ucfirst($sub['plan'])) ?></div></div>
    <div class="stat"><div class="label">Amount</div><div class="value">₹<?= number_format((float)$sub['amount']) ?></div></div>
    <div class="stat"><div class="label">Renewal Date</div><div class="value" style="font-size:1.2rem"><?= e(date('d M Y', strtotime($sub['renewal_date']))) ?></div></div>
    <div class="stat"><div class="label">Status</div>
      <div class="value <?= $expired ? 'red' : 'green' ?>" style="font-size:1.2rem">
        <?= $expired ? 'Expired' : 'Active' ?>
      </div>
    </div>
  </div>

  <?php if ($expired): ?>
    <div class="toast toast-error" style="margin:8px 0 0">
      Your subscription has expired. Please renew to keep using all features.
    </div>
  <?php elseif ($daysLeft !== null && $daysLeft <= 7): ?>
    <div class="toast toast-warn" style="margin:8px 0 0">
      Your subscription expires in <?= $daysLeft ?> day<?= $daysLeft === 1 ? '' : 's' ?> (on <?= e(date('d M Y', strtotime($sub['renewal_date']))) ?>). Please renew soon.
    </div>
  <?php endif; ?>
<?php endif; ?>
</div></div>

<div class="panel"><div class="panel-head"><h3>Renew Your Subscription</h3></div><div class="panel-body">
  <p style="color:var(--slate)">To continue or renew your subscription, please contact us:</p>
  <div style="display:flex;flex-direction:column;gap:12px;max-width:420px;margin-top:10px">
    <a class="btn btn-ghost" href="mailto:<?= e($email) ?>?subject=Subscription%20Renewal" style="justify-content:flex-start;gap:10px">
      ✉ <span>Email: <strong><?= e($email) ?></strong></span>
    </a>
    <a class="btn btn-primary" href="https://wa.me/<?= e($waDigits) ?>?text=<?= $waMsg ?>" target="_blank" rel="noopener" style="justify-content:flex-start;gap:10px">
      💬 <span>WhatsApp: <strong><?= e($wa) ?></strong></span>
    </a>
  </div>
  <p style="color:var(--muted);font-size:.83rem;margin-top:16px">
    Once your payment is confirmed, your renewal date will be updated by our team.
  </p>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

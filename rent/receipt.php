<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('pg_owner');
$oid = current_owner_id();
$id = (int)($_GET['id'] ?? 0);

$st = db()->prepare("SELECT p.*, s.full_name, s.mobile, o.pg_name, o.address pg_addr, o.logo pg_logo
  FROM rent_payments p
  JOIN students s ON s.id=p.student_id
  JOIN pg_owners o ON o.id=p.owner_id
  WHERE p.id=? AND p.owner_id=?");
$st->execute([$id, $oid]); $r = $st->fetch();
if (!$r) { http_response_code(404); exit('Receipt not found'); }
$total = (float)$r['amount'] + (float)$r['late_fee'];
?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Receipt #<?= (int)$r['id'] ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
  body{background:#fff;padding:30px}
  .receipt{max-width:520px;margin:auto;border:1px solid var(--line);border-radius:14px;padding:28px}
  .receipt h2{margin:0}
  .rrow{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px dashed var(--line)}
  .total{font-size:1.3rem;font-weight:700;margin-top:10px}
  @media print{.noprint{display:none}}
</style></head><body>
<div class="receipt">
  <?php if (!empty($r['pg_logo'])): ?>
    <img src="/<?= e($r['pg_logo']) ?>" alt="logo" style="height:54px;margin-bottom:10px">
  <?php endif; ?>
  <h2><?= e($r['pg_name']) ?></h2>
  <p style="color:var(--muted);margin:.2em 0 18px"><?= e($r['pg_addr']) ?></p>
  <p><strong>Rent Receipt</strong> · #<?= str_pad((string)$r['id'],6,'0',STR_PAD_LEFT) ?></p>
  <div class="rrow"><span>Student</span><span><?= e($r['full_name']) ?></span></div>
  <div class="rrow"><span>Mobile</span><span><?= e($r['mobile']) ?></span></div>
  <div class="rrow"><span>Rent Month</span><span><?= e($r['rent_month']) ?></span></div>
  <div class="rrow"><span>Payment Date</span><span><?= e($r['payment_date']) ?></span></div>
  <div class="rrow"><span>Mode</span><span><?= e($r['payment_mode']) ?></span></div>
  <?php if ($r['transaction_id']): ?><div class="rrow"><span>Txn ID</span><span><?= e($r['transaction_id']) ?></span></div><?php endif; ?>
  <div class="rrow"><span>Rent</span><span>₹<?= number_format((float)$r['amount'],2) ?></span></div>
  <div class="rrow"><span>Late Fee</span><span>₹<?= number_format((float)$r['late_fee'],2) ?></span></div>
  <div class="rrow total"><span>Total Paid</span><span>₹<?= number_format($total,2) ?></span></div>

  <div style="text-align:center;margin-top:22px">
    <div id="qrcode" style="display:inline-block"></div>
    <div style="color:var(--muted);font-size:.78rem;margin-top:8px">Scan to verify this receipt</div>
  </div>

  <div class="noprint" style="margin-top:20px;display:flex;gap:10px">
    <button class="btn btn-primary" onclick="window.print()">Print</button>
    <a class="btn btn-ghost" href="/rent/index.php">Back</a>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  // Encode this receipt's own URL so it can be scanned and re-opened/verified
  new QRCode(document.getElementById('qrcode'), {
    text: window.location.href,
    width: 120, height: 120,
    correctLevel: QRCode.CorrectLevel.M
  });
</script>
</body></html>

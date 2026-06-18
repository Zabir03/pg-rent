<?php
$page_title = 'Rent Reminders';
require_once __DIR__ . '/../includes/header.php';
require_role('pg_owner');
$oid = current_owner_id();

// PG name for the message
$st = db()->prepare("SELECT pg_name FROM pg_owners WHERE id=?");
$st->execute([$oid]); $pgName = $st->fetch()['pg_name'] ?? 'our PG';

// selected month (default current)
$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$monthLabel = date('F Y', strtotime($month . '-01'));

// active students + whether they've PAID for the selected month
$sql = "SELECT s.id, s.full_name, s.mobile, s.monthly_rent,
          (SELECT COUNT(*) FROM rent_payments p
           WHERE p.student_id=s.id AND p.rent_month=? AND p.status='paid') paid_count
        FROM students s
        WHERE s.owner_id=? AND s.status='active'
        ORDER BY s.full_name";
$q = db()->prepare($sql); $q->execute([$month, $oid]); $students = $q->fetchAll();

// normalise an Indian mobile number for wa.me (needs country code, digits only)
function wa_number(string $raw): ?string {
    $d = preg_replace('/\D+/', '', $raw);      // strip non-digits
    if ($d === '') return null;
    if (strlen($d) === 10) $d = '91' . $d;     // assume India if 10 digits
    if (str_starts_with($d, '0')) $d = '91' . ltrim($d, '0');
    return $d;
}
?>
<form class="toolbar" method="get">
  <label style="margin:0">Month
    <input type="month" name="month" value="<?= e($month) ?>" onchange="this.form.submit()">
  </label>
</form>

<div class="panel"><div class="panel-head"><h3>Rent Reminders — <?= e($monthLabel) ?></h3></div>
<div class="table-wrap"><table class="data">
  <thead><tr><th>Student</th><th>Mobile</th><th>Rent</th><th>Status</th><th>Reminder</th></tr></thead>
  <tbody>
  <?php if (!$students): ?>
    <tr><td colspan="5" style="text-align:center;color:var(--muted);padding:24px">No active students.</td></tr>
  <?php else: foreach ($students as $s):
      $paid = (int)$s['paid_count'] > 0;
      $num  = wa_number($s['mobile'] ?? '');
      $amt  = number_format((float)$s['monthly_rent']);
      // pre-filled WhatsApp message
      $msg  = "Hello {$s['full_name']}, this is a friendly reminder that your rent of Rs.{$amt} "
            . "for {$monthLabel} at {$pgName} is due. Kindly pay at the earliest. Thank you.";
      $link = $num ? 'https://wa.me/' . $num . '?text=' . rawurlencode($msg) : null;
  ?>
    <tr>
      <td><strong><?= e($s['full_name']) ?></strong></td>
      <td><?= e($s['mobile'] ?: '—') ?></td>
      <td>₹<?= $amt ?></td>
      <td>
        <?php if ($paid): ?><span class="badge green">paid</span>
        <?php else: ?><span class="badge red">due</span><?php endif; ?>
      </td>
      <td>
        <?php if ($paid): ?>
          <span style="color:var(--muted)">—</span>
        <?php elseif ($link): ?>
          <a class="btn btn-primary btn-sm" href="<?= e($link) ?>" target="_blank" rel="noopener">
            💬 WhatsApp
          </a>
        <?php else: ?>
          <span class="badge gray">no mobile</span>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div></div>

<p style="color:var(--muted);font-size:.85rem">
  Tip: the button opens WhatsApp with a ready-to-send message. You tap send — nothing is sent automatically,
  so you stay in control. Numbers are assumed to be Indian (+91) when 10 digits.
</p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

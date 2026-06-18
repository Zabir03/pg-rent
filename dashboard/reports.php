<?php
require_once __DIR__ . '/../includes/functions.php';
require_role('pg_owner');
$oid = current_owner_id();

/* ---- Report data builders -------------------------------- */
function report_query(int $oid, string $type): array {
    switch ($type) {
        case 'rent':
            $sql = "SELECT s.full_name AS Student, p.rent_month AS Month, p.amount AS Amount,
                      p.late_fee AS LateFee, p.payment_mode AS Mode, p.payment_date AS Date, p.status AS Status
                    FROM rent_payments p JOIN students s ON s.id=p.student_id
                    WHERE p.owner_id=? ORDER BY p.payment_date DESC";
            $head = ['Student','Month','Amount','LateFee','Mode','Date','Status'];
            break;
        case 'students':
            $sql = "SELECT full_name AS Name, mobile AS Mobile, college_company AS College,
                      monthly_rent AS Rent, joining_date AS Joined, status AS Status
                    FROM students WHERE owner_id=? ORDER BY full_name";
            $head = ['Name','Mobile','College','Rent','Joined','Status'];
            break;
        case 'expenses':
            $sql = "SELECT expense_date AS Date, category AS Category, amount AS Amount, note AS Note
                    FROM expenses WHERE owner_id=? ORDER BY expense_date DESC";
            $head = ['Date','Category','Amount','Note'];
            break;
        case 'rooms':
            $sql = "SELECT room_number AS Room, floor_number AS Floor, room_type AS Type,
                      capacity AS Capacity, rent_amount AS Rent, status AS Status
                    FROM rooms WHERE owner_id=? ORDER BY room_number";
            $head = ['Room','Floor','Type','Capacity','Rent','Status'];
            break;
        default:
            return [[], []];
    }
    $st = db()->prepare($sql); $st->execute([$oid]);
    return [$head, $st->fetchAll(PDO::FETCH_NUM)];
}

$type   = $_GET['type']   ?? '';
$format = $_GET['format'] ?? '';
$valid  = ['rent','students','expenses','rooms'];

if (in_array($type, $valid, true) && $format) {
    [$head, $rows] = report_query($oid, $type);

    if ($format === 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $type . '_report.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $head);
        foreach ($rows as $r) fputcsv($out, $r);
        fclose($out); exit;
    }

    if ($format === 'excel') {
        // HTML-table .xls — opens natively in Excel / LibreOffice, no library needed
        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $type . '_report.xls"');
        echo "<table border='1'><tr>";
        foreach ($head as $h) echo '<th>' . htmlspecialchars($h) . '</th>';
        echo '</tr>';
        foreach ($rows as $r) {
            echo '<tr>';
            foreach ($r as $c) echo '<td>' . htmlspecialchars((string)$c) . '</td>';
            echo '</tr>';
        }
        echo '</table>'; exit;
    }

    if ($format === 'pdf') {
        // Print-optimised page; user saves as PDF via browser (Ctrl+P → Save as PDF)
        $stPg = db()->prepare("SELECT pg_name FROM pg_owners WHERE id=?");
        $stPg->execute([$oid]); $pg = $stPg->fetch()['pg_name'] ?? 'PG';
        ?><!DOCTYPE html><html lang="en"><head><meta charset="utf-8">
        <title><?= ucfirst($type) ?> Report</title>
        <style>
          body{font-family:Arial,sans-serif;padding:30px;color:#1b2430}
          h1{margin:0}.sub{color:#666;margin:4px 0 20px}
          table{width:100%;border-collapse:collapse;font-size:13px}
          th,td{border:1px solid #ccc;padding:8px 10px;text-align:left}
          th{background:#f0f0f0}
          @media print{.noprint{display:none}}
          .noprint{margin-bottom:16px}
          button{padding:9px 16px;font-size:14px;cursor:pointer}
        </style></head><body>
        <div class="noprint">
          <button onclick="window.print()">🖨 Save as PDF / Print</button>
        </div>
        <h1><?= htmlspecialchars($pg) ?></h1>
        <div class="sub"><?= ucfirst($type) ?> Report · generated <?= date('d M Y') ?></div>
        <table><tr><?php foreach ($head as $h) echo '<th>' . htmlspecialchars($h) . '</th>'; ?></tr>
        <?php foreach ($rows as $r): ?>
          <tr><?php foreach ($r as $c) echo '<td>' . htmlspecialchars((string)$c) . '</td>'; ?></tr>
        <?php endforeach; ?>
        </table>
        <script>window.onload=()=>setTimeout(()=>window.print(),400)</script>
        </body></html><?php
        exit;
    }
}

$page_title = 'Reports';
require_once __DIR__ . '/../includes/header.php';

$rev = db()->prepare("SELECT COALESCE(SUM(amount+late_fee),0) t FROM rent_payments WHERE owner_id=? AND status='paid'");
$rev->execute([$oid]); $totalRev = (float)$rev->fetch()['t'];
$exp = db()->prepare("SELECT COALESCE(SUM(amount),0) t FROM expenses WHERE owner_id=?");
$exp->execute([$oid]); $totalExp = (float)$exp->fetch()['t'];

$reports = [
  'rent'     => 'Rent Report',
  'students' => 'Student Report',
  'expenses' => 'Expense Report',
  'rooms'    => 'Room Report',
];
?>
<div class="stat-grid">
  <div class="stat"><div class="label">Total Collected</div><div class="value green">₹<?= number_format($totalRev) ?></div></div>
  <div class="stat"><div class="label">Total Expenses</div><div class="value amber">₹<?= number_format($totalExp) ?></div></div>
  <div class="stat"><div class="label">Net Profit</div><div class="value">₹<?= number_format($totalRev - $totalExp) ?></div></div>
</div>

<div class="panel"><div class="panel-head"><h3>Download Reports</h3></div>
<div class="table-wrap"><table class="data">
  <thead><tr><th>Report</th><th>CSV</th><th>Excel</th><th>PDF</th></tr></thead>
  <tbody>
  <?php foreach ($reports as $key => $label): ?>
    <tr>
      <td><strong><?= e($label) ?></strong></td>
      <td><a class="btn btn-ghost btn-sm" href="?type=<?= $key ?>&format=csv">⬇ CSV</a></td>
      <td><a class="btn btn-ghost btn-sm" href="?type=<?= $key ?>&format=excel">⬇ Excel</a></td>
      <td><a class="btn btn-ghost btn-sm" href="?type=<?= $key ?>&format=pdf" target="_blank">⬇ PDF</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div></div>

<p style="color:var(--muted);font-size:.85rem">
  PDF opens a print-ready page — choose "Save as PDF" in your browser's print dialog. Excel files open directly in Excel, LibreOffice, or Google Sheets.
</p>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

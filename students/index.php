<?php
$page_title = 'Students';
require_once __DIR__ . '/../includes/header.php';
require_role('pg_owner');
$oid = current_owner_id();

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';

$sql = "SELECT * FROM students WHERE owner_id=?";
$params = [$oid];
if ($search !== '') { $sql .= " AND (full_name LIKE ? OR mobile LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; }
if (in_array($status, ['active','left'], true)) { $sql .= " AND status=?"; $params[]=$status; }
$sql .= " ORDER BY created_at DESC LIMIT 200";
$st = db()->prepare($sql); $st->execute($params); $rows = $st->fetchAll();
?>
<form class="toolbar" method="get">
  <input name="q" placeholder="Search name or mobile…" value="<?= e($search) ?>">
  <select name="status">
    <option value="">All status</option>
    <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
    <option value="left" <?= $status==='left'?'selected':'' ?>>Left</option>
  </select>
  <button class="btn btn-ghost">Filter</button>
  <a class="btn btn-primary" href="/students/form.php" style="margin-left:auto">+ Add Student</a>
</form>

<div class="panel"><div class="table-wrap"><table class="data">
  <thead><tr><th>Name</th><th>Mobile</th><th>College/Company</th><th>Rent</th><th>Joined</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rows): ?>
    <tr><td colspan="7" style="text-align:center; color:var(--muted); padding:30px">No students yet. Add your first one.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr>
      <td><strong><?= e($r['full_name']) ?></strong></td>
      <td><?= e($r['mobile']) ?></td>
      <td><?= e($r['college_company']) ?></td>
      <td>₹<?= number_format((float)$r['monthly_rent']) ?></td>
      <td><?= e($r['joining_date']) ?></td>
      <td><span class="badge <?= $r['status']==='active'?'green':'gray' ?>"><?= e($r['status']) ?></span></td>
      <td style="white-space:nowrap">
        <a class="btn btn-ghost btn-sm" href="/students/form.php?id=<?= (int)$r['id'] ?>">Edit</a>
        <a class="btn btn-danger btn-sm" href="/students/delete.php?id=<?= (int)$r['id'] ?>"
           data-confirm="Delete this student permanently?">Del</a>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

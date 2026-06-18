<?php
$page_title = 'Rooms & Beds';
require_once __DIR__ . '/../includes/header.php';
require_role('pg_owner');
$oid = current_owner_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'add_room') {
        db()->prepare("INSERT INTO rooms (owner_id,room_number,floor_number,room_type,capacity,rent_amount,status)
          VALUES (?,?,?,?,?,?,?)")->execute([
            $oid, trim($_POST['room_number']), trim($_POST['floor_number']),
            trim($_POST['room_type']), (int)$_POST['capacity'],
            (float)$_POST['rent_amount'], $_POST['status'] ?? 'available'
        ]);
        log_activity('add room');
        flash('Room added.');
    }
    header('Location: /rooms/index.php'); exit;
}

$rows = db()->prepare("SELECT r.*,
  (SELECT COUNT(*) FROM beds b WHERE b.room_id=r.id) beds_total,
  (SELECT COUNT(*) FROM beds b WHERE b.room_id=r.id AND b.status='occupied') beds_occ
  FROM rooms r WHERE owner_id=? ORDER BY room_number");
$rows->execute([$oid]); $rooms = $rows->fetchAll();
?>
<div class="panel"><div class="panel-head"><h3>Add Room</h3></div><div class="panel-body">
<form method="post">
  <?= csrf_field() ?><input type="hidden" name="action" value="add_room">
  <div class="form-grid">
    <label>Room Number <input name="room_number" required></label>
    <label>Floor <input name="floor_number"></label>
    <label>Room Type <input name="room_type" placeholder="single/double/triple"></label>
    <label>Capacity <input type="number" name="capacity" value="1" min="1"></label>
    <label>Rent Amount <input type="number" step="0.01" name="rent_amount" value="0"></label>
    <label>Status <select name="status">
      <option value="available">Available</option>
      <option value="occupied">Occupied</option>
      <option value="maintenance">Maintenance</option>
    </select></label>
  </div>
  <div style="margin-top:12px"><button class="btn btn-primary">Add Room</button></div>
</form>
</div></div>

<div class="panel"><div class="panel-head"><h3>Rooms (<?= count($rooms) ?>)</h3></div>
<div class="table-wrap"><table class="data">
  <thead><tr><th>Room</th><th>Floor</th><th>Type</th><th>Capacity</th><th>Beds</th><th>Rent</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$rooms): ?><tr><td colspan="8" style="text-align:center;color:var(--muted);padding:26px">No rooms yet.</td></tr>
  <?php else: foreach ($rooms as $r):
    $b = $r['status']==='available'?'green':($r['status']==='maintenance'?'amber':'blue'); ?>
    <tr>
      <td><strong><?= e($r['room_number']) ?></strong></td>
      <td><?= e($r['floor_number']) ?></td>
      <td><?= e($r['room_type']) ?></td>
      <td><?= (int)$r['capacity'] ?></td>
      <td><?= (int)$r['beds_occ'] ?>/<?= (int)$r['beds_total'] ?></td>
      <td>₹<?= number_format((float)$r['rent_amount']) ?></td>
      <td><span class="badge <?= $b ?>"><?= e($r['status']) ?></span></td>
      <td><a class="btn btn-ghost btn-sm" href="/rooms/beds.php?room=<?= (int)$r['id'] ?>">Beds</a></td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table></div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

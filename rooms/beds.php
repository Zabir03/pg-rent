<?php
$page_title = 'Bed Management';
require_once __DIR__ . '/../includes/header.php';
require_role('pg_owner');
$oid = current_owner_id();
$roomId = (int)($_GET['room'] ?? 0);

// verify room belongs to owner
$st = db()->prepare("SELECT * FROM rooms WHERE id=? AND owner_id=?");
$st->execute([$roomId, $oid]); $room = $st->fetch();
if (!$room) { http_response_code(404); exit('Room not found'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'add_bed') {
        db()->prepare("INSERT INTO beds (room_id,bed_label,status) VALUES (?,?, 'available')")
            ->execute([$roomId, trim($_POST['bed_label'])]);
        flash('Bed added.');
    } elseif ($act === 'allocate') {
        $bedId = (int)$_POST['bed_id']; $studentId = (int)$_POST['student_id'];
        db()->prepare("UPDATE beds SET status='occupied' WHERE id=? AND room_id=?")->execute([$bedId,$roomId]);
        db()->prepare("INSERT INTO room_allocations (student_id,room_id,bed_id,allocated_on,is_active)
          VALUES (?,?,?,CURDATE(),1)")->execute([$studentId,$roomId,$bedId]);
        db()->prepare("UPDATE rooms SET status='occupied' WHERE id=?")->execute([$roomId]);
        flash('Bed allocated.');
    } elseif ($act === 'vacate') {
        $bedId = (int)$_POST['bed_id'];
        db()->prepare("UPDATE beds SET status='available' WHERE id=? AND room_id=?")->execute([$bedId,$roomId]);
        db()->prepare("UPDATE room_allocations SET is_active=0, vacated_on=CURDATE()
          WHERE bed_id=? AND is_active=1")->execute([$bedId]);
        flash('Bed vacated.');
    }
    header("Location: /rooms/beds.php?room=$roomId"); exit;
}

$beds = db()->prepare("SELECT b.*,
  (SELECT s.full_name FROM room_allocations ra JOIN students s ON s.id=ra.student_id
   WHERE ra.bed_id=b.id AND ra.is_active=1 LIMIT 1) student_name
  FROM beds b WHERE room_id=? ORDER BY bed_label");
$beds->execute([$roomId]); $beds = $beds->fetchAll();

$students = db()->prepare("SELECT id,full_name FROM students WHERE owner_id=? AND status='active' ORDER BY full_name");
$students->execute([$oid]); $students = $students->fetchAll();
?>
<p><a href="/rooms/index.php">← Rooms</a></p>
<div class="panel"><div class="panel-head"><h3>Room <?= e($room['room_number']) ?> — Beds</h3></div>
<div class="panel-body">
  <form method="post" class="toolbar">
    <?= csrf_field() ?><input type="hidden" name="action" value="add_bed">
    <input name="bed_label" placeholder="Bed label (A, B, 1…)" required>
    <button class="btn btn-primary">+ Add Bed</button>
  </form>

  <table class="data">
    <thead><tr><th>Bed</th><th>Status</th><th>Occupant</th><th>Action</th></tr></thead>
    <tbody>
    <?php if (!$beds): ?><tr><td colspan="4" style="color:var(--muted);padding:20px">No beds yet.</td></tr>
    <?php else: foreach ($beds as $b): ?>
      <tr>
        <td><strong><?= e($b['bed_label']) ?></strong></td>
        <td><span class="badge <?= $b['status']==='available'?'green':'blue' ?>"><?= e($b['status']) ?></span></td>
        <td><?= e($b['student_name'] ?? '—') ?></td>
        <td>
          <?php if ($b['status']==='available'): ?>
            <form method="post" style="display:flex;gap:6px">
              <?= csrf_field() ?><input type="hidden" name="action" value="allocate">
              <input type="hidden" name="bed_id" value="<?= (int)$b['id'] ?>">
              <select name="student_id" required style="margin:0;max-width:180px">
                <option value="">Select student…</option>
                <?php foreach ($students as $s): ?>
                  <option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-primary btn-sm">Allocate</button>
            </form>
          <?php else: ?>
            <form method="post"><?= csrf_field() ?>
              <input type="hidden" name="action" value="vacate">
              <input type="hidden" name="bed_id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-danger btn-sm" data-confirm="Vacate this bed?">Vacate</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody>
  </table>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

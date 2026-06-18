<?php
$page_title = 'Student';
require_once __DIR__ . '/../includes/header.php';
require_role('pg_owner');
$oid = current_owner_id();

$id = (int)($_GET['id'] ?? 0);
$s = [
  'full_name'=>'','mobile'=>'','parent_name'=>'','parent_mobile'=>'','email'=>'',
  'address'=>'','college_company'=>'','id_proof_type'=>'','id_proof_number'=>'',
  'joining_date'=>date('Y-m-d'),'leaving_date'=>'','security_deposit'=>'',
  'monthly_rent'=>'','status'=>'active','photo'=>'','aadhaar_doc'=>'','id_proof_doc'=>'',
];
if ($id) {
    $st = db()->prepare("SELECT * FROM students WHERE id=? AND owner_id=?");
    $st->execute([$id, $oid]);
    $found = $st->fetch();
    if (!$found) { http_response_code(404); exit('Not found'); }
    $s = array_merge($s, $found);
}

function save_upload(string $field): ?string {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg','jpeg','png','pdf','webp'];
    if (!in_array(strtolower($ext), $allowed, true)) return null;
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    move_uploaded_file($_FILES[$field]['tmp_name'], "$dir/$name");
    return 'uploads/' . $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $f = fn($k) => trim($_POST[$k] ?? '');
    $data = [
      'full_name'=>$f('full_name'),'mobile'=>$f('mobile'),'parent_name'=>$f('parent_name'),
      'parent_mobile'=>$f('parent_mobile'),'email'=>$f('email'),'address'=>$f('address'),
      'college_company'=>$f('college_company'),'id_proof_type'=>$f('id_proof_type'),
      'id_proof_number'=>$f('id_proof_number'),'joining_date'=>$f('joining_date') ?: null,
      'leaving_date'=>$f('leaving_date') ?: null,
      'security_deposit'=>(float)$f('security_deposit'),'monthly_rent'=>(float)$f('monthly_rent'),
      'status'=>in_array($f('status'),['active','left'],true)?$f('status'):'active',
    ];
    $photo  = save_upload('photo')      ?? ($s['photo'] ?: null);
    $aadh   = save_upload('aadhaar_doc')?? ($s['aadhaar_doc'] ?: null);
    $idp    = save_upload('id_proof_doc')?? ($s['id_proof_doc'] ?: null);

    if ($id) {
        $sql = "UPDATE students SET full_name=?,mobile=?,parent_name=?,parent_mobile=?,email=?,
          address=?,college_company=?,id_proof_type=?,id_proof_number=?,joining_date=?,leaving_date=?,
          security_deposit=?,monthly_rent=?,status=?,photo=?,aadhaar_doc=?,id_proof_doc=?
          WHERE id=? AND owner_id=?";
        db()->prepare($sql)->execute([...array_values($data),$photo,$aadh,$idp,$id,$oid]);
        log_activity("update student #$id");
        flash('Student updated.');
    } else {
        $sql = "INSERT INTO students (owner_id,full_name,mobile,parent_name,parent_mobile,email,
          address,college_company,id_proof_type,id_proof_number,joining_date,leaving_date,
          security_deposit,monthly_rent,status,photo,aadhaar_doc,id_proof_doc)
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        db()->prepare($sql)->execute([$oid,...array_values($data),$photo,$aadh,$idp]);
        log_activity("add student");
        flash('Student added.');
    }
    header('Location: /students/index.php');
    exit;
}
?>
<div class="panel"><div class="panel-head"><h3><?= $id?'Edit':'Add' ?> Student</h3></div>
<div class="panel-body">
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="full">Full Name <input name="full_name" required value="<?= e($s['full_name']) ?>"></label>
    <label>Mobile <input name="mobile" value="<?= e($s['mobile']) ?>"></label>
    <label>Email <input type="email" name="email" value="<?= e($s['email']) ?>"></label>
    <label>Parent Name <input name="parent_name" value="<?= e($s['parent_name']) ?>"></label>
    <label>Parent Mobile <input name="parent_mobile" value="<?= e($s['parent_mobile']) ?>"></label>
    <label class="full">Address <textarea name="address" rows="2"><?= e($s['address']) ?></textarea></label>
    <label>College / Company <input name="college_company" value="<?= e($s['college_company']) ?>"></label>
    <label>ID Proof Type <input name="id_proof_type" value="<?= e($s['id_proof_type']) ?>" placeholder="Aadhaar / PAN"></label>
    <label>ID Proof Number <input name="id_proof_number" value="<?= e($s['id_proof_number']) ?>"></label>
    <label>Joining Date <input type="date" name="joining_date" value="<?= e($s['joining_date']) ?>"></label>
    <label>Leaving Date <input type="date" name="leaving_date" value="<?= e($s['leaving_date']) ?>"></label>
    <label>Security Deposit <input type="number" step="0.01" name="security_deposit" value="<?= e((string)$s['security_deposit']) ?>"></label>
    <label>Monthly Rent <input type="number" step="0.01" name="monthly_rent" value="<?= e((string)$s['monthly_rent']) ?>"></label>
    <label>Status
      <select name="status">
        <option value="active" <?= $s['status']==='active'?'selected':'' ?>>Active</option>
        <option value="left" <?= $s['status']==='left'?'selected':'' ?>>Left</option>
      </select>
    </label>
    <label>Photo <input type="file" name="photo" accept="image/*"></label>
    <label>Aadhaar Card <input type="file" name="aadhaar_doc"></label>
    <label>ID Proof Doc <input type="file" name="id_proof_doc"></label>
  </div>
  <div style="margin-top:14px; display:flex; gap:10px">
    <button class="btn btn-primary">Save Student</button>
    <a class="btn btn-ghost" href="/students/index.php">Cancel</a>
  </div>
</form>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

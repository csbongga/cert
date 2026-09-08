<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

/* ---------- อัปโหลดเทมเพลตใหม่ ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    if ($name === '') $name = 'เทมเพลตใหม่';

    if (empty($_FILES['template']['name']) || $_FILES['template']['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'กรุณาเลือกไฟล์ภาพ');
        redirect('/admin/templates.php');
    }
    $tmp  = $_FILES['template']['tmp_name'];
    $info = getimagesize($tmp);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
    if (!$info || !isset($allowed[$info[2]])) {
        flash('error', 'ไฟล์ต้องเป็น JPG หรือ PNG เท่านั้น');
        redirect('/admin/templates.php');
    }
    if (!is_dir(TEMPLATE_DIR)) @mkdir(TEMPLATE_DIR, 0775, true);
    $newName = 'tpl_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $allowed[$info[2]];
    if (move_uploaded_file($tmp, TEMPLATE_DIR . '/' . $newName)) {
        $ins = db()->prepare('INSERT INTO templates (name, filename) VALUES (?, ?)');
        $ins->execute([$name, $newName]);
        flash('success', 'อัปโหลดเทมเพลตเรียบร้อย');
    } else {
        flash('error', 'อัปโหลดไฟล์ไม่สำเร็จ');
    }
    redirect('/admin/templates.php');
}

/* ---------- เปลี่ยนชื่อเทมเพลต ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rename') {
    verify_csrf();
    $tid  = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    if ($name !== '') {
        db()->prepare('UPDATE templates SET name = ? WHERE id = ?')->execute([$name, $tid]);
        flash('success', 'เปลี่ยนชื่อเรียบร้อย');
    }
    redirect('/admin/templates.php');
}

/* ---------- ลบเทมเพลต ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $tid = (int)$_POST['id'];
    $s = db()->prepare('SELECT * FROM templates WHERE id = ?');
    $s->execute([$tid]);
    $tpl = $s->fetch();
    if ($tpl) {
        // ตรวจว่ามีหลักสูตรใช้อยู่หรือไม่
        $u = db()->prepare('SELECT COUNT(*) c FROM courses WHERE template_image = ?');
        $u->execute([$tpl['filename']]);
        if ((int)$u->fetch()['c'] > 0) {
            flash('error', 'ลบไม่ได้ เพราะมีหลักสูตรใช้เทมเพลตนี้อยู่ (เปลี่ยนเทมเพลตของหลักสูตรก่อน)');
        } else {
            db()->prepare('DELETE FROM templates WHERE id = ?')->execute([$tid]);
            if (is_file(TEMPLATE_DIR . '/' . $tpl['filename'])) {
                @unlink(TEMPLATE_DIR . '/' . $tpl['filename']);
            }
            flash('success', 'ลบเทมเพลตเรียบร้อย');
        }
    }
    redirect('/admin/templates.php');
}

$templates = db()->query(
    'SELECT t.*, (SELECT COUNT(*) FROM courses c WHERE c.template_image = t.filename) AS used
     FROM templates t ORDER BY t.created_at DESC'
)->fetchAll();

$pageTitle = 'คลังเทมเพลต';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
  <h1 class="page-title" style="margin:0">คลังเทมเพลตใบประกาศ</h1>
  <a class="btn-ghost" href="<?= url('/admin/index.php') ?>">← แผงควบคุม</a>
</div>
<p class="page-sub">อัปโหลดเทมเพลตเก็บไว้ในคลัง แล้วเลือกใช้กับหลักสูตรใดก็ได้ (แนะนำภาพแนวนอนสัดส่วน A4 เช่น 3508×2480px เพื่อความคมชัด)</p>

<div class="form-card wide" style="margin-left:0">
  <h3>➕ อัปโหลดเทมเพลตใหม่</h3>
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="upload">
    <div class="field">
      <label>ชื่อเทมเพลต</label>
      <input type="text" name="name" placeholder="เช่น เทมเพลตคณะวิศวกรรมศาสตร์" required>
    </div>
    <div class="field">
      <label>ไฟล์ภาพ (JPG/PNG)</label>
      <input type="file" name="template" accept="image/jpeg,image/png" required>
    </div>
    <button class="btn" type="submit">อัปโหลด</button>
  </form>
</div>

<h3 class="mt">เทมเพลตในคลัง (<?= count($templates) ?>)</h3>
<?php if (!$templates): ?>
  <p class="muted">ยังไม่มีเทมเพลต อัปโหลดอันแรกด้านบนได้เลย</p>
<?php else: ?>
<div class="grid">
  <?php foreach ($templates as $t): ?>
    <div class="card">
      <div class="course-thumb" style="height:150px;background-image:url('<?= e(url('/uploads/templates/' . rawurlencode($t['filename']))) ?>')"></div>
      <div class="card-body">
        <form method="post" style="display:flex;gap:8px;margin-bottom:10px">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="rename">
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <input type="text" name="name" value="<?= e($t['name']) ?>" style="flex:1">
          <button class="btn-ghost" type="submit">บันทึกชื่อ</button>
        </form>
        <div class="toolbar" style="margin:0">
          <?php if ($t['used'] > 0): ?>
            <span class="badge badge-ok">ใช้อยู่ <?= (int)$t['used'] ?> หลักสูตร</span>
          <?php else: ?>
            <span class="badge badge-muted">ยังไม่ถูกใช้</span>
          <?php endif; ?>
          <form method="post" onsubmit="return confirm('ลบเทมเพลตนี้?')" style="margin:0">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
            <button class="btn btn-danger" type="submit" <?= $t['used'] > 0 ? 'disabled title="มีหลักสูตรใช้อยู่"' : '' ?>>ลบ</button>
          </form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>

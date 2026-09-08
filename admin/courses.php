<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// ---------- ลบหลักสูตร ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $del = (int)$_POST['id'];
    db()->prepare('DELETE FROM courses WHERE id = ?')->execute([$del]);
    flash('success', 'ลบหลักสูตรเรียบร้อย');
    redirect('/admin/courses.php');
}

// ---------- เพิ่มหลักสูตรใหม่ ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    verify_csrf();
    $title = trim($_POST['title'] ?? '');
    if ($title === '') {
        flash('error', 'กรุณากรอกชื่อหลักสูตร');
    } else {
        $ins = db()->prepare(
            'INSERT INTO courses (title, description, pass_score, quiz_count) VALUES (?, ?, 7, 10)'
        );
        $ins->execute([$title, trim($_POST['description'] ?? '')]);
        $newId = (int)db()->lastInsertId();
        flash('success', 'สร้างหลักสูตรแล้ว ตั้งค่าเทมเพลตและเพิ่มข้อสอบได้เลย');
        redirect('/admin/course_edit.php?id=' . $newId);
    }
}

$courses = db()->query(
    'SELECT c.*, (SELECT COUNT(*) FROM questions q WHERE q.course_id=c.id) qn
     FROM courses c ORDER BY c.created_at DESC'
)->fetchAll();

$pageTitle = 'จัดการหลักสูตร';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
  <h1 class="page-title" style="margin:0">จัดการหลักสูตร</h1>
  <a class="btn-ghost" href="<?= url('/admin/index.php') ?>">← แผงควบคุม</a>
</div>

<div class="form-card wide" style="margin-left:0">
  <h3>➕ เพิ่มหลักสูตรใหม่</h3>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="field">
      <label>ชื่อหลักสูตร / หัวข้ออบรม</label>
      <input type="text" name="title" required placeholder="เช่น ความปลอดภัยในการทำงาน">
    </div>
    <div class="field">
      <label>รายละเอียด (ไม่บังคับ)</label>
      <textarea name="description"></textarea>
    </div>
    <button class="btn" type="submit">สร้างหลักสูตร</button>
  </form>
</div>

<h3 class="mt">หลักสูตรทั้งหมด</h3>
<?php if (!$courses): ?>
  <p class="muted">ยังไม่มีหลักสูตร</p>
<?php else: ?>
<table class="data">
  <thead><tr><th>ชื่อหลักสูตร</th><th>ข้อสอบ</th><th>เกณฑ์</th><th>เทมเพลต</th><th>สถานะ</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($courses as $c): ?>
    <tr>
      <td><?= e($c['title']) ?></td>
      <td><?= (int)$c['qn'] ?> ข้อ</td>
      <td><?= (int)$c['pass_score'] ?>/<?= (int)$c['quiz_count'] ?></td>
      <td><?= $c['template_image'] ? '✅' : '<span class="muted">ยังไม่ตั้ง</span>' ?></td>
      <td><?= $c['is_active'] ? '<span class="badge badge-ok">เปิด</span>' : '<span class="badge badge-muted">ปิด</span>' ?></td>
      <td class="actions">
        <a class="btn-ghost" href="<?= url('/admin/questions.php?course=' . $c['id']) ?>">ข้อสอบ</a>
        <a class="btn-ghost" href="<?= url('/admin/course_edit.php?id=' . $c['id']) ?>">แก้ไข</a>
        <form method="post" onsubmit="return confirm('ลบหลักสูตรนี้และข้อสอบทั้งหมด?')" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <button class="btn btn-danger" type="submit">ลบ</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>

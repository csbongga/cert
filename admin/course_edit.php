<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$id = (int)($_GET['id'] ?? 0);
$s = db()->prepare('SELECT * FROM courses WHERE id = ?');
$s->execute([$id]);
$course = $s->fetch();
if (!$course) { die('ไม่พบหลักสูตร'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $title     = trim($_POST['title'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $pass      = max(0, (int)($_POST['pass_score'] ?? 7));
    $quiz      = max(1, (int)($_POST['quiz_count'] ?? 10));
    $nameTop   = (float)($_POST['name_top'] ?? 76);
    $nameSize  = max(8, (int)($_POST['name_size'] ?? 34));
    $courseTop = (float)($_POST['course_top'] ?? 113);
    $courseSize= max(8, (int)($_POST['course_size'] ?? 22));
    $detailText= trim($_POST['detail_text'] ?? '');
    $detailTop = (float)($_POST['detail_top'] ?? 133);
    $detailSize= max(8, (int)($_POST['detail_size'] ?? 15));
    $showMeta  = isset($_POST['show_meta']) ? 1 : 0;
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    if ($pass > $quiz) $pass = $quiz;

    // เลือกเทมเพลตจากคลัง
    $templateImage = $course['template_image'];
    $templateId = (int)($_POST['template_id'] ?? 0);
    if ($templateId > 0) {
        $ts = db()->prepare('SELECT filename FROM templates WHERE id = ?');
        $ts->execute([$templateId]);
        $row = $ts->fetch();
        $templateImage = $row ? $row['filename'] : $templateImage;
    } elseif ($templateId === 0 && isset($_POST['template_id'])) {
        $templateImage = null; // เลือก "ไม่ใช้เทมเพลต"
    }

    $upd = db()->prepare(
        'UPDATE courses SET title=?, description=?, pass_score=?, quiz_count=?,
             name_top=?, name_size=?, course_top=?, course_size=?,
             detail_text=?, detail_top=?, detail_size=?, show_meta=?,
             is_active=?, template_image=? WHERE id=?'
    );
    $upd->execute([$title, $desc, $pass, $quiz, $nameTop, $nameSize, $courseTop, $courseSize,
        $detailText, $detailTop, $detailSize, $showMeta, $isActive, $templateImage, $id]);

    flash('success', 'บันทึกการตั้งค่าเรียบร้อย');
    redirect('/admin/course_edit.php?id=' . $id);
}

$imgUrl = $course['template_image']
    ? url('/uploads/templates/' . rawurlencode($course['template_image'])) : '';

$allTemplates = db()->query('SELECT * FROM templates ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'แก้ไขหลักสูตร';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
  <h1 class="page-title" style="margin:0">แก้ไข: <?= e($course['title']) ?></h1>
  <div class="actions">
    <a class="btn-ghost" href="<?= url('/admin/questions.php?course=' . $id) ?>">📝 จัดการข้อสอบ</a>
    <a class="btn-ghost" href="<?= url('/admin/courses.php') ?>">← หลักสูตรทั้งหมด</a>
  </div>
</div>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row" style="align-items:flex-start">
    <div class="form-card wide" style="margin:0;flex:1.2">
      <h3>ข้อมูลหลักสูตร</h3>
      <div class="field">
        <label>ชื่อหลักสูตร</label>
        <input type="text" name="title" value="<?= e($course['title']) ?>" required>
      </div>
      <div class="field">
        <label>รายละเอียด</label>
        <textarea name="description"><?= e($course['description']) ?></textarea>
      </div>
      <div class="row">
        <div class="field">
          <label>จำนวนข้อที่สุ่มมาสอบ</label>
          <input type="number" name="quiz_count" min="1" value="<?= (int)$course['quiz_count'] ?>">
        </div>
        <div class="field">
          <label>เกณฑ์ผ่าน (จำนวนข้อ)</label>
          <input type="number" name="pass_score" min="0" value="<?= (int)$course['pass_score'] ?>">
        </div>
      </div>
      <div class="field">
        <label><input type="checkbox" name="is_active" <?= $course['is_active'] ? 'checked' : '' ?>> เปิดให้ผู้ใช้เห็นหลักสูตรนี้</label>
      </div>

      <hr style="border:none;border-top:1px solid var(--border);margin:20px 0">
      <div class="toolbar" style="margin-bottom:10px">
        <h3 style="margin:0">เลือกเทมเพลตใบประกาศ</h3>
        <a class="btn-ghost" href="<?= url('/admin/templates.php') ?>">+ อัปโหลด/จัดการคลังเทมเพลต</a>
      </div>
      <?php if (!$allTemplates): ?>
        <div class="alert alert-warn">ยังไม่มีเทมเพลตในคลัง — <a href="<?= url('/admin/templates.php') ?>">อัปโหลดก่อน</a></div>
      <?php else: ?>
        <div class="tpl-picker">
          <label class="tpl-opt">
            <input type="radio" name="template_id" value="0" <?= empty($course['template_image']) ? 'checked' : '' ?>>
            <span class="tpl-none">ไม่ใช้เทมเพลต</span>
          </label>
          <?php foreach ($allTemplates as $t): ?>
            <label class="tpl-opt">
              <input type="radio" name="template_id" value="<?= (int)$t['id'] ?>"
                     <?= $course['template_image'] === $t['filename'] ? 'checked' : '' ?>>
              <img src="<?= e(url('/uploads/templates/' . rawurlencode($t['filename']))) ?>" alt="">
              <span><?= e($t['name']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h3>ตำแหน่งข้อความบนใบประกาศ (มม.)</h3>
      <div class="row">
        <div class="field">
          <label>ชื่อผู้อบรม — ระยะจากบน</label>
          <input type="number" step="0.5" id="name_top" name="name_top" value="<?= e((string)$course['name_top']) ?>">
        </div>
        <div class="field">
          <label>ขนาดฟอนต์ชื่อ (pt)</label>
          <input type="number" id="name_size" name="name_size" value="<?= (int)$course['name_size'] ?>">
        </div>
      </div>
      <div class="row">
        <div class="field">
          <label>ชื่อหลักสูตร — ระยะจากบน</label>
          <input type="number" step="0.5" id="course_top" name="course_top" value="<?= e((string)$course['course_top']) ?>">
        </div>
        <div class="field">
          <label>ขนาดฟอนต์หลักสูตร (pt)</label>
          <input type="number" id="course_size" name="course_size" value="<?= (int)$course['course_size'] ?>">
        </div>
      </div>

      <div class="field">
        <label>รายละเอียดการอบรม (วันที่/สถานที่ — บรรทัดเดียว)</label>
        <input type="text" id="detail_text" name="detail_text" maxlength="255"
               value="<?= e($course['detail_text'] ?? '') ?>"
               placeholder="เช่น วันที่ 10 กันยายน 2568 ณ ห้องประชุมคณะวิศวกรรมศาสตร์">
        <div class="help">เว้นว่างไว้ = ไม่แสดงบรรทัดนี้บนใบประกาศ</div>
      </div>
      <div class="row">
        <div class="field">
          <label>รายละเอียด — ระยะจากบน</label>
          <input type="number" step="0.5" id="detail_top" name="detail_top" value="<?= e((string)($course['detail_top'] ?? 133)) ?>">
        </div>
        <div class="field">
          <label>ขนาดฟอนต์รายละเอียด (pt)</label>
          <input type="number" id="detail_size" name="detail_size" value="<?= (int)($course['detail_size'] ?? 15) ?>">
        </div>
      </div>

      <div class="field">
        <label><input type="checkbox" name="show_meta" <?= $course['show_meta'] ? 'checked' : '' ?>> แสดงเลขที่ใบประกาศ + วันที่ด้านล่าง</label>
      </div>
      <button class="btn" type="submit">💾 บันทึก</button>
    </div>

    <div style="flex:1;min-width:300px">
      <h3>ตัวอย่าง</h3>
      <div id="preview" style="position:relative;width:100%;border:1px solid var(--border);border-radius:10px;overflow:hidden;<?= $imgUrl ? '' : 'display:none' ?>">
        <img id="pvImg" src="<?= e($imgUrl) ?>" style="display:block;width:100%">
        <div id="pvName" style="position:absolute;left:0;width:100%;text-align:center;font-weight:bold;color:#1f2937">ชื่อ นามสกุล</div>
        <div id="pvCourse" style="position:absolute;left:0;width:100%;text-align:center;color:#374151"><?= e($course['title']) ?></div>
        <div id="pvDetail" style="position:absolute;left:0;width:100%;text-align:center;color:#4b5563"><?= e($course['detail_text'] ?? '') ?></div>
      </div>
      <div id="previewEmpty" class="alert alert-warn" style="<?= $imgUrl ? 'display:none' : '' ?>">เลือกเทมเพลตจากคลังเพื่อดูตัวอย่าง</div>
      <p class="help">เลือกเทมเพลต + ปรับตัวเลขด้านซ้าย แล้วดูตำแหน่งได้ทันที (ค่าจริงจะบันทึกเมื่อกดบันทึก)</p>
    </div>
  </div>
</form>

<script>
// พรีวิวตำแหน่ง + สลับเทมเพลตแบบสด
(function () {
  var box = document.getElementById('preview');
  var empty = document.getElementById('previewEmpty');
  var pvImg = document.getElementById('pvImg');
  if (!box) return;
  function pt2px(pt) { return box.clientWidth / 297 * (pt * 0.3528); } // 1pt=0.3528mm
  function upd() {
    if (box.style.display === 'none') return;
    var nt = parseFloat(document.getElementById('name_top').value) || 0;
    var ns = parseFloat(document.getElementById('name_size').value) || 20;
    var ct = parseFloat(document.getElementById('course_top').value) || 0;
    var cs = parseFloat(document.getElementById('course_size').value) || 16;
    var dt = parseFloat(document.getElementById('detail_top').value) || 0;
    var ds = parseFloat(document.getElementById('detail_size').value) || 14;
    var name = document.getElementById('pvName'), crs = document.getElementById('pvCourse');
    var det = document.getElementById('pvDetail');
    name.style.top = (nt / 210 * 100) + '%'; name.style.fontSize = pt2px(ns) + 'px';
    crs.style.top  = (ct / 210 * 100) + '%'; crs.style.fontSize  = pt2px(cs) + 'px';
    det.style.top  = (dt / 210 * 100) + '%'; det.style.fontSize  = pt2px(ds) + 'px';
    det.textContent = document.getElementById('detail_text').value;
  }
  ['name_top','name_size','course_top','course_size','detail_top','detail_size','detail_text'].forEach(function (id) {
    document.getElementById(id).addEventListener('input', upd);
  });
  // สลับภาพเทมเพลตเมื่อเลือก radio
  document.querySelectorAll('input[name="template_id"]').forEach(function (r) {
    r.addEventListener('change', function () {
      if (r.value === '0') {
        box.style.display = 'none';
        if (empty) empty.style.display = '';
      } else {
        var img = r.closest('.tpl-opt').querySelector('img');
        if (img) pvImg.src = img.src;
        box.style.display = '';
        if (empty) empty.style.display = 'none';
      }
    });
  });
  pvImg.addEventListener('load', upd);
  window.addEventListener('load', upd); upd();
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>

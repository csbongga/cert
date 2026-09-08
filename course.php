<?php
require_once __DIR__ . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM courses WHERE id = ? AND is_active = 1');
$stmt->execute([$id]);
$course = $stmt->fetch();
if (!$course) {
    http_response_code(404);
    $pageTitle = 'ไม่พบหลักสูตร';
    require __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-error">ไม่พบหลักสูตรที่ต้องการ</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$qCount = (int)db()->query(
    'SELECT COUNT(*) c FROM questions WHERE course_id = ' . (int)$id
)->fetch()['c'];

// สถานะของผู้ใช้
$cert = null; $bestAttempt = null;
if (is_logged_in()) {
    $uid = current_user()['id'];
    $s = db()->prepare('SELECT * FROM certificates WHERE user_id = ? AND course_id = ?');
    $s->execute([$uid, $id]);
    $cert = $s->fetch() ?: null;

    $s = db()->prepare(
        'SELECT * FROM attempts WHERE user_id = ? AND course_id = ? ORDER BY score DESC, created_at DESC LIMIT 1'
    );
    $s->execute([$uid, $id]);
    $bestAttempt = $s->fetch() ?: null;
}

$pageTitle = $course['title'];
require __DIR__ . '/includes/header.php';
?>
<div class="card">
  <?php if ($course['template_image']): ?>
    <div class="course-thumb" style="height:220px;background-image:url('<?= e(url('/uploads/templates/' . rawurlencode($course['template_image']))) ?>')"></div>
  <?php endif; ?>
  <div class="card-body">
    <h1 class="page-title"><?= e($course['title']) ?></h1>
    <p class="muted" style="white-space:pre-line"><?= e($course['description']) ?></p>
    <hr style="border:none;border-top:1px solid var(--border);margin:20px 0">
    <p>📝 แบบทดสอบ <b><?= min($qCount, (int)$course['quiz_count']) ?> ข้อ</b>
       · เกณฑ์ผ่าน <b><?= (int)$course['pass_score'] ?> ข้อ</b></p>

    <?php if (!is_logged_in()): ?>
      <div class="alert alert-info">กรุณา <a href="<?= url('/login.php') ?>">เข้าสู่ระบบ</a>
        หรือ <a href="<?= url('/register.php') ?>">สมัครสมาชิก</a> เพื่อทำแบบทดสอบ</div>

    <?php elseif ($cert): ?>
      <div class="alert alert-success">🎉 คุณผ่านหลักสูตรนี้แล้ว (เลขที่ใบประกาศ <?= e($cert['cert_code']) ?>)</div>
      <a class="btn btn-lg" href="<?= url('/certificate.php?course=' . $id) ?>">⬇️ ดาวน์โหลดใบประกาศ (PDF)</a>

    <?php elseif ($qCount === 0): ?>
      <div class="alert alert-warn">หลักสูตรนี้ยังไม่มีคำถาม โปรดรอผู้ดูแลเพิ่มข้อสอบ</div>

    <?php else: ?>
      <?php if ($bestAttempt): ?>
        <div class="alert alert-warn">ครั้งที่ผ่านมาได้ <?= (int)$bestAttempt['score'] ?>/<?= (int)$bestAttempt['total'] ?> ข้อ ยังไม่ผ่านเกณฑ์ ลองอีกครั้งได้</div>
      <?php endif; ?>
      <a class="btn btn-lg" href="<?= url('/quiz.php?course=' . $id) ?>">▶️ เริ่มทำแบบทดสอบ</a>
    <?php endif; ?>
  </div>
</div>
<p class="mt"><a href="<?= url('/index.php') ?>">← กลับไปหน้าหลักสูตร</a></p>
<?php require __DIR__ . '/includes/footer.php'; ?>

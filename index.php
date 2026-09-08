<?php
require_once __DIR__ . '/includes/functions.php';

$courses = db()->query(
    'SELECT c.*,
            (SELECT COUNT(*) FROM questions q WHERE q.course_id = c.id) AS q_count
     FROM courses c
     WHERE c.is_active = 1
     ORDER BY c.created_at DESC'
)->fetchAll();

// สถานะของผู้ใช้ปัจจุบันต่อแต่ละคอร์ส
$passedCourses = [];
if (is_logged_in()) {
    $stmt = db()->prepare('SELECT course_id FROM certificates WHERE user_id = ?');
    $stmt->execute([current_user()['id']]);
    $passedCourses = array_column($stmt->fetchAll(), 'course_id');
}

// สถิติสำหรับ Hero
$statCourses = count($courses);
$statCerts   = (int)db()->query('SELECT COUNT(*) c FROM certificates')->fetch()['c'];
$statUsers   = (int)db()->query('SELECT COUNT(*) c FROM users WHERE role = "user"')->fetch()['c'];

$pageTitle = 'หลักสูตรทั้งหมด';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <h1>เรียนรู้ ทำแบบทดสอบ<br>รับใบประกาศออนไลน์ทันที</h1>
  <p>สมัครสมาชิก เลือกหลักสูตรที่สนใจ ทำแบบทดสอบให้ผ่านเกณฑ์ แล้วดาวน์โหลดใบประกาศเป็นไฟล์ PDF ได้ทันที</p>
  <div class="actions">
    <?php if (is_logged_in()): ?>
      <a class="btn btn-light btn-lg" href="#courses">เลือกหลักสูตร</a>
      <a class="btn-ghost" href="<?= url('/dashboard.php') ?>">ใบประกาศของฉัน</a>
    <?php else: ?>
      <a class="btn btn-light btn-lg" href="<?= url('/register.php') ?>">สมัครสมาชิกฟรี</a>
      <a class="btn-ghost" href="<?= url('/login.php') ?>">เข้าสู่ระบบ</a>
    <?php endif; ?>
  </div>
  <div class="hero-stats">
    <div class="hero-stat"><b><?= $statCourses ?></b><span>หลักสูตรเปิดอบรม</span></div>
    <div class="hero-stat"><b><?= $statCerts ?></b><span>ใบประกาศที่ออกแล้ว</span></div>
    <div class="hero-stat"><b><?= $statUsers ?></b><span>ผู้เข้าอบรม</span></div>
  </div>
</section>

<div class="toolbar" id="courses">
  <h2 class="page-title" style="margin:0">หลักสูตรอบรม</h2>
</div>
<p class="page-sub">เลือกหลักสูตร ทำแบบทดสอบให้ผ่านเกณฑ์ แล้วดาวน์โหลดใบประกาศได้ทันที</p>

<?php if (!$courses): ?>
  <div class="alert alert-info">ยังไม่มีหลักสูตรในระบบ</div>
<?php else: ?>
<div class="grid">
  <?php foreach ($courses as $c): ?>
    <?php
      $thumb = $c['template_image']
        ? url('/uploads/templates/' . rawurlencode($c['template_image']))
        : null;
      $done = in_array($c['id'], $passedCourses);
    ?>
    <div class="card">
      <?php if ($thumb): ?>
        <a href="<?= url('/course.php?id=' . $c['id']) ?>" class="course-thumb"
           style="background-image:url('<?= e($thumb) ?>')"></a>
      <?php endif; ?>
      <div class="card-body">
        <h3><?= e($c['title']) ?></h3>
        <p><?= e(mb_strimwidth((string)$c['description'], 0, 100, '…')) ?></p>
        <div class="toolbar">
          <span class="muted">📝 <?= (int)$c['q_count'] ?> ข้อ · ผ่าน <?= (int)$c['pass_score'] ?>/<?= (int)$c['quiz_count'] ?></span>
          <?php if ($done): ?><span class="badge badge-ok">ได้รับใบประกาศแล้ว</span><?php endif; ?>
        </div>
        <a class="btn btn-block mt" href="<?= url('/course.php?id=' . $c['id']) ?>">
          <?= $done ? 'ดูรายละเอียด' : 'เริ่มเรียน / ทำแบบทดสอบ' ?>
        </a>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

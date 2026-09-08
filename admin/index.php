<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$stats = [
    'courses'  => (int)db()->query('SELECT COUNT(*) c FROM courses')->fetch()['c'],
    'users'    => (int)db()->query('SELECT COUNT(*) c FROM users WHERE role="user"')->fetch()['c'],
    'certs'    => (int)db()->query('SELECT COUNT(*) c FROM certificates')->fetch()['c'],
    'attempts' => (int)db()->query('SELECT COUNT(*) c FROM attempts')->fetch()['c'],
];

$pageTitle = 'จัดการระบบ';
require __DIR__ . '/../includes/header.php';
?>
<h1 class="page-title">แผงควบคุมผู้ดูแลระบบ</h1>
<p class="page-sub">จัดการหลักสูตร ข้อสอบ และดูสถิติการออกใบประกาศ</p>

<div class="stat-grid">
  <div class="card"><div class="card-body"><h3><?= $stats['courses'] ?></h3><p>หลักสูตร</p></div></div>
  <div class="card"><div class="card-body"><h3><?= $stats['users'] ?></h3><p>ผู้อบรม</p></div></div>
  <div class="card"><div class="card-body"><h3><?= $stats['certs'] ?></h3><p>ใบประกาศที่ออกแล้ว</p></div></div>
  <div class="card"><div class="card-body"><h3><?= $stats['attempts'] ?></h3><p>ครั้งที่ทำแบบทดสอบ</p></div></div>
</div>

<div class="toolbar mt">
  <h3 style="margin:0">การจัดการ</h3>
</div>
<div class="actions">
  <a class="btn" href="<?= url('/admin/courses.php') ?>">📚 จัดการหลักสูตร &amp; ข้อสอบ</a>
  <a class="btn-ghost" href="<?= url('/admin/templates.php') ?>">🖼️ คลังเทมเพลต</a>
  <a class="btn-ghost" href="<?= url('/admin/certificates.php') ?>">🎓 รายการใบประกาศที่ออก</a>
  <a class="btn-ghost" href="<?= url('/index.php') ?>">🌐 ดูหน้าเว็บผู้ใช้</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$uid = current_user()['id'];

$certs = db()->prepare(
    'SELECT ce.*, c.title FROM certificates ce
     JOIN courses c ON c.id = ce.course_id
     WHERE ce.user_id = ? ORDER BY ce.issued_at DESC'
);
$certs->execute([$uid]);
$certs = $certs->fetchAll();

$attempts = db()->prepare(
    'SELECT a.*, c.title FROM attempts a
     JOIN courses c ON c.id = a.course_id
     WHERE a.user_id = ? ORDER BY a.created_at DESC LIMIT 20'
);
$attempts->execute([$uid]);
$attempts = $attempts->fetchAll();

$pageTitle = 'ใบประกาศของฉัน';
require __DIR__ . '/includes/header.php';
?>
<h1 class="page-title">ใบประกาศของฉัน</h1>
<p class="page-sub">รวมใบประกาศที่ได้รับและประวัติการทำแบบทดสอบ</p>

<h3>🎓 ใบประกาศที่ได้รับ</h3>
<?php if (!$certs): ?>
  <div class="alert alert-info">ยังไม่มีใบประกาศ — <a href="<?= url('/index.php') ?>">เลือกหลักสูตรเพื่อเริ่มอบรม</a></div>
<?php else: ?>
  <table class="data">
    <thead><tr><th>หลักสูตร</th><th>เลขที่</th><th>วันที่ออก</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($certs as $c): ?>
      <tr>
        <td><?= e($c['title']) ?></td>
        <td><?= e($c['cert_code']) ?></td>
        <td><?= e(thai_date($c['issued_at'])) ?></td>
        <td><a class="btn" href="<?= url('/certificate.php?course=' . $c['course_id']) ?>">⬇️ PDF</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<h3 class="mt">📊 ประวัติการทำแบบทดสอบ</h3>
<?php if (!$attempts): ?>
  <p class="muted">ยังไม่มีประวัติ</p>
<?php else: ?>
  <table class="data">
    <thead><tr><th>หลักสูตร</th><th>คะแนน</th><th>ผล</th><th>วันที่</th></tr></thead>
    <tbody>
    <?php foreach ($attempts as $a): ?>
      <tr>
        <td><?= e($a['title']) ?></td>
        <td><?= (int)$a['score'] ?>/<?= (int)$a['total'] ?></td>
        <td><?= $a['passed'] ? '<span class="badge badge-ok">ผ่าน</span>' : '<span class="badge badge-muted">ไม่ผ่าน</span>' ?></td>
        <td><?= e(thai_date($a['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

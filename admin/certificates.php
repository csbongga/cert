<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$rows = db()->query(
    'SELECT ce.*, u.name AS uname, u.email, c.title
     FROM certificates ce
     JOIN users u ON u.id = ce.user_id
     JOIN courses c ON c.id = ce.course_id
     ORDER BY ce.issued_at DESC'
)->fetchAll();

$pageTitle = 'ใบประกาศที่ออกแล้ว';
require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
  <h1 class="page-title" style="margin:0">ใบประกาศที่ออกแล้ว</h1>
  <a class="btn-ghost" href="<?= url('/admin/index.php') ?>">← แผงควบคุม</a>
</div>

<?php if (!$rows): ?>
  <p class="muted">ยังไม่มีการออกใบประกาศ</p>
<?php else: ?>
<table class="data">
  <thead><tr><th>เลขที่</th><th>ผู้อบรม</th><th>อีเมล</th><th>หลักสูตร</th><th>วันที่ออก</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['cert_code']) ?></td>
      <td><?= e($r['uname']) ?></td>
      <td><?= e($r['email']) ?></td>
      <td><?= e($r['title']) ?></td>
      <td><?= e(thai_date($r['issued_at'])) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/functions.php';
$u = current_user();
$pageTitle = $pageTitle ?? SITE_NAME;
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= url('/assets/style.css') ?>">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="<?= url('/index.php') ?>">🎓 <?= e(SITE_NAME) ?></a>
    <nav class="nav-links">
      <a href="<?= url('/index.php') ?>">หลักสูตร</a>
      <?php if ($u): ?>
        <a href="<?= url('/dashboard.php') ?>">ใบประกาศของฉัน</a>
        <?php if ($u['role'] === 'admin'): ?>
          <a href="<?= url('/admin/index.php') ?>">จัดการระบบ</a>
        <?php endif; ?>
        <span class="nav-user">👤 <?= e($u['name']) ?></span>
        <a class="btn-ghost" href="<?= url('/logout.php') ?>">ออกจากระบบ</a>
      <?php else: ?>
        <a href="<?= url('/login.php') ?>">เข้าสู่ระบบ</a>
        <a class="btn-primary" href="<?= url('/register.php') ?>">สมัครสมาชิก</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
<?php foreach (get_flashes() as $f): ?>
  <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>

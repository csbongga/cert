<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) redirect('/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $email = trim(mb_strtolower($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';
    set_old(['name' => $name, 'email' => $email]);

    $errors = [];
    if ($name === '') $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'อีเมลไม่ถูกต้อง';
    if (mb_strlen($pass) < 6) $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
    if ($pass !== $pass2) $errors[] = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'อีเมลนี้ถูกใช้สมัครแล้ว';
        }
    }

    if (!$errors) {
        $ins = db()->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "user")'
        );
        $ins->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $_SESSION['user_id'] = (int)db()->lastInsertId();
        clear_old();
        flash('success', 'สมัครสมาชิกสำเร็จ ยินดีต้อนรับ!');
        redirect('/index.php');
    }
    foreach ($errors as $er) flash('error', $er);
}

$pageTitle = 'สมัครสมาชิก';
require __DIR__ . '/includes/header.php';
?>
<div class="form-card">
  <h1 class="page-title">สมัครสมาชิก</h1>
  <p class="page-sub">สร้างบัญชีเพื่อเข้าอบรมและรับใบประกาศ</p>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label>ชื่อ-นามสกุล</label>
      <input type="text" name="name" value="<?= old('name') ?>" required>
      <div class="help">ชื่อนี้จะปรากฏบนใบประกาศ</div>
    </div>
    <div class="field">
      <label>อีเมล</label>
      <input type="email" name="email" value="<?= old('email') ?>" required>
    </div>
    <div class="field">
      <label>รหัสผ่าน</label>
      <input type="password" name="password" required>
    </div>
    <div class="field">
      <label>ยืนยันรหัสผ่าน</label>
      <input type="password" name="password2" required>
    </div>
    <button class="btn btn-block" type="submit">สมัครสมาชิก</button>
  </form>
  <p class="text-center mt muted">มีบัญชีแล้ว? <a href="<?= url('/login.php') ?>">เข้าสู่ระบบ</a></p>
</div>
<?php clear_old(); require __DIR__ . '/includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) redirect('/index.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim(mb_strtolower($_POST['email'] ?? ''));
    $pass  = $_POST['password'] ?? '';
    set_old(['email' => $email]);

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        clear_old();
        flash('success', 'เข้าสู่ระบบสำเร็จ');
        redirect($user['role'] === 'admin' ? '/admin/index.php' : '/index.php');
    }
    flash('error', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');
}

$pageTitle = 'เข้าสู่ระบบ';
require __DIR__ . '/includes/header.php';
?>
<div class="form-card">
  <h1 class="page-title">เข้าสู่ระบบ</h1>
  <p class="page-sub">เข้าสู่ระบบเพื่อทำแบบทดสอบและดาวน์โหลดใบประกาศ</p>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label>อีเมล</label>
      <input type="email" name="email" value="<?= old('email') ?>" required>
    </div>
    <div class="field">
      <label>รหัสผ่าน</label>
      <input type="password" name="password" required>
    </div>
    <button class="btn btn-block" type="submit">เข้าสู่ระบบ</button>
  </form>
  <p class="text-center mt muted">ยังไม่มีบัญชี? <a href="<?= url('/register.php') ?>">สมัครสมาชิก</a></p>
</div>
<?php clear_old(); require __DIR__ . '/includes/footer.php'; ?>

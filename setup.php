<?php
/**
 * สคริปต์ติดตั้งครั้งแรก (รันครั้งเดียว)
 * - สร้างบัญชีผู้ดูแลระบบ
 * - สร้างหลักสูตรตัวอย่าง + คำถามตัวอย่าง 10 ข้อ
 * - คัดลอกไฟล์ template.jpg ไปไว้ที่ uploads/templates
 *
 * เปิดผ่านเบราว์เซอร์: http://localhost/09%20ใบประกาศ/setup.php
 * ** เมื่อติดตั้งเสร็จแล้วควรลบไฟล์นี้ทิ้งเพื่อความปลอดภัย **
 */
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: text/html; charset=utf-8');
$log = [];

// เตรียมโฟลเดอร์อัปโหลด
foreach ([UPLOAD_DIR, TEMPLATE_DIR, CERT_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
        $log[] = "สร้างโฟลเดอร์: $dir";
    }
}

try {
    $pdo = db();
} catch (Throwable $e) {
    die('ยังเชื่อมต่อฐานข้อมูลไม่ได้ กรุณา Import database.sql ก่อน แล้วรันไฟล์นี้ใหม่<br>' . e($e->getMessage()));
}

// ---------- 1) บัญชีผู้ดูแลระบบ ----------
$adminEmail = 'admin@example.com';
$adminPass  = 'admin123';
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$adminEmail]);
if ($stmt->fetch()) {
    $log[] = "มีบัญชีผู้ดูแลระบบอยู่แล้ว ($adminEmail)";
} else {
    $ins = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "admin")'
    );
    $ins->execute(['ผู้ดูแลระบบ', $adminEmail, password_hash($adminPass, PASSWORD_DEFAULT)]);
    $log[] = "สร้างผู้ดูแลระบบ: <b>$adminEmail</b> / รหัสผ่าน: <b>$adminPass</b>";
}

// ---------- 2) คัดลอกเทมเพลตตัวอย่าง + เพิ่มเข้าคลังเทมเพลต ----------
$templateFile = null;
$src = __DIR__ . '/template.jpg';
if (is_file($src)) {
    $templateFile = 'template_sample.jpg';
    $dst = TEMPLATE_DIR . '/' . $templateFile;
    if (!is_file($dst)) {
        copy($src, $dst);
        $log[] = "คัดลอกเทมเพลตตัวอย่างไปที่ uploads/templates/$templateFile";
    }
    // ลงทะเบียนในคลังเทมเพลต (กันซ้ำด้วย UNIQUE filename)
    $t = $pdo->prepare('SELECT id FROM templates WHERE filename = ?');
    $t->execute([$templateFile]);
    if (!$t->fetch()) {
        $pdo->prepare('INSERT INTO templates (name, filename) VALUES (?, ?)')
            ->execute(['เทมเพลตตัวอย่าง (ม.อุบลฯ)', $templateFile]);
        $log[] = 'เพิ่มเทมเพลตตัวอย่างเข้าคลังเทมเพลต';
    }
}

// ---------- 3) หลักสูตรตัวอย่าง + คำถาม ----------
$stmt = $pdo->query('SELECT COUNT(*) AS c FROM courses');
if ((int)$stmt->fetch()['c'] === 0) {
    $ins = $pdo->prepare(
        'INSERT INTO courses (title, description, template_image, pass_score, quiz_count)
         VALUES (?, ?, ?, 7, 10)'
    );
    $ins->execute([
        'ความปลอดภัยในการทำงาน (ตัวอย่าง)',
        'หลักสูตรอบรมพื้นฐานด้านความปลอดภัย อาชีวอนามัย และสภาพแวดล้อมในการทำงาน',
        $templateFile,
    ]);
    $courseId = (int)$pdo->lastInsertId();

    $questions = [
        ['อุปกรณ์ใดต่อไปนี้เป็นอุปกรณ์ป้องกันอันตรายส่วนบุคคล (PPE)?',
            [['หมวกนิรภัย', 1], ['โทรศัพท์มือถือ', 0], ['กระเป๋าเอกสาร', 0], ['ปากกา', 0]]],
        ['เมื่อเกิดเพลิงไหม้เล็กน้อย ควรใช้อุปกรณ์ใดดับไฟเป็นอันดับแรก?',
            [['ถังดับเพลิง', 1], ['ผ้าห่ม', 0], ['น้ำเปล่า', 0], ['พัดลม', 0]]],
        ['สีของป้ายเตือนอันตรายโดยทั่วไปคือสีใด?',
            [['เหลือง', 1], ['ฟ้า', 0], ['ขาว', 0], ['ม่วง', 0]]],
        ['ก่อนใช้เครื่องจักร ควรทำสิ่งใดเป็นอันดับแรก?',
            [['ตรวจสอบความพร้อมของเครื่อง', 1], ['รีบเปิดใช้งานทันที', 0], ['ถอดการ์ดป้องกันออก', 0], ['เพิ่มความเร็วสูงสุด', 0]]],
        ['หากพบสายไฟชำรุด ควรทำอย่างไร?',
            [['แจ้งผู้ดูแลและงดใช้งาน', 1], ['ใช้ต่อไปตามปกติ', 0], ['ใช้เทปพันแล้วใช้ต่อ', 0], ['ดึงสายออกแรง ๆ', 0]]],
        ['การยกของหนักที่ถูกวิธีควรใช้ส่วนใดของร่างกายเป็นหลัก?',
            [['ขาและเข่า', 1], ['หลังส่วนล่าง', 0], ['คอ', 0], ['ข้อมือ', 0]]],
        ['เครื่องหมายทางออกฉุกเฉิน (Exit) มักเป็นสีใด?',
            [['เขียว', 1], ['แดง', 0], ['ดำ', 0], ['ชมพู', 0]]],
        ['สิ่งใดควรทำเมื่อได้ยินสัญญาณเตือนภัย?',
            [['อพยพตามเส้นทางหนีไฟ', 1], ['ทำงานต่อไป', 0], ['ปิดหูแล้วรอ', 0], ['วิ่งขึ้นชั้นบนสุด', 0]]],
        ['อุปกรณ์ป้องกันการได้ยินใช้ในสถานการณ์ใด?',
            [['พื้นที่ที่มีเสียงดังมาก', 1], ['พื้นที่มืด', 0], ['พื้นที่เปียก', 0], ['พื้นที่ร้อน', 0]]],
        ['การรายงานอุบัติเหตุในที่ทำงานมีความสำคัญเพราะเหตุใด?',
            [['เพื่อป้องกันไม่ให้เกิดซ้ำ', 1], ['เพื่อลงโทษพนักงาน', 0], ['ไม่มีความจำเป็น', 0], ['เพื่อความล่าช้า', 0]]],
    ];

    $qStmt = $pdo->prepare('INSERT INTO questions (course_id, question) VALUES (?, ?)');
    $cStmt = $pdo->prepare('INSERT INTO choices (question_id, choice_text, is_correct) VALUES (?, ?, ?)');
    foreach ($questions as $q) {
        $qStmt->execute([$courseId, $q[0]]);
        $qid = (int)$pdo->lastInsertId();
        foreach ($q[1] as $c) {
            $cStmt->execute([$qid, $c[0], $c[1]]);
        }
    }
    $log[] = 'สร้างหลักสูตรตัวอย่างพร้อมคำถาม 10 ข้อเรียบร้อย';
} else {
    $log[] = 'มีหลักสูตรอยู่แล้ว ข้ามการสร้างตัวอย่าง';
}
?>
<!doctype html>
<html lang="th">
<head><meta charset="utf-8"><title>ติดตั้งระบบ</title>
<style>body{font-family:system-ui,'Segoe UI',sans-serif;max-width:680px;margin:40px auto;padding:0 20px;line-height:1.7;color:#1f2937}
h1{color:#2563eb}li{margin:4px 0}.box{background:#f0fdf4;border:1px solid #86efac;padding:16px 20px;border-radius:12px}
a.btn{display:inline-block;margin-top:20px;background:#2563eb;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none}</style></head>
<body>
<h1>✅ ติดตั้งระบบเรียบร้อย</h1>
<div class="box"><ul>
<?php foreach ($log as $line): ?><li><?= $line ?></li><?php endforeach; ?>
</ul></div>
<p style="color:#b91c1c;margin-top:20px"><b>⚠️ เพื่อความปลอดภัย กรุณาลบไฟล์ setup.php ทิ้งหลังติดตั้งเสร็จ</b></p>
<a class="btn" href="<?= url('/index.php') ?>">ไปหน้าเว็บไซต์</a>
</body></html>

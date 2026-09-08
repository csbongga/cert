<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/lib/cert.php';
require_login();

$courseId = (int)($_GET['course'] ?? 0);
$user = current_user();

// ต้องมีใบประกาศ (ผ่านแล้ว) เท่านั้น
$s = db()->prepare('SELECT * FROM certificates WHERE user_id = ? AND course_id = ?');
$s->execute([$user['id'], $courseId]);
$cert = $s->fetch();
if (!$cert) {
    http_response_code(403);
    die('คุณยังไม่มีสิทธิ์ดาวน์โหลดใบประกาศของหลักสูตรนี้ (ต้องสอบผ่านก่อน)');
}

$s = db()->prepare('SELECT * FROM courses WHERE id = ?');
$s->execute([$courseId]);
$course = $s->fetch();

// ตรวจไฟล์เทมเพลต
$templateFile = $course['template_image'];
$imgPath = $templateFile ? TEMPLATE_DIR . '/' . $templateFile : null;
if (!$imgPath || !is_file($imgPath)) {
    die('ยังไม่ได้ตั้งค่าภาพเทมเพลตใบประกาศสำหรับหลักสูตรนี้ กรุณาแจ้งผู้ดูแลระบบ');
}

$safeName = 'certificate-' . preg_replace('/[^A-Za-z0-9\-]/', '', $cert['cert_code']) . '.pdf';

/* ---------- โหมดพรีวิวในเบราว์เซอร์ (?view=html) ---------- */
$forceHtml = (($_GET['view'] ?? '') === 'html');

/* ---------- ใช้ mPDF ถ้าติดตั้งแล้ว ---------- */
$autoload = __DIR__ . '/vendor/autoload.php';
if (!$forceHtml && is_file($autoload)) {
    require_once $autoload;
    if (class_exists('\Mpdf\Mpdf')) {
        $html = render_certificate_html($course, $user, $cert, 'file://' . str_replace('\\', '/', $imgPath));
        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode'          => 'utf-8',
                'format'        => [297, 210],   // A4 แนวนอน
                'margin_left'   => 0, 'margin_right' => 0,
                'margin_top'    => 0, 'margin_bottom' => 0,
                'default_font'  => 'garuda',     // ฟอนต์ไทยที่มากับ mPDF
                'img_dpi'       => 300,          // คงความละเอียดภาพเทมเพลต (กันภาพแตก)
                'tempDir'       => sys_get_temp_dir(),
            ]);
            $mpdf->WriteHTML($html);
            $mpdf->Output($safeName, \Mpdf\Output\Destination::DOWNLOAD);
            exit;
        } catch (Throwable $e) {
            if (DEBUG_MODE) die('สร้าง PDF ไม่สำเร็จ: ' . e($e->getMessage()));
            die('สร้าง PDF ไม่สำเร็จ');
        }
    }
}

/* ---------- สำรอง: หน้าพิมพ์เป็น PDF ผ่านเบราว์เซอร์ ---------- */
$imgSrc = url('/uploads/templates/' . rawurlencode($templateFile));
$html = render_certificate_html($course, $user, $cert, $imgSrc);

// แทรกปุ่มพิมพ์ + คำแนะนำ ก่อน </body>
$msg = $forceHtml
    ? 'ตัวอย่างใบประกาศ — กด <b>พิมพ์</b> แล้วเลือก “บันทึกเป็น PDF” ได้'
    : 'ยังไม่ได้ติดตั้ง mPDF — กด <b>พิมพ์</b> แล้วเลือก “บันทึกเป็น PDF”';
$bar = '<div class="noprint" style="position:fixed;top:0;left:0;right:0;background:#1f2937;color:#fff;'
     . 'padding:12px 20px;text-align:center;font-family:sans-serif;z-index:99">'
     . $msg . ' &nbsp; '
     . '<button onclick="window.print()" style="padding:6px 16px;border:0;border-radius:6px;'
     . 'background:#2563eb;color:#fff;cursor:pointer">🖨️ พิมพ์ / บันทึก PDF</button></div>'
     . '<style>@media print{.noprint{display:none}} body{padding-top:0}</style>';
$html = str_replace('</body>', $bar . '</body>', $html);

header('Content-Type: text/html; charset=utf-8');
echo $html;

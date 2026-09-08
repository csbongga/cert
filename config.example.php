<?php
/**
 * ตัวอย่างไฟล์ตั้งค่า — คัดลอกไฟล์นี้เป็น config.php แล้วแก้ค่าให้ตรงกับเครื่อง/โฮสต์ของคุณ
 *   cp config.example.php config.php
 */

// ---------- ฐานข้อมูล ----------
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'certdb');      // ชื่อฐานข้อมูล
define('DB_USER', 'root');        // ชื่อผู้ใช้ฐานข้อมูล
define('DB_PASS', '');            // รหัสผ่านฐานข้อมูล
define('DB_CHARSET', 'utf8mb4');

// ---------- ข้อมูลเว็บไซต์ ----------
define('SITE_NAME', 'ระบบออกใบประกาศออนไลน์');

// URL ฐานของเว็บ: root ของโดเมน = '' ; ในโฟลเดอร์ /cert = '/cert'
define('BASE_URL', '');

// โฟลเดอร์เก็บไฟล์อัปโหลด (ไม่ต้องแก้)
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('TEMPLATE_DIR', UPLOAD_DIR . '/templates');
define('CERT_DIR', UPLOAD_DIR . '/certs');

// เขตเวลา
date_default_timezone_set('Asia/Bangkok');

// ระหว่างพัฒนา = 1 (เห็น error) ; ใช้งานจริง = 0
define('DEBUG_MODE', 1);
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

<?php
/**
 * เชื่อมต่อฐานข้อมูลด้วย PDO (MySQL)
 * คืนค่าเป็น singleton ผ่านฟังก์ชัน db()
 */
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
    );
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die('เชื่อมต่อฐานข้อมูลไม่สำเร็จ: ' . htmlspecialchars($e->getMessage())
                . '<br>ตรวจสอบว่าเปิด MySQL แล้ว และ import ไฟล์ database.sql เรียบร้อย');
        }
        die('เชื่อมต่อฐานข้อมูลไม่สำเร็จ กรุณาติดต่อผู้ดูแลระบบ');
    }

    return $pdo;
}

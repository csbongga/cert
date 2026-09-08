<?php
/**
 * ฟังก์ชันช่วยเหลือทั่วไป: session, auth, csrf, flash, escaping
 */
require_once __DIR__ . '/../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- Output escaping ---------- */
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . $path;
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(419);
        die('เซสชันหมดอายุหรือคำขอไม่ถูกต้อง กรุณาลองใหม่');
    }
}

/* ---------- Flash messages ---------- */
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Authentication ---------- */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $u = current_user();
    return $u && $u['role'] === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('error', 'กรุณาเข้าสู่ระบบก่อน');
        redirect('/login.php');
    }
}

function require_admin(): void
{
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('เฉพาะผู้ดูแลระบบเท่านั้น');
    }
}

/* ---------- Misc ---------- */
function old(string $key, string $default = ''): string
{
    return e($_SESSION['old'][$key] ?? $default);
}

function set_old(array $data): void
{
    $_SESSION['old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

/** จัดรูปแบบวันที่ไทย เช่น 7 กันยายน 2568 */
function thai_date(string $datetime): string
{
    $months = [1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
        'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $t = strtotime($datetime);
    $d = (int)date('j', $t);
    $m = (int)date('n', $t);
    $y = (int)date('Y', $t) + 543;
    return "$d {$months[$m]} $y";
}

# คู่มืออัพเว็บขึ้น Shared Hosting (cPanel) — ที่ example.com/cert

> เว็บนี้ตั้งค่าไว้สำหรับรันในซับโฟลเดอร์ `/cert` แล้ว (`BASE_URL = '/cert'`)
> ถ้าใช้ชื่อโฟลเดอร์อื่น ให้แก้ `BASE_URL` ใน `config.php` ให้ตรง

---

## ✅ สิ่งที่โฮสต์ต้องมี
- PHP 7.4 ขึ้นไป (แนะนำ 8.1+) เปิดส่วนขยาย **gd** และ **mbstring** — โฮสต์ทั่วไปเปิดให้อยู่แล้ว
- MySQL / MariaDB
- (ไม่ต้องมี Composer บนเซิร์ฟเวอร์ เพราะรวมโฟลเดอร์ `vendor/` มาให้แล้ว)

---

## ขั้นตอนที่ 1 — สร้างฐานข้อมูลใน cPanel
1. เข้า cPanel → **MySQL® Databases**
2. **Create New Database** — ตั้งชื่อ เช่น `cert` → ได้ชื่อจริงเป็น `ชื่อผู้ใช้cPanel_cert` (จดไว้)
3. **Add New User** — สร้างผู้ใช้ + รหัสผ่าน (จดไว้)
4. **Add User To Database** — เลือก user + database ที่เพิ่งสร้าง → ติ๊ก **ALL PRIVILEGES** → Make Changes

> จดไว้ 3 อย่าง: ชื่อฐานข้อมูล, ชื่อผู้ใช้ฐานข้อมูล, รหัสผ่าน

## ขั้นตอนที่ 2 — อัปโหลดไฟล์
1. เข้า cPanel → **File Manager** → เข้าโฟลเดอร์ `public_html`
2. สร้างโฟลเดอร์ชื่อ `cert`
3. อัปโหลดไฟล์ `cert_deploy.zip` เข้าไปในโฟลเดอร์ `cert` แล้วคลิกขวา → **Extract**
   (หรือใช้ FTP เช่น FileZilla อัปโหลดไฟล์ทั้งหมดเข้า `public_html/cert`)

## ขั้นตอนที่ 3 — ตั้งค่าการเชื่อมต่อฐานข้อมูล
แก้ไฟล์ `public_html/cert/config.php` (คลิกขวา → Edit) ใส่ค่าจากขั้นตอนที่ 1:
```php
define('DB_HOST', 'localhost');            // shared hosting ส่วนใหญ่ใช้ localhost
define('DB_NAME', 'ชื่อผู้ใช้cPanel_cert'); // ชื่อฐานข้อมูลเต็ม
define('DB_USER', 'ชื่อผู้ใช้ฐานข้อมูล');
define('DB_PASS', 'รหัสผ่านฐานข้อมูล');
...
define('BASE_URL', '/cert');               // ตรงกับโฟลเดอร์ (ถ้าอยู่ root ใช้ '')
```

## ขั้นตอนที่ 4 — Import ตารางฐานข้อมูล
1. เข้า cPanel → **phpMyAdmin**
2. เลือกฐานข้อมูลที่สร้างไว้ (ด้านซ้าย)
3. แท็บ **Import** → เลือกไฟล์ `database_hosting.sql` → **Go**

## ขั้นตอนที่ 5 — ติดตั้งข้อมูลเริ่มต้น
1. เปิดเบราว์เซอร์ไปที่ `https://example.com/cert/setup.php`
2. จะสร้างบัญชีผู้ดูแล + หลักสูตรตัวอย่าง + เทมเพลตตัวอย่างให้
3. **เข้าสู่ระบบด้วย** `admin@example.com` / `admin123`

## ขั้นตอนที่ 6 — ตั้งสิทธิ์โฟลเดอร์อัปโหลด
ให้โฟลเดอร์ `cert/uploads`, `cert/uploads/templates`, `cert/uploads/certs`
มีสิทธิ์เขียนได้ (คลิกขวาใน File Manager → Change Permissions → **755** หรือ **775**)
> ปกติเมื่อ extract แล้วมักตั้งให้อยู่แล้ว ถ้าอัปโหลดเทมเพลตไม่ได้ค่อยมาปรับข้อนี้

---

## 🔒 ขั้นตอนที่ 7 — ความปลอดภัย (สำคัญ! ทำก่อนใช้งานจริง)
1. **ลบไฟล์** `setup.php` และ `composer.phar` ออกจากเซิร์ฟเวอร์
2. แก้ `config.php` → เปลี่ยน `DEBUG_MODE` เป็น **0**
3. **เปลี่ยนรหัสผ่านผู้ดูแล** — เข้า phpMyAdmin ไม่สะดวก แนะนำ:
   - สมัครสมาชิกใหม่ด้วยอีเมลจริงของคุณผ่านหน้าเว็บ
   - เข้า phpMyAdmin → ตาราง `users` → แก้ `role` ของบัญชีคุณเป็น `admin`
   - แล้วลบ/ปิดบัญชี `admin@example.com` เดิม
4. ลบหลักสูตร/เทมเพลตตัวอย่าง แล้วใส่ของจริง
5. ใช้ **HTTPS** (เปิด SSL ฟรีจาก cPanel → SSL/TLS Status → Run AutoSSL)

---

## ปัญหาที่พบบ่อย
| อาการ | สาเหตุ/วิธีแก้ |
|---|---|
| หน้าเว็บขาว/Error 500 | ตั้ง `DEBUG_MODE` เป็น 1 ชั่วคราวเพื่อดู error / เช็ค PHP version ให้เป็น 7.4+ |
| CSS ไม่ขึ้น รูปแตก path | `BASE_URL` ไม่ตรงโฟลเดอร์ — ต้องเป็น `/cert` |
| เชื่อมต่อ DB ไม่ได้ | ชื่อ DB/user/pass ใน `config.php` ไม่ตรง หรือยังไม่ได้ Add User To Database |
| โหลด PDF แล้วภาษาไทยหาย/ขึ้นกล่อง | โฮสต์ปิด `gd` — แจ้งผู้ให้บริการเปิด extension gd |
| อัปโหลดเทมเพลตไม่ได้ | ตั้ง permission โฟลเดอร์ uploads เป็น 755/775 |

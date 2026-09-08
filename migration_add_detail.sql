-- ============================================================
--  MIGRATION: เพิ่มบรรทัด "รายละเอียดการอบรม" (วันที่/สถานที่) บนใบประกาศ
--  ใช้กับเว็บที่ติดตั้งไปแล้ว (ข้อมูลเดิมไม่หาย)
--  วิธีใช้บน host: phpMyAdmin → เลือกฐานข้อมูล cert → แท็บ SQL → วางคำสั่งนี้ → Go
-- ============================================================

ALTER TABLE `courses`
  ADD COLUMN `detail_text` VARCHAR(255) NULL AFTER `course_size`,
  ADD COLUMN `detail_top`  DECIMAL(5,1) NOT NULL DEFAULT 133.0 AFTER `detail_text`,
  ADD COLUMN `detail_size` TINYINT UNSIGNED NOT NULL DEFAULT 15 AFTER `detail_top`;

-- ============================================================
--  ระบบออกใบประกาศออนไลน์ - โครงสร้างฐานข้อมูล
--  วิธีใช้: เปิด phpMyAdmin แล้ว Import ไฟล์นี้
--  (ไฟล์นี้จะสร้างฐานข้อมูล certdb ให้อัตโนมัติ)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `certdb`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `certdb`;

-- ---------- ผู้ใช้งาน (ผู้อบรม + ผู้ดูแลระบบ) ----------
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(150) NOT NULL,
  `email`         VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('user','admin') NOT NULL DEFAULT 'user',
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- หลักสูตร/หัวข้ออบรม ----------
CREATE TABLE IF NOT EXISTS `courses` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`          VARCHAR(255) NOT NULL,
  `description`    TEXT NULL,
  `template_image` VARCHAR(255) NULL,       -- ชื่อไฟล์ภาพเทมเพลตใบประกาศ
  `pass_score`     TINYINT UNSIGNED NOT NULL DEFAULT 7,  -- เกณฑ์ผ่าน (จากคะแนนเต็ม)
  `quiz_count`     TINYINT UNSIGNED NOT NULL DEFAULT 10, -- จำนวนข้อที่สุ่มมาสอบ
  -- ตำแหน่งวางข้อความบนใบประกาศ (หน่วย mm บนกระดาษ A4 แนวนอน 297x210)
  `name_top`       DECIMAL(5,1) NOT NULL DEFAULT 76.0,
  `name_size`      TINYINT UNSIGNED NOT NULL DEFAULT 34,
  `course_top`     DECIMAL(5,1) NOT NULL DEFAULT 113.0,
  `course_size`    TINYINT UNSIGNED NOT NULL DEFAULT 22,
  `show_meta`      TINYINT(1) NOT NULL DEFAULT 1,        -- แสดงวันที่ + รหัสใบประกาศด้านล่าง
  `is_active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- คลังเทมเพลตใบประกาศ ----------
CREATE TABLE IF NOT EXISTS `templates` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150) NOT NULL,
  `filename`   VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_templates_filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- คำถาม ----------
CREATE TABLE IF NOT EXISTS `questions` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id`  INT UNSIGNED NOT NULL,
  `question`   TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_questions_course` (`course_id`),
  CONSTRAINT `fk_questions_course` FOREIGN KEY (`course_id`)
    REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ตัวเลือก (ช้อยส์) + เฉลย ----------
CREATE TABLE IF NOT EXISTS `choices` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` INT UNSIGNED NOT NULL,
  `choice_text` VARCHAR(500) NOT NULL,
  `is_correct`  TINYINT(1) NOT NULL DEFAULT 0,  -- 1 = เป็นคำตอบที่ถูก
  PRIMARY KEY (`id`),
  KEY `idx_choices_question` (`question_id`),
  CONSTRAINT `fk_choices_question` FOREIGN KEY (`question_id`)
    REFERENCES `questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- การทำแบบทดสอบ (ผลสอบแต่ละครั้ง) ----------
CREATE TABLE IF NOT EXISTS `attempts` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `course_id`  INT UNSIGNED NOT NULL,
  `score`      TINYINT UNSIGNED NOT NULL,
  `total`      TINYINT UNSIGNED NOT NULL,
  `passed`     TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_user` (`user_id`),
  KEY `idx_attempts_course` (`course_id`),
  CONSTRAINT `fk_attempts_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attempts_course` FOREIGN KEY (`course_id`)
    REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- ใบประกาศที่ออกแล้ว ----------
CREATE TABLE IF NOT EXISTS `certificates` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `course_id`  INT UNSIGNED NOT NULL,
  `attempt_id` INT UNSIGNED NULL,
  `cert_code`  VARCHAR(30) NOT NULL,
  `issued_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cert_user_course` (`user_id`, `course_id`),
  UNIQUE KEY `uq_cert_code` (`cert_code`),
  CONSTRAINT `fk_cert_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cert_course` FOREIGN KEY (`course_id`)
    REFERENCES `courses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

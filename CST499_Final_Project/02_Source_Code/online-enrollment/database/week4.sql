-- Additive Week 4 migration. Import after the Week 3 schema.sql.
USE online_enrollment;
CREATE TABLE IF NOT EXISTS courses (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(20) NOT NULL UNIQUE,
 title VARCHAR(120) NOT NULL,
 credits TINYINT UNSIGNED NOT NULL,
 CONSTRAINT ck_course_credits CHECK (credits BETWEEN 1 AND 12)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS semesters (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 year SMALLINT UNSIGNED NOT NULL,
 term ENUM('Spring','Summer','Fall') NOT NULL,
 UNIQUE KEY uq_semester (year,term)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS offerings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 course_id BIGINT UNSIGNED NOT NULL,
 semester_id BIGINT UNSIGNED NOT NULL,
 section VARCHAR(10) NOT NULL DEFAULT '01',
 capacity INT UNSIGNED NOT NULL,
 UNIQUE KEY uq_offering (course_id,semester_id,section),
 CONSTRAINT ck_offering_capacity CHECK (capacity > 0),
 CONSTRAINT fk_offering_course FOREIGN KEY (course_id) REFERENCES courses(id),
 CONSTRAINT fk_offering_semester FOREIGN KEY (semester_id) REFERENCES semesters(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS enrollments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 account_id BIGINT UNSIGNED NOT NULL,
 offering_id BIGINT UNSIGNED NOT NULL,
 enrolled_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 UNIQUE KEY uq_student_offering (account_id,offering_id),
 CONSTRAINT fk_enrollment_account FOREIGN KEY (account_id) REFERENCES accounts(id),
 CONSTRAINT fk_enrollment_offering FOREIGN KEY (offering_id) REFERENCES offerings(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS waiting_list (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 account_id BIGINT UNSIGNED NOT NULL,
 offering_id BIGINT UNSIGNED NOT NULL,
 joined_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 UNIQUE KEY uq_wait_student_offering (account_id,offering_id),
 KEY ix_queue_order (offering_id,joined_at,id),
 CONSTRAINT fk_wait_account FOREIGN KEY (account_id) REFERENCES accounts(id),
 CONSTRAINT fk_wait_offering FOREIGN KEY (offering_id) REFERENCES offerings(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS seat_offers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 offering_id BIGINT UNSIGNED NOT NULL,
 recipient_id BIGINT UNSIGNED NOT NULL,
 waiting_id BIGINT UNSIGNED NULL UNIQUE,
 status ENUM('pending','accepted','declined','expired') NOT NULL DEFAULT 'pending',
 created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 expires_at DATETIME(6) NOT NULL,
 notice VARCHAR(500) NOT NULL,
 KEY ix_offer_capacity (offering_id,status,expires_at),
 CONSTRAINT ck_pending_queue CHECK (status <> 'pending' OR waiting_id IS NOT NULL),
 CONSTRAINT fk_offer_offering FOREIGN KEY (offering_id) REFERENCES offerings(id),
 CONSTRAINT fk_offer_recipient FOREIGN KEY (recipient_id) REFERENCES accounts(id),
 CONSTRAINT fk_offer_wait FOREIGN KEY (waiting_id) REFERENCES waiting_list(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS notification_tasks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 offer_id BIGINT UNSIGNED NOT NULL UNIQUE,
 status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
 attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
 next_attempt DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
 CONSTRAINT fk_notification_offer FOREIGN KEY (offer_id) REFERENCES seat_offers(id)
) ENGINE=InnoDB;

-- Import into phpMyAdmin using an administrator account.
CREATE DATABASE IF NOT EXISTS online_enrollment
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE online_enrollment;

CREATE TABLE IF NOT EXISTS accounts (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id VARCHAR(30) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student') NOT NULL DEFAULT 'student',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  CONSTRAINT uq_accounts_user_id UNIQUE (user_id),
  CONSTRAINT uq_accounts_email UNIQUE (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS profiles (
  account_id BIGINT UNSIGNED NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  phone VARCHAR(25) NOT NULL,
  PRIMARY KEY (account_id),
  CONSTRAINT fk_profiles_account FOREIGN KEY (account_id)
    REFERENCES accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB;

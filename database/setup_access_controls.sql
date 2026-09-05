-- Run once in phpMyAdmin on kadad_kadad_class.
USE kadad_kadad_class;

-- Add guest access flag to classes.
ALTER TABLE classes ADD COLUMN allow_guest TINYINT(1) NOT NULL DEFAULT 0 AFTER status;

-- If the column already exists, skip the ALTER TABLE statement above.

CREATE TABLE IF NOT EXISTS class_allowed_users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  class_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  email VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL DEFAULT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_class_allowed_user (class_id,user_id),
  KEY idx_class_allowed_email (class_id,email),
  CONSTRAINT fk_cau_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_cau_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

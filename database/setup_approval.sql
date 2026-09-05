-- Run once in phpMyAdmin on kadad_kadad_class.
-- 1) Class start time (skip this statement if starts_at already exists).
ALTER TABLE classes ADD COLUMN starts_at DATETIME NULL AFTER status;
ALTER TABLE classes ADD INDEX idx_classes_starts_at (starts_at);

-- 2) Student join approval workflow.
CREATE TABLE IF NOT EXISTS class_join_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  class_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  decided_by BIGINT UNSIGNED NULL,
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at TIMESTAMP NULL DEFAULT NULL,
  reject_reason VARCHAR(500) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_class_join_request (class_id,user_id),
  KEY idx_cjr_class_status (class_id,status),
  KEY idx_cjr_user_status (user_id,status),
  CONSTRAINT fk_cjr_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
  CONSTRAINT fk_cjr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_cjr_decider FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

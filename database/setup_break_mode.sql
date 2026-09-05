-- Run once in phpMyAdmin on kadad_kadad_class.
USE kadad_kadad_class;

ALTER TABLE classes
  ADD COLUMN break_active TINYINT(1) NOT NULL DEFAULT 0 AFTER allow_guest,
  ADD COLUMN break_started_at DATETIME NULL AFTER break_active,
  ADD COLUMN break_ad_name VARCHAR(255) NULL AFTER break_started_at;

ALTER TABLE classes
  ADD INDEX idx_classes_break_active (break_active);

-- Run once in phpMyAdmin on kadad_kadad_class.
USE kadad_kadad_class;

ALTER TABLE users
  ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER role;

ALTER TABLE users
  ADD INDEX idx_users_created_by (created_by),
  ADD CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE classes
  ADD COLUMN break_music_url VARCHAR(1000) NULL AFTER break_ad_name;

ALTER TABLE class_participants
  ADD INDEX idx_cp_live (class_id,user_id,left_at);

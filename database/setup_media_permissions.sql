-- Run once in phpMyAdmin on kadad_kadad_class.
USE kadad_kadad_class;

ALTER TABLE class_participants
  ADD COLUMN can_audio TINYINT(1) NOT NULL DEFAULT 0 AFTER role,
  ADD COLUMN can_video TINYINT(1) NOT NULL DEFAULT 0 AFTER can_audio,
  ADD COLUMN can_screen_share TINYINT(1) NOT NULL DEFAULT 0 AFTER can_video;

ALTER TABLE class_participants
  ADD INDEX idx_cp_permissions (class_id, user_id, can_audio, can_video, can_screen_share);

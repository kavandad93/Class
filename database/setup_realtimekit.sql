-- RealtimeKit integration for Kadad Class.
USE kadad_kadad_class;

ALTER TABLE classes ADD COLUMN realtime_meeting_id VARCHAR(64) NULL AFTER room_code;
ALTER TABLE classes ADD UNIQUE KEY uq_classes_realtime_meeting_id (realtime_meeting_id);

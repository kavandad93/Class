USE kadad_kadad_class;

-- Per-class room visibility controls.
ALTER TABLE classes ADD COLUMN show_users TINYINT(1) NOT NULL DEFAULT 1 AFTER allow_guest;
ALTER TABLE classes ADD COLUMN show_chat TINYINT(1) NOT NULL DEFAULT 1 AFTER show_users;

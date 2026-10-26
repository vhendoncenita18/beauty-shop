-- Add profile details for new User registrations without rewriting migration 001.
-- Existing rows keep blank first/last names and NULL optional profile fields.

ALTER TABLE users
    ADD COLUMN first_name VARCHAR(100) NOT NULL DEFAULT '' AFTER id,
    ADD COLUMN middle_name VARCHAR(100) NULL DEFAULT NULL AFTER first_name,
    ADD COLUMN last_name VARCHAR(100) NOT NULL DEFAULT '' AFTER middle_name,
    ADD COLUMN phone_number VARCHAR(30) NULL DEFAULT NULL AFTER last_name;

INSERT INTO schema_migrations (migration_id)
VALUES ('002_add_user_profile_fields');

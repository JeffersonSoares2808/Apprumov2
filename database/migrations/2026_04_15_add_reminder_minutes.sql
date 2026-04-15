-- Add configurable reminder lead time (in minutes) per vendor.
-- Default: 1440 minutes = 24 hours before (1 day).
ALTER TABLE notification_settings
    ADD COLUMN reminder_minutes_before INT NOT NULL DEFAULT 1440
    AFTER send_reminders;

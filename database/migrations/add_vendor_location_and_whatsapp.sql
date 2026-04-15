-- Migration: Add location (latitude/longitude) and WhatsApp API fields to vendors
-- Run this migration on your database to enable Google Maps and WhatsApp notifications.

ALTER TABLE vendors
    ADD COLUMN latitude DECIMAL(10, 7) NULL AFTER address,
    ADD COLUMN longitude DECIMAL(10, 7) NULL AFTER latitude,
    ADD COLUMN whatsapp_api_token VARCHAR(255) NULL AFTER longitude,
    ADD COLUMN whatsapp_phone_id VARCHAR(60) NULL AFTER whatsapp_api_token;

-- Add 'whatsapp' as a notification channel
ALTER TABLE notification_log
    MODIFY COLUMN channel ENUM('email', 'sms', 'whatsapp') NOT NULL;

-- Add WhatsApp notification toggle to notification_settings
ALTER TABLE notification_settings
    ADD COLUMN whatsapp_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER sms_enabled,
    ADD COLUMN whatsapp_notify_vendor TINYINT(1) NOT NULL DEFAULT 1 AFTER whatsapp_enabled;

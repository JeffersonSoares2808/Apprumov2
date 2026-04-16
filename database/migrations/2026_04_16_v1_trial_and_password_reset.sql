-- Migration: Add password reset tokens table and trial support
-- Version 1.0

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_password_reset_email (email),
    INDEX idx_password_reset_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add trial_ends_at to vendors for 2-day free trial
ALTER TABLE vendors ADD COLUMN IF NOT EXISTS trial_ends_at DATE NULL AFTER plan_expires_at;

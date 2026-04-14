-- Migration: professional_schedule_and_performance
-- Date: 2026-04-14
-- Description: Add schedule_type to professionals table and performance indexes

-- 1. Add schedule_type column to professionals
ALTER TABLE professionals
    ADD COLUMN IF NOT EXISTS schedule_type ENUM('weekly', 'specific') NOT NULL DEFAULT 'weekly'
    AFTER commission_rate;

-- 2. Performance indexes

-- Composite index for frequent vendor+date+status queries on appointments
CREATE INDEX idx_appointments_vendor_date_status ON appointments(vendor_id, appointment_date, status);

-- Per-professional appointment lookups
CREATE INDEX idx_appointments_prof_date ON appointments(professional_id, appointment_date);

-- Waiting list queries by vendor and desired date
CREATE INDEX idx_waiting_vendor_date ON waiting_list_entries(vendor_id, desired_date);

-- KPI queries on financial transactions
CREATE INDEX idx_financial_vendor_kind_status ON financial_transactions(vendor_id, kind, status);

-- Composite index on professional availability
CREATE INDEX idx_prof_avail_active ON professional_availability(professional_id, day_of_week, is_active);

-- Notification log queries by vendor and creation date
CREATE INDEX idx_notification_log_vendor_created ON notification_log(vendor_id, created_at);

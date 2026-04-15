-- Performance indexes for common queries
-- clients lookup by vendor + phone (used heavily in upsertClient)
CREATE INDEX IF NOT EXISTS idx_clients_vendor_phone ON clients (vendor_id, phone);

-- financial_transactions by vendor + date (finance page, reports)
CREATE INDEX IF NOT EXISTS idx_financial_vendor_date_status ON financial_transactions (vendor_id, transaction_date, status);

-- appointments covering index for conflict checks
CREATE INDEX IF NOT EXISTS idx_appointments_conflict ON appointments (vendor_id, appointment_date, status, start_time, end_time);

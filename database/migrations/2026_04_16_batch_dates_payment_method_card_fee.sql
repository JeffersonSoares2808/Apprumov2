-- Feature 3: Payment method and card fee on appointments and financial_transactions
ALTER TABLE appointments
    ADD COLUMN payment_method ENUM('cash', 'card', 'pix', 'other') NULL DEFAULT NULL AFTER paid_at,
    ADD COLUMN card_fee DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER payment_method;

ALTER TABLE financial_transactions
    ADD COLUMN payment_method ENUM('cash', 'card', 'pix', 'other') NULL DEFAULT NULL AFTER status,
    ADD COLUMN card_fee DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER payment_method;

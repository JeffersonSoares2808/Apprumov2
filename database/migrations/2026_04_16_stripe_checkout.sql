-- Add Stripe checkout URL to plans
ALTER TABLE plans ADD COLUMN stripe_checkout_url VARCHAR(500) NULL AFTER description;

-- Add Stripe payment tracking to vendors
ALTER TABLE vendors ADD COLUMN stripe_session_id VARCHAR(255) NULL AFTER plan_expires_at;
ALTER TABLE vendors ADD COLUMN stripe_paid_at DATETIME NULL AFTER stripe_session_id;

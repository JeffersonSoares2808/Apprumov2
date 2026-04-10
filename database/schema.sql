CREATE TABLE IF NOT EXISTS platform_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_id VARCHAR(120) NULL UNIQUE,
    google_sub VARCHAR(190) NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    full_name VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NULL,
    role ENUM('admin', 'vendor') NOT NULL DEFAULT 'vendor',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    duration_days INT NOT NULL DEFAULT 30,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS vendors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    plan_id BIGINT UNSIGNED NULL,
    business_name VARCHAR(190) NOT NULL,
    slug VARCHAR(190) NOT NULL UNIQUE,
    category VARCHAR(120) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    bio TEXT NULL,
    address VARCHAR(255) NULL,
    profile_image VARCHAR(255) NULL,
    cover_image VARCHAR(255) NULL,
    button_color VARCHAR(20) NOT NULL DEFAULT '#ddb76a',
    public_rating DECIMAL(2, 1) NOT NULL DEFAULT 5.0,
    rating_count INT NOT NULL DEFAULT 0,
    interval_between_appointments INT NOT NULL DEFAULT 0,
    status ENUM('pending', 'active', 'suspended', 'expired') NOT NULL DEFAULT 'pending',
    plan_started_at DATE NULL,
    plan_expires_at DATE NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_vendors_user FOREIGN KEY (user_id) REFERENCES platform_users (id) ON DELETE CASCADE,
    CONSTRAINT fk_vendors_plan FOREIGN KEY (plan_id) REFERENCES plans (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS vendor_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('owner', 'manager', 'staff') NOT NULL DEFAULT 'staff',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_vendor_user (vendor_id, user_id),
    KEY idx_vendor_users_user (user_id),
    CONSTRAINT fk_vendor_users_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE,
    CONSTRAINT fk_vendor_users_user FOREIGN KEY (user_id) REFERENCES platform_users (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS vendor_hours (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    weekday TINYINT NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_vendor_weekday (vendor_id, weekday),
    CONSTRAINT fk_vendor_hours_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS vendor_special_days (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    special_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_vendor_special_date (vendor_id, special_date),
    CONSTRAINT fk_vendor_special_days_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(190) NOT NULL,
    description TEXT NULL,
    duration_minutes INT NOT NULL DEFAULT 30,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_services_vendor_active (vendor_id, is_active),
    CONSTRAINT fk_services_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    description TEXT NULL,
    sale_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    cost_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    stock_quantity INT NOT NULL DEFAULT 0,
    min_stock_quantity INT NOT NULL DEFAULT 0,
    category VARCHAR(120) NULL,
    image_path VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_products_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS clients (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(190) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_vendor_phone (vendor_id, phone),
    CONSTRAINT fk_clients_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS appointments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    client_id BIGINT UNSIGNED NULL,
    customer_name VARCHAR(190) NOT NULL,
    customer_email VARCHAR(190) NULL,
    customer_phone VARCHAR(40) NOT NULL,
    appointment_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    duration_minutes INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status ENUM('confirmed', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'confirmed',
    source ENUM('manual', 'public_booking') NOT NULL DEFAULT 'manual',
    lgpd_consent TINYINT(1) NOT NULL DEFAULT 0,
    notes TEXT NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_appointments_vendor_date (vendor_id, appointment_date),
    KEY idx_appointments_status (status),
    CONSTRAINT fk_appointments_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE,
    CONSTRAINT fk_appointments_client FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS waiting_list_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NULL,
    customer_name VARCHAR(190) NOT NULL,
    customer_phone VARCHAR(40) NOT NULL,
    desired_date DATE NOT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_waiting_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE,
    CONSTRAINT fk_waiting_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS product_sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    customer_name VARCHAR(190) NULL,
    sold_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_product_sales_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE,
    CONSTRAINT fk_product_sales_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS financial_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NULL,
    product_id BIGINT UNSIGNED NULL,
    product_sale_id BIGINT UNSIGNED NULL,
    kind ENUM('income', 'expense', 'loss') NOT NULL DEFAULT 'income',
    source ENUM('appointment', 'product_sale', 'manual') NOT NULL DEFAULT 'appointment',
    title VARCHAR(190) NOT NULL,
    description VARCHAR(255) NULL,
    amount DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status ENUM('open', 'paid', 'cancelled') NOT NULL DEFAULT 'open',
    transaction_date DATE NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_appointment_transaction (appointment_id),
    UNIQUE KEY unique_product_sale_transaction (product_sale_id),
    KEY idx_financial_vendor_date (vendor_id, transaction_date),
    KEY idx_financial_status (status),
    CONSTRAINT fk_financial_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE,
    CONSTRAINT fk_financial_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE SET NULL,
    CONSTRAINT fk_financial_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL,
    CONSTRAINT fk_financial_sale FOREIGN KEY (product_sale_id) REFERENCES product_sales (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS notification_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL UNIQUE,
    email_enabled TINYINT(1) NOT NULL DEFAULT 1,
    sms_enabled TINYINT(1) NOT NULL DEFAULT 1,
    notify_on_booking TINYINT(1) NOT NULL DEFAULT 1,
    notify_on_status_change TINYINT(1) NOT NULL DEFAULT 1,
    notify_on_payment TINYINT(1) NOT NULL DEFAULT 1,
    notify_on_low_stock TINYINT(1) NOT NULL DEFAULT 1,
    send_reminders TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT fk_notification_settings_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notification_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NULL,
    channel ENUM('email', 'sms') NOT NULL,
    recipient VARCHAR(190) NOT NULL,
    event_type VARCHAR(60) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    error_message VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    KEY idx_notification_log_vendor (vendor_id),
    KEY idx_notification_log_event (event_type),
    KEY idx_notification_log_created (created_at),
    CONSTRAINT fk_notification_log_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE
);

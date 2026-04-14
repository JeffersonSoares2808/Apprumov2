-- =====================================================
-- MIGRAÇÃO: Profissionais, Agenda Avançada e Retornos
-- Execute este SQL no phpMyAdmin da Hostinger
-- =====================================================

-- 1. Tabela de Profissionais
CREATE TABLE IF NOT EXISTS professionals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(40) NULL,
    color VARCHAR(20) NOT NULL DEFAULT '#0e2b47',
    commission_rate DECIMAL(5, 2) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_professionals_vendor (vendor_id),
    UNIQUE KEY unique_vendor_user_prof (vendor_id, user_id),
    CONSTRAINT fk_professionals_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE,
    CONSTRAINT fk_professionals_user FOREIGN KEY (user_id) REFERENCES platform_users (id) ON DELETE CASCADE
);

-- 2. Disponibilidade semanal do profissional
CREATE TABLE IF NOT EXISTS professional_availability (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    professional_id BIGINT UNSIGNED NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Dom, 1=Seg, 2=Ter, 3=Qua, 4=Qui, 5=Sex, 6=Sab',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_prof_avail_professional (professional_id),
    CONSTRAINT fk_prof_avail_professional FOREIGN KEY (professional_id) REFERENCES professionals (id) ON DELETE CASCADE
);

-- 3. Exceções de horário do profissional (folgas, feriados, horários especiais)
CREATE TABLE IF NOT EXISTS professional_exceptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    professional_id BIGINT UNSIGNED NOT NULL,
    exception_date DATE NOT NULL,
    is_available TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=folga, 1=horário especial',
    start_time TIME NULL,
    end_time TIME NULL,
    reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY unique_prof_exception_date (professional_id, exception_date),
    CONSTRAINT fk_prof_exception_professional FOREIGN KEY (professional_id) REFERENCES professionals (id) ON DELETE CASCADE
);

-- 4. Tabela de retornos de serviço
CREATE TABLE IF NOT EXISTS service_returns (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendor_id BIGINT UNSIGNED NOT NULL,
    appointment_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    customer_name VARCHAR(190) NOT NULL DEFAULT '',
    customer_phone VARCHAR(40) NOT NULL DEFAULT '',
    quantity_total INT NOT NULL DEFAULT 1,
    quantity_used INT NOT NULL DEFAULT 0,
    return_appointment_id BIGINT UNSIGNED NULL,
    expires_at DATE NOT NULL,
    status ENUM('available', 'scheduled', 'used', 'expired') NOT NULL DEFAULT 'available',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    KEY idx_service_returns_vendor (vendor_id),
    KEY idx_service_returns_status (status),
    KEY idx_service_returns_expires (expires_at),
    CONSTRAINT fk_service_returns_vendor FOREIGN KEY (vendor_id) REFERENCES vendors (id) ON DELETE CASCADE,
    CONSTRAINT fk_service_returns_appointment FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE CASCADE,
    CONSTRAINT fk_service_returns_service FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE CASCADE
);

-- 5. Adicionar coluna professional_id na tabela appointments
ALTER TABLE appointments
    ADD COLUMN professional_id BIGINT UNSIGNED NULL AFTER client_id,
    ADD KEY idx_appointments_professional (professional_id),
    ADD CONSTRAINT fk_appointments_professional FOREIGN KEY (professional_id) REFERENCES professionals (id) ON DELETE SET NULL;

-- 6. Adicionar campos de retorno na tabela services
ALTER TABLE services
    ADD COLUMN has_return TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active,
    ADD COLUMN return_quantity INT NOT NULL DEFAULT 1 AFTER has_return,
    ADD COLUMN return_days INT NOT NULL DEFAULT 30 AFTER return_quantity;

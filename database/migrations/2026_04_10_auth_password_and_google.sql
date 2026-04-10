-- Migração: autenticação por senha + base para login Google (colunas)
-- Seguro para rodar em banco existente.

ALTER TABLE platform_users
    ADD COLUMN IF NOT EXISTS google_sub VARCHAR(190) NULL UNIQUE,
    ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL;

-- Multiusuários por negócio (se ainda não aplicou)
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

-- Backfill: garante vínculo owner para donos atuais
INSERT INTO vendor_users (vendor_id, user_id, role, created_at, updated_at)
SELECT v.id, v.user_id, 'owner', NOW(), NOW()
FROM vendors v
LEFT JOIN vendor_users vu
  ON vu.vendor_id = v.id AND vu.user_id = v.user_id
WHERE vu.id IS NULL;


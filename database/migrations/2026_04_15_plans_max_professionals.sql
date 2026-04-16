-- Add max_professionals column to plans table to differentiate individual vs company plans
ALTER TABLE plans ADD COLUMN max_professionals INT NOT NULL DEFAULT 0 AFTER duration_days;

-- Update existing plans to match new pricing structure
UPDATE plans SET name = 'Plano Único', price = 80.00, max_professionals = 0,
    description = 'Agenda, serviços, produtos e perfil público. Ideal para profissionais individuais.'
    WHERE id = 1;

UPDATE plans SET name = 'Plano Empresa', price = 129.00, max_professionals = 99,
    description = 'Tudo do Plano Único + equipe de profissionais, agenda avançada por profissional e relatórios completos.'
    WHERE id = 2;

INSERT INTO platform_users (id, external_id, email, full_name, role, created_at, updated_at) VALUES
(1, 'seed-admin', 'admin@apprumo.local', 'Administrador Apprumo', 'admin', NOW(), NOW()),
(2, 'seed-vendor', 'demo@apprumo.local', 'Joedna de Oliveira', 'vendor', NOW(), NOW()),
(3, 'seed-pending', 'pending@apprumo.local', 'Profissional Pendente', 'vendor', NOW(), NOW());

INSERT INTO plans (id, name, price, duration_days, description, is_active, created_at, updated_at) VALUES
(1, 'Plano Essencial', 79.90, 30, 'Agenda, serviços, produtos e perfil público.', 1, NOW(), NOW()),
(2, 'Plano Profissional', 129.90, 30, 'Inclui relatórios, financeiro avançado e operações completas.', 1, NOW(), NOW());

INSERT INTO vendors (
    id, user_id, plan_id, business_name, slug, category, phone, bio, address, button_color,
    public_rating, rating_count, interval_between_appointments, status, plan_started_at, plan_expires_at, created_at, updated_at
) VALUES
(1, 2, 2, 'Studio Joedna Oliveira', 'studio-joedna-oliveira', 'Estética avançada', '5511999999999',
'Atendimento especializado com foco em experiência premium e agenda organizada.',
'Rua Exemplo, 123 - São Paulo/SP', '#d9a7a7', 4.9, 124, 15, 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NOW(), NOW()),
(2, 3, NULL, 'Espaço Pendente', 'espaco-pendente', 'Beleza', '5511988887777',
'Cadastro aguardando aprovação.', NULL, '#ddb76a', 5.0, 0, 0, 'pending', NULL, NULL, NOW(), NOW());

INSERT INTO vendor_users (vendor_id, user_id, role, created_at, updated_at) VALUES
(1, 2, 'owner', NOW(), NOW()),
(2, 3, 'owner', NOW(), NOW());

INSERT INTO vendor_hours (vendor_id, weekday, is_enabled, start_time, end_time, created_at, updated_at) VALUES
(1, 0, 0, '08:00:00', '18:00:00', NOW(), NOW()),
(1, 1, 1, '08:00:00', '18:00:00', NOW(), NOW()),
(1, 2, 1, '08:00:00', '18:00:00', NOW(), NOW()),
(1, 3, 1, '08:00:00', '18:00:00', NOW(), NOW()),
(1, 4, 1, '08:00:00', '18:00:00', NOW(), NOW()),
(1, 5, 1, '08:00:00', '18:00:00', NOW(), NOW()),
(1, 6, 1, '08:00:00', '13:00:00', NOW(), NOW()),
(2, 0, 0, '08:00:00', '18:00:00', NOW(), NOW()),
(2, 1, 1, '08:00:00', '18:00:00', NOW(), NOW()),
(2, 2, 1, '08:00:00', '18:00:00', NOW(), NOW()),
(2, 3, 1, '08:00:00', '18:00:00', NOW(), NOW()),
(2, 4, 1, '08:00:00', '18:00:00', NOW(), NOW()),
(2, 5, 1, '08:00:00', '18:00:00', NOW(), NOW()),
(2, 6, 0, '08:00:00', '18:00:00', NOW(), NOW());

INSERT INTO services (id, vendor_id, title, description, duration_minutes, price, is_active, created_at, updated_at) VALUES
(1, 1, 'Bioestimulador de colágeno', 'Sessão premium com duração estendida.', 60, 450.00, 1, NOW(), NOW()),
(2, 1, 'Blefaroplastia sem corte', 'Procedimento estético com alta procura.', 90, 650.00, 1, NOW(), NOW()),
(3, 1, 'Depilação a Led', 'Atendimento rápido com recorrência alta.', 30, 120.00, 1, NOW(), NOW()),
(4, 1, 'Consulta avaliativa', 'Primeira conversa para desenho do tratamento.', 30, 90.00, 1, NOW(), NOW());

INSERT INTO products (id, vendor_id, name, description, sale_price, cost_price, stock_quantity, min_stock_quantity, category, is_active, created_at, updated_at) VALUES
(1, 1, 'Sérum Revitalizante', 'Produto de apoio para pós-atendimento.', 99.90, 45.00, 12, 5, 'Skincare', 1, NOW(), NOW()),
(2, 1, 'Máscara Calmante', 'Uso profissional e revenda.', 69.90, 31.00, 3, 5, 'Skincare', 1, NOW(), NOW());

INSERT INTO clients (id, vendor_id, name, phone, email, created_at, updated_at) VALUES
(1, 1, 'Karla Portal', '5511911111111', 'karla@example.com', NOW(), NOW()),
(2, 1, 'Aline', '5511922222222', 'aline@example.com', NOW(), NOW()),
(3, 1, 'Charleide', '5511933333333', NULL, NOW(), NOW());

INSERT INTO appointments (
    id, vendor_id, service_id, client_id, customer_name, customer_email, customer_phone,
    appointment_date, start_time, end_time, duration_minutes, price, status, source, lgpd_consent, notes, paid_at, created_at, updated_at
) VALUES
(1, 1, 1, 1, 'Karla Portal', 'karla@example.com', '5511911111111', CURDATE(), '07:30:00', '08:30:00', 60, 450.00, 'confirmed', 'manual', 1, NULL, NULL, NOW(), NOW()),
(2, 1, 3, 2, 'Aline', 'aline@example.com', '5511922222222', CURDATE(), '10:30:00', '11:00:00', 30, 120.00, 'confirmed', 'manual', 1, NULL, NULL, NOW(), NOW()),
(3, 1, 3, 3, 'Charleide', NULL, '5511933333333', CURDATE(), '11:00:00', '11:30:00', 30, 120.00, 'completed', 'manual', 1, NULL, NOW(), NOW(), NOW());

INSERT INTO financial_transactions (
    vendor_id, appointment_id, kind, source, title, description, amount, status, transaction_date, created_at, updated_at
) VALUES
(1, 1, 'income', 'appointment', 'Agendamento', 'Bioestimulador de colágeno - Karla Portal', 450.00, 'open', CURDATE(), NOW(), NOW()),
(1, 2, 'income', 'appointment', 'Agendamento', 'Depilação a Led - Aline', 120.00, 'open', CURDATE(), NOW(), NOW()),
(1, 3, 'income', 'appointment', 'Agendamento', 'Depilação a Led - Charleide', 120.00, 'paid', CURDATE(), NOW(), NOW());

INSERT INTO waiting_list_entries (vendor_id, service_id, customer_name, customer_phone, desired_date, notes, created_at, updated_at) VALUES
(1, 3, 'Cliente Espera', '5511944444444', CURDATE(), 'Pode encaixar em qualquer horário após as 14h.', NOW(), NOW());

-- Apprumo — verificação de instalação (somente leitura, não altera dados).
-- Execute no banco u223253452_aprumo após importar schema.sql.
-- Esperado: 13 linhas (uma por tabela).

SELECT TABLE_NAME AS tabela_ok
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'platform_users',
    'plans',
    'vendors',
    'vendor_users',
    'vendor_hours',
    'vendor_special_days',
    'services',
    'products',
    'clients',
    'appointments',
    'waiting_list_entries',
    'product_sales',
    'financial_transactions'
)
ORDER BY TABLE_NAME;

-- Se retornar menos de 13 linhas, aplique a estrutura completa:
-- mysql -h HOST -u USUARIO -p NOME_BANCO < database/schema.sql

<?php

return [
    'name' => getenv('APP_NAME') ?: 'Apprumo',
    'env' => getenv('APP_ENV') ?: 'production',
    'debug' => filter_var(getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOLEAN),
    'base_url' => rtrim(getenv('APP_URL') ?: '', '/'),
    'timezone' => getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo',
    'force_https' => filter_var(getenv('APP_FORCE_HTTPS') ?: (getenv('APP_ENV') === 'production' ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN),
    'trust_proxy' => filter_var(getenv('APP_TRUST_PROXY') ?: false, FILTER_VALIDATE_BOOLEAN),
    'support_whatsapp' => getenv('SUPPORT_WHATSAPP') ?: '5511999999999',
    'default_button_color' => getenv('DEFAULT_BUTTON_COLOR') ?: '#ddb76a',
    'admin_emails' => array_values(array_filter(array_map('trim', explode(',', getenv('ADMIN_EMAILS') ?: '')))),
    'upload_max_bytes' => (int) (getenv('UPLOAD_MAX_BYTES') ?: 5242880),
    'session_cookie_secure' => filter_var(getenv('SESSION_COOKIE_SECURE') ?: (getenv('APP_ENV') === 'production' ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN),
    'session_same_site' => getenv('SESSION_SAME_SITE') ?: 'Lax',
    'session_idle_timeout' => (int) (getenv('SESSION_IDLE_TIMEOUT') ?: 7200),
    'plan_expiry_warning_days' => (int) (getenv('PLAN_EXPIRY_WARNING_DAYS') ?: 7),
    'admin_plan_due_soon_days' => (int) (getenv('ADMIN_PLAN_DUE_SOON_DAYS') ?: 10),
];

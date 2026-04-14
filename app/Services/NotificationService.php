<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * NotificationService — handles automatic email and SMS notifications.
 *
 * Email: uses PHP mail() with HTML templates.
 * SMS:   uses generic HTTP API (Twilio-compatible). Configure via env vars:
 *        SMS_API_URL, SMS_API_KEY, SMS_FROM_NUMBER
 *
 * All notifications are logged in `notification_log` table for audit.
 */
final class NotificationService
{
    // ── Notification triggers ────────────────────────────────────────

    /**
     * Send confirmation notification when an appointment is created.
     */
    public static function appointmentCreated(int $vendorId, int $appointmentId): void
    {
        $data = self::appointmentData($vendorId, $appointmentId);
        if (!$data) {
            return;
        }

        $vendor = $data['vendor'];
        $appointment = $data['appointment'];
        $businessName = $vendor['business_name'];
        $date = format_date($appointment['appointment_date']);
        $time = format_time($appointment['start_time']);
        $service = $appointment['service_title'] ?? 'Atendimento';

        // Email to customer
        if (!empty($appointment['customer_email'])) {
            self::sendEmail(
                $appointment['customer_email'],
                "Agendamento confirmado — {$businessName}",
                self::renderTemplate('appointment_confirmed', [
                    'customer_name' => $appointment['customer_name'],
                    'business_name' => $businessName,
                    'service' => $service,
                    'date' => $date,
                    'time' => $time,
                    'price' => money((float) $appointment['price']),
                    'vendor_phone' => $vendor['phone'] ?? '',
                ]),
                $vendorId,
                $appointmentId,
                'appointment_confirmed'
            );
        }

        // SMS to customer
        if (!empty($appointment['customer_phone'])) {
            $smsBody = "✅ {$businessName}: Seu agendamento de {$service} está confirmado para {$date} às {$time}. Valor: " . money((float) $appointment['price']);
            self::sendSms(
                $appointment['customer_phone'],
                $smsBody,
                $vendorId,
                $appointmentId,
                'appointment_confirmed'
            );
        }

        // Email to vendor (notification)
        if (!empty($vendor['email'])) {
            self::sendEmail(
                $vendor['email'],
                "Novo agendamento — {$appointment['customer_name']}",
                self::renderTemplate('vendor_new_appointment', [
                    'customer_name' => $appointment['customer_name'],
                    'customer_phone' => $appointment['customer_phone'],
                    'service' => $service,
                    'date' => $date,
                    'time' => $time,
                    'price' => money((float) $appointment['price']),
                    'business_name' => $businessName,
                ]),
                $vendorId,
                $appointmentId,
                'vendor_new_appointment'
            );
        }
    }

    /**
     * Send notification when appointment status changes.
     */
    public static function appointmentStatusChanged(int $vendorId, int $appointmentId, string $newStatus): void
    {
        $data = self::appointmentData($vendorId, $appointmentId);
        if (!$data) {
            return;
        }

        $vendor = $data['vendor'];
        $appointment = $data['appointment'];
        $businessName = $vendor['business_name'];
        $date = format_date($appointment['appointment_date']);
        $time = format_time($appointment['start_time']);
        $service = $appointment['service_title'] ?? 'Atendimento';

        $statusMessages = [
            'completed' => [
                'subject' => "Atendimento concluído — {$businessName}",
                'sms' => "✅ {$businessName}: Seu atendimento de {$service} ({$date} às {$time}) foi concluído. Obrigado pela preferência!",
                'template' => 'appointment_completed',
            ],
            'cancelled' => [
                'subject' => "Agendamento cancelado — {$businessName}",
                'sms' => "❌ {$businessName}: Seu agendamento de {$service} para {$date} às {$time} foi cancelado. Entre em contato para reagendar.",
                'template' => 'appointment_cancelled',
            ],
            'no_show' => [
                'subject' => "Falta registrada — {$businessName}",
                'sms' => "⚠️ {$businessName}: Registramos sua ausência no agendamento de {$service} ({$date} às {$time}). Entre em contato para reagendar.",
                'template' => 'appointment_no_show',
            ],
        ];

        if (!isset($statusMessages[$newStatus])) {
            return;
        }

        $msg = $statusMessages[$newStatus];

        if (!empty($appointment['customer_email'])) {
            self::sendEmail(
                $appointment['customer_email'],
                $msg['subject'],
                self::renderTemplate($msg['template'], [
                    'customer_name' => $appointment['customer_name'],
                    'business_name' => $businessName,
                    'service' => $service,
                    'date' => $date,
                    'time' => $time,
                    'price' => money((float) $appointment['price']),
                    'vendor_phone' => $vendor['phone'] ?? '',
                ]),
                $vendorId,
                $appointmentId,
                $msg['template']
            );
        }

        if (!empty($appointment['customer_phone'])) {
            self::sendSms(
                $appointment['customer_phone'],
                $msg['sms'],
                $vendorId,
                $appointmentId,
                $msg['template']
            );
        }
    }

    /**
     * Send payment received notification.
     */
    public static function paymentReceived(int $vendorId, int $transactionId): void
    {
        $transaction = Database::selectOne(
            'SELECT ft.*, a.customer_name, a.customer_email, a.customer_phone,
                    s.title AS service_title
             FROM financial_transactions ft
             LEFT JOIN appointments a ON a.id = ft.appointment_id
             LEFT JOIN services s ON s.id = a.service_id
             WHERE ft.id = :id AND ft.vendor_id = :vendor_id LIMIT 1',
            ['id' => $transactionId, 'vendor_id' => $vendorId]
        );

        if (!$transaction || empty($transaction['customer_email'])) {
            return;
        }

        $vendor = VendorService::findById($vendorId);
        if (!$vendor) {
            return;
        }

        $businessName = $vendor['business_name'];

        if (!empty($transaction['customer_email'])) {
            self::sendEmail(
                $transaction['customer_email'],
                "Pagamento confirmado — {$businessName}",
                self::renderTemplate('payment_received', [
                    'customer_name' => $transaction['customer_name'] ?? 'Cliente',
                    'business_name' => $businessName,
                    'service' => $transaction['service_title'] ?? $transaction['description'] ?? 'Atendimento',
                    'amount' => money((float) $transaction['amount']),
                    'date' => format_date($transaction['transaction_date']),
                ]),
                $vendorId,
                null,
                'payment_received'
            );
        }
    }

    /**
     * Send low stock alert to vendor.
     */
    public static function lowStockAlert(int $vendorId, array $product): void
    {
        $vendor = VendorService::findById($vendorId);
        if (!$vendor || empty($vendor['email'])) {
            return;
        }

        self::sendEmail(
            $vendor['email'],
            "⚠️ Estoque baixo — {$product['name']}",
            self::renderTemplate('low_stock_alert', [
                'business_name' => $vendor['business_name'],
                'product_name' => $product['name'],
                'current_stock' => (int) $product['stock_quantity'],
                'min_stock' => (int) $product['min_stock_quantity'],
            ]),
            $vendorId,
            null,
            'low_stock_alert'
        );
    }

    /**
     * Send appointment reminder (for use via cron/scheduled task).
     */
    public static function sendReminders(): int
    {
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $appointments = Database::select(
            'SELECT a.*, s.title AS service_title, v.business_name, v.phone AS vendor_phone
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             LEFT JOIN vendors v ON v.id = a.vendor_id
             WHERE a.appointment_date = :date
               AND a.status = "confirmed"
               AND a.id NOT IN (
                   SELECT COALESCE(appointment_id, 0) FROM notification_log
                   WHERE event_type = "appointment_reminder"
                     AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
               )',
            ['date' => $tomorrow]
        );

        $sent = 0;
        foreach ($appointments as $appointment) {
            $date = format_date($appointment['appointment_date']);
            $time = format_time($appointment['start_time']);
            $service = $appointment['service_title'] ?? 'Atendimento';
            $businessName = $appointment['business_name'] ?? 'Profissional';

            if (!empty($appointment['customer_email'])) {
                self::sendEmail(
                    $appointment['customer_email'],
                    "Lembrete: agendamento amanhã — {$businessName}",
                    self::renderTemplate('appointment_reminder', [
                        'customer_name' => $appointment['customer_name'],
                        'business_name' => $businessName,
                        'service' => $service,
                        'date' => $date,
                        'time' => $time,
                        'vendor_phone' => $appointment['vendor_phone'] ?? '',
                    ]),
                    (int) $appointment['vendor_id'],
                    (int) $appointment['id'],
                    'appointment_reminder'
                );
                $sent++;
            }

            if (!empty($appointment['customer_phone'])) {
                self::sendSms(
                    $appointment['customer_phone'],
                    "📅 Lembrete {$businessName}: Você tem {$service} amanhã ({$date}) às {$time}. Confirme ou reagende pelo WhatsApp.",
                    (int) $appointment['vendor_id'],
                    (int) $appointment['id'],
                    'appointment_reminder'
                );
                $sent++;
            }
        }

        return $sent;
    }

    // ── Delivery channels ────────────────────────────────────────────

    private static function sendEmail(
        string $to,
        string $subject,
        string $htmlBody,
        int $vendorId,
        ?int $appointmentId,
        string $eventType
    ): bool {
        $fromName = app_config('app.name', 'Apprumo');
        $fromEmail = getenv('MAIL_FROM') ?: 'noreply@apprumo.com.br';
        $smtpHost = getenv('SMTP_HOST') ?: '';
        $smtpPort = (int) (getenv('SMTP_PORT') ?: 587);
        $smtpUser = getenv('SMTP_USER') ?: '';
        $smtpPass = getenv('SMTP_PASS') ?: '';

        $success = false;

        if ($smtpHost !== '' && $smtpUser !== '') {
            $success = self::sendSmtp($smtpHost, $smtpPort, $smtpUser, $smtpPass, $fromEmail, $fromName, $to, $subject, $htmlBody);
        } else {
            $headers = implode("\r\n", [
                "From: {$fromName} <{$fromEmail}>",
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'X-Mailer: Apprumo/2.0',
            ]);
            $success = @mail($to, $subject, $htmlBody, $headers);
        }

        self::log($vendorId, $appointmentId, 'email', $to, $eventType, $success);
        return $success;
    }

    private static function sendSms(
        string $to,
        string $body,
        int $vendorId,
        ?int $appointmentId,
        string $eventType
    ): bool {
        $apiUrl = getenv('SMS_API_URL') ?: '';
        $apiKey = getenv('SMS_API_KEY') ?: '';
        $fromNumber = getenv('SMS_FROM_NUMBER') ?: '';

        if ($apiUrl === '' || $apiKey === '') {
            self::log($vendorId, $appointmentId, 'sms', $to, $eventType, false, 'SMS not configured');
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $to);
        if (strlen($phone) <= 10) {
            $phone = '55' . $phone;
        }

        $payload = json_encode([
            'to' => '+' . $phone,
            'from' => $fromNumber,
            'body' => $body,
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init($apiUrl);
        if ($ch === false) {
            self::log($vendorId, $appointmentId, 'sms', $to, $eventType, false, 'cURL init failed');
            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $success = $httpCode >= 200 && $httpCode < 300;
        self::log($vendorId, $appointmentId, 'sms', $to, $eventType, $success, $success ? null : "HTTP {$httpCode}: {$error}");
        return $success;
    }

    /**
     * Simple SMTP sender (no external lib).
     */
    private static function sendSmtp(
        string $host,
        int $port,
        string $user,
        string $pass,
        string $fromEmail,
        string $fromName,
        string $to,
        string $subject,
        string $htmlBody
    ): bool {
        $socket = @fsockopen(($port === 465 ? 'ssl://' : '') . $host, $port, $errno, $errstr, 10);
        if (!$socket) {
            return false;
        }

        $read = function () use ($socket): string {
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $response;
        };

        $send = function (string $cmd) use ($socket, $read): string {
            fwrite($socket, $cmd . "\r\n");
            return $read();
        };

        $read(); // greeting
        $send('EHLO apprumo');

        if ($port === 587) {
            $send('STARTTLS');
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
            $send('EHLO apprumo');
        }

        $send('AUTH LOGIN');
        $send(base64_encode($user));
        $send(base64_encode($pass));
        $send("MAIL FROM:<{$fromEmail}>");
        $send("RCPT TO:<{$to}>");
        $send('DATA');

        $boundary = md5((string) time());
        $message = implode("\r\n", [
            "From: {$fromName} <{$fromEmail}>",
            "To: {$to}",
            "Subject: {$subject}",
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            '',
            "--{$boundary}",
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $htmlBody,
            '',
            "--{$boundary}--",
        ]);

        $result = $send($message . "\r\n.");
        $send('QUIT');
        fclose($socket);

        return str_starts_with(trim($result), '250');
    }

    // ── Notification log ─────────────────────────────────────────────

    private static function log(
        int $vendorId,
        ?int $appointmentId,
        string $channel,
        string $recipient,
        string $eventType,
        bool $success,
        ?string $errorMessage = null
    ): void {
        try {
            Database::statement(
                'INSERT INTO notification_log (vendor_id, appointment_id, channel, recipient, event_type, success, error_message, created_at)
                 VALUES (:vendor_id, :appointment_id, :channel, :recipient, :event_type, :success, :error_message, NOW())',
                [
                    'vendor_id' => $vendorId,
                    'appointment_id' => $appointmentId,
                    'channel' => $channel,
                    'recipient' => $recipient,
                    'event_type' => $eventType,
                    'success' => $success ? 1 : 0,
                    'error_message' => $errorMessage,
                ]
            );
        } catch (\Throwable $e) {
            // Silently fail — notification log should not block operations
            error_log('NotificationService::log error: ' . $e->getMessage());
        }
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private static function appointmentData(int $vendorId, int $appointmentId): ?array
    {
        $appointment = Database::selectOne(
            'SELECT a.*, s.title AS service_title
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             WHERE a.id = :id AND a.vendor_id = :vendor_id LIMIT 1',
            ['id' => $appointmentId, 'vendor_id' => $vendorId]
        );

        if (!$appointment) {
            return null;
        }

        $vendor = VendorService::findById($vendorId);
        if (!$vendor) {
            return null;
        }

        return ['appointment' => $appointment, 'vendor' => $vendor];
    }

    /**
     * Render an email template with variables.
     */
    private static function renderTemplate(string $template, array $vars): string
    {
        $appName = app_config('app.name', 'Apprumo');
        $brandColor = '#1e293b';
        $accentColor = '#1AB2C7';

        $contentMap = [
            'appointment_confirmed' => "
                <h2 style='color:{$brandColor};margin:0 0 8px;'>Agendamento confirmado! ✅</h2>
                <p>Olá <strong>{{customer_name}}</strong>,</p>
                <p>Seu agendamento em <strong>{{business_name}}</strong> foi confirmado:</p>
                <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Serviço</td><td style='padding:8px 12px;border:1px solid #eee;'>{{service}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Data</td><td style='padding:8px 12px;border:1px solid #eee;'>{{date}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Horário</td><td style='padding:8px 12px;border:1px solid #eee;'>{{time}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Valor</td><td style='padding:8px 12px;border:1px solid #eee;'>{{price}}</td></tr>
                </table>
                <p style='color:#64748b;font-size:0.9rem;'>Caso precise cancelar ou reagendar, entre em contato pelo telefone: {{vendor_phone}}</p>",

            'appointment_completed' => "
                <h2 style='color:{$brandColor};margin:0 0 8px;'>Atendimento concluído ✅</h2>
                <p>Olá <strong>{{customer_name}}</strong>,</p>
                <p>Seu atendimento de <strong>{{service}}</strong> em <strong>{{business_name}}</strong> foi concluído com sucesso.</p>
                <p><strong>Data:</strong> {{date}} às {{time}}<br><strong>Valor:</strong> {{price}}</p>
                <p>Obrigado pela preferência! Esperamos vê-lo novamente em breve.</p>",

            'appointment_cancelled' => "
                <h2 style='color:#e11d48;margin:0 0 8px;'>Agendamento cancelado ❌</h2>
                <p>Olá <strong>{{customer_name}}</strong>,</p>
                <p>Seu agendamento de <strong>{{service}}</strong> para <strong>{{date}} às {{time}}</strong> em <strong>{{business_name}}</strong> foi cancelado.</p>
                <p style='color:#64748b;'>Para reagendar, entre em contato pelo telefone: {{vendor_phone}}</p>",

            'appointment_no_show' => "
                <h2 style='color:#d97706;margin:0 0 8px;'>Ausência registrada ⚠️</h2>
                <p>Olá <strong>{{customer_name}}</strong>,</p>
                <p>Registramos sua ausência no agendamento de <strong>{{service}}</strong> em <strong>{{business_name}}</strong> (<strong>{{date}} às {{time}}</strong>).</p>
                <p style='color:#64748b;'>Para reagendar, entre em contato pelo telefone: {{vendor_phone}}</p>",

            'appointment_reminder' => "
                <h2 style='color:{$brandColor};margin:0 0 8px;'>Lembrete de agendamento 📅</h2>
                <p>Olá <strong>{{customer_name}}</strong>,</p>
                <p>Este é um lembrete do seu agendamento <strong>amanhã</strong> em <strong>{{business_name}}</strong>:</p>
                <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Serviço</td><td style='padding:8px 12px;border:1px solid #eee;'>{{service}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Data</td><td style='padding:8px 12px;border:1px solid #eee;'>{{date}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Horário</td><td style='padding:8px 12px;border:1px solid #eee;'>{{time}}</td></tr>
                </table>
                <p style='color:#64748b;font-size:0.9rem;'>Caso precise cancelar ou reagendar, entre em contato: {{vendor_phone}}</p>",

            'vendor_new_appointment' => "
                <h2 style='color:{$brandColor};margin:0 0 8px;'>Novo agendamento recebido! 🔔</h2>
                <p>Você recebeu um novo agendamento em <strong>{{business_name}}</strong>:</p>
                <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Cliente</td><td style='padding:8px 12px;border:1px solid #eee;'>{{customer_name}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Telefone</td><td style='padding:8px 12px;border:1px solid #eee;'>{{customer_phone}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Serviço</td><td style='padding:8px 12px;border:1px solid #eee;'>{{service}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Data</td><td style='padding:8px 12px;border:1px solid #eee;'>{{date}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Horário</td><td style='padding:8px 12px;border:1px solid #eee;'>{{time}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Valor</td><td style='padding:8px 12px;border:1px solid #eee;'>{{price}}</td></tr>
                </table>",

            'payment_received' => "
                <h2 style='color:{$brandColor};margin:0 0 8px;'>Pagamento confirmado 💰</h2>
                <p>Olá <strong>{{customer_name}}</strong>,</p>
                <p>Confirmamos o recebimento do pagamento de <strong>{{amount}}</strong> referente a <strong>{{service}}</strong> em <strong>{{business_name}}</strong>.</p>
                <p><strong>Data:</strong> {{date}}</p>
                <p>Obrigado!</p>",

            'low_stock_alert' => "
                <h2 style='color:#d97706;margin:0 0 8px;'>Alerta de estoque baixo ⚠️</h2>
                <p>Olá! O produto <strong>{{product_name}}</strong> em <strong>{{business_name}}</strong> está com estoque baixo:</p>
                <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Produto</td><td style='padding:8px 12px;border:1px solid #eee;'>{{product_name}}</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Estoque atual</td><td style='padding:8px 12px;border:1px solid #eee;'>{{current_stock}} unidades</td></tr>
                    <tr><td style='padding:8px 12px;border:1px solid #eee;font-weight:600;background:#f8fafc;'>Estoque mínimo</td><td style='padding:8px 12px;border:1px solid #eee;'>{{min_stock}} unidades</td></tr>
                </table>
                <p>Recomendamos reabastecer o quanto antes.</p>",
        ];

        $content = $contentMap[$template] ?? '<p>Notificação do sistema.</p>';

        foreach ($vars as $key => $value) {
            $content = str_replace('{{' . $key . '}}', htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'), $content);
        }

        return "<!DOCTYPE html>
<html lang='pt-BR'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;background:#f8fafc;font-family:\"Plus Jakarta Sans\",system-ui,sans-serif;'>
<table role='presentation' width='100%' style='background:#f8fafc;padding:32px 16px;'>
<tr><td align='center'>
<table role='presentation' width='100%' style='max-width:560px;background:#ffffff;border-radius:18px;box-shadow:0 8px 30px rgba(14,43,71,0.08);overflow:hidden;'>
    <tr><td style='background:linear-gradient(135deg,{$brandColor} 0%,#312e81 100%);padding:24px 28px;text-align:center;'>
        <span style='color:{$accentColor};font-size:1.4rem;font-weight:800;letter-spacing:0.02em;'>{$appName}</span>
    </td></tr>
    <tr><td style='padding:28px;font-size:0.95rem;line-height:1.65;color:#0f172a;'>
        {$content}
    </td></tr>
    <tr><td style='padding:16px 28px;border-top:1px solid #eee;text-align:center;'>
        <p style='margin:0;font-size:0.78rem;color:#94a3b3;'>Esta é uma notificação automática do {$appName}.<br>Não responda diretamente a este e-mail.</p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>";
    }

    /**
     * Get notification settings for a vendor.
     */
    public static function getSettings(int $vendorId): array
    {
        $settings = Database::selectOne(
            'SELECT * FROM notification_settings WHERE vendor_id = :vendor_id LIMIT 1',
            ['vendor_id' => $vendorId]
        );

        return $settings ?: [
            'vendor_id' => $vendorId,
            'email_enabled' => 1,
            'sms_enabled' => 1,
            'notify_on_booking' => 1,
            'notify_on_status_change' => 1,
            'notify_on_payment' => 1,
            'notify_on_low_stock' => 1,
            'send_reminders' => 1,
        ];
    }

    /**
     * Update notification settings for a vendor.
     */
    public static function updateSettings(int $vendorId, array $data): void
    {
        $existing = Database::selectOne(
            'SELECT id FROM notification_settings WHERE vendor_id = :vendor_id LIMIT 1',
            ['vendor_id' => $vendorId]
        );

        $fields = [
            'email_enabled' => isset($data['email_enabled']) ? 1 : 0,
            'sms_enabled' => isset($data['sms_enabled']) ? 1 : 0,
            'notify_on_booking' => isset($data['notify_on_booking']) ? 1 : 0,
            'notify_on_status_change' => isset($data['notify_on_status_change']) ? 1 : 0,
            'notify_on_payment' => isset($data['notify_on_payment']) ? 1 : 0,
            'notify_on_low_stock' => isset($data['notify_on_low_stock']) ? 1 : 0,
            'send_reminders' => isset($data['send_reminders']) ? 1 : 0,
        ];

        if ($existing) {
            Database::statement(
                'UPDATE notification_settings SET
                    email_enabled = :email_enabled,
                    sms_enabled = :sms_enabled,
                    notify_on_booking = :notify_on_booking,
                    notify_on_status_change = :notify_on_status_change,
                    notify_on_payment = :notify_on_payment,
                    notify_on_low_stock = :notify_on_low_stock,
                    send_reminders = :send_reminders,
                    updated_at = NOW()
                 WHERE vendor_id = :vendor_id',
                $fields + ['vendor_id' => $vendorId]
            );
        } else {
            Database::statement(
                'INSERT INTO notification_settings (vendor_id, email_enabled, sms_enabled, notify_on_booking, notify_on_status_change, notify_on_payment, notify_on_low_stock, send_reminders, created_at, updated_at)
                 VALUES (:vendor_id, :email_enabled, :sms_enabled, :notify_on_booking, :notify_on_status_change, :notify_on_payment, :notify_on_low_stock, :send_reminders, NOW(), NOW())',
                $fields + ['vendor_id' => $vendorId]
            );
        }
    }

    /**
     * Get notification history for a vendor.
     */
    public static function history(int $vendorId, int $limit = 50): array
    {
        return Database::select(
            'SELECT * FROM notification_log
             WHERE vendor_id = :vendor_id
             ORDER BY created_at DESC
             LIMIT ' . $limit,
            ['vendor_id' => $vendorId]
        );
    }
}

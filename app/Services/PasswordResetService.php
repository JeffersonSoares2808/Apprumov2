<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class PasswordResetService
{
    private const TOKEN_EXPIRY_MINUTES = 60;

    /**
     * Create a password reset token and send email with reset link.
     */
    public static function sendResetLink(string $email): void
    {
        $email = trim($email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Informe um e-mail válido.');
        }

        $user = Database::selectOne(
            'SELECT id, email, full_name FROM platform_users WHERE email = :email LIMIT 1',
            ['email' => $email]
        );

        if (!$user) {
            // Don't reveal whether email exists - still show success
            return;
        }

        // Invalidate old tokens
        Database::statement(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE email = :email AND used_at IS NULL',
            ['email' => $email]
        );

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + (self::TOKEN_EXPIRY_MINUTES * 60));

        Database::statement(
            'INSERT INTO password_reset_tokens (email, token, expires_at, created_at)
             VALUES (:email, :token, :expires_at, NOW())',
            [
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt,
            ]
        );

        // Send email
        $resetUrl = base_url('reset-password?token=' . $token);
        $htmlBody = self::buildResetEmail($user['full_name'], $resetUrl);
        self::sendEmail($email, 'Redefinir senha — Apprumo', $htmlBody);
    }

    /**
     * Validate a reset token and return the associated email.
     */
    public static function validateToken(string $token): ?string
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $record = Database::selectOne(
            'SELECT email, expires_at, used_at FROM password_reset_tokens
             WHERE token = :token LIMIT 1',
            ['token' => $token]
        );

        if (!$record) {
            return null;
        }

        if ($record['used_at'] !== null) {
            return null;
        }

        if (strtotime($record['expires_at']) < time()) {
            return null;
        }

        return $record['email'];
    }

    /**
     * Reset the user's password using a valid token.
     */
    public static function resetPassword(string $token, string $password, string $passwordConfirm): void
    {
        $email = self::validateToken($token);
        if (!$email) {
            throw new RuntimeException('Link de redefinição inválido ou expirado. Solicite um novo.');
        }

        if (mb_strlen($password) < 8) {
            throw new RuntimeException('A senha deve ter pelo menos 8 caracteres.');
        }

        if ($password !== $passwordConfirm) {
            throw new RuntimeException('As senhas não conferem.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!$hash) {
            throw new RuntimeException('Erro ao gerar a senha. Tente novamente.');
        }

        Database::statement(
            'UPDATE platform_users SET password_hash = :hash, updated_at = NOW() WHERE email = :email',
            ['hash' => $hash, 'email' => $email]
        );

        // Mark token as used
        Database::statement(
            'UPDATE password_reset_tokens SET used_at = NOW() WHERE token = :token',
            ['token' => $token]
        );
    }

    /**
     * Build beautiful HTML email for password reset.
     */
    private static function buildResetEmail(string $name, string $resetUrl): string
    {
        $appName = app_config('app.name', 'Apprumo');

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',system-ui,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:40px 20px;">
<tr><td align="center">
<table width="100%" style="max-width:520px;background:#ffffff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);overflow:hidden;">
    <tr><td style="background:linear-gradient(135deg,#071A2A 0%,#0d2d47 100%);padding:32px 40px;text-align:center;">
        <h1 style="color:#ffffff;font-size:24px;margin:0;">{$appName}</h1>
        <p style="color:rgba(255,255,255,0.7);font-size:13px;margin:8px 0 0;">Gestão Integrada para Profissionais</p>
    </td></tr>
    <tr><td style="padding:40px;">
        <h2 style="color:#0f172a;font-size:20px;margin:0 0 8px;">Olá, {$name}!</h2>
        <p style="color:#64748b;font-size:15px;line-height:1.6;margin:0 0 24px;">
            Recebemos uma solicitação para redefinir a senha da sua conta. Clique no botão abaixo para criar uma nova senha:
        </p>
        <table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
            <a href="{$resetUrl}" style="display:inline-block;background:linear-gradient(135deg,#1AB2C7 0%,#0d9aad 100%);color:#ffffff;text-decoration:none;padding:14px 40px;border-radius:10px;font-size:16px;font-weight:600;letter-spacing:0.5px;">
                Redefinir minha senha
            </a>
        </td></tr></table>
        <p style="color:#94a3b8;font-size:13px;line-height:1.5;margin:24px 0 0;">
            Este link expira em 60 minutos. Se você não solicitou a redefinição, ignore este e-mail.
        </p>
        <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">
        <p style="color:#cbd5e1;font-size:12px;margin:0;text-align:center;">
            Se o botão não funcionar, copie e cole este link no navegador:<br>
            <span style="color:#1AB2C7;word-break:break-all;">{$resetUrl}</span>
        </p>
    </td></tr>
    <tr><td style="background:#f8fafc;padding:20px 40px;text-align:center;">
        <p style="color:#94a3b8;font-size:12px;margin:0;">© {$appName} — JS Sistemas Inteligentes</p>
    </td></tr>
</table>
</td></tr></table>
</body>
</html>
HTML;
    }

    /**
     * Send email using SMTP or PHP mail().
     */
    private static function sendEmail(string $to, string $subject, string $htmlBody): bool
    {
        $fromName = app_config('app.name', 'Apprumo');
        $fromEmail = getenv('MAIL_FROM') ?: 'noreply@apprumo.com.br';
        $smtpHost = getenv('SMTP_HOST') ?: '';
        $smtpPort = (int) (getenv('SMTP_PORT') ?: 587);
        $smtpUser = getenv('SMTP_USER') ?: '';
        $smtpPass = getenv('SMTP_PASS') ?: '';

        if ($smtpHost !== '' && $smtpUser !== '') {
            return self::sendSmtp($smtpHost, $smtpPort, $smtpUser, $smtpPass, $fromEmail, $fromName, $to, $subject, $htmlBody);
        }

        $headers = implode("\r\n", [
            "From: {$fromName} <{$fromEmail}>",
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: Apprumo/1.0',
        ]);

        return @mail($to, $subject, $htmlBody, $headers);
    }

    /**
     * Send email via SMTP (fsockopen-based, no external dependencies).
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
        $prefix = ($port === 465) ? 'ssl://' : '';
        $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);
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

        $write = function (string $cmd) use ($socket): void {
            fwrite($socket, $cmd . "\r\n");
        };

        $read(); // greeting

        $write("EHLO localhost");
        $read();

        if ($port === 587) {
            $write("STARTTLS");
            $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write("EHLO localhost");
            $read();
        }

        $write("AUTH LOGIN");
        $read();
        $write(base64_encode($user));
        $read();
        $write(base64_encode($pass));
        $auth = $read();

        if (!str_starts_with($auth, '235')) {
            fclose($socket);
            return false;
        }

        $write("MAIL FROM:<{$fromEmail}>");
        $read();
        $write("RCPT TO:<{$to}>");
        $read();
        $write("DATA");
        $read();

        $boundary = md5(uniqid((string) time()));
        $message = "From: {$fromName} <{$fromEmail}>\r\n";
        $message .= "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "\r\n";
        $message .= $htmlBody;
        $message .= "\r\n.";

        $write($message);
        $result = $read();

        $write("QUIT");
        fclose($socket);

        return str_starts_with($result, '250');
    }
}

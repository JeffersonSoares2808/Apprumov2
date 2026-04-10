<?php

declare(strict_types=1);

namespace App\Services;

use App\Security\SecurityLogger;

final class UploadService
{
    public static function storeImage(?array $file, string $directory = 'general'): ?string
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            SecurityLogger::warning('upload_error', ['directory' => $directory, 'error' => $file['error'] ?? null]);
            return null;
        }

        if ((int) ($file['size'] ?? 0) > (int) app_config('app.upload_max_bytes', 5242880)) {
            SecurityLogger::warning('upload_too_large', ['directory' => $directory, 'size' => $file['size'] ?? null]);
            return null;
        }

        $mime = mime_content_type($file['tmp_name']) ?: '';
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            SecurityLogger::warning('upload_invalid_mime', ['directory' => $directory, 'mime' => $mime]);
            return null;
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            SecurityLogger::warning('upload_invalid_image', ['directory' => $directory]);
            return null;
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $folder = BASE_PATH . '/uploads/' . trim($directory, '/');
        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }

        $filename = $directory . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $folder . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            SecurityLogger::warning('upload_move_failed', ['directory' => $directory, 'target' => $target]);
            return null;
        }

        return '/uploads/' . trim($directory, '/') . '/' . $filename;
    }
}

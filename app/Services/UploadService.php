<?php

declare(strict_types=1);

namespace App\Services;

use App\Security\SecurityLogger;

final class UploadService
{
    /** Maximum width/height for uploaded images (preserves aspect ratio). */
    private const MAX_IMAGE_DIMENSION = 1200;

    /** JPEG quality for resized images (0–100). */
    private const JPEG_QUALITY = 82;

    /** WebP quality for resized images (0–100). */
    private const WEBP_QUALITY = 80;

    /** PNG compression level (0–9, higher = smaller file). */
    private const PNG_COMPRESSION = 7;

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

        // Resize and compress image to reduce page weight
        self::optimizeImage($target, $mime, $imageInfo[0] ?? 0, $imageInfo[1] ?? 0);

        return '/uploads/' . trim($directory, '/') . '/' . $filename;
    }

    /**
     * Resize and compress an image in-place if it exceeds MAX_IMAGE_DIMENSION.
     */
    private static function optimizeImage(string $path, string $mime, int $origWidth, int $origHeight): void
    {
        if (!function_exists('imagecreatefromjpeg')) {
            return; // GD not available — skip optimization
        }

        $maxDim = self::MAX_IMAGE_DIMENSION;

        // Only resize if image is larger than the max dimension
        $needsResize = $origWidth > $maxDim || $origHeight > $maxDim;

        if (!$needsResize) {
            // Still re-save to apply compression even if dimensions are fine
            self::recompressImage($path, $mime);
            return;
        }

        // Calculate new dimensions preserving aspect ratio
        $ratio = min($maxDim / max($origWidth, 1), $maxDim / max($origHeight, 1));
        $newWidth = (int) round($origWidth * $ratio);
        $newHeight = (int) round($origHeight * $ratio);

        $source = match ($mime) {
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => @imagecreatefromjpeg($path),
        };

        if ($source === false) {
            SecurityLogger::warning('upload_image_resize_failed', ['path' => basename($path), 'mime' => $mime]);
            return;
        }

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if ($resized === false) {
            SecurityLogger::warning('upload_image_canvas_failed', ['width' => $newWidth, 'height' => $newHeight]);
            imagedestroy($source);
            return;
        }

        // Preserve transparency for PNG/WebP
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            if ($transparent !== false) {
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
        }

        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        match ($mime) {
            'image/png' => imagepng($resized, $path, self::PNG_COMPRESSION),
            'image/webp' => imagewebp($resized, $path, self::WEBP_QUALITY),
            default => imagejpeg($resized, $path, self::JPEG_QUALITY),
        };

        imagedestroy($source);
        imagedestroy($resized);
    }

    /**
     * Re-compress an image without resizing (to reduce file size).
     */
    private static function recompressImage(string $path, string $mime): void
    {
        $source = match ($mime) {
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => @imagecreatefromjpeg($path),
        };

        if ($source === false) {
            return;
        }

        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($source, false);
            imagesavealpha($source, true);
        }

        match ($mime) {
            'image/png' => imagepng($source, $path, self::PNG_COMPRESSION),
            'image/webp' => imagewebp($source, $path, self::WEBP_QUALITY),
            default => imagejpeg($source, $path, self::JPEG_QUALITY),
        };

        imagedestroy($source);
    }
}

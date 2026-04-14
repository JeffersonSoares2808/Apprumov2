<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class ServiceCatalogService
{
    public static function save(int $vendorId, array $data, array $files = []): void
    {
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new RuntimeException('Informe o título do serviço.');
        }

        $existing = $id > 0 ? self::find($vendorId, $id) : null;
        $imagePath = UploadService::storeImage($files['image'] ?? null, 'services') ?: ($existing['image_path'] ?? null);

        $payload = [
            'vendor_id' => $vendorId,
            'title' => $title,
            'description' => trim((string) ($data['description'] ?? '')),
            'duration_minutes' => max(5, (int) ($data['duration_minutes'] ?? 30)),
            'price' => (float) ($data['price'] ?? 0),
            'image_path' => $imagePath,
            'is_active' => isset($data['is_active']) ? 1 : 0,
            'has_return' => isset($data['has_return']) ? 1 : 0,
            'return_quantity' => max(1, (int) ($data['return_quantity'] ?? 1)),
            'return_days' => max(1, (int) ($data['return_days'] ?? 30)),
        ];

        if ($existing) {
            Database::statement(
                'UPDATE services SET
                    title = :title,
                    description = :description,
                    duration_minutes = :duration_minutes,
                    price = :price,
                    image_path = :image_path,
                    is_active = :is_active,
                    has_return = :has_return,
                    return_quantity = :return_quantity,
                    return_days = :return_days,
                    updated_at = NOW()
                 WHERE id = :id AND vendor_id = :vendor_id',
                $payload + ['id' => $id]
            );

            return;
        }

        Database::statement(
            'INSERT INTO services (
                vendor_id, title, description, duration_minutes, price, image_path, is_active, has_return, return_quantity, return_days, created_at, updated_at
             ) VALUES (
                :vendor_id, :title, :description, :duration_minutes, :price, :image_path, :is_active, :has_return, :return_quantity, :return_days, NOW(), NOW()
             )',
            $payload
        );
    }

    public static function toggle(int $vendorId, int $serviceId): void
    {
        $service = self::find($vendorId, $serviceId);
        if (!$service) {
            throw new RuntimeException('Serviço não encontrado.');
        }

        Database::statement(
            'UPDATE services SET is_active = :is_active, updated_at = NOW() WHERE id = :id AND vendor_id = :vendor_id',
            [
                'is_active' => (int) !$service['is_active'],
                'id' => $serviceId,
                'vendor_id' => $vendorId,
            ]
        );
    }

    public static function delete(int $vendorId, int $serviceId): void
    {
        Database::statement('DELETE FROM services WHERE id = :id AND vendor_id = :vendor_id', [
            'id' => $serviceId,
            'vendor_id' => $vendorId,
        ]);
    }

    public static function find(int $vendorId, int $serviceId): ?array
    {
        return Database::selectOne(
            'SELECT * FROM services WHERE id = :id AND vendor_id = :vendor_id LIMIT 1',
            ['id' => $serviceId, 'vendor_id' => $vendorId]
        );
    }
}

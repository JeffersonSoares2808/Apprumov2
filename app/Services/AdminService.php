<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class AdminService
{
    public static function savePlan(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $price = (float) ($data['price'] ?? 0);
        $durationDays = max(1, (int) ($data['duration_days'] ?? 30));
        $maxProfessionals = max(0, (int) ($data['max_professionals'] ?? 0));
        $description = trim((string) ($data['description'] ?? ''));
        $isActive = isset($data['is_active']) ? 1 : 0;

        if ($name === '') {
            throw new RuntimeException('Informe o nome do plano.');
        }

        if ($id > 0) {
            Database::statement(
                'UPDATE plans
                 SET name = :name, price = :price, duration_days = :duration_days, max_professionals = :max_professionals,
                     description = :description, is_active = :is_active, updated_at = NOW()
                 WHERE id = :id',
                [
                    'name' => $name,
                    'price' => $price,
                    'duration_days' => $durationDays,
                    'max_professionals' => $maxProfessionals,
                    'description' => $description,
                    'is_active' => $isActive,
                    'id' => $id,
                ]
            );

            return;
        }

        Database::statement(
            'INSERT INTO plans (name, price, duration_days, max_professionals, description, is_active, created_at, updated_at)
             VALUES (:name, :price, :duration_days, :max_professionals, :description, :is_active, NOW(), NOW())',
            [
                'name' => $name,
                'price' => $price,
                'duration_days' => $durationDays,
                'max_professionals' => $maxProfessionals,
                'description' => $description,
                'is_active' => $isActive,
            ]
        );
    }

    public static function deletePlan(int $planId): void
    {
        Database::statement('DELETE FROM plans WHERE id = :id', ['id' => $planId]);
    }

    public static function activateVendor(int $vendorId, int $planId): void
    {
        $plan = Database::selectOne('SELECT * FROM plans WHERE id = :id LIMIT 1', ['id' => $planId]);
        if (!$plan) {
            throw new RuntimeException('Plano não encontrado.');
        }

        $start = date('Y-m-d');
        $expires = date('Y-m-d', strtotime($start . ' +' . (int) $plan['duration_days'] . ' days'));

        Database::statement(
            'UPDATE vendors
             SET status = \'active\',
                 plan_id = :plan_id,
                 plan_started_at = :start_date,
                 plan_expires_at = :expires_at,
                 updated_at = NOW()
             WHERE id = :id',
            [
                'plan_id' => $planId,
                'start_date' => $start,
                'expires_at' => $expires,
                'id' => $vendorId,
            ]
        );
    }

    public static function renewVendor(int $vendorId, int $planId): void
    {
        $vendor = VendorService::findById($vendorId);
        if (!$vendor) {
            throw new RuntimeException('Vendor não encontrado.');
        }

        $plan = Database::selectOne('SELECT * FROM plans WHERE id = :id LIMIT 1', ['id' => $planId]);
        if (!$plan) {
            throw new RuntimeException('Plano não encontrado.');
        }

        $durationDays = max(1, (int) ($plan['duration_days'] ?? 30));
        $today = date('Y-m-d');
        $currentExpires = !empty($vendor['plan_expires_at']) ? (string) $vendor['plan_expires_at'] : '';

        // Se ainda está ativo e com vencimento no futuro, renovamos a partir do vencimento (não perde dias).
        // Caso contrário, reinicia a partir de hoje.
        $base = $today;
        if (($vendor['status'] ?? '') === 'active' && $currentExpires !== '' && strtotime($currentExpires) > strtotime($today)) {
            $base = $currentExpires;
        }

        $start = $today;
        $expires = date('Y-m-d', strtotime($base . ' +' . $durationDays . ' days'));

        Database::statement(
            'UPDATE vendors
             SET status = \'active\',
                 plan_id = :plan_id,
                 plan_started_at = :start_date,
                 plan_expires_at = :expires_at,
                 updated_at = NOW()
             WHERE id = :id',
            [
                'plan_id' => $planId,
                'start_date' => $start,
                'expires_at' => $expires,
                'id' => $vendorId,
            ]
        );
    }

    public static function suspendVendor(int $vendorId): void
    {
        Database::statement(
            'UPDATE vendors SET status = \'suspended\', updated_at = NOW() WHERE id = :id',
            ['id' => $vendorId]
        );
    }

    public static function reactivateVendor(int $vendorId, ?int $planId = null): void
    {
        $vendor = VendorService::findById($vendorId);
        if (!$vendor) {
            throw new RuntimeException('Vendor não encontrado.');
        }

        $targetPlanId = $planId ?: (int) ($vendor['plan_id'] ?? 0);
        if ($targetPlanId <= 0) {
            throw new RuntimeException('Selecione um plano para reativar este vendor.');
        }

        self::activateVendor($vendorId, $targetPlanId);
    }
}

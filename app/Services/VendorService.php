<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class VendorService
{
    private static function daysUntilDate(?string $date): ?int
    {
        if (!$date) {
            return null;
        }

        $target = strtotime($date . ' 23:59:59');
        if ($target === false) {
            return null;
        }

        $today = strtotime(date('Y-m-d') . ' 00:00:00');
        if ($today === false) {
            return null;
        }

        return (int) ceil(($target - $today) / 86400);
    }

    public static function findByUserId(int $userId): ?array
    {
        $vendor = Database::selectOne(
            'SELECT v.*, p.name AS plan_name
             FROM vendors v
             LEFT JOIN plans p ON p.id = v.plan_id
             WHERE v.user_id = :user_id
             LIMIT 1',
            ['user_id' => $userId]
        );

        if (!$vendor) {
            // Multiusuário: fallback para vínculo via vendor_users
            $vendor = Database::selectOne(
                'SELECT v.*, p.name AS plan_name
                 FROM vendor_users vu
                 INNER JOIN vendors v ON v.id = vu.vendor_id
                 LEFT JOIN plans p ON p.id = v.plan_id
                 WHERE vu.user_id = :user_id
                 ORDER BY FIELD(vu.role, "owner", "manager", "staff"), v.created_at ASC
                 LIMIT 1',
                ['user_id' => $userId]
            );
            if (!$vendor) {
                return null;
            }
        }

        return self::refreshStatus($vendor);
    }

    public static function listForUser(int $userId): array
    {
        $rows = Database::select(
            'SELECT DISTINCT v.*, p.name AS plan_name
             FROM (
                 SELECT id AS vendor_id FROM vendors WHERE user_id = :user_id_owner
                 UNION ALL
                 SELECT vendor_id FROM vendor_users WHERE user_id = :user_id_member
             ) x
             INNER JOIN vendors v ON v.id = x.vendor_id
             LEFT JOIN plans p ON p.id = v.plan_id
             ORDER BY v.created_at ASC',
            [
                'user_id_owner' => $userId,
                'user_id_member' => $userId,
            ]
        );

        return array_map(fn (array $vendor): array => self::refreshStatus($vendor), $rows);
    }

    public static function userHasAccess(int $userId, int $vendorId): bool
    {
        $directOwner = Database::selectOne(
            'SELECT id FROM vendors WHERE id = :vendor_id AND user_id = :user_id LIMIT 1',
            ['vendor_id' => $vendorId, 'user_id' => $userId]
        );
        if ($directOwner) {
            return true;
        }

        $viaMembership = Database::selectOne(
            'SELECT id FROM vendor_users WHERE vendor_id = :vendor_id AND user_id = :user_id LIMIT 1',
            ['vendor_id' => $vendorId, 'user_id' => $userId]
        );

        return (bool) $viaMembership;
    }

    public static function attachUser(int $vendorId, int $userId, string $role = 'staff'): void
    {
        $role = in_array($role, ['owner', 'manager', 'staff'], true) ? $role : 'staff';

        Database::statement(
            'INSERT INTO vendor_users (vendor_id, user_id, role, created_at, updated_at)
             VALUES (:vendor_id, :user_id, :role, NOW(), NOW())
             ON DUPLICATE KEY UPDATE role = VALUES(role), updated_at = NOW()',
            [
                'vendor_id' => $vendorId,
                'user_id' => $userId,
                'role' => $role,
            ]
        );
    }

    public static function findById(int $vendorId): ?array
    {
        $vendor = Database::selectOne(
            'SELECT v.*, p.name AS plan_name, u.full_name, u.email
             FROM vendors v
             INNER JOIN platform_users u ON u.id = v.user_id
             LEFT JOIN plans p ON p.id = v.plan_id
             WHERE v.id = :id
             LIMIT 1',
            ['id' => $vendorId]
        );

        if (!$vendor) {
            return null;
        }

        return self::refreshStatus($vendor);
    }

    public static function findBySlug(string $slug): ?array
    {
        $vendor = Database::selectOne(
            'SELECT v.*, p.name AS plan_name
             FROM vendors v
             LEFT JOIN plans p ON p.id = v.plan_id
             WHERE v.slug = :slug
             LIMIT 1',
            ['slug' => $slug]
        );

        if (!$vendor) {
            return null;
        }

        return self::refreshStatus($vendor);
    }

    public static function listAll(string $filter = 'all'): array
    {
        $vendors = Database::select(
            'SELECT v.*, p.name AS plan_name, p.duration_days, u.full_name, u.email
             FROM vendors v
             INNER JOIN platform_users u ON u.id = v.user_id
             LEFT JOIN plans p ON p.id = v.plan_id
             ORDER BY FIELD(v.status, \'pending\', \'active\', \'suspended\', \'expired\'), v.created_at DESC'
        );

        $vendors = array_map(fn (array $vendor): array => self::refreshStatus($vendor), $vendors);

        if ($filter === 'all') {
            return $vendors;
        }

        if ($filter === 'due_soon') {
            $window = (int) app_config('app.admin_plan_due_soon_days', 10);

            return array_values(array_filter($vendors, static function (array $vendor) use ($window): bool {
                if (($vendor['status'] ?? '') !== 'active') {
                    return false;
                }

                $days = self::daysUntilDate($vendor['plan_expires_at'] ?? null);
                if ($days === null) {
                    return false;
                }

                return $days >= 0 && $days <= $window;
            }));
        }

        return array_values(array_filter($vendors, static fn (array $vendor): bool => ($vendor['status'] ?? '') === $filter));
    }

    public static function listPlans(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM plans';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY price ASC, duration_days ASC';

        return Database::select($sql);
    }

    public static function services(int $vendorId, bool $onlyActive = false): array
    {
        $sql = 'SELECT * FROM services WHERE vendor_id = :vendor_id';
        if ($onlyActive) {
            $sql .= ' AND is_active = 1';
        }
        $sql .= ' ORDER BY is_active DESC, title ASC';

        return Database::select($sql, ['vendor_id' => $vendorId]);
    }

    public static function products(int $vendorId): array
    {
        return Database::select(
            'SELECT *,
                CASE WHEN stock_quantity <= min_stock_quantity THEN 1 ELSE 0 END AS is_low_stock
             FROM products
             WHERE vendor_id = :vendor_id
             ORDER BY is_low_stock DESC, name ASC',
            ['vendor_id' => $vendorId]
        );
    }

    public static function weeklyHours(int $vendorId): array
    {
        $rows = Database::select(
            'SELECT * FROM vendor_hours WHERE vendor_id = :vendor_id ORDER BY weekday ASC',
            ['vendor_id' => $vendorId]
        );

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['weekday']] = $row;
        }

        for ($weekday = 0; $weekday <= 6; $weekday++) {
            if (!isset($indexed[$weekday])) {
                $indexed[$weekday] = [
                    'weekday' => $weekday,
                    'is_enabled' => $weekday >= 1 && $weekday <= 5 ? 1 : 0,
                    'start_time' => '08:00:00',
                    'end_time' => '18:00:00',
                ];
            }
        }

        ksort($indexed);

        return array_values($indexed);
    }

    public static function specialDays(int $vendorId): array
    {
        return Database::select(
            'SELECT * FROM vendor_special_days WHERE vendor_id = :vendor_id ORDER BY special_date ASC',
            ['vendor_id' => $vendorId]
        );
    }

    public static function effectiveStatus(array $vendor): string
    {
        return self::refreshStatus($vendor)['status'];
    }

    public static function createOnboarding(int $userId, array $data): int
    {
        $existing = self::findByUserId($userId);
        if ($existing) {
            throw new RuntimeException('Este usuário já possui um cadastro.');
        }

        $businessName = trim((string) ($data['business_name'] ?? ''));
        $category = trim((string) ($data['category'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($businessName === '' || $category === '' || $phone === '') {
            throw new RuntimeException('Preencha nome do negócio, categoria e telefone.');
        }

        $slug = self::generateUniqueSlug($businessName);

        Database::statement(
            'INSERT INTO vendors (
                user_id, business_name, slug, category, phone, status, button_color, public_rating, rating_count,
                interval_between_appointments, created_at, updated_at
             ) VALUES (
                :user_id, :business_name, :slug, :category, :phone, \'pending\', :button_color, 5.0, 0,
                0, NOW(), NOW()
             )',
            [
                'user_id' => $userId,
                'business_name' => $businessName,
                'slug' => $slug,
                'category' => $category,
                'phone' => $phone,
                'button_color' => app_config('app.default_button_color', '#1AB2C7'),
            ]
        );

        $vendorId = Database::lastInsertId();
        self::seedDefaultHours($vendorId);
        // Multiusuário: garante vínculo explícito para futuras permissões/seleção.
        self::attachUser((int) $vendorId, $userId, 'owner');

        return $vendorId;
    }

    public static function saveSettings(int $vendorId, array $data, array $files = []): void
    {
        $vendor = self::findById($vendorId);
        if (!$vendor) {
            throw new RuntimeException('Vendor não encontrado.');
        }

        $profileImage = UploadService::storeImage($files['profile_image'] ?? null, 'profiles') ?: $vendor['profile_image'];
        $coverImage = UploadService::storeImage($files['cover_image'] ?? null, 'covers') ?: $vendor['cover_image'];
        $businessName = trim((string) ($data['business_name'] ?? $vendor['business_name']));
        $slug = trim((string) ($data['slug'] ?? $vendor['slug']));
        $slug = self::generateUniqueSlug($slug !== '' ? $slug : $businessName, $vendorId);

        Database::statement(
            'UPDATE vendors SET
                business_name = :business_name,
                slug = :slug,
                category = :category,
                bio = :bio,
                address = :address,
                latitude = :latitude,
                longitude = :longitude,
                whatsapp_api_token = :whatsapp_api_token,
                whatsapp_phone_id = :whatsapp_phone_id,
                phone = :phone,
                button_color = :button_color,
                interval_between_appointments = :interval_between_appointments,
                profile_image = :profile_image,
                cover_image = :cover_image,
                updated_at = NOW()
             WHERE id = :id',
            [
                'business_name' => $businessName,
                'slug' => $slug,
                'category' => trim((string) ($data['category'] ?? '')),
                'bio' => trim((string) ($data['bio'] ?? '')),
                'address' => trim((string) ($data['address'] ?? '')),
                'latitude' => self::parseCoordinate($data['latitude'] ?? null),
                'longitude' => self::parseCoordinate($data['longitude'] ?? null),
                'whatsapp_api_token' => trim((string) ($data['whatsapp_api_token'] ?? $vendor['whatsapp_api_token'] ?? '')),
                'whatsapp_phone_id' => trim((string) ($data['whatsapp_phone_id'] ?? $vendor['whatsapp_phone_id'] ?? '')),
                'phone' => trim((string) ($data['phone'] ?? '')),
                'button_color' => trim((string) ($data['button_color'] ?? app_config('app.default_button_color', '#1AB2C7'))),
                'interval_between_appointments' => max(0, (int) ($data['interval_between_appointments'] ?? 0)),
                'profile_image' => $profileImage,
                'cover_image' => $coverImage,
                'id' => $vendorId,
            ]
        );

        self::saveWeeklyHours($vendorId, $data['weekly_hours'] ?? []);
        self::saveSpecialDays($vendorId, $data['special_days'] ?? []);
    }

    public static function saveWeeklyHours(int $vendorId, array $weeklyHours): void
    {
        Database::statement('DELETE FROM vendor_hours WHERE vendor_id = :vendor_id', ['vendor_id' => $vendorId]);

        for ($weekday = 0; $weekday <= 6; $weekday++) {
            $row = $weeklyHours[$weekday] ?? [];
            Database::statement(
                'INSERT INTO vendor_hours (vendor_id, weekday, is_enabled, start_time, end_time, created_at, updated_at)
                 VALUES (:vendor_id, :weekday, :is_enabled, :start_time, :end_time, NOW(), NOW())',
                [
                    'vendor_id' => $vendorId,
                    'weekday' => $weekday,
                    'is_enabled' => isset($row['is_enabled']) ? 1 : 0,
                    'start_time' => ($row['start_time'] ?? '08:00') . ':00',
                    'end_time' => ($row['end_time'] ?? '18:00') . ':00',
                ]
            );
        }
    }

    public static function saveSpecialDays(int $vendorId, array $specialDays): void
    {
        Database::statement('DELETE FROM vendor_special_days WHERE vendor_id = :vendor_id', ['vendor_id' => $vendorId]);

        foreach ($specialDays as $day) {
            $date = trim((string) ($day['special_date'] ?? ''));
            if ($date === '') {
                continue;
            }

            Database::statement(
                'INSERT INTO vendor_special_days (
                    vendor_id, special_date, start_time, end_time, is_available, created_at, updated_at
                 ) VALUES (
                    :vendor_id, :special_date, :start_time, :end_time, :is_available, NOW(), NOW()
                 )',
                [
                    'vendor_id' => $vendorId,
                    'special_date' => $date,
                    'start_time' => (($day['start_time'] ?? '08:00') ?: '08:00') . ':00',
                    'end_time' => (($day['end_time'] ?? '18:00') ?: '18:00') . ':00',
                    'is_available' => isset($day['is_available']) ? 1 : 0,
                ]
            );
        }
    }

    public static function generateUniqueSlug(string $value, int $exceptVendorId = 0): string
    {
        $base = slugify($value);
        $slug = $base;
        $suffix = 1;

        while (true) {
            $params = ['slug' => $slug];
            $sql = 'SELECT id FROM vendors WHERE slug = :slug';

            if ($exceptVendorId > 0) {
                $sql .= ' AND id != :except_id';
                $params['except_id'] = $exceptVendorId;
            }

            $exists = Database::selectOne($sql . ' LIMIT 1', $params);
            if (!$exists) {
                return $slug;
            }

            $suffix++;
            $slug = $base . '-' . $suffix;
        }
    }

    private static function refreshStatus(array $vendor): array
    {
        if (($vendor['status'] ?? '') === 'active' && !empty($vendor['plan_expires_at']) && strtotime((string) $vendor['plan_expires_at']) < strtotime(date('Y-m-d'))) {
            Database::statement(
                'UPDATE vendors SET status = \'expired\', updated_at = NOW() WHERE id = :id',
                ['id' => $vendor['id']]
            );
            $vendor['status'] = 'expired';
        }

        return $vendor;
    }

    private static function seedDefaultHours(int $vendorId): void
    {
        for ($weekday = 0; $weekday <= 6; $weekday++) {
            Database::statement(
                'INSERT INTO vendor_hours (vendor_id, weekday, is_enabled, start_time, end_time, created_at, updated_at)
                 VALUES (:vendor_id, :weekday, :is_enabled, :start_time, :end_time, NOW(), NOW())',
                [
                    'vendor_id' => $vendorId,
                    'weekday' => $weekday,
                    'is_enabled' => $weekday >= 1 && $weekday <= 5 ? 1 : 0,
                    'start_time' => '08:00:00',
                    'end_time' => '18:00:00',
                ]
            );
        }
    }

    private static function parseCoordinate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $float = (float) $value;
        if ($float === 0.0 && trim((string) $value) !== '0') {
            return null;
        }
        return $float;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class ProfessionalService
{
    public static function listByVendor(int $vendorId): array
    {
        return Database::select(
            'SELECT p.*, u.email AS user_email, u.full_name AS user_full_name
             FROM professionals p
             INNER JOIN platform_users u ON u.id = p.user_id
             WHERE p.vendor_id = :vendor_id
             ORDER BY p.name ASC',
            ['vendor_id' => $vendorId]
        );
    }

    public static function listActiveByVendor(int $vendorId): array
    {
        return Database::select(
            'SELECT p.*, u.email AS user_email, u.full_name AS user_full_name
             FROM professionals p
             INNER JOIN platform_users u ON u.id = p.user_id
             WHERE p.vendor_id = :vendor_id AND p.is_active = 1
             ORDER BY p.name ASC',
            ['vendor_id' => $vendorId]
        );
    }

    public static function create(int $vendorId, array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $color = trim((string) ($data['color'] ?? '#1AB2C7'));
        $commissionRate = (float) ($data['commission_rate'] ?? 0);
        $scheduleType = in_array(($data['schedule_type'] ?? 'weekly'), ['weekly', 'specific'], true) ? $data['schedule_type'] : 'weekly';

        if ($name === '' || $email === '') {
            throw new RuntimeException('Nome e e-mail são obrigatórios.');
        }

        $user = Database::selectOne(
            'SELECT id FROM platform_users WHERE email = :email LIMIT 1',
            ['email' => $email]
        );

        if (!$user) {
            throw new RuntimeException('Usuário não encontrado com este e-mail. O profissional precisa ter uma conta no sistema.');
        }

        $existing = Database::selectOne(
            'SELECT id FROM professionals WHERE vendor_id = :vendor_id AND user_id = :user_id LIMIT 1',
            ['vendor_id' => $vendorId, 'user_id' => $user['id']]
        );

        if ($existing) {
            throw new RuntimeException('Este usuário já é um profissional deste negócio.');
        }

        Database::statement(
            'INSERT INTO professionals (vendor_id, user_id, name, email, phone, color, commission_rate, schedule_type, created_at, updated_at)
             VALUES (:vendor_id, :user_id, :name, :email, :phone, :color, :commission_rate, :schedule_type, NOW(), NOW())',
            [
                'vendor_id' => $vendorId,
                'user_id' => $user['id'],
                'name' => $name,
                'email' => $email,
                'phone' => $phone ?: null,
                'color' => $color,
                'commission_rate' => $commissionRate,
                'schedule_type' => $scheduleType,
            ]
        );

        $professionalId = Database::lastInsertId();

        // Default availability: Mon-Fri 08:00-18:00
        $defaultDays = [1, 2, 3, 4, 5];
        foreach ($defaultDays as $day) {
            Database::statement(
                'INSERT INTO professional_availability (professional_id, day_of_week, start_time, end_time, is_active, created_at, updated_at)
                 VALUES (:professional_id, :day_of_week, :start_time, :end_time, 1, NOW(), NOW())',
                [
                    'professional_id' => $professionalId,
                    'day_of_week' => $day,
                    'start_time' => '08:00:00',
                    'end_time' => '18:00:00',
                ]
            );
        }

        return $professionalId;
    }

    public static function update(int $vendorId, int $professionalId, array $data): void
    {
        $professional = self::findById($vendorId, $professionalId);
        if (!$professional) {
            throw new RuntimeException('Profissional não encontrado.');
        }

        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $color = trim((string) ($data['color'] ?? '#1AB2C7'));
        $commissionRate = (float) ($data['commission_rate'] ?? 0);
        $scheduleType = in_array(($data['schedule_type'] ?? ''), ['weekly', 'specific'], true) ? $data['schedule_type'] : ($professional['schedule_type'] ?? 'weekly');

        if ($name === '') {
            throw new RuntimeException('Nome é obrigatório.');
        }

        Database::statement(
            'UPDATE professionals
             SET name = :name, phone = :phone, color = :color, commission_rate = :commission_rate, schedule_type = :schedule_type, updated_at = NOW()
             WHERE id = :id AND vendor_id = :vendor_id',
            [
                'id' => $professionalId,
                'vendor_id' => $vendorId,
                'name' => $name,
                'phone' => $phone ?: null,
                'color' => $color,
                'commission_rate' => $commissionRate,
                'schedule_type' => $scheduleType,
            ]
        );
    }

    public static function toggle(int $vendorId, int $professionalId): void
    {
        $professional = self::findById($vendorId, $professionalId);
        if (!$professional) {
            throw new RuntimeException('Profissional não encontrado.');
        }

        Database::statement(
            'UPDATE professionals
             SET is_active = :is_active, updated_at = NOW()
             WHERE id = :id AND vendor_id = :vendor_id',
            [
                'id' => $professionalId,
                'vendor_id' => $vendorId,
                'is_active' => (int) $professional['is_active'] ? 0 : 1,
            ]
        );
    }

    public static function delete(int $vendorId, int $professionalId): void
    {
        $professional = self::findById($vendorId, $professionalId);
        if (!$professional) {
            throw new RuntimeException('Profissional não encontrado.');
        }

        $futureAppointments = Database::selectOne(
            'SELECT COUNT(*) AS total
             FROM appointments
             WHERE professional_id = :professional_id
               AND appointment_date >= CURDATE()
               AND status NOT IN (\'cancelled\', \'no_show\')',
            ['professional_id' => $professionalId]
        );

        if ((int) ($futureAppointments['total'] ?? 0) > 0) {
            throw new RuntimeException('Não é possível excluir um profissional com agendamentos futuros.');
        }

        Database::transaction(function () use ($professionalId): void {
            Database::statement('DELETE FROM professional_exceptions WHERE professional_id = :id', ['id' => $professionalId]);
            Database::statement('DELETE FROM professional_availability WHERE professional_id = :id', ['id' => $professionalId]);
            Database::statement('DELETE FROM professionals WHERE id = :id', ['id' => $professionalId]);
        });
    }

    public static function findById(int $vendorId, int $professionalId): ?array
    {
        return Database::selectOne(
            'SELECT p.*, u.email AS user_email, u.full_name AS user_full_name
             FROM professionals p
             INNER JOIN platform_users u ON u.id = p.user_id
             WHERE p.id = :id AND p.vendor_id = :vendor_id
             LIMIT 1',
            ['id' => $professionalId, 'vendor_id' => $vendorId]
        );
    }

    public static function getAvailability(int $professionalId): array
    {
        return Database::select(
            'SELECT * FROM professional_availability
             WHERE professional_id = :professional_id
             ORDER BY day_of_week ASC',
            ['professional_id' => $professionalId]
        );
    }

    public static function updateAvailability(int $vendorId, int $professionalId, array $availabilityData): void
    {
        $professional = self::findById($vendorId, $professionalId);
        if (!$professional) {
            throw new RuntimeException('Profissional não encontrado.');
        }

        Database::transaction(function () use ($professionalId, $availabilityData): void {
            Database::statement('DELETE FROM professional_availability WHERE professional_id = :id', ['id' => $professionalId]);

            foreach ($availabilityData as $data) {
                $dayOfWeek = (int) ($data['day_of_week'] ?? -1);
                $startTime = trim((string) ($data['start_time'] ?? ''));
                $endTime = trim((string) ($data['end_time'] ?? ''));
                $isActive = (int) ($data['is_active'] ?? 0);

                if ($dayOfWeek < 0 || $dayOfWeek > 6) {
                    continue;
                }

                Database::statement(
                    'INSERT INTO professional_availability (professional_id, day_of_week, start_time, end_time, is_active, created_at, updated_at)
                     VALUES (:professional_id, :day_of_week, :start_time, :end_time, :is_active, NOW(), NOW())',
                    [
                        'professional_id' => $professionalId,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $startTime !== '' ? $startTime : '08:00:00',
                        'end_time' => $endTime !== '' ? $endTime : '18:00:00',
                        'is_active' => $isActive,
                    ]
                );
            }
        });
    }

    public static function getExceptions(int $professionalId, string $startDate, string $endDate): array
    {
        return Database::select(
            'SELECT * FROM professional_exceptions
             WHERE professional_id = :professional_id
               AND exception_date BETWEEN :start_date AND :end_date
             ORDER BY exception_date ASC',
            [
                'professional_id' => $professionalId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );
    }

    public static function addException(int $vendorId, int $professionalId, array $data): void
    {
        $professional = self::findById($vendorId, $professionalId);
        if (!$professional) {
            throw new RuntimeException('Profissional não encontrado.');
        }

        $exceptionDate = trim((string) ($data['exception_date'] ?? ''));
        $isAvailable = (int) ($data['is_available'] ?? 1);
        $startTime = trim((string) ($data['start_time'] ?? ''));
        $endTime = trim((string) ($data['end_time'] ?? ''));
        $reason = trim((string) ($data['reason'] ?? ''));

        if ($exceptionDate === '') {
            throw new RuntimeException('Data da exceção é obrigatória.');
        }

        if ($isAvailable && ($startTime === '' || $endTime === '')) {
            throw new RuntimeException('Horários são obrigatórios quando disponível.');
        }

        Database::statement(
            'INSERT INTO professional_exceptions (professional_id, exception_date, is_available, start_time, end_time, reason, created_at, updated_at)
             VALUES (:professional_id, :exception_date, :is_available, :start_time, :end_time, :reason, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
             is_available = VALUES(is_available),
             start_time = VALUES(start_time),
             end_time = VALUES(end_time),
             reason = VALUES(reason),
             updated_at = NOW()',
            [
                'professional_id' => $professionalId,
                'exception_date' => $exceptionDate,
                'is_available' => $isAvailable,
                'start_time' => $isAvailable ? $startTime : null,
                'end_time' => $isAvailable ? $endTime : null,
                'reason' => $reason ?: null,
            ]
        );
    }

    public static function deleteException(int $vendorId, int $professionalId, string $exceptionDate): void
    {
        $professional = self::findById($vendorId, $professionalId);
        if (!$professional) {
            throw new RuntimeException('Profissional não encontrado.');
        }

        Database::statement(
            'DELETE FROM professional_exceptions
             WHERE professional_id = :professional_id AND exception_date = :exception_date',
            [
                'professional_id' => $professionalId,
                'exception_date' => $exceptionDate,
            ]
        );
    }

    /**
     * Get the working hours for a professional on a specific date,
     * considering exceptions first, then regular availability.
     */
    public static function getWorkingHoursForDate(int $professionalId, string $date): ?array
    {
        // Check exceptions first
        $exception = Database::selectOne(
            'SELECT * FROM professional_exceptions
             WHERE professional_id = :professional_id AND exception_date = :exception_date
             LIMIT 1',
            ['professional_id' => $professionalId, 'exception_date' => $date]
        );

        if ($exception) {
            if (!(int) $exception['is_available']) {
                return null; // Day off
            }
            return [
                'start_time' => $exception['start_time'],
                'end_time' => $exception['end_time'],
            ];
        }

        // Check regular availability
        $dayOfWeek = (int) date('w', strtotime($date));
        $availability = Database::selectOne(
            'SELECT * FROM professional_availability
             WHERE professional_id = :professional_id AND day_of_week = :day_of_week AND is_active = 1
             LIMIT 1',
            ['professional_id' => $professionalId, 'day_of_week' => $dayOfWeek]
        );

        if (!$availability) {
            return null;
        }

        return [
            'start_time' => $availability['start_time'],
            'end_time' => $availability['end_time'],
        ];
    }
}

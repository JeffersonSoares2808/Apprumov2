<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class AppointmentService
{
    public static function dashboardData(int $vendorId): array
    {
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        $counts = Database::selectOne(
            'SELECT
                COALESCE(SUM(CASE WHEN appointment_date = CURDATE() THEN 1 ELSE 0 END), 0) AS today_total,
                COALESCE(SUM(CASE WHEN appointment_date = CURDATE() AND status = "confirmed" THEN 1 ELSE 0 END), 0) AS today_confirmed,
                COALESCE(SUM(CASE WHEN status = "completed" AND appointment_date BETWEEN :completed_month_start AND :completed_month_end THEN price ELSE 0 END), 0) AS completed_revenue,
                COALESCE(SUM(CASE WHEN status IN ("cancelled", "no_show") AND appointment_date BETWEEN :loss_month_start AND :loss_month_end THEN price ELSE 0 END), 0) AS month_losses
             FROM appointments
             WHERE vendor_id = :vendor_id',
            [
                'vendor_id' => $vendorId,
                'completed_month_start' => $monthStart,
                'completed_month_end' => $monthEnd,
                'loss_month_start' => $monthStart,
                'loss_month_end' => $monthEnd,
            ]
        ) ?: [];

        $upcoming = Database::select(
            'SELECT a.*, s.title AS service_title
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             WHERE a.vendor_id = :vendor_id
               AND CONCAT(a.appointment_date, " ", a.start_time) >= NOW()
               AND a.status IN ("confirmed", "completed")
             ORDER BY a.appointment_date ASC, a.start_time ASC
             LIMIT 5',
            ['vendor_id' => $vendorId]
        );

        $lowStock = Database::selectOne(
            'SELECT COUNT(*) AS total
             FROM products
             WHERE vendor_id = :vendor_id AND stock_quantity <= min_stock_quantity',
            ['vendor_id' => $vendorId]
        );

        $waitingToday = Database::selectOne(
            'SELECT COUNT(*) AS total FROM waiting_list_entries WHERE vendor_id = :vendor_id AND desired_date = :date',
            ['vendor_id' => $vendorId, 'date' => $today]
        );

        return [
            'counts' => $counts + [
                'today_total' => 0,
                'today_confirmed' => 0,
                'completed_revenue' => 0,
                'month_losses' => 0,
            ],
            'upcoming' => $upcoming,
            'low_stock_count' => (int) ($lowStock['total'] ?? 0),
            'waiting_count' => (int) ($waitingToday['total'] ?? 0),
        ];
    }

    public static function agendaData(int $vendorId, ?string $selectedDate = null): array
    {
        $selectedDate = $selectedDate ?: date('Y-m-d');

        return [
            'selected_date' => $selectedDate,
            'week_strip' => self::weekStrip($selectedDate),
            'appointments' => self::appointmentsForDate($vendorId, $selectedDate),
            'waiting_list' => self::waitingListForDate($vendorId, $selectedDate),
        ];
    }

    public static function appointmentsForDate(int $vendorId, string $date): array
    {
        return Database::select(
            'SELECT a.*, s.title AS service_title
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             WHERE a.vendor_id = :vendor_id
               AND a.appointment_date = :appointment_date
             ORDER BY a.start_time ASC',
            [
                'vendor_id' => $vendorId,
                'appointment_date' => $date,
            ]
        );
    }

    public static function waitingListForDate(int $vendorId, string $date): array
    {
        return Database::select(
            'SELECT w.*, s.title AS service_title
             FROM waiting_list_entries w
             LEFT JOIN services s ON s.id = w.service_id
             WHERE w.vendor_id = :vendor_id
               AND w.desired_date = :desired_date
             ORDER BY w.created_at DESC',
            [
                'vendor_id' => $vendorId,
                'desired_date' => $date,
            ]
        );
    }

    public static function create(int $vendorId, array $data, bool $isPublic = false): int
    {
        $serviceId = (int) ($data['service_id'] ?? 0);
        $service = Database::selectOne(
            'SELECT * FROM services WHERE id = :id AND vendor_id = :vendor_id LIMIT 1',
            ['id' => $serviceId, 'vendor_id' => $vendorId]
        );

        if (!$service) {
            throw new RuntimeException('Serviço não encontrado.');
        }

        $appointmentDate = trim((string) ($data['appointment_date'] ?? ''));
        $startTime = trim((string) ($data['start_time'] ?? ''));
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $customerPhone = trim((string) ($data['customer_phone'] ?? ''));
        $customerEmail = trim((string) ($data['customer_email'] ?? ''));

        if ($appointmentDate === '' || $startTime === '' || $customerName === '' || $customerPhone === '') {
            throw new RuntimeException('Preencha nome, telefone, serviço, data e horário.');
        }

        $vendor = VendorService::findById($vendorId);
        if (!$vendor) {
            throw new RuntimeException('Vendor não encontrado.');
        }

        $durationMinutes = (int) $service['duration_minutes'];
        $price = isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : (float) $service['price'];
        $bufferMinutes = (int) ($vendor['interval_between_appointments'] ?? 0);
        $normalizedStart = self::normalizeTime($startTime);
        $endTime = date('H:i:s', strtotime($appointmentDate . ' ' . $normalizedStart . ' +' . $durationMinutes . ' minutes'));
        $endWithBuffer = date('H:i:s', strtotime($appointmentDate . ' ' . $normalizedStart . ' +' . ($durationMinutes + $bufferMinutes) . ' minutes'));

        if ($isPublic) {
            $slots = self::availableSlots($vendor, $service, $appointmentDate);
            if (!in_array(substr($normalizedStart, 0, 5), $slots, true)) {
                throw new RuntimeException('Este horário não está mais disponível.');
            }
        }

        if (self::hasConflict($vendorId, $appointmentDate, $normalizedStart, $endWithBuffer)) {
            throw new RuntimeException('Já existe um atendimento ocupando esse horário.');
        }

        return Database::transaction(function () use (
            $vendorId,
            $serviceId,
            $service,
            $appointmentDate,
            $normalizedStart,
            $endTime,
            $durationMinutes,
            $price,
            $customerName,
            $customerEmail,
            $customerPhone,
            $data,
            $isPublic
        ): int {
            $clientId = self::upsertClient($vendorId, $customerName, $customerPhone, $customerEmail);

            Database::statement(
                'INSERT INTO appointments (
                    vendor_id, service_id, client_id, customer_name, customer_email, customer_phone, appointment_date,
                    start_time, end_time, duration_minutes, price, status, source, lgpd_consent, notes, created_at, updated_at
                 ) VALUES (
                    :vendor_id, :service_id, :client_id, :customer_name, :customer_email, :customer_phone, :appointment_date,
                    :start_time, :end_time, :duration_minutes, :price, "confirmed", :source, :lgpd_consent, :notes, NOW(), NOW()
                 )',
                [
                    'vendor_id' => $vendorId,
                    'service_id' => $serviceId,
                    'client_id' => $clientId,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail !== '' ? $customerEmail : null,
                    'customer_phone' => $customerPhone,
                    'appointment_date' => $appointmentDate,
                    'start_time' => $normalizedStart,
                    'end_time' => $endTime,
                    'duration_minutes' => $durationMinutes,
                    'price' => $price,
                    'source' => $isPublic ? 'public_booking' : 'manual',
                    'lgpd_consent' => isset($data['lgpd_consent']) ? 1 : 0,
                    'notes' => trim((string) ($data['notes'] ?? '')),
                ]
            );

            $appointmentId = Database::lastInsertId();
            self::syncFinancialTransaction($appointmentId);

            return $appointmentId;
        });
    }

    public static function updateStatus(int $vendorId, int $appointmentId, string $status): void
    {
        $allowed = ['confirmed', 'completed', 'cancelled', 'no_show'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Status inválido.');
        }

        Database::statement(
            'UPDATE appointments
             SET status = :next_status,
                 paid_at = CASE WHEN :paid_status = "completed" THEN NOW() ELSE paid_at END,
                 updated_at = NOW()
             WHERE id = :id AND vendor_id = :vendor_id',
            [
                'next_status' => $status,
                'paid_status' => $status,
                'id' => $appointmentId,
                'vendor_id' => $vendorId,
            ]
        );

        self::syncFinancialTransaction($appointmentId);
    }

    public static function delete(int $vendorId, int $appointmentId): void
    {
        Database::transaction(static function () use ($vendorId, $appointmentId): void {
            Database::statement('DELETE FROM financial_transactions WHERE appointment_id = :appointment_id AND vendor_id = :vendor_id', [
                'appointment_id' => $appointmentId,
                'vendor_id' => $vendorId,
            ]);

            Database::statement('DELETE FROM appointments WHERE id = :id AND vendor_id = :vendor_id', [
                'id' => $appointmentId,
                'vendor_id' => $vendorId,
            ]);
        });
    }

    public static function createWaitingEntry(int $vendorId, array $data): void
    {
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $customerPhone = trim((string) ($data['customer_phone'] ?? ''));
        $desiredDate = trim((string) ($data['desired_date'] ?? date('Y-m-d')));

        if ($customerName === '' || $customerPhone === '') {
            throw new RuntimeException('Preencha nome e telefone para a fila de espera.');
        }

        Database::statement(
            'INSERT INTO waiting_list_entries (
                vendor_id, service_id, customer_name, customer_phone, desired_date, notes, created_at, updated_at
             ) VALUES (
                :vendor_id, :service_id, :customer_name, :customer_phone, :desired_date, :notes, NOW(), NOW()
             )',
            [
                'vendor_id' => $vendorId,
                'service_id' => (int) ($data['service_id'] ?? 0) ?: null,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'desired_date' => $desiredDate,
                'notes' => trim((string) ($data['notes'] ?? '')),
            ]
        );
    }

    public static function deleteWaitingEntry(int $vendorId, int $entryId): void
    {
        Database::statement('DELETE FROM waiting_list_entries WHERE id = :id AND vendor_id = :vendor_id', [
            'id' => $entryId,
            'vendor_id' => $vendorId,
        ]);
    }

    public static function availableSlots(array $vendor, array $service, string $date): array
    {
        $window = self::workingWindow((int) $vendor['id'], $date);
        if (!$window) {
            return [];
        }

        $buffer = (int) ($vendor['interval_between_appointments'] ?? 0);
        $step = (int) $service['duration_minutes'] + $buffer;
        $slots = [];

        $current = strtotime($date . ' ' . $window['start_time']);
        $endBoundary = strtotime($date . ' ' . $window['end_time']);
        $nowBoundary = strtotime(date('Y-m-d H:i'));

        while ($current < $endBoundary) {
            $slotStart = date('H:i:s', $current);
            $slotEnd = date('H:i:s', strtotime('+' . ((int) $service['duration_minutes'] + $buffer) . ' minutes', $current));
            $slotEndTimestamp = strtotime($date . ' ' . $slotEnd);

            if ($slotEndTimestamp > $endBoundary) {
                break;
            }

            if ($date === date('Y-m-d') && $current < $nowBoundary) {
                $current = strtotime('+' . $step . ' minutes', $current);
                continue;
            }

            if (!self::hasConflict((int) $vendor['id'], $date, $slotStart, $slotEnd)) {
                $slots[] = date('H:i', $current);
            }

            $current = strtotime('+' . $step . ' minutes', $current);
        }

        return $slots;
    }

    public static function workingWindow(int $vendorId, string $date): ?array
    {
        $special = Database::selectOne(
            'SELECT * FROM vendor_special_days WHERE vendor_id = :vendor_id AND special_date = :special_date LIMIT 1',
            ['vendor_id' => $vendorId, 'special_date' => $date]
        );

        if ($special) {
            if (!(int) $special['is_available']) {
                return null;
            }

            return [
                'start_time' => $special['start_time'],
                'end_time' => $special['end_time'],
            ];
        }

        $weekday = (int) date('w', strtotime($date));
        $weekly = Database::selectOne(
            'SELECT * FROM vendor_hours WHERE vendor_id = :vendor_id AND weekday = :weekday LIMIT 1',
            ['vendor_id' => $vendorId, 'weekday' => $weekday]
        );

        if (!$weekly || !(int) $weekly['is_enabled']) {
            return null;
        }

        return [
            'start_time' => $weekly['start_time'],
            'end_time' => $weekly['end_time'],
        ];
    }

    public static function weekStrip(string $selectedDate): array
    {
        $timestamp = strtotime($selectedDate);
        $sunday = strtotime('last sunday', $timestamp);
        if ((int) date('w', $timestamp) === 0) {
            $sunday = $timestamp;
        }

        $labels = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
        $days = [];

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime('+' . $i . ' days', $sunday));
            $days[] = [
                'date' => $date,
                'label' => $labels[$i],
                'day_number' => date('d', strtotime($date)),
                'is_active' => $date === $selectedDate,
                'is_today' => $date === date('Y-m-d'),
            ];
        }

        return $days;
    }

    public static function findWaitingEntry(int $vendorId, int $entryId): ?array
    {
        return Database::selectOne(
            'SELECT w.*, s.title AS service_title
             FROM waiting_list_entries w
             LEFT JOIN services s ON s.id = w.service_id
             WHERE w.id = :id AND w.vendor_id = :vendor_id
             LIMIT 1',
            ['id' => $entryId, 'vendor_id' => $vendorId]
        );
    }

    public static function serviceById(int $vendorId, int $serviceId): ?array
    {
        return Database::selectOne(
            'SELECT * FROM services WHERE id = :id AND vendor_id = :vendor_id LIMIT 1',
            ['id' => $serviceId, 'vendor_id' => $vendorId]
        );
    }

    public static function findAppointment(int $vendorId, int $appointmentId): ?array
    {
        return Database::selectOne(
            'SELECT a.*, s.title AS service_title
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             WHERE a.id = :id AND a.vendor_id = :vendor_id
             LIMIT 1',
            ['id' => $appointmentId, 'vendor_id' => $vendorId]
        );
    }

    private static function normalizeTime(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        return date('H:i:s', strtotime($time));
    }

    private static function hasConflict(int $vendorId, string $date, string $startTime, string $endTime, int $ignoreId = 0): bool
    {
        $sql = 'SELECT id
                FROM appointments
                WHERE vendor_id = :vendor_id
                  AND appointment_date = :appointment_date
                  AND status NOT IN ("cancelled", "no_show")
                  AND start_time < :end_time
                  AND end_time > :start_time';

        $params = [
            'vendor_id' => $vendorId,
            'appointment_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];

        if ($ignoreId > 0) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        return Database::selectOne($sql . ' LIMIT 1', $params) !== null;
    }

    private static function upsertClient(int $vendorId, string $name, string $phone, string $email = ''): int
    {
        $existing = Database::selectOne(
            'SELECT * FROM clients WHERE vendor_id = :vendor_id AND phone = :phone LIMIT 1',
            ['vendor_id' => $vendorId, 'phone' => $phone]
        );

        if ($existing) {
            Database::statement(
                'UPDATE clients
                 SET name = :name, email = :email, updated_at = NOW()
                 WHERE id = :id',
                [
                    'name' => $name,
                    'email' => $email !== '' ? $email : $existing['email'],
                    'id' => $existing['id'],
                ]
            );

            return (int) $existing['id'];
        }

        Database::statement(
            'INSERT INTO clients (vendor_id, name, phone, email, created_at, updated_at)
             VALUES (:vendor_id, :name, :phone, :email, NOW(), NOW())',
            [
                'vendor_id' => $vendorId,
                'name' => $name,
                'phone' => $phone,
                'email' => $email !== '' ? $email : null,
            ]
        );

        return Database::lastInsertId();
    }

    private static function syncFinancialTransaction(int $appointmentId): void
    {
        $appointment = Database::selectOne(
            'SELECT a.*, s.title AS service_title
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             WHERE a.id = :id
             LIMIT 1',
            ['id' => $appointmentId]
        );

        if (!$appointment) {
            return;
        }

        $kind = in_array($appointment['status'], ['cancelled', 'no_show'], true) ? 'loss' : 'income';
        $status = match ($appointment['status']) {
            'completed' => 'paid',
            'cancelled', 'no_show' => 'cancelled',
            default => 'open',
        };

        $existing = Database::selectOne(
            'SELECT id FROM financial_transactions WHERE appointment_id = :appointment_id LIMIT 1',
            ['appointment_id' => $appointmentId]
        );

        $payload = [
            'vendor_id' => $appointment['vendor_id'],
            'appointment_id' => $appointmentId,
            'kind' => $kind,
            'source' => 'appointment',
            'title' => 'Agendamento',
            'description' => trim(($appointment['service_title'] ?? 'Serviço') . ' - ' . ($appointment['customer_name'] ?? 'Cliente')),
            'amount' => $appointment['price'],
            'status' => $status,
            'transaction_date' => $appointment['appointment_date'],
        ];

        if ($existing) {
            $updatePayload = [
                'kind' => $payload['kind'],
                'source' => $payload['source'],
                'title' => $payload['title'],
                'description' => $payload['description'],
                'amount' => $payload['amount'],
                'status' => $payload['status'],
                'transaction_date' => $payload['transaction_date'],
                'id' => $existing['id'],
            ];

            Database::statement(
                'UPDATE financial_transactions
                 SET kind = :kind,
                     source = :source,
                     title = :title,
                     description = :description,
                     amount = :amount,
                     status = :status,
                     transaction_date = :transaction_date,
                     updated_at = NOW()
                 WHERE id = :id',
                $updatePayload
            );

            return;
        }

        Database::statement(
            'INSERT INTO financial_transactions (
                vendor_id, appointment_id, kind, source, title, description, amount, status, transaction_date, created_at, updated_at
             ) VALUES (
                :vendor_id, :appointment_id, :kind, :source, :title, :description, :amount, :status, :transaction_date, NOW(), NOW()
             )',
            $payload
        );
    }
}

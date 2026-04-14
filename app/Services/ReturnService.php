<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

/**
 * ReturnService — gerencia retornos gratuitos de serviços.
 *
 * Fluxo:
 *  1. Ao concluir atendimento → ReturnService::createFromAppointment()
 *  2. Cliente pode agendar retorno → ReturnService::scheduleReturn()
 *  3. Retorno vencido é marcado automaticamente → ReturnService::expireOld()
 */
final class ReturnService
{
    /**
     * Create a return record when an appointment is completed,
     * if the service has returns enabled.
     */
    public static function createFromAppointment(int $vendorId, int $appointmentId): ?int
    {
        $appt = Database::selectOne(
            'SELECT a.*, s.has_return, s.return_quantity, s.return_days, s.title AS service_title
             FROM appointments a
             JOIN services s ON s.id = a.service_id AND s.vendor_id = a.vendor_id
             WHERE a.id = :id AND a.vendor_id = :vendor_id
             LIMIT 1',
            ['id' => $appointmentId, 'vendor_id' => $vendorId]
        );

        if (!$appt || !(int) ($appt['has_return'] ?? 0)) {
            return null;
        }

        $existing = Database::selectOne(
            'SELECT id FROM service_returns WHERE appointment_id = :id AND vendor_id = :vendor_id LIMIT 1',
            ['id' => $appointmentId, 'vendor_id' => $vendorId]
        );

        if ($existing) {
            return (int) $existing['id'];
        }

        $expiresAt = date('Y-m-d', strtotime('+' . (int) $appt['return_days'] . ' days'));

        Database::statement(
            'INSERT INTO service_returns
                (vendor_id, appointment_id, service_id, customer_name, customer_phone,
                 quantity_total, quantity_used, expires_at, status, created_at, updated_at)
             VALUES
                (:vendor_id, :appointment_id, :service_id, :customer_name, :customer_phone,
                 :quantity_total, 0, :expires_at, "available", NOW(), NOW())',
            [
                'vendor_id'      => $vendorId,
                'appointment_id' => $appointmentId,
                'service_id'     => (int) $appt['service_id'],
                'customer_name'  => $appt['customer_name'] ?? '',
                'customer_phone' => $appt['customer_phone'] ?? '',
                'quantity_total' => (int) $appt['return_quantity'],
                'expires_at'     => $expiresAt,
            ]
        );

        return Database::lastInsertId();
    }

    /**
     * Schedule a return appointment.
     */
    public static function scheduleReturn(int $vendorId, int $returnId, int $returnAppointmentId): void
    {
        $ret = self::find($vendorId, $returnId);
        if (!$ret) {
            throw new RuntimeException('Retorno não encontrado.');
        }
        if ($ret['status'] === 'used' || $ret['status'] === 'expired') {
            throw new RuntimeException('Este retorno já foi utilizado ou expirou.');
        }
        if ((int) $ret['quantity_used'] >= (int) $ret['quantity_total']) {
            throw new RuntimeException('Quantidade de retornos esgotada.');
        }

        Database::statement(
            'UPDATE service_returns
             SET return_appointment_id = :appt_id, status = "scheduled", updated_at = NOW()
             WHERE id = :id AND vendor_id = :vendor_id',
            [
                'appt_id'   => $returnAppointmentId,
                'id'        => $returnId,
                'vendor_id' => $vendorId,
            ]
        );
    }

    /**
     * Mark a return as used.
     */
    public static function markUsed(int $vendorId, int $returnId): void
    {
        $ret = self::find($vendorId, $returnId);
        if (!$ret) {
            throw new RuntimeException('Retorno não encontrado.');
        }

        $newUsed = (int) $ret['quantity_used'] + 1;
        $status  = $newUsed >= (int) $ret['quantity_total'] ? 'used' : 'available';

        Database::statement(
            'UPDATE service_returns
             SET quantity_used = :used, status = :status, updated_at = NOW()
             WHERE id = :id AND vendor_id = :vendor_id',
            [
                'used'      => $newUsed,
                'status'    => $status,
                'id'        => $returnId,
                'vendor_id' => $vendorId,
            ]
        );
    }

    public static function find(int $vendorId, int $returnId): ?array
    {
        return Database::selectOne(
            'SELECT r.*, s.title AS service_title
             FROM service_returns r
             LEFT JOIN services s ON s.id = r.service_id
             WHERE r.id = :id AND r.vendor_id = :vendor_id
             LIMIT 1',
            ['id' => $returnId, 'vendor_id' => $vendorId]
        );
    }

    /**
     * List active/scheduled returns for a vendor.
     */
    public static function listActive(int $vendorId, int $limit = 50, int $offset = 0): array
    {
        return Database::select(
            "SELECT r.*, s.title AS service_title,
                    a.appointment_date AS original_date,
                    a.start_time AS original_time
             FROM service_returns r
             LEFT JOIN services s ON s.id = r.service_id
             LEFT JOIN appointments a ON a.id = r.appointment_id
             WHERE r.vendor_id = :vendor_id
               AND r.status IN ('available', 'scheduled')
               AND r.expires_at >= CURDATE()
             ORDER BY r.expires_at ASC
             LIMIT :lim OFFSET :off",
            ['vendor_id' => $vendorId, 'lim' => $limit, 'off' => $offset]
        );
    }

    /**
     * Find active returns by customer phone.
     */
    public static function findByPhone(int $vendorId, string $phone): array
    {
        return Database::select(
            "SELECT r.*, s.title AS service_title
             FROM service_returns r
             LEFT JOIN services s ON s.id = r.service_id
             WHERE r.vendor_id = :vendor_id
               AND r.customer_phone = :phone
               AND r.status IN ('available', 'scheduled')
               AND r.expires_at >= CURDATE()
             ORDER BY r.expires_at ASC",
            ['vendor_id' => $vendorId, 'phone' => $phone]
        );
    }

    /**
     * Expire old returns — run via daily cron.
     */
    public static function expireOld(): int
    {
        Database::statement(
            "UPDATE service_returns
             SET status = 'expired', updated_at = NOW()
             WHERE status IN ('available', 'scheduled')
               AND expires_at < CURDATE()"
        );

        $row = Database::selectOne('SELECT ROW_COUNT() AS n');
        return (int) ($row['n'] ?? 0);
    }

    /**
     * Count active returns for dashboard display.
     */
    public static function countActive(int $vendorId): int
    {
        $row = Database::selectOne(
            "SELECT COUNT(*) AS n FROM service_returns
             WHERE vendor_id = :vendor_id
               AND status IN ('available', 'scheduled')
               AND expires_at >= CURDATE()",
            ['vendor_id' => $vendorId]
        );
        return (int) ($row['n'] ?? 0);
    }
}

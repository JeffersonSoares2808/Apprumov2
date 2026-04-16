<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ReportService
{
    public static function build(int $vendorId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?: date('Y-m-01');
        $endDate = $endDate ?: date('Y-m-t');

        $appointments = Database::select(
            'SELECT a.*, s.title AS service_title
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             WHERE a.vendor_id = :vendor_id
               AND a.appointment_date BETWEEN :start_date AND :end_date',
            [
                'vendor_id' => $vendorId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        $transactions = Database::select(
            'SELECT * FROM financial_transactions
             WHERE vendor_id = :vendor_id
               AND transaction_date BETWEEN :start_date AND :end_date',
            [
                'vendor_id' => $vendorId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        $totalAppointments = count($appointments);
        $completedAppointments = array_filter($appointments, static fn (array $item): bool => $item['status'] === 'completed');
        $cancelledAppointments = array_filter($appointments, static fn (array $item): bool => in_array($item['status'], ['cancelled', 'no_show'], true));
        $completionRate = $totalAppointments > 0 ? (count($completedAppointments) / $totalAppointments) * 100 : 0;

        $totalRevenue = array_reduce($transactions, static function (float $carry, array $item): float {
            return $carry + (($item['kind'] === 'income' && $item['status'] === 'paid') ? (float) $item['amount'] : 0.0);
        }, 0.0);

        $appointmentRevenue = array_reduce($transactions, static function (float $carry, array $item): float {
            return $carry + (($item['kind'] === 'income' && $item['status'] === 'paid' && $item['source'] === 'appointment') ? (float) $item['amount'] : 0.0);
        }, 0.0);

        $losses = array_reduce($transactions, static function (float $carry, array $item): float {
            return $carry + (($item['kind'] === 'loss') ? (float) $item['amount'] : 0.0);
        }, 0.0);

        $averageTicket = count($completedAppointments) > 0 ? $appointmentRevenue / count($completedAppointments) : 0;

        $serviceRows = Database::select(
            'SELECT s.title, COALESCE(SUM(ft.amount), 0) AS total
             FROM financial_transactions ft
             INNER JOIN appointments a ON a.id = ft.appointment_id
             INNER JOIN services s ON s.id = a.service_id
             WHERE ft.vendor_id = :vendor_id
               AND ft.transaction_date BETWEEN :start_date AND :end_date
               AND ft.kind = \'income\'
               AND ft.status = \'paid\'
             GROUP BY s.id, s.title
             ORDER BY total DESC',
            [
                'vendor_id' => $vendorId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );

        $maxRevenue = 0.0;
        foreach ($serviceRows as $row) {
            $maxRevenue = max($maxRevenue, (float) $row['total']);
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'kpis' => [
                'total_appointments' => $totalAppointments,
                'completion_rate' => $completionRate,
                'total_revenue' => $totalRevenue,
                'average_ticket' => $averageTicket,
                'cancelled_appointments' => count($cancelledAppointments),
                'financial_losses' => $losses,
            ],
            'service_revenue' => array_map(static function (array $row) use ($maxRevenue): array {
                $total = (float) $row['total'];

                return [
                    'title' => $row['title'],
                    'total' => $total,
                    'percentage' => $maxRevenue > 0 ? ($total / $maxRevenue) * 100 : 0,
                ];
            }, $serviceRows),
        ];
    }

    /**
     * Build a daily professional payment report.
     *
     * Groups completed appointments by professional for the given date,
     * showing revenue, commission, card fees, and totals.
     */
    public static function professionalsDailyReport(int $vendorId, string $date): array
    {
        $appointments = Database::select(
            'SELECT a.*, s.title AS service_title, p.name AS professional_name,
                    p.commission_rate, p.id AS prof_id, p.color AS professional_color
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             LEFT JOIN professionals p ON p.id = a.professional_id
             WHERE a.vendor_id = :vendor_id
               AND a.appointment_date = :report_date
             ORDER BY p.name ASC, a.start_time ASC',
            [
                'vendor_id' => $vendorId,
                'report_date' => $date,
            ]
        );

        // Group by professional
        $byProfessional = [];
        $totals = [
            'total_appointments' => 0,
            'completed' => 0,
            'total_revenue' => 0.0,
            'total_commission' => 0.0,
            'total_card_fees' => 0.0,
            'total_net' => 0.0,
        ];

        foreach ($appointments as $appt) {
            $profId = (int) ($appt['prof_id'] ?? 0);
            $profName = $appt['professional_name'] ?? 'Sem profissional';
            $profColor = $appt['professional_color'] ?? '#999999';
            $commissionRate = (float) ($appt['commission_rate'] ?? 0);

            if (!isset($byProfessional[$profId])) {
                $byProfessional[$profId] = [
                    'id' => $profId,
                    'name' => $profName,
                    'color' => $profColor,
                    'commission_rate' => $commissionRate,
                    'appointments' => [],
                    'total_appointments' => 0,
                    'completed' => 0,
                    'revenue' => 0.0,
                    'commission' => 0.0,
                    'card_fees' => 0.0,
                    'net' => 0.0,
                ];
            }

            $isCompleted = $appt['status'] === 'completed';
            $price = (float) $appt['price'];
            $cardFee = (float) ($appt['card_fee'] ?? 0);
            $commission = $isCompleted ? round($price * $commissionRate / 100, 2) : 0;

            $byProfessional[$profId]['appointments'][] = [
                'id' => $appt['id'],
                'service_title' => $appt['service_title'] ?? 'Serviço',
                'customer_name' => $appt['customer_name'],
                'start_time' => substr($appt['start_time'], 0, 5),
                'end_time' => substr($appt['end_time'], 0, 5),
                'price' => $price,
                'card_fee' => $cardFee,
                'status' => $appt['status'],
                'payment_method' => $appt['payment_method'] ?? null,
                'commission' => $commission,
            ];

            $byProfessional[$profId]['total_appointments']++;
            $totals['total_appointments']++;

            if ($isCompleted) {
                $byProfessional[$profId]['completed']++;
                $byProfessional[$profId]['revenue'] += $price;
                $byProfessional[$profId]['commission'] += $commission;
                $byProfessional[$profId]['card_fees'] += $cardFee;
                // Net = price + card fee (charged on top to patient) - commission paid out
                $byProfessional[$profId]['net'] += ($price + $cardFee - $commission);

                $totals['completed']++;
                $totals['total_revenue'] += $price;
                $totals['total_commission'] += $commission;
                $totals['total_card_fees'] += $cardFee;
                $totals['total_net'] += ($price + $cardFee - $commission);
            }
        }

        return [
            'date' => $date,
            'professionals' => array_values($byProfessional),
            'totals' => $totals,
        ];
    }

    public static function clients(int $vendorId): array
    {
        return Database::select(
            'SELECT
                customer_name AS name,
                customer_phone AS phone,
                COUNT(*) AS visit_count,
                COALESCE(SUM(CASE WHEN status = \'completed\' THEN price ELSE 0 END), 0) AS total_spent,
                MAX(appointment_date) AS last_visit
             FROM appointments
             WHERE vendor_id = :vendor_id
               AND customer_phone IS NOT NULL
               AND customer_phone != \'\'
             GROUP BY customer_phone, customer_name
             ORDER BY visit_count DESC, total_spent DESC, last_visit DESC',
            ['vendor_id' => $vendorId]
        );
    }
}

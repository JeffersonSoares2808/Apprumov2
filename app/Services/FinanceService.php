<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class FinanceService
{
    public static function monthData(int $vendorId, ?string $month = null): array
    {
        $month = $month ?: date('Y-m');
        $start = date('Y-m-01', strtotime($month . '-01'));
        $end = date('Y-m-t', strtotime($start));
        $monthNames = [
            1 => 'janeiro',
            2 => 'fevereiro',
            3 => 'março',
            4 => 'abril',
            5 => 'maio',
            6 => 'junho',
            7 => 'julho',
            8 => 'agosto',
            9 => 'setembro',
            10 => 'outubro',
            11 => 'novembro',
            12 => 'dezembro',
        ];

        $kpis = Database::selectOne(
            'SELECT
                COALESCE(SUM(CASE WHEN kind = \'income\' AND status = \'paid\' THEN amount ELSE 0 END), 0) AS total_received,
                COALESCE(SUM(CASE WHEN kind = \'income\' AND status = \'open\' THEN amount ELSE 0 END), 0) AS total_open,
                COALESCE(SUM(CASE WHEN kind = \'loss\' THEN amount ELSE 0 END), 0) AS total_losses,
                COALESCE(SUM(CASE WHEN kind = \'income\' AND status = \'paid\' AND source = \'appointment\' THEN amount ELSE 0 END), 0) AS service_revenue,
                COALESCE(SUM(CASE WHEN kind = \'income\' AND status = \'paid\' AND source = \'product_sale\' THEN amount ELSE 0 END), 0) AS product_revenue
             FROM financial_transactions
             WHERE vendor_id = :vendor_id
               AND transaction_date BETWEEN :start_date AND :end_date',
            [
                'vendor_id' => $vendorId,
                'start_date' => $start,
                'end_date' => $end,
            ]
        ) ?: [
            'total_received' => 0,
            'total_open' => 0,
            'total_losses' => 0,
            'service_revenue' => 0,
            'product_revenue' => 0,
        ];

        $transactions = Database::select(
            'SELECT
                ft.*,
                a.customer_name,
                a.status AS appointment_status,
                s.title AS service_title,
                p.name AS product_name
             FROM financial_transactions ft
             LEFT JOIN appointments a ON a.id = ft.appointment_id
             LEFT JOIN services s ON s.id = a.service_id
             LEFT JOIN products p ON p.id = ft.product_id
             WHERE ft.vendor_id = :vendor_id
               AND ft.transaction_date BETWEEN :start_date AND :end_date
             ORDER BY ft.transaction_date DESC, ft.created_at DESC',
            [
                'vendor_id' => $vendorId,
                'start_date' => $start,
                'end_date' => $end,
            ]
        );

        return [
            'month' => $month,
            'month_label' => ($monthNames[(int) date('n', strtotime($start))] ?? '') . ' de ' . date('Y', strtotime($start)),
            'previous_month' => date('Y-m', strtotime($start . ' -1 month')),
            'next_month' => date('Y-m', strtotime($start . ' +1 month')),
            'kpis' => $kpis,
            'transactions' => $transactions,
        ];
    }
}

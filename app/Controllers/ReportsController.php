<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AuthService;
use App\Services\ReportService;

final class ReportsController extends Controller
{
    public function index(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();
        $startDate = (string) $request->query('start_date', date('Y-m-01'));
        $endDate = (string) $request->query('end_date', date('Y-m-t'));

        $this->render('vendor/reports', [
            'title' => 'Relatórios',
            'vendor' => $vendor,
            'report' => ReportService::build((int) $vendor['id'], $startDate, $endDate),
        ], 'vendor');
    }
}

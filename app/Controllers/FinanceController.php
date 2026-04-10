<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AppointmentService;
use App\Services\AuthService;
use App\Services\FinanceService;

final class FinanceController extends Controller
{
    public function index(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();
        $month = (string) $request->query('month', date('Y-m'));

        $this->render('vendor/finance', [
            'title' => 'Financeiro',
            'vendor' => $vendor,
            'finance' => FinanceService::monthData((int) $vendor['id'], $month),
        ], 'vendor');
    }

    public function markPaid(Request $request, string $appointmentId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();
        $month = (string) $request->input('month', date('Y-m'));

        AppointmentService::updateStatus((int) $vendor['id'], (int) $appointmentId, 'completed');
        $this->flashSuccess('Agendamento marcado como pago.');
        $this->redirect('/vendor/finance?month=' . urlencode($month));
    }

    public function markNoShow(Request $request, string $appointmentId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();
        $month = (string) $request->input('month', date('Y-m'));

        AppointmentService::updateStatus((int) $vendor['id'], (int) $appointmentId, 'no_show');
        $this->flashSuccess('Falta registrada.');
        $this->redirect('/vendor/finance?month=' . urlencode($month));
    }
}

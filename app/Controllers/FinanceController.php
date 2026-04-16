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
        $paymentMethod = (string) $request->input('payment_method', '');
        $cardFee = (float) $request->input('card_fee', 0);

        // Validate payment method
        $validMethods = ['cash', 'card', 'pix', 'other'];
        if ($paymentMethod !== '' && !in_array($paymentMethod, $validMethods, true)) {
            $paymentMethod = '';
        }

        // Card fee only applies to card payments
        if ($paymentMethod !== 'card') {
            $cardFee = 0;
        }

        AppointmentService::updateStatus(
            (int) $vendor['id'],
            (int) $appointmentId,
            'completed',
            $paymentMethod !== '' ? $paymentMethod : null,
            $cardFee
        );
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

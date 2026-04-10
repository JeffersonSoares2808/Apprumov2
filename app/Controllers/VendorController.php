<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AppointmentService;
use App\Services\AuthService;
use App\Services\VendorService;
use RuntimeException;

final class VendorController extends Controller
{
    public function dashboard(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();

        $this->render('vendor/dashboard', [
            'title' => 'Dashboard',
            'vendor' => $vendor,
            'dashboard' => AppointmentService::dashboardData((int) $vendor['id']),
        ], 'vendor');
    }

    public function agenda(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();
        $selectedDate = (string) $request->query('date', date('Y-m-d'));
        $services = VendorService::services((int) $vendor['id']);
        $serviceSlots = [];

        foreach ($services as $service) {
            $serviceSlots[(int) $service['id']] = AppointmentService::availableSlots($vendor, $service, $selectedDate);
        }

        $this->render('vendor/agenda', [
            'title' => 'Agenda',
            'vendor' => $vendor,
            'agenda' => AppointmentService::agendaData((int) $vendor['id'], $selectedDate),
            'services' => $services,
            'service_slots' => $serviceSlots,
        ], 'vendor');
    }

    public function storeAppointment(Request $request): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();
        $date = (string) $request->input('appointment_date', date('Y-m-d'));

        try {
            AppointmentService::create((int) $vendor['id'], $request->input());
            $this->flashSuccess('Gravado com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/agenda?date=' . urlencode($date));
    }

    public function updateAppointmentStatus(Request $request, string $appointmentId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();
        $date = (string) $request->input('redirect_date', date('Y-m-d'));

        try {
            AppointmentService::updateStatus((int) $vendor['id'], (int) $appointmentId, (string) $request->input('status', 'confirmed'));
            $this->flashSuccess('Status atualizado.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/agenda?date=' . urlencode($date));
    }

    public function deleteAppointment(Request $request, string $appointmentId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();
        $date = (string) $request->input('redirect_date', date('Y-m-d'));

        AppointmentService::delete((int) $vendor['id'], (int) $appointmentId);
        $this->flashSuccess('Agendamento excluído.');
        $this->redirect('/vendor/agenda?date=' . urlencode($date));
    }

    public function storeWaiting(Request $request): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();
        $date = (string) $request->input('desired_date', date('Y-m-d'));

        try {
            AppointmentService::createWaitingEntry((int) $vendor['id'], $request->input());
            $this->flashSuccess('Cliente adicionado à fila.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/agenda?date=' . urlencode($date));
    }

    public function deleteWaiting(Request $request, string $entryId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();
        $date = (string) $request->input('redirect_date', date('Y-m-d'));

        AppointmentService::deleteWaitingEntry((int) $vendor['id'], (int) $entryId);
        $this->flashSuccess('Cliente removido da fila.');
        $this->redirect('/vendor/agenda?date=' . urlencode($date));
    }

    public function menu(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();

        $this->render('vendor/menu', [
            'title' => 'Mais',
            'vendor' => $vendor,
        ], 'vendor');
    }
}

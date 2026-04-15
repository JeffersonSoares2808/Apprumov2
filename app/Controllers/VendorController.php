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
        $vendorId = (int) $vendor['id'];
        $dashboardData = AppointmentService::dashboardData($vendorId);
        $services = VendorService::services($vendorId);

        // Extra data for smarter AI insights
        $weeklyHours = VendorService::weeklyHours($vendorId);
        $enabledDays = array_filter($weeklyHours, static fn(array $h) => (int) ($h['is_enabled'] ?? 0) === 1);
        $notificationSettings = \App\Services\NotificationService::getSettings($vendorId);
        $clients = \App\Core\Database::select(
            'SELECT COUNT(*) AS total, 
                    SUM(CASE WHEN visit_count >= 3 THEN 1 ELSE 0 END) AS recurring
             FROM (
                 SELECT customer_phone, COUNT(*) AS visit_count
                 FROM appointments WHERE vendor_id = :vid AND status = \'completed\'
                 GROUP BY customer_phone
             ) AS client_stats',
            ['vid' => $vendorId]
        );
        $clientStats = $clients[0] ?? ['total' => 0, 'recurring' => 0];

        // Last 30 days revenue vs previous 30 days
        $revenueComparison = \App\Core\Database::selectOne(
            'SELECT 
                COALESCE(SUM(CASE WHEN appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE() THEN price ELSE 0 END), 0) AS recent_revenue,
                COALESCE(SUM(CASE WHEN appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND DATE_SUB(CURDATE(), INTERVAL 31 DAY) THEN price ELSE 0 END), 0) AS previous_revenue
             FROM appointments
             WHERE vendor_id = :vid AND status = \'completed\'',
            ['vid' => $vendorId]
        ) ?: ['recent_revenue' => 0, 'previous_revenue' => 0];

        // Busiest day of week
        $busiestDay = \App\Core\Database::selectOne(
            'SELECT DAYOFWEEK(appointment_date) AS dow, COUNT(*) AS total
             FROM appointments
             WHERE vendor_id = :vid AND status IN (\'confirmed\', \'completed\')
               AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
             GROUP BY DAYOFWEEK(appointment_date)
             ORDER BY total DESC
             LIMIT 1',
            ['vid' => $vendorId]
        );

        // No-show rate last 30 days
        $noShowData = \App\Core\Database::selectOne(
            'SELECT 
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN status = \'no_show\' THEN 1 ELSE 0 END), 0) AS no_shows
             FROM appointments
             WHERE vendor_id = :vid
               AND appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()',
            ['vid' => $vendorId]
        ) ?: ['total' => 0, 'no_shows' => 0];

        $this->render('vendor/dashboard', [
            'title' => 'Dashboard',
            'vendor' => $vendor,
            'dashboard' => $dashboardData,
            'services' => $services,
            'ai_data' => [
                'enabled_days' => count($enabledDays),
                'total_clients' => (int) ($clientStats['total'] ?? 0),
                'recurring_clients' => (int) ($clientStats['recurring'] ?? 0),
                'recent_revenue' => (float) ($revenueComparison['recent_revenue'] ?? 0),
                'previous_revenue' => (float) ($revenueComparison['previous_revenue'] ?? 0),
                'busiest_dow' => (int) ($busiestDay['dow'] ?? 0),
                'busiest_count' => (int) ($busiestDay['total'] ?? 0),
                'no_show_total' => (int) ($noShowData['total'] ?? 0),
                'no_show_count' => (int) ($noShowData['no_shows'] ?? 0),
                'has_profile_image' => !empty($vendor['profile_image']),
                'has_bio' => !empty($vendor['bio']) && strlen(trim($vendor['bio'])) > 10,
                'has_address' => !empty($vendor['address']),
                'has_whatsapp_api' => !empty($vendor['whatsapp_api_token']),
                'reminder_minutes' => (int) ($notificationSettings['reminder_minutes_before'] ?? 1440),
                'reminders_enabled' => (int) ($notificationSettings['send_reminders'] ?? 1),
            ],
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

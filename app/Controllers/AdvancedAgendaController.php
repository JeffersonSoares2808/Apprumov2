<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\AppointmentService;
use App\Services\AuthService;
use App\Services\ProfessionalService;
use App\Services\VendorService;
use RuntimeException;

final class AdvancedAgendaController extends Controller
{
    public function index(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();
        $view = (string) $request->query('view', 'week');
        $startDate = (string) $request->query('date', date('Y-m-d'));

        if (!in_array($view, ['day', 'week', 'month'], true)) {
            $view = 'week';
        }

        $professionals = ProfessionalService::listActiveByVendor((int) $vendor['id']);
        $services = VendorService::services((int) $vendor['id']);

        // Build calendar data per professional
        $calendarData = self::buildCalendarData((int) $vendor['id'], $professionals, $view, $startDate);

        $this->render('vendor/advanced-agenda', [
            'title' => 'Agenda Profissional',
            'vendor' => $vendor,
            'view' => $view,
            'start_date' => $startDate,
            'calendar_data' => $calendarData,
            'professionals' => $professionals,
            'services' => $services,
        ], 'vendor');
    }

    public function createAppointment(Request $request): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();
        $view = (string) $request->input('view', 'week');
        $startDate = (string) $request->input('date', date('Y-m-d'));

        try {
            $data = $request->input();
            AppointmentService::create((int) $vendor['id'], $data);
            $this->flashSuccess('Agendamento criado com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/advanced-agenda?view=' . urlencode($view) . '&date=' . urlencode($startDate));
    }

    public function updateAppointmentStatus(Request $request, string $appointmentId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();
        $view = (string) $request->input('view', 'week');
        $startDate = (string) $request->input('date', date('Y-m-d'));

        try {
            AppointmentService::updateStatus((int) $vendor['id'], (int) $appointmentId, (string) $request->input('status', 'confirmed'));
            $this->flashSuccess('Status atualizado.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/advanced-agenda?view=' . urlencode($view) . '&date=' . urlencode($startDate));
    }

    public function deleteAppointment(Request $request, string $appointmentId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();
        $view = (string) $request->input('view', 'week');
        $startDate = (string) $request->input('date', date('Y-m-d'));

        AppointmentService::delete((int) $vendor['id'], (int) $appointmentId);
        $this->flashSuccess('Agendamento excluído.');

        $this->redirect('/vendor/advanced-agenda?view=' . urlencode($view) . '&date=' . urlencode($startDate));
    }

    public function professionals(Request $request): void
    {
        $vendor = AuthService::requireActiveVendor();
        $professionals = ProfessionalService::listByVendor((int) $vendor['id']);

        // Load linked services for each professional
        foreach ($professionals as &$prof) {
            $prof['linked_services'] = ProfessionalService::getLinkedServices((int) $prof['id']);
            // For specific-schedule professionals, load upcoming registered dates
            if (($prof['schedule_type'] ?? 'weekly') === 'specific') {
                $prof['upcoming_dates'] = ProfessionalService::getExceptions(
                    (int) $prof['id'],
                    date('Y-m-d'),
                    date('Y-m-d', strtotime('+90 days'))
                );
            }
        }
        unset($prof);

        $this->render('vendor/professionals', [
            'title' => 'Profissionais',
            'vendor' => $vendor,
            'professionals' => $professionals,
            'services' => VendorService::services((int) $vendor['id']),
        ], 'vendor');
    }

    public function storeProfessional(Request $request): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            ProfessionalService::create((int) $vendor['id'], $request->input());
            $this->flashSuccess('Profissional adicionado com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/professionals');
    }

    public function updateProfessional(Request $request, string $professionalId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            ProfessionalService::update((int) $vendor['id'], (int) $professionalId, $request->input());
            $this->flashSuccess('Profissional atualizado.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/professionals');
    }

    public function toggleProfessional(Request $request, string $professionalId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        ProfessionalService::toggle((int) $vendor['id'], (int) $professionalId);
        $this->flashSuccess('Status do profissional atualizado.');

        $this->redirect('/vendor/professionals');
    }

    public function deleteProfessional(Request $request, string $professionalId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            ProfessionalService::delete((int) $vendor['id'], (int) $professionalId);
            $this->flashSuccess('Profissional excluído.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/professionals');
    }

    public function updateProfessionalServices(Request $request, string $professionalId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            $serviceIds = $request->input('service_ids', []);
            if (!is_array($serviceIds)) {
                $serviceIds = [];
            }
            ProfessionalService::updateLinkedServices((int) $vendor['id'], (int) $professionalId, $serviceIds);
            $this->flashSuccess('Serviços vinculados com sucesso.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/professionals');
    }

    public function professionalAvailability(Request $request, string $professionalId): void
    {
        $vendor = AuthService::requireActiveVendor();
        $professional = ProfessionalService::findById((int) $vendor['id'], (int) $professionalId);

        if (!$professional) {
            $this->flashError('Profissional não encontrado.');
            $this->redirect('/vendor/professionals');
            return;
        }

        $this->render('vendor/professional-availability', [
            'title' => 'Disponibilidade - ' . $professional['name'],
            'vendor' => $vendor,
            'professional' => $professional,
            'availability' => ProfessionalService::getAvailability((int) $professionalId),
        ], 'vendor');
    }

    public function updateProfessionalAvailability(Request $request, string $professionalId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            $availabilityData = $request->input('availability', []);
            if (!is_array($availabilityData)) {
                $availabilityData = [];
            }
            ProfessionalService::updateAvailability((int) $vendor['id'], (int) $professionalId, $availabilityData);
            $this->flashSuccess('Disponibilidade atualizada.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/professionals/' . $professionalId . '/availability');
    }

    public function professionalExceptions(Request $request, string $professionalId): void
    {
        $vendor = AuthService::requireActiveVendor();
        $professional = ProfessionalService::findById((int) $vendor['id'], (int) $professionalId);

        if (!$professional) {
            $this->flashError('Profissional não encontrado.');
            $this->redirect('/vendor/professionals');
            return;
        }

        $isSpecific = ($professional['schedule_type'] ?? 'weekly') === 'specific';

        // For specific-schedule professionals, show a wider default range (3 months ahead)
        $defaultStart = date('Y-m-01');
        $defaultEnd = $isSpecific ? date('Y-m-t', strtotime('+2 months')) : date('Y-m-t');

        $startDate = (string) $request->query('start_date', $defaultStart);
        $endDate = (string) $request->query('end_date', $defaultEnd);

        $this->render('vendor/professional-exceptions', [
            'title' => 'Exceções - ' . $professional['name'],
            'vendor' => $vendor,
            'professional' => $professional,
            'exceptions' => ProfessionalService::getExceptions((int) $professionalId, $startDate, $endDate),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ], 'vendor');
    }

    public function addProfessionalException(Request $request, string $professionalId): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        try {
            ProfessionalService::addException((int) $vendor['id'], (int) $professionalId, $request->input());
            $this->flashSuccess('Exceção adicionada.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/vendor/professionals/' . $professionalId . '/exceptions');
    }

    public function deleteProfessionalException(Request $request, string $professionalId, string $date): void
    {
        $this->validateCsrf($request);
        $vendor = AuthService::requireActiveVendor();

        ProfessionalService::deleteException((int) $vendor['id'], (int) $professionalId, $date);
        $this->flashSuccess('Exceção removida.');

        $this->redirect('/vendor/professionals/' . $professionalId . '/exceptions');
    }

    /**
     * Build calendar data grouped by professional for the given view and date.
     */
    private static function buildCalendarData(int $vendorId, array $professionals, string $view, string $startDate): array
    {
        $dates = self::getDatesForView($view, $startDate);
        $endDate = $dates[count($dates) - 1];

        // Fetch all appointments for date range
        $appointments = self::fetchAppointmentsForRange($vendorId, $dates[0], $endDate);

        // Group by professional
        $grouped = [];
        foreach ($appointments as $appt) {
            $profId = (int) ($appt['professional_id'] ?? 0);
            $date = $appt['appointment_date'];
            $grouped[$profId][$date][] = $appt;
        }

        // Batch-load all working hours data upfront (2 queries total instead of 2×N×D)
        $professionalIds = array_map(static fn(array $p): int => (int) $p['id'], $professionals);
        $workingData = self::batchLoadWorkingHours($professionalIds, $dates[0], $endDate);

        $result = [
            'dates' => $dates,
            'professionals' => [],
            'unassigned' => [],
        ];

        foreach ($professionals as $prof) {
            $profData = [
                'id' => (int) $prof['id'],
                'name' => $prof['name'],
                'color' => $prof['color'],
                'slots' => [],
            ];

            foreach ($dates as $date) {
                $profData['slots'][$date] = [
                    'appointments' => $grouped[(int) $prof['id']][$date] ?? [],
                    'working_hours' => self::resolveWorkingHours($workingData, (int) $prof['id'], $date),
                ];
            }

            $result['professionals'][] = $profData;
        }

        // Unassigned appointments (professional_id = NULL or 0)
        foreach ($dates as $date) {
            $result['unassigned'][$date] = $grouped[0][$date] ?? [];
        }

        return $result;
    }

    /**
     * Batch-load availability and exceptions for all professionals in two queries.
     *
     * @param int[] $professionalIds
     * @return array{availability: array<int, array<int, array>>, exceptions: array<int, array<string, array>>}
     */
    private static function batchLoadWorkingHours(array $professionalIds, string $startDate, string $endDate): array
    {
        if (empty($professionalIds)) {
            return ['availability' => [], 'exceptions' => [], 'schedule_types' => []];
        }

        $params = [];
        $placeholders = [];
        foreach (array_values($professionalIds) as $i => $id) {
            $key = "pid{$i}";
            $placeholders[] = ":{$key}";
            $params[$key] = (int) $id;
        }
        $inClause = implode(',', $placeholders);

        $availability = \App\Core\Database::select(
            "SELECT professional_id, day_of_week, start_time, end_time FROM professional_availability WHERE professional_id IN ({$inClause}) AND is_active = 1 ORDER BY professional_id, day_of_week",
            $params
        );

        $exceptParams = array_merge($params, ['start' => $startDate, 'end' => $endDate]);
        $exceptions = \App\Core\Database::select(
            "SELECT professional_id, exception_date, is_available, start_time, end_time FROM professional_exceptions WHERE professional_id IN ({$inClause}) AND exception_date BETWEEN :start AND :end",
            $exceptParams
        );

        // Load schedule types for all professionals
        $professionals = \App\Core\Database::select(
            "SELECT id, schedule_type FROM professionals WHERE id IN ({$inClause})",
            $params
        );

        // Index by professional_id
        $availMap = [];
        foreach ($availability as $row) {
            $availMap[(int) $row['professional_id']][(int) $row['day_of_week']] = $row;
        }

        $exceptMap = [];
        foreach ($exceptions as $row) {
            $exceptMap[(int) $row['professional_id']][$row['exception_date']] = $row;
        }

        $scheduleTypes = [];
        foreach ($professionals as $row) {
            $scheduleTypes[(int) $row['id']] = $row['schedule_type'] ?? 'weekly';
        }

        return ['availability' => $availMap, 'exceptions' => $exceptMap, 'schedule_types' => $scheduleTypes];
    }

    /**
     * Resolve working hours for a single professional+date from pre-loaded data.
     */
    private static function resolveWorkingHours(array $workingData, int $professionalId, string $date): ?array
    {
        // Check exceptions first (overrides regular availability)
        $exception = $workingData['exceptions'][$professionalId][$date] ?? null;
        if ($exception) {
            if (!(int) $exception['is_available']) {
                return null;
            }
            return ['start_time' => $exception['start_time'], 'end_time' => $exception['end_time']];
        }

        // For "specific" schedule type, only exception dates are valid.
        // No exception registered = not working that day.
        $scheduleType = $workingData['schedule_types'][$professionalId] ?? 'weekly';
        if ($scheduleType === 'specific') {
            return null;
        }

        // Check regular weekly availability
        $dayOfWeek = (int) date('w', strtotime($date));
        $avail = $workingData['availability'][$professionalId][$dayOfWeek] ?? null;
        if (!$avail) {
            return null;
        }

        return ['start_time' => $avail['start_time'], 'end_time' => $avail['end_time']];
    }

    /**
     * Get array of date strings for the given view type.
     */
    private static function getDatesForView(string $view, string $startDate): array
    {
        switch ($view) {
            case 'day':
                return [$startDate];

            case 'week':
                $dates = [];
                $timestamp = strtotime($startDate);
                $sunday = strtotime('last sunday', $timestamp);
                if ((int) date('w', $timestamp) === 0) {
                    $sunday = $timestamp;
                }
                for ($i = 0; $i < 7; $i++) {
                    $dates[] = date('Y-m-d', strtotime('+' . $i . ' days', $sunday));
                }
                return $dates;

            case 'month':
                $dates = [];
                $firstDay = date('Y-m-01', strtotime($startDate));
                $lastDay = date('Y-m-t', strtotime($startDate));
                $current = strtotime($firstDay);
                $end = strtotime($lastDay);
                while ($current <= $end) {
                    $dates[] = date('Y-m-d', $current);
                    $current = strtotime('+1 day', $current);
                }
                return $dates;

            default:
                return [$startDate];
        }
    }
    /**
     * Fetch appointments for a date range with professional info.
     */
    private static function fetchAppointmentsForRange(int $vendorId, string $startDate, string $endDate): array
    {
        return \App\Core\Database::select(
            'SELECT a.*, s.title AS service_title, p.name AS professional_name, p.color AS professional_color
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             LEFT JOIN professionals p ON p.id = a.professional_id
             WHERE a.vendor_id = :vendor_id
               AND a.appointment_date BETWEEN :start_date AND :end_date
               AND a.status NOT IN (\'cancelled\', \'no_show\')
             ORDER BY a.appointment_date, a.start_time',
            [
                'vendor_id' => $vendorId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        );
    }
}

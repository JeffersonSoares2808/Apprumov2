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
                COALESCE(SUM(CASE WHEN appointment_date = CURDATE() AND status = \'confirmed\' THEN 1 ELSE 0 END), 0) AS today_confirmed,
                COALESCE(SUM(CASE WHEN status = \'completed\' AND appointment_date BETWEEN :completed_month_start AND :completed_month_end THEN price ELSE 0 END), 0) AS completed_revenue,
                COALESCE(SUM(CASE WHEN status IN (\'cancelled\', \'no_show\') AND appointment_date BETWEEN :loss_month_start AND :loss_month_end THEN price ELSE 0 END), 0) AS month_losses
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
               AND CONCAT(a.appointment_date, \' \', a.start_time) >= NOW()
               AND a.status IN (\'confirmed\', \'completed\')
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
            'month_calendar' => self::monthCalendar($vendorId, $selectedDate),
            'appointments' => self::appointmentsForDate($vendorId, $selectedDate),
            'waiting_list' => self::waitingListForDate($vendorId, $selectedDate),
        ];
    }

    public static function appointmentsForDate(int $vendorId, string $date): array
    {
        return Database::select(
            'SELECT a.*, s.title AS service_title, p.name AS professional_name, p.color AS professional_color
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             LEFT JOIN professionals p ON p.id = a.professional_id
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

        // Prevent scheduling appointments in the past
        if (strtotime($appointmentDate) < strtotime(date('Y-m-d'))) {
            throw new RuntimeException('Não é possível agendar em uma data passada.');
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
            $pubProfId = !empty($data['professional_id']) ? (int) $data['professional_id'] : null;
            $slots = self::availableSlots($vendor, $service, $appointmentDate, $pubProfId);
            if (!in_array(substr($normalizedStart, 0, 5), $slots, true)) {
                throw new RuntimeException('Este horário não está mais disponível.');
            }
        }

        $professionalId = !empty($data['professional_id']) ? (int) $data['professional_id'] : null;

        // Validate that the professional is available on this date and that
        // the requested time falls within their working hours.
        if ($professionalId !== null) {
            $profRecord = Database::selectOne(
                'SELECT schedule_type, name FROM professionals WHERE id = :id AND vendor_id = :vendor_id LIMIT 1',
                ['id' => $professionalId, 'vendor_id' => $vendorId]
            );
            if ($profRecord) {
                $profHours = ProfessionalService::getWorkingHoursForDate($professionalId, $appointmentDate);
                $profName = htmlspecialchars($profRecord['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $formattedDate = date('d/m/Y', strtotime($appointmentDate));

                if (!$profHours) {
                    if (($profRecord['schedule_type'] ?? 'weekly') === 'specific') {
                        throw new RuntimeException(
                            'O profissional ' . $profName . ' não está cadastrado para a data ' . $formattedDate . '. Cadastre a data nas Datas Específicas do profissional antes de agendar.'
                        );
                    }
                    $dayNames = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
                    $dow = (int) date('w', strtotime($appointmentDate));
                    throw new RuntimeException(
                        'O profissional ' . $profName . ' não atende na ' . $dayNames[$dow] . ' (' . $formattedDate . '). Verifique a disponibilidade semanal do profissional.'
                    );
                }

                // Validate that the appointment time falls within the professional's working hours
                $profStartTs = strtotime($appointmentDate . ' ' . $profHours['start_time']);
                $profEndTs   = strtotime($appointmentDate . ' ' . $profHours['end_time']);
                $apptStartTs = strtotime($appointmentDate . ' ' . $normalizedStart);
                $apptEndTs   = strtotime($appointmentDate . ' ' . $endTime);

                if ($apptStartTs < $profStartTs || $apptEndTs > $profEndTs) {
                    $profStartFmt = date('H:i', $profStartTs);
                    $profEndFmt   = date('H:i', $profEndTs);
                    throw new RuntimeException(
                        'O horário solicitado está fora do expediente do profissional ' . $profName
                        . ' (disponível das ' . $profStartFmt . ' às ' . $profEndFmt . ' em ' . $formattedDate . ').'
                    );
                }
            }
        }

        // Auto-assign a professional if none was selected but the service has linked professionals
        if ($professionalId === null) {
            $serviceProfessionals = ProfessionalService::getByService($vendorId, $serviceId);
            if (!empty($serviceProfessionals)) {
                foreach ($serviceProfessionals as $prof) {
                    $profHours = ProfessionalService::getWorkingHoursForDate((int) $prof['id'], $appointmentDate);
                    if (!$profHours) {
                        continue;
                    }
                    $profStartTs = strtotime($appointmentDate . ' ' . $profHours['start_time']);
                    $profEndTs = strtotime($appointmentDate . ' ' . $profHours['end_time']);
                    $slotStartTs = strtotime($appointmentDate . ' ' . $normalizedStart);
                    $slotEndTs = strtotime($appointmentDate . ' ' . $endWithBuffer);
                    if ($slotStartTs < $profStartTs || $slotEndTs > $profEndTs) {
                        continue;
                    }
                    if (!self::hasConflict($vendorId, $appointmentDate, $normalizedStart, $endWithBuffer, 0, (int) $prof['id'], $bufferMinutes)) {
                        $professionalId = (int) $prof['id'];
                        break;
                    }
                }
            }
        }

        $appointmentId = Database::transaction(function () use (
            $vendorId,
            $serviceId,
            $service,
            $appointmentDate,
            $normalizedStart,
            $endTime,
            $endWithBuffer,
            $durationMinutes,
            $price,
            $customerName,
            $customerEmail,
            $customerPhone,
            $professionalId,
            $bufferMinutes,
            $data,
            $isPublic
        ): int {
            // Check conflict INSIDE transaction with row-level lock to prevent double-booking
            if (self::hasConflictForUpdate($vendorId, $appointmentDate, $normalizedStart, $endWithBuffer, 0, $professionalId, $bufferMinutes)) {
                throw new RuntimeException('Já existe um atendimento ocupando esse horário.');
            }

            $clientId = self::upsertClient($vendorId, $customerName, $customerPhone, $customerEmail);

            Database::statement(
                'INSERT INTO appointments (
                    vendor_id, service_id, client_id, professional_id, customer_name, customer_email, customer_phone, appointment_date,
                    start_time, end_time, duration_minutes, price, status, source, lgpd_consent, notes, created_at, updated_at
                 ) VALUES (
                    :vendor_id, :service_id, :client_id, :professional_id, :customer_name, :customer_email, :customer_phone, :appointment_date,
                    :start_time, :end_time, :duration_minutes, :price, \'confirmed\', :source, :lgpd_consent, :notes, NOW(), NOW()
                 )',
                [
                    'vendor_id' => $vendorId,
                    'service_id' => $serviceId,
                    'client_id' => $clientId,
                    'professional_id' => $professionalId,
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

            $id = Database::lastInsertId();
            self::syncFinancialTransaction($id);

            return $id;
        });

        // Fire notification after transaction commits
        try {
            NotificationService::appointmentCreated($vendorId, $appointmentId);
        } catch (\Throwable $e) {
            error_log('Notification error on appointment create: ' . $e->getMessage());
        }

        return $appointmentId;
    }

    public static function updateStatus(int $vendorId, int $appointmentId, string $status, ?string $paymentMethod = null, float $cardFee = 0): void
    {
        $allowed = ['confirmed', 'completed', 'cancelled', 'no_show'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException('Status inválido.');
        }

        // Validate payment method
        $validMethods = ['cash', 'card', 'pix', 'other'];
        if ($paymentMethod !== null && !in_array($paymentMethod, $validMethods, true)) {
            $paymentMethod = null;
        }

        // Card fee only with card payments
        if ($paymentMethod !== 'card') {
            $cardFee = 0;
        }

        Database::statement(
            'UPDATE appointments
             SET status = :next_status,
                 paid_at = CASE WHEN :paid_status = \'completed\' THEN NOW() ELSE paid_at END,
                 payment_method = CASE WHEN :pm_status = \'completed\' THEN :payment_method ELSE payment_method END,
                 card_fee = CASE WHEN :cf_status = \'completed\' THEN :card_fee ELSE card_fee END,
                 updated_at = NOW()
             WHERE id = :id AND vendor_id = :vendor_id',
            [
                'next_status' => $status,
                'paid_status' => $status,
                'pm_status' => $status,
                'payment_method' => $paymentMethod,
                'cf_status' => $status,
                'card_fee' => $cardFee,
                'id' => $appointmentId,
                'vendor_id' => $vendorId,
            ]
        );

        self::syncFinancialTransaction($appointmentId);

        // Auto-create return when appointment is completed
        if ($status === 'completed') {
            try {
                ReturnService::createFromAppointment($vendorId, $appointmentId);
            } catch (\Throwable $e) {
                error_log('Return creation error: ' . $e->getMessage());
            }
        }

        // Fire notification
        try {
            NotificationService::appointmentStatusChanged($vendorId, $appointmentId, $status);
        } catch (\Throwable $e) {
            error_log('Notification error on status change: ' . $e->getMessage());
        }
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

    /**
     * Build a full day timeline with all time slots (occupied + free) for the vendor.
     * Each slot has: time, end_time, status ('free'|'occupied'), and optional appointment data.
     * Uses 30-minute intervals for the timeline grid.
     *
     * When $professionals are provided, each free slot includes 'available_professional_ids'
     * indicating which professionals are working during that time.  Slots where no professional
     * is available are excluded from the timeline so that the schedule accurately reflects
     * which hours are actually open for booking.
     *
     * @param array $professionals  Active professionals (each must have 'id' key). Pass empty to use vendor-only window.
     */
    public static function dayTimeline(int $vendorId, string $date, array $professionals = []): array
    {
        $vendor = \App\Services\VendorService::findById($vendorId);
        if (!$vendor) {
            return [];
        }

        $window = self::workingWindow($vendorId, $date);

        // Pre-compute professional working hours for this date
        $profAvailability = [];
        foreach ($professionals as $prof) {
            $profHours = ProfessionalService::getWorkingHoursForDate((int) $prof['id'], $date);
            if ($profHours) {
                $profAvailability[(int) $prof['id']] = $profHours;
            }
        }

        // When there are active professionals, the timeline window is determined
        // by the vendor hours.  Professional availability is used to decide which
        // slots are bookable, but the overall window must NOT exceed vendor hours
        // (the business opening hours).  If the vendor is closed on this date,
        // professionals alone do not open the timeline.
        if (!empty($profAvailability)) {
            if ($window) {
                // Vendor window exists – keep it as the boundary.
                // Professional hours outside the vendor window are clipped by the
                // per-slot availability check below.
            } else {
                // Vendor is closed on this date.  Even though professionals may be
                // configured for this day, the business is closed – return empty.
                return [];
            }
        }

        if (!$window) {
            return [];
        }

        $appointments = self::appointmentsForDate($vendorId, $date);

        // Build appointment lookup by start_time for quick matching
        $appointmentMap = [];
        foreach ($appointments as $appt) {
            $key = substr($appt['start_time'], 0, 5);
            $appointmentMap[$key] = $appt;
        }

        // Build the set of times occupied by appointments (considering duration)
        $occupiedSlots = [];
        foreach ($appointments as $appt) {
            if (in_array($appt['status'], ['cancelled', 'no_show'], true)) {
                continue;
            }
            $start = strtotime($date . ' ' . $appt['start_time']);
            $end = strtotime($date . ' ' . $appt['end_time']);
            $durationSlots = (int) ceil(($end - $start) / 1800); // 1800 = 30 minutes
            for ($i = 0; $i < $durationSlots; $i++) {
                $occupiedSlots[date('H:i', $start + $i * 1800)] = true;
            }
        }

        $hasProfessionals = !empty($professionals);
        $step = 30; // 30-minute timeline intervals
        $timeline = [];
        $current = strtotime($date . ' ' . $window['start_time']);
        $endBoundary = strtotime($date . ' ' . $window['end_time']);
        $nowTimestamp = time();
        $isToday = $date === date('Y-m-d');

        while ($current < $endBoundary) {
            $slotTime = date('H:i', $current);
            $slotEnd = date('H:i', strtotime('+' . $step . ' minutes', $current));
            $isPast = $isToday && $current < $nowTimestamp;

            // Check if this slot has an appointment starting at this time
            $appt = $appointmentMap[$slotTime] ?? null;

            if ($appt && !in_array($appt['status'], ['cancelled', 'no_show'], true)) {
                $timeline[] = [
                    'time' => $slotTime,
                    'end_time' => substr($appt['end_time'], 0, 5),
                    'status' => 'occupied',
                    'is_past' => $isPast,
                    'appointment' => $appt,
                    'available_professional_ids' => [],
                ];
            } elseif (isset($occupiedSlots[$slotTime])) {
                // Slot is continuation of a running appointment - skip display
                // (the appointment card already covers this time)
            } else {
                // Determine which professionals are available at this slot
                $availableProfIds = [];
                if ($hasProfessionals) {
                    $slotStartTs = $current;
                    $slotEndTs = strtotime('+' . $step . ' minutes', $current);
                    foreach ($profAvailability as $profId => $hours) {
                        $profStart = strtotime($date . ' ' . $hours['start_time']);
                        $profEnd   = strtotime($date . ' ' . $hours['end_time']);
                        if ($slotStartTs >= $profStart && $slotEndTs <= $profEnd) {
                            $availableProfIds[] = $profId;
                        }
                    }

                    // If professionals are configured but none available at this slot,
                    // skip this slot entirely (don't show as "free").
                    if (empty($availableProfIds)) {
                        $current = strtotime('+' . $step . ' minutes', $current);
                        continue;
                    }
                }

                $timeline[] = [
                    'time' => $slotTime,
                    'end_time' => $slotEnd,
                    'status' => $isPast ? 'past' : 'free',
                    'is_past' => $isPast,
                    'appointment' => null,
                    'available_professional_ids' => $availableProfIds,
                ];
            }

            $current = strtotime('+' . $step . ' minutes', $current);
        }

        return $timeline;
    }

    public static function availableSlots(array $vendor, array $service, string $date, ?int $professionalId = null): array
    {
        $vendorId = (int) $vendor['id'];
        $serviceId = (int) $service['id'];

        // Get professionals linked to this service (if any)
        $serviceProfessionals = ProfessionalService::getByService($vendorId, $serviceId);
        $hasProfessionals = !empty($serviceProfessionals);

        // If a specific professional is selected, use the intersection of
        // their working hours and the vendor's hours so that slots never
        // extend beyond the business opening times.
        $vendorWindow = self::workingWindow($vendorId, $date);

        if ($professionalId !== null && $professionalId > 0) {
            $profWindow = ProfessionalService::getWorkingHoursForDate($professionalId, $date);
            if (!$profWindow || !$vendorWindow) {
                return [];
            }
            // Intersect: latest start, earliest end
            $profStart = strtotime($date . ' ' . $profWindow['start_time']);
            $profEnd   = strtotime($date . ' ' . $profWindow['end_time']);
            $vStart    = strtotime($date . ' ' . $vendorWindow['start_time']);
            $vEnd      = strtotime($date . ' ' . $vendorWindow['end_time']);
            $windowStart = max($profStart, $vStart);
            $windowEnd   = min($profEnd, $vEnd);
            if ($windowStart >= $windowEnd) {
                return []; // No overlapping hours
            }
            $window = [
                'start_time' => date('H:i:s', $windowStart),
                'end_time'   => date('H:i:s', $windowEnd),
            ];
        } else {
            $window = $vendorWindow;
        }

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

            if ($professionalId !== null && $professionalId > 0) {
                // Specific professional selected: check conflict for that professional
                if (!self::hasConflict($vendorId, $date, $slotStart, $slotEnd, 0, $professionalId, $buffer)) {
                    $slots[] = date('H:i', $current);
                }
            } elseif ($hasProfessionals) {
                // "Any professional" mode: slot is available if at least ONE
                // linked professional is working AND has no conflict at this time.
                foreach ($serviceProfessionals as $prof) {
                    $profHours = ProfessionalService::getWorkingHoursForDate((int) $prof['id'], $date);
                    if (!$profHours) {
                        continue; // This professional doesn't work on this date
                    }
                    // Verify slot falls within this professional's working hours
                    $profStart = strtotime($date . ' ' . $profHours['start_time']);
                    $profEnd = strtotime($date . ' ' . $profHours['end_time']);
                    if ($current < $profStart || $slotEndTimestamp > $profEnd) {
                        continue;
                    }
                    // Check conflict for this professional
                    if (!self::hasConflict($vendorId, $date, $slotStart, $slotEnd, 0, (int) $prof['id'], $buffer)) {
                        $slots[] = date('H:i', $current);
                        break; // At least one professional available, slot is valid
                    }
                }
            } else {
                // No professionals linked: use vendor-level conflict check
                if (!self::hasConflict($vendorId, $date, $slotStart, $slotEnd, 0, null, $buffer)) {
                    $slots[] = date('H:i', $current);
                }
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

    /**
     * Build a full month calendar grid for the given date, with appointment
     * counts per day so the calendar can show busy indicators.
     */
    public static function monthCalendar(int $vendorId, string $selectedDate): array
    {
        $timestamp = strtotime($selectedDate);
        $year = (int) date('Y', $timestamp);
        $month = (int) date('n', $timestamp);

        $firstDay = mktime(0, 0, 0, $month, 1, $year);
        $daysInMonth = (int) date('t', $firstDay);
        $startWeekday = (int) date('w', $firstDay); // 0=Sun

        $monthStart = date('Y-m-01', $firstDay);
        $monthEnd = date('Y-m-t', $firstDay);

        // Fetch appointment counts for the whole month in a single query
        $counts = Database::select(
            'SELECT appointment_date, COUNT(*) AS total
             FROM appointments
             WHERE vendor_id = :vendor_id
               AND appointment_date BETWEEN :start AND :end
               AND status NOT IN (\'cancelled\', \'no_show\')
             GROUP BY appointment_date',
            [
                'vendor_id' => $vendorId,
                'start' => $monthStart,
                'end' => $monthEnd,
            ]
        );

        $countMap = [];
        foreach ($counts as $row) {
            $countMap[$row['appointment_date']] = (int) $row['total'];
        }

        $today = date('Y-m-d');
        $prevMonth = date('Y-m-d', mktime(0, 0, 0, $month - 1, 1, $year));
        $nextMonth = date('Y-m-d', mktime(0, 0, 0, $month + 1, 1, $year));

        $monthLabels = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
        ];

        // Build calendar grid (6 rows max × 7 cols)
        $weeks = [];
        $dayCounter = 1;
        for ($row = 0; $row < 6; $row++) {
            $week = [];
            for ($col = 0; $col < 7; $col++) {
                if (($row === 0 && $col < $startWeekday) || $dayCounter > $daysInMonth) {
                    $week[] = null;
                } else {
                    $date = sprintf('%04d-%02d-%02d', $year, $month, $dayCounter);
                    $week[] = [
                        'date' => $date,
                        'day' => $dayCounter,
                        'is_today' => $date === $today,
                        'is_selected' => $date === $selectedDate,
                        'appointment_count' => $countMap[$date] ?? 0,
                    ];
                    $dayCounter++;
                }
            }
            $weeks[] = $week;
            if ($dayCounter > $daysInMonth) {
                break;
            }
        }

        return [
            'month_label' => $monthLabels[$month] . ' ' . $year,
            'prev_month_date' => $prevMonth,
            'next_month_date' => $nextMonth,
            'prev_week_date' => date('Y-m-d', strtotime('-7 days', $timestamp)),
            'next_week_date' => date('Y-m-d', strtotime('+7 days', $timestamp)),
            'weeks' => $weeks,
        ];
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

    private static function hasConflict(int $vendorId, string $date, string $startTime, string $endTime, int $ignoreId = 0, ?int $professionalId = null, int $bufferMinutes = 0): bool
    {
        return self::doConflictQuery($vendorId, $date, $startTime, $endTime, $ignoreId, $professionalId, false, $bufferMinutes);
    }

    /**
     * Check for conflicts using SELECT ... FOR UPDATE to prevent race conditions.
     * Must be called inside a transaction.
     */
    private static function hasConflictForUpdate(int $vendorId, string $date, string $startTime, string $endTime, int $ignoreId = 0, ?int $professionalId = null, int $bufferMinutes = 0): bool
    {
        return self::doConflictQuery($vendorId, $date, $startTime, $endTime, $ignoreId, $professionalId, true, $bufferMinutes);
    }

    /**
     * @param int $bufferMinutes  Interval between appointments (vendor setting).
     *                            When > 0, existing appointments are treated as if
     *                            their end_time extends by this many minutes, preventing
     *                            back-to-back bookings that violate the buffer.
     */
    private static function doConflictQuery(int $vendorId, string $date, string $startTime, string $endTime, int $ignoreId, ?int $professionalId, bool $forUpdate, int $bufferMinutes = 0): bool
    {
        // Ensure bufferMinutes is a non-negative integer (defense-in-depth)
        $bufferMinutes = max(0, $bufferMinutes);

        // When a buffer is configured, we extend each existing appointment's
        // end_time by bufferMinutes so the overlap check accounts for the
        // required gap between appointments.
        $endTimeExpr = $bufferMinutes > 0
            ? 'DATE_ADD(end_time, INTERVAL ' . $bufferMinutes . ' MINUTE)'
            : 'end_time';

        $sql = 'SELECT id
                FROM appointments
                WHERE vendor_id = :vendor_id
                  AND appointment_date = :appointment_date
                  AND status NOT IN (\'cancelled\', \'no_show\')
                  AND start_time < :end_time
                  AND ' . $endTimeExpr . ' > :start_time';

        $params = [
            'vendor_id' => $vendorId,
            'appointment_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];

        if ($professionalId !== null && $professionalId > 0) {
            // Check conflicts for this specific professional OR unassigned (NULL)
            // appointments that also occupy the same time range.
            $sql .= ' AND (professional_id = :professional_id OR professional_id IS NULL)';
            $params['professional_id'] = $professionalId;
        } else {
            // When no professional is specified, only check unassigned appointments
            $sql .= ' AND professional_id IS NULL';
        }

        if ($ignoreId > 0) {
            $sql .= ' AND id != :ignore_id';
            $params['ignore_id'] = $ignoreId;
        }

        $sql .= ' LIMIT 1';

        if ($forUpdate) {
            $sql .= ' FOR UPDATE';
        }

        return Database::selectOne($sql, $params) !== null;
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

        $cardFee = (float) ($appointment['card_fee'] ?? 0);
        // Card fee is charged ON TOP to the patient, so total transaction = price + cardFee
        $amount = (float) $appointment['price'] + $cardFee;
        $paymentMethod = $appointment['payment_method'] ?? null;

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
            'amount' => $amount,
            'status' => $status,
            'payment_method' => $paymentMethod,
            'card_fee' => $cardFee,
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
                'payment_method' => $payload['payment_method'],
                'card_fee' => $payload['card_fee'],
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
                     payment_method = :payment_method,
                     card_fee = :card_fee,
                     transaction_date = :transaction_date,
                     updated_at = NOW()
                 WHERE id = :id',
                $updatePayload
            );

            return;
        }

        Database::statement(
            'INSERT INTO financial_transactions (
                vendor_id, appointment_id, kind, source, title, description, amount, status, payment_method, card_fee, transaction_date, created_at, updated_at
             ) VALUES (
                :vendor_id, :appointment_id, :kind, :source, :title, :description, :amount, :status, :payment_method, :card_fee, :transaction_date, NOW(), NOW()
             )',
            $payload
        );
    }
}

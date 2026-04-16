<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Security\RateLimiter;
use App\Security\SecurityLogger;
use App\Services\AppointmentService;
use App\Services\ProfessionalService;
use App\Services\VendorService;
use RuntimeException;

final class PublicController extends Controller
{
    public function profile(Request $request, string $slug): void
    {
        $vendor = VendorService::findBySlug($slug);
        if (!$vendor || VendorService::effectiveStatus($vendor) !== 'active') {
            http_response_code(404);
            echo 'Perfil não encontrado.';
            return;
        }

        // Load active professionals with their availability and linked services
        $professionals = ProfessionalService::listActiveByVendor((int) $vendor['id']);
        foreach ($professionals as &$prof) {
            $prof['availability'] = ProfessionalService::getAvailability((int) $prof['id']);
            $prof['linked_services'] = ProfessionalService::getLinkedServices((int) $prof['id']);
            // For specific-schedule professionals, load upcoming dates (next 60 days)
            if (($prof['schedule_type'] ?? 'weekly') === 'specific') {
                $prof['upcoming_dates'] = ProfessionalService::getExceptions(
                    (int) $prof['id'],
                    date('Y-m-d'),
                    date('Y-m-d', strtotime('+60 days'))
                );
            }
        }
        unset($prof);

        $this->render('public/profile', [
            'title' => $vendor['business_name'],
            'vendor' => $vendor,
            'services' => VendorService::services((int) $vendor['id'], true),
            'professionals' => $professionals,
            'reviews' => Database::select(
                'SELECT reviewer_name, rating, comment, created_at FROM vendor_reviews WHERE vendor_id = :vid ORDER BY created_at DESC LIMIT 20',
                ['vid' => (int) $vendor['id']]
            ),
        ], 'public');
    }

    public function booking(Request $request, string $slug, string $serviceId): void
    {
        $vendor = VendorService::findBySlug($slug);
        if (!$vendor || VendorService::effectiveStatus($vendor) !== 'active') {
            http_response_code(404);
            echo 'Página de agendamento indisponível.';
            return;
        }

        $service = AppointmentService::serviceById((int) $vendor['id'], (int) $serviceId);
        if (!$service || !(int) $service['is_active']) {
            http_response_code(404);
            echo 'Serviço não encontrado.';
            return;
        }

        $selectedDate = (string) $request->query('date', date('Y-m-d'));
        $successId = (int) $request->query('success', 0);

        if ($successId > 0) {
            $appointment = AppointmentService::findAppointment((int) $vendor['id'], $successId);
            if ($appointment) {
                $this->render('public/booking-success', [
                    'title' => 'Agendamento confirmado',
                    'vendor' => $vendor,
                    'service' => $service,
                    'appointment' => $appointment,
                ], 'public');
                return;
            }
        }

        // Load professionals that perform this service
        $serviceProfessionals = ProfessionalService::getByService((int) $vendor['id'], (int) $serviceId);
        $selectedProfessionalId = (int) $request->query('professional', 0);

        // If a professional is selected and has their own schedule, use it
        $professionalId = ($selectedProfessionalId > 0) ? $selectedProfessionalId : null;

        $availableDates = $this->availableDates((int) $vendor['id'], $service, 21, $professionalId);
        $slots = AppointmentService::availableSlots($vendor, $service, $selectedDate, $professionalId);

        if ($slots === [] && $availableDates !== []) {
            $selectedDate = $availableDates[0]['date'];
            $slots = AppointmentService::availableSlots($vendor, $service, $selectedDate, $professionalId);
        }

        $this->render('public/booking', [
            'title' => 'Agendar ' . $service['title'],
            'vendor' => $vendor,
            'service' => $service,
            'selected_date' => $selectedDate,
            'available_dates' => $availableDates,
            'slots' => $slots,
            'professionals' => $serviceProfessionals,
            'selected_professional_id' => $selectedProfessionalId,
        ], 'public');
    }

    public function storeBooking(Request $request, string $slug, string $serviceId): void
    {
        $this->validateCsrf($request);

        $vendor = VendorService::findBySlug($slug);
        if (!$vendor || VendorService::effectiveStatus($vendor) !== 'active') {
            http_response_code(404);
            echo 'Página de agendamento indisponível.';
            return;
        }

        try {
            if (!RateLimiter::attempt('public-booking:' . $request->ip(), 8, 600)) {
                SecurityLogger::warning('public_booking_rate_limited', ['slug' => $slug]);
                throw new RuntimeException('Muitas tentativas de agendamento. Tente novamente em alguns minutos.');
            }

            $appointmentId = AppointmentService::create((int) $vendor['id'], [
                'service_id' => (int) $serviceId,
                'professional_id' => $request->input('professional_id'),
                'customer_name' => $request->input('customer_name'),
                'customer_email' => $request->input('customer_email'),
                'customer_phone' => $request->input('customer_phone'),
                'appointment_date' => $request->input('appointment_date'),
                'start_time' => $request->input('start_time'),
                'lgpd_consent' => $request->input('lgpd_consent'),
            ], true);

            $this->redirect('/book/' . $slug . '/' . $serviceId . '?success=' . $appointmentId);
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
            $date = (string) $request->input('appointment_date', date('Y-m-d'));
            $this->redirect('/book/' . $slug . '/' . $serviceId . '?date=' . urlencode($date));
        }
    }

    public function storeReview(Request $request, string $slug): void
    {
        $this->validateCsrf($request);

        $vendor = VendorService::findBySlug($slug);
        if (!$vendor || VendorService::effectiveStatus($vendor) !== 'active') {
            http_response_code(404);
            echo 'Perfil não encontrado.';
            return;
        }

        try {
            if (!RateLimiter::attempt('review:' . $request->ip(), 5, 3600)) {
                throw new RuntimeException('Muitas avaliações enviadas. Tente novamente mais tarde.');
            }

            $name = trim((string) $request->input('reviewer_name', ''));
            $rating = (int) $request->input('rating', 5);
            $comment = trim((string) $request->input('comment', ''));

            if ($name === '') {
                throw new RuntimeException('Informe seu nome para avaliar.');
            }
            if ($rating < 1 || $rating > 5) {
                $rating = 5;
            }

            Database::statement(
                'INSERT INTO vendor_reviews (vendor_id, reviewer_name, rating, comment, created_at) VALUES (:vid, :name, :rating, :comment, :created)',
                [
                    'vid' => (int) $vendor['id'],
                    'name' => mb_substr($name, 0, 120),
                    'rating' => $rating,
                    'comment' => $comment !== '' ? mb_substr($comment, 0, 500) : null,
                    'created' => date('Y-m-d H:i:s'),
                ]
            );

            // Update vendor average rating
            $stats = Database::selectOne(
                'SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM vendor_reviews WHERE vendor_id = :vid',
                ['vid' => (int) $vendor['id']]
            );
            if ($stats) {
                Database::statement(
                    'UPDATE vendors SET public_rating = :avg, rating_count = :cnt WHERE id = :vid',
                    [
                        'avg' => round((float) $stats['avg_rating'], 1),
                        'cnt' => (int) $stats['cnt'],
                        'vid' => (int) $vendor['id'],
                    ]
                );
            }

            $this->flashSuccess('Avaliação enviada com sucesso! Obrigado pelo seu feedback.');
        } catch (RuntimeException $exception) {
            $this->flashError($exception->getMessage());
        }

        $this->redirect('/p/' . $slug . '#avaliacoes');
    }

    private function availableDates(int $vendorId, array $service, int $daysAhead, ?int $professionalId = null): array
    {
        $dates = [];
        $weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];

        for ($offset = 0; $offset < $daysAhead; $offset++) {
            $date = date('Y-m-d', strtotime('+' . $offset . ' days'));
            $vendor = VendorService::findById($vendorId);
            if (!$vendor) {
                continue;
            }

            $slots = AppointmentService::availableSlots($vendor, $service, $date, $professionalId);
            if ($slots !== []) {
                $dates[] = [
                    'date' => $date,
                    'label' => format_date($date, 'd/m'),
                    'weekday' => $weekdays[(int) date('w', strtotime($date))],
                ];
            }
        }

        return $dates;
    }
}

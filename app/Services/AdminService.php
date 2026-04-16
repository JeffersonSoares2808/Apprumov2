<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class AdminService
{
    public static function savePlan(array $data): void
    {
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $price = (float) ($data['price'] ?? 0);
        $durationDays = max(1, (int) ($data['duration_days'] ?? 30));
        $maxProfessionals = max(0, (int) ($data['max_professionals'] ?? 0));
        $description = trim((string) ($data['description'] ?? ''));
        $stripeCheckoutUrl = trim((string) ($data['stripe_checkout_url'] ?? ''));
        $isActive = isset($data['is_active']) ? 1 : 0;

        if ($name === '') {
            throw new RuntimeException('Informe o nome do plano.');
        }

        if ($id > 0) {
            Database::statement(
                'UPDATE plans
                 SET name = :name, price = :price, duration_days = :duration_days, max_professionals = :max_professionals,
                     description = :description, stripe_checkout_url = :stripe_checkout_url,
                     is_active = :is_active, updated_at = NOW()
                 WHERE id = :id',
                [
                    'name' => $name,
                    'price' => $price,
                    'duration_days' => $durationDays,
                    'max_professionals' => $maxProfessionals,
                    'description' => $description,
                    'stripe_checkout_url' => $stripeCheckoutUrl !== '' ? $stripeCheckoutUrl : null,
                    'is_active' => $isActive,
                    'id' => $id,
                ]
            );

            return;
        }

        Database::statement(
            'INSERT INTO plans (name, price, duration_days, max_professionals, description, stripe_checkout_url, is_active, created_at, updated_at)
             VALUES (:name, :price, :duration_days, :max_professionals, :description, :stripe_checkout_url, :is_active, NOW(), NOW())',
            [
                'name' => $name,
                'price' => $price,
                'duration_days' => $durationDays,
                'max_professionals' => $maxProfessionals,
                'description' => $description,
                'stripe_checkout_url' => $stripeCheckoutUrl !== '' ? $stripeCheckoutUrl : null,
                'is_active' => $isActive,
            ]
        );
    }

    public static function deletePlan(int $planId): void
    {
        Database::statement('DELETE FROM plans WHERE id = :id', ['id' => $planId]);
    }

    public static function activateVendor(int $vendorId, int $planId): void
    {
        $plan = Database::selectOne('SELECT * FROM plans WHERE id = :id LIMIT 1', ['id' => $planId]);
        if (!$plan) {
            throw new RuntimeException('Plano não encontrado.');
        }

        $start = date('Y-m-d');
        $expires = date('Y-m-d', strtotime($start . ' +' . (int) $plan['duration_days'] . ' days'));

        Database::statement(
            'UPDATE vendors
             SET status = \'active\',
                 plan_id = :plan_id,
                 plan_started_at = :start_date,
                 plan_expires_at = :expires_at,
                 updated_at = NOW()
             WHERE id = :id',
            [
                'plan_id' => $planId,
                'start_date' => $start,
                'expires_at' => $expires,
                'id' => $vendorId,
            ]
        );

        // Enforce professional limits for the new plan
        self::enforceMaxProfessionals($vendorId, (int) ($plan['max_professionals'] ?? 0));
    }

    public static function renewVendor(int $vendorId, int $planId): void
    {
        $vendor = VendorService::findById($vendorId);
        if (!$vendor) {
            throw new RuntimeException('Vendor não encontrado.');
        }

        $plan = Database::selectOne('SELECT * FROM plans WHERE id = :id LIMIT 1', ['id' => $planId]);
        if (!$plan) {
            throw new RuntimeException('Plano não encontrado.');
        }

        $durationDays = max(1, (int) ($plan['duration_days'] ?? 30));
        $today = date('Y-m-d');
        $currentExpires = !empty($vendor['plan_expires_at']) ? (string) $vendor['plan_expires_at'] : '';

        // Se ainda está ativo e com vencimento no futuro, renovamos a partir do vencimento (não perde dias).
        // Caso contrário, reinicia a partir de hoje.
        $base = $today;
        if (($vendor['status'] ?? '') === 'active' && $currentExpires !== '' && strtotime($currentExpires) > strtotime($today)) {
            $base = $currentExpires;
        }

        $start = $today;
        $expires = date('Y-m-d', strtotime($base . ' +' . $durationDays . ' days'));

        Database::statement(
            'UPDATE vendors
             SET status = \'active\',
                 plan_id = :plan_id,
                 plan_started_at = :start_date,
                 plan_expires_at = :expires_at,
                 updated_at = NOW()
             WHERE id = :id',
            [
                'plan_id' => $planId,
                'start_date' => $start,
                'expires_at' => $expires,
                'id' => $vendorId,
            ]
        );
    }

    public static function suspendVendor(int $vendorId): void
    {
        Database::statement(
            'UPDATE vendors SET status = \'suspended\', updated_at = NOW() WHERE id = :id',
            ['id' => $vendorId]
        );
    }

    public static function reactivateVendor(int $vendorId, ?int $planId = null): void
    {
        $vendor = VendorService::findById($vendorId);
        if (!$vendor) {
            throw new RuntimeException('Vendor não encontrado.');
        }

        $targetPlanId = $planId ?: (int) ($vendor['plan_id'] ?? 0);
        if ($targetPlanId <= 0) {
            throw new RuntimeException('Selecione um plano para reativar este vendor.');
        }

        self::activateVendor($vendorId, $targetPlanId);
    }

    /**
     * Process a Stripe checkout session completion.
     * Matches the vendor by email and activates/renews with the matching plan.
     */
    public static function processStripeCheckout(string $customerEmail, string $sessionId, ?string $paymentLinkUrl = null): bool
    {
        $user = Database::selectOne(
            'SELECT id FROM platform_users WHERE email = :email LIMIT 1',
            ['email' => $customerEmail]
        );

        if (!$user) {
            return false;
        }

        $vendor = Database::selectOne(
            'SELECT v.* FROM vendors v WHERE v.user_id = :user_id LIMIT 1',
            ['user_id' => $user['id']]
        );

        if (!$vendor) {
            return false;
        }

        // Find matching plan by stripe_checkout_url
        $plan = null;
        if ($paymentLinkUrl !== null && $paymentLinkUrl !== '') {
            $plan = Database::selectOne(
                'SELECT * FROM plans WHERE stripe_checkout_url = :url AND is_active = 1 LIMIT 1',
                ['url' => $paymentLinkUrl]
            );
        }

        // Fallback: use current vendor plan or first active plan
        if (!$plan && (int) ($vendor['plan_id'] ?? 0) > 0) {
            $plan = Database::selectOne(
                'SELECT * FROM plans WHERE id = :id LIMIT 1',
                ['id' => $vendor['plan_id']]
            );
        }

        if (!$plan) {
            $plan = Database::selectOne(
                'SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC LIMIT 1'
            );
        }

        if (!$plan) {
            return false;
        }

        // Record stripe payment info
        Database::statement(
            'UPDATE vendors SET stripe_session_id = :session_id, stripe_paid_at = NOW(), updated_at = NOW() WHERE id = :id',
            ['session_id' => $sessionId, 'id' => $vendor['id']]
        );

        // Activate or renew
        $vendorId = (int) $vendor['id'];
        $planId = (int) $plan['id'];

        if (($vendor['status'] ?? '') === 'active') {
            self::renewVendor($vendorId, $planId);
        } else {
            self::activateVendor($vendorId, $planId);
        }

        return true;
    }

    /**
     * Get payment history for admin overview.
     */
    public static function listPayments(): array
    {
        return Database::select(
            'SELECT v.id, v.business_name, v.stripe_session_id, v.stripe_paid_at,
                    v.status, v.plan_started_at, v.plan_expires_at,
                    p.name AS plan_name, p.price AS plan_price,
                    u.email, u.full_name
             FROM vendors v
             INNER JOIN platform_users u ON u.id = v.user_id
             LEFT JOIN plans p ON p.id = v.plan_id
             WHERE v.stripe_paid_at IS NOT NULL
             ORDER BY v.stripe_paid_at DESC'
        );
    }

    /**
     * Enforce max_professionals limit for a vendor.
     * When a plan is changed/downgraded, excess professionals are deactivated
     * (most recently created ones first) so the vendor stays within the plan limit.
     */
    private static function enforceMaxProfessionals(int $vendorId, int $maxProfessionals): void
    {
        if ($maxProfessionals <= 0) {
            // Plan doesn't support professionals: deactivate ALL
            Database::statement(
                'UPDATE professionals SET is_active = 0, updated_at = NOW() WHERE vendor_id = :vendor_id AND is_active = 1',
                ['vendor_id' => $vendorId]
            );
            return;
        }

        $activeCount = Database::selectOne(
            'SELECT COUNT(*) AS total FROM professionals WHERE vendor_id = :vendor_id AND is_active = 1',
            ['vendor_id' => $vendorId]
        );

        $total = (int) ($activeCount['total'] ?? 0);
        if ($total <= $maxProfessionals) {
            return; // Within limit
        }

        // Deactivate excess professionals (keep the oldest ones active)
        $excess = $total - $maxProfessionals;
        $toDeactivate = Database::select(
            'SELECT id FROM professionals WHERE vendor_id = :vendor_id AND is_active = 1 ORDER BY created_at DESC LIMIT ' . $excess,
            ['vendor_id' => $vendorId]
        );

        foreach ($toDeactivate as $prof) {
            Database::statement(
                'UPDATE professionals SET is_active = 0, updated_at = NOW() WHERE id = :id',
                ['id' => $prof['id']]
            );
        }
    }
}

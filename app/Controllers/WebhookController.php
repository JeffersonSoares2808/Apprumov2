<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AdminService;

final class WebhookController
{
    public function stripe(): void
    {
        $payload = file_get_contents('php://input');
        if ($payload === false || $payload === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Empty payload']);
            return;
        }

        $secret = app_config('app.stripe_webhook_secret', '');

        // Verify webhook signature if secret is configured
        if ($secret !== '') {
            $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
            if (!$this->verifyStripeSignature($payload, $sigHeader, $secret)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid signature']);
                return;
            }
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON']);
            return;
        }

        $type = $event['type'] ?? '';

        if ($type === 'checkout.session.completed') {
            $this->handleCheckoutCompleted($event['data']['object'] ?? []);
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
    }

    private function handleCheckoutCompleted(array $session): void
    {
        $customerEmail = $session['customer_email'] ?? $session['customer_details']['email'] ?? '';
        $sessionId = $session['id'] ?? '';
        $paymentLink = $session['payment_link'] ?? null;

        if ($customerEmail === '' || $sessionId === '') {
            return;
        }

        // Resolve the payment link URL to match against plans
        $paymentLinkUrl = null;
        if ($paymentLink !== null && $paymentLink !== '') {
            $paymentLinkUrl = 'https://buy.stripe.com/' . $paymentLink;
        }

        AdminService::processStripeCheckout($customerEmail, $sessionId, $paymentLinkUrl);
    }

    private function verifyStripeSignature(string $payload, string $sigHeader, string $secret): bool
    {
        if ($sigHeader === '') {
            return false;
        }

        $elements = explode(',', $sigHeader);
        $timestamp = null;
        $signatures = [];

        foreach ($elements as $element) {
            $parts = explode('=', trim($element), 2);
            if (count($parts) !== 2) {
                continue;
            }

            if ($parts[0] === 't') {
                $timestamp = $parts[1];
            } elseif ($parts[0] === 'v1') {
                $signatures[] = $parts[1];
            }
        }

        if ($timestamp === null || $signatures === []) {
            return false;
        }

        // Reject if timestamp is too old (5 minute tolerance)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return true;
            }
        }

        return false;
    }
}

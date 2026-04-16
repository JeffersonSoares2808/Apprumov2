<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Security\RateLimiter;
use App\Services\AiAssistantService;
use App\Services\VendorService;

/**
 * Public AI Chatbot Controller — serves the customer-facing AI assistant
 * on the vendor's public profile page (/p/{slug}).
 *
 * No authentication required; uses vendor slug to identify the business.
 * Only allows safe, public-facing actions (check availability, book appointment).
 */
final class PublicAiController extends Controller
{
    /**
     * Handle public AI chat requests (POST JSON).
     * Expects: { "message": "...", "history": [...] }
     * Returns: { "reply": "...", "action": null|{...} }
     */
    public function chat(Request $request, string $slug): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $vendor = VendorService::findBySlug($slug);
        if (!$vendor || VendorService::effectiveStatus($vendor) !== 'active') {
            http_response_code(404);
            echo json_encode(['error' => 'Estabelecimento não encontrado.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $vendorId = (int) $vendor['id'];

        // Read JSON body
        $body = json_decode(file_get_contents('php://input') ?: '{}', true);
        $message = trim((string) ($body['message'] ?? ''));
        $history = is_array($body['history'] ?? null) ? $body['history'] : [];

        if ($message === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Mensagem vazia.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Rate limit: max 15 messages per minute per IP (stricter for public)
        if (!RateLimiter::attempt('public-ai:' . $request->ip(), 15, 60)) {
            http_response_code(429);
            echo json_encode(['error' => 'Muitas mensagens. Aguarde um momento.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = AiAssistantService::publicChat($vendorId, $message, $history);

        echo json_encode([
            'reply' => $result['reply'],
            'action' => $result['action'],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Execute a confirmed public AI action (POST JSON).
     * Expects: { "action": {...} }
     * Returns: { "result": "..." }
     */
    public function executeAction(Request $request, string $slug): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $vendor = VendorService::findBySlug($slug);
        if (!$vendor || VendorService::effectiveStatus($vendor) !== 'active') {
            http_response_code(404);
            echo json_encode(['error' => 'Estabelecimento não encontrado.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Rate limit
        if (!RateLimiter::attempt('public-ai-exec:' . $request->ip(), 10, 60)) {
            http_response_code(429);
            echo json_encode(['error' => 'Muitas solicitações. Aguarde um momento.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $body = json_decode(file_get_contents('php://input') ?: '{}', true);
        $action = $body['action'] ?? null;

        if (!is_array($action) || empty($action['action'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = AiAssistantService::executePublicAction((int) $vendor['id'], $action);

        echo json_encode(['result' => $result], JSON_UNESCAPED_UNICODE);
    }
}

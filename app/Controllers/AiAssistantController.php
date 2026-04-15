<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Services\AiAssistantService;
use App\Services\AuthService;
use RuntimeException;

final class AiAssistantController extends Controller
{
    /**
     * Handle AI chat requests via AJAX (POST JSON).
     * Expects: { "message": "...", "history": [...] }
     * Returns: { "reply": "...", "action": null|{...} }
     */
    public function chat(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vendor = AuthService::requireActiveVendor();
        } catch (\Throwable $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado.'], JSON_UNESCAPED_UNICODE);
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

        // Rate limit: max 30 messages per minute per vendor
        $cacheKey = 'ai_rate_' . $vendorId;
        $ratePath = sys_get_temp_dir() . '/apprumo_ai_rate_' . $vendorId . '.json';
        $rateData = is_file($ratePath) ? (json_decode(file_get_contents($ratePath), true) ?: []) : [];
        $now = time();
        $rateData = array_filter($rateData, fn($ts) => ($now - $ts) < 60);
        if (count($rateData) >= 30) {
            http_response_code(429);
            echo json_encode(['error' => 'Muitas mensagens. Aguarde um momento.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $rateData[] = $now;
        file_put_contents($ratePath, json_encode($rateData));

        $result = AiAssistantService::chat($vendorId, $message, $history);

        // If an action was returned and user confirms, execute it
        $executeAction = (bool) ($body['execute_action'] ?? false);
        $actionResult = null;

        if ($executeAction && $result['action'] !== null) {
            $actionResult = AiAssistantService::executeAction($vendorId, $result['action']);
        }

        echo json_encode([
            'reply' => $result['reply'],
            'action' => $result['action'],
            'action_result' => $actionResult,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Execute a confirmed AI action (POST JSON).
     * Expects: { "action": {...} }
     * Returns: { "result": "..." }
     */
    public function executeAction(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $vendor = AuthService::requireActiveVendor();
        } catch (\Throwable $e) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autenticado.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $body = json_decode(file_get_contents('php://input') ?: '{}', true);
        $action = $body['action'] ?? null;

        if (!is_array($action) || empty($action['action'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Ação inválida.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $result = AiAssistantService::executeAction((int) $vendor['id'], $action);

        echo json_encode(['result' => $result], JSON_UNESCAPED_UNICODE);
    }
}

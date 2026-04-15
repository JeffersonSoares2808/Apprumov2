<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;

/**
 * AI Assistant Service — uses Groq free API (llama models) to provide
 * an intelligent assistant that knows the entire Apprumo system.
 *
 * The assistant can:
 * - Answer questions about how the platform works
 * - Help create services, products and professionals
 * - Give business insights based on real data
 * - Guide the user through any feature
 */
final class AiAssistantService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';
    private const MAX_TOKENS = 1024;

    /**
     * Build the system prompt with full platform knowledge + vendor context.
     */
    public static function systemPrompt(int $vendorId): string
    {
        $vendor = VendorService::findById($vendorId);
        $services = VendorService::services($vendorId);
        $professionals = ProfessionalService::listByVendor($vendorId);

        $productCount = Database::selectOne(
            'SELECT COUNT(*) AS total FROM products WHERE vendor_id = :vid',
            ['vid' => $vendorId]
        );

        $clientCount = Database::selectOne(
            'SELECT COUNT(*) AS total FROM clients WHERE vendor_id = :vid',
            ['vid' => $vendorId]
        );

        $todayAppointments = Database::selectOne(
            'SELECT COUNT(*) AS total FROM appointments WHERE vendor_id = :vid AND appointment_date = CURDATE() AND status IN (\'confirmed\', \'completed\')',
            ['vid' => $vendorId]
        );

        $monthRevenue = Database::selectOne(
            'SELECT COALESCE(SUM(price), 0) AS total FROM appointments WHERE vendor_id = :vid AND status = \'completed\' AND appointment_date BETWEEN DATE_FORMAT(CURDATE(), \'%Y-%m-01\') AND CURDATE()',
            ['vid' => $vendorId]
        );

        $serviceList = '';
        foreach ($services as $s) {
            $serviceList .= "  - {$s['title']}: R\$ " . number_format((float) $s['price'], 2, ',', '.') . " ({$s['duration_minutes']} min)" . ((int) $s['is_active'] ? '' : ' [INATIVO]') . "\n";
        }

        $profList = '';
        foreach ($professionals as $p) {
            $profList .= "  - {$p['name']} ({$p['email']})" . ((int) $p['is_active'] ? '' : ' [INATIVO]') . "\n";
        }

        $businessName = $vendor['business_name'] ?? 'Negócio';
        $category = $vendor['category'] ?? 'não definida';
        $slug = $vendor['slug'] ?? '';

        $serviceCount = count($services);
        $profCount = count($professionals);
        $monthRevenueFormatted = number_format((float) ($monthRevenue['total'] ?? 0), 2, ',', '.');

        return <<<PROMPT
Você é a **Assistente IA do Apprumo** — uma assistente inteligente integrada ao sistema de gestão Apprumo.
Responda SEMPRE em português do Brasil, de forma clara, amigável e objetiva. Use emojis com moderação.

## Sobre o Apprumo
O Apprumo é uma plataforma brasileira de gestão para autônomos e pequenos negócios (salões, barbearias, clínicas, estúdios, etc).
Funcionalidades principais:
- **Agenda**: Gestão de agendamentos com calendário, timeline do dia, fila de espera
- **Serviços**: Cadastro de serviços com preço, duração, imagem e retornos
- **Profissionais**: Equipe com escalas semanais ou datas específicas, comissão, vínculos com serviços
- **Produtos**: Estoque com controle de entrada/saída e vendas
- **Finanças**: Receitas, despesas, ticket médio, status de pagamentos
- **Clientes**: Base de clientes com recorrência e contato por WhatsApp
- **Relatórios**: Indicadores de desempenho, comparativos mensais
- **Perfil público**: Página de agendamento online em /p/{$slug}
- **Configurações**: Identidade visual, horários, notificações, WhatsApp API

## Navegação do Sistema (rotas)
- /vendor/dashboard — Dashboard principal com indicadores
- /vendor/agenda — Agenda de agendamentos do dia
- /vendor/services — Gestão de serviços
- /vendor/products — Gestão de produtos e estoque
- /vendor/finance — Painel financeiro
- /vendor/reports — Relatórios e indicadores
- /vendor/clients — Base de clientes
- /vendor/professionals — Gestão da equipe
- /vendor/advanced-agenda — Agenda avançada por profissional
- /vendor/settings — Configurações do negócio
- /vendor/menu — Hub de áreas extras

## Como criar via IA
Quando o usuário pedir para CRIAR algo, responda com um JSON de ação no formato:
```json
{"action": "create_service", "data": {"title": "...", "price": 0, "duration_minutes": 30, "description": "..."}}
```
ou
```json
{"action": "create_product", "data": {"name": "...", "sale_price": 0, "cost_price": 0, "stock_quantity": 0, "description": "..."}}
```

Ações disponíveis: create_service, create_product
Inclua SEMPRE o bloco JSON quando o usuário pedir para criar algo.
Se o usuário não fornecer todos os dados, pergunte antes de gerar o JSON.

## Dados atuais do negócio "{$businessName}"
- Categoria: {$category}
- Link público: /p/{$slug}
- Serviços cadastrados ({$serviceCount}):
{$serviceList}
- Profissionais ({$profCount}):
{$profList}
- Produtos: {$productCount['total']} cadastrados
- Clientes: {$clientCount['total']} na base
- Agendamentos hoje: {$todayAppointments['total']}
- Receita do mês: R\$ {$monthRevenueFormatted}

## Regras
1. Responda apenas sobre o Apprumo e gestão do negócio. Recuse educadamente assuntos fora do escopo.
2. Seja sempre construtivo e proativo — sugira melhorias quando apropriado.
3. Se não souber algo, diga honestamente e sugira onde encontrar a informação no sistema.
4. Mantenha respostas curtas (max 3 parágrafos) exceto quando o usuário pedir detalhes.
5. Use listas e formatação para facilitar a leitura.
PROMPT;
    }

    /**
     * Send a chat message to the AI and get a response.
     *
     * @param int    $vendorId
     * @param string $userMessage
     * @param array  $history  Previous messages [{role, content}, ...]
     * @return array{reply: string, action: ?array}
     */
    public static function chat(int $vendorId, string $userMessage, array $history = []): array
    {
        $apiKey = App::config('app.groq_api_key', '');

        if ($apiKey === '') {
            return self::fallbackResponse($userMessage, $vendorId);
        }

        $systemPrompt = self::systemPrompt($vendorId);

        // Build messages array
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        // Add conversation history (last 10 messages to stay within context)
        $recentHistory = array_slice($history, -10);
        foreach ($recentHistory as $msg) {
            if (isset($msg['role'], $msg['content'])) {
                $messages[] = [
                    'role' => $msg['role'] === 'user' ? 'user' : 'assistant',
                    'content' => (string) $msg['content'],
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        $payload = json_encode([
            'model' => self::MODEL,
            'messages' => $messages,
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => 0.7,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init(self::GROQ_API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            error_log('Groq API error: HTTP ' . $httpCode . ' — ' . ($curlError ?: $response));
            return self::fallbackResponse($userMessage, $vendorId);
        }

        $decoded = json_decode($response, true);
        $reply = $decoded['choices'][0]['message']['content'] ?? '';

        if ($reply === '') {
            return self::fallbackResponse($userMessage, $vendorId);
        }

        // Try to extract action JSON from response
        $action = null;
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $reply, $matches)) {
            $action = json_decode($matches[1], true);
            if (!isset($action['action'])) {
                $action = null;
            }
        }

        return ['reply' => $reply, 'action' => $action];
    }

    /**
     * Execute an AI-generated action (create service, product, etc).
     *
     * @return string Success/error message
     */
    public static function executeAction(int $vendorId, array $action): string
    {
        $type = $action['action'] ?? '';
        $data = $action['data'] ?? [];

        switch ($type) {
            case 'create_service':
                return self::executeCreateService($vendorId, $data);
            case 'create_product':
                return self::executeCreateProduct($vendorId, $data);
            default:
                return 'Ação não reconhecida: ' . e($type);
        }
    }

    private static function executeCreateService(int $vendorId, array $data): string
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            return '❌ O serviço precisa ter um título.';
        }

        $serviceData = [
            'title' => $title,
            'price' => (float) ($data['price'] ?? 0),
            'duration_minutes' => (int) ($data['duration_minutes'] ?? 30),
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active' => true,
        ];

        try {
            ServiceCatalogService::save($vendorId, $serviceData);
            return '✅ Serviço "' . e($title) . '" criado com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao criar serviço: ' . e($ex->getMessage());
        }
    }

    private static function executeCreateProduct(int $vendorId, array $data): string
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return '❌ O produto precisa ter um nome.';
        }

        try {
            ProductService::save($vendorId, [
                'name' => $name,
                'sale_price' => (float) ($data['sale_price'] ?? 0),
                'cost_price' => (float) ($data['cost_price'] ?? 0),
                'stock_quantity' => (int) ($data['stock_quantity'] ?? 0),
                'description' => trim((string) ($data['description'] ?? '')),
                'is_active' => true,
            ]);
            return '✅ Produto "' . e($name) . '" criado com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao criar produto: ' . e($ex->getMessage());
        }
    }

    /**
     * Provide intelligent offline responses when Groq API is unavailable.
     */
    private static function fallbackResponse(string $message, int $vendorId): array
    {
        $lower = mb_strtolower(trim($message), 'UTF-8');

        if (str_contains($lower, 'serviço') || str_contains($lower, 'servico')) {
            $services = VendorService::services($vendorId);
            if (empty($services)) {
                return ['reply' => '📋 Você ainda não tem serviços cadastrados. Vá em **Serviços** no menu para criar o primeiro!', 'action' => null];
            }
            $list = implode("\n", array_map(fn($s) => "• {$s['title']} — R\$ " . number_format((float) $s['price'], 2, ',', '.'), $services));
            return ['reply' => "📋 Seus serviços:\n{$list}\n\nPara gerenciar, acesse a seção **Serviços** no menu.", 'action' => null];
        }

        if (str_contains($lower, 'agenda') || str_contains($lower, 'agendamento')) {
            $today = Database::selectOne(
                'SELECT COUNT(*) AS total FROM appointments WHERE vendor_id = :vid AND appointment_date = CURDATE() AND status = \'confirmed\'',
                ['vid' => $vendorId]
            );
            return ['reply' => "📅 Você tem **{$today['total']}** agendamento(s) confirmado(s) para hoje. Acesse a **Agenda** para ver os detalhes.", 'action' => null];
        }

        if (str_contains($lower, 'financ') || str_contains($lower, 'receita') || str_contains($lower, 'dinheiro')) {
            return ['reply' => '💰 Para ver suas finanças, acesse **Finanças** no menu principal. Lá você encontra receitas, despesas e indicadores do mês.', 'action' => null];
        }

        if (str_contains($lower, 'ajuda') || str_contains($lower, 'help') || str_contains($lower, 'como')) {
            return ['reply' => "🤖 Posso ajudar com:\n• **Criar serviços** — diga \"crie um serviço de corte por R\$50\"\n• **Ver agenda** — \"quantos agendamentos tenho hoje?\"\n• **Relatórios** — \"como está meu faturamento?\"\n• **Dúvidas** — \"como funciona o agendamento online?\"\n\nÉ só perguntar!", 'action' => null];
        }

        return [
            'reply' => "🤖 Olá! Sou a assistente IA do Apprumo. Para respostas mais completas, configure sua chave da API Groq nas variáveis de ambiente (GROQ_API_KEY). Enquanto isso, posso ajudar com informações básicas!\n\nDigite **ajuda** para ver o que posso fazer.",
            'action' => null,
        ];
    }
}

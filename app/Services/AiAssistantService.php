<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Database;

/**
 * AI Assistant Service — uses Groq free API (llama models) to provide
 * an intelligent assistant that knows the entire Apprumo system.
 *
 * The assistant acts as a **full virtual employee** and can:
 * - Answer questions about how the platform works
 * - Create / update / toggle / delete services
 * - Create / update / delete products and register sales
 * - Create / update / delete professionals & manage availability
 * - Book appointments, update status, cancel, delete
 * - Check available time-slots for any date/service
 * - Manage the waiting list
 * - Look up clients and their history
 * - Pull financial summaries and business reports
 * - Update vendor business hours
 * - Check returns/credits
 * - Give business insights based on real data
 * All destructive/mutating actions require explicit user confirmation.
 */
final class AiAssistantService
{
    private const MAX_TOKENS = 3000;

    /**
     * AI provider definitions (order = priority for fallback).
     * Each entry: [config_key, api_url, model, display_name]
     */
    private const PROVIDERS = [
        ['app.groq_api_key',      'https://api.groq.com/openai/v1/chat/completions',        'llama-3.3-70b-versatile',              'Groq'],
        ['app.gemini_api_key',    'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions', 'gemini-2.0-flash',    'Gemini'],
        ['app.cerebras_api_key',  'https://api.cerebras.ai/v1/chat/completions',             'llama-3.3-70b',                        'Cerebras'],
        ['app.together_api_key',  'https://api.together.xyz/v1/chat/completions',            'meta-llama/Llama-3.3-70B-Instruct-Turbo', 'Together'],
        ['app.openrouter_api_key','https://openrouter.ai/api/v1/chat/completions',           'meta-llama/llama-3.3-70b-instruct:free','OpenRouter'],
    ];

    /**
     * Return the ordered list of configured providers (those with a non-empty API key).
     *
     * @return list<array{key: string, url: string, model: string, name: string}>
     */
    private static function configuredProviders(): array
    {
        $providers = [];
        foreach (self::PROVIDERS as [$configKey, $url, $model, $name]) {
            $apiKey = App::config($configKey, '');
            if ($apiKey !== '' && $apiKey !== false) {
                $providers[] = [
                    'key'   => (string) $apiKey,
                    'url'   => $url,
                    'model' => $model,
                    'name'  => $name,
                ];
            }
        }
        return $providers;
    }

    /**
     * Build the system prompt with full platform knowledge + vendor context.
     */
    public static function systemPrompt(int $vendorId): string
    {
        $vendor = VendorService::findById($vendorId);
        $services = VendorService::services($vendorId);
        $professionals = ProfessionalService::listByVendor($vendorId);
        $products = VendorService::products($vendorId);

        $clientCount = Database::selectOne(
            'SELECT COUNT(*) AS total FROM clients WHERE vendor_id = :vid',
            ['vid' => $vendorId]
        );

        $todayAppointments = Database::select(
            'SELECT a.id, a.customer_name, a.customer_phone, a.appointment_date, a.start_time, a.end_time, a.status, a.price, s.title AS service_title, p.name AS professional_name
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             LEFT JOIN professionals p ON p.id = a.professional_id
             WHERE a.vendor_id = :vid AND a.appointment_date = CURDATE() AND a.status IN (\'confirmed\', \'completed\')
             ORDER BY a.start_time ASC',
            ['vid' => $vendorId]
        );

        // Upcoming appointments (next 7 days)
        $upcomingAppointments = Database::select(
            'SELECT a.id, a.customer_name, a.customer_phone, a.appointment_date, a.start_time, a.end_time, a.status, a.price, s.title AS service_title, p.name AS professional_name
             FROM appointments a
             LEFT JOIN services s ON s.id = a.service_id
             LEFT JOIN professionals p ON p.id = a.professional_id
             WHERE a.vendor_id = :vid AND a.appointment_date > CURDATE() AND a.appointment_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND a.status = \'confirmed\'
             ORDER BY a.appointment_date ASC, a.start_time ASC
             LIMIT 20',
            ['vid' => $vendorId]
        );

        $monthRevenue = Database::selectOne(
            'SELECT COALESCE(SUM(price), 0) AS total FROM appointments WHERE vendor_id = :vid AND status = \'completed\' AND appointment_date BETWEEN DATE_FORMAT(CURDATE(), \'%Y-%m-01\') AND CURDATE()',
            ['vid' => $vendorId]
        );

        // Financial summary
        $financeData = Database::selectOne(
            'SELECT
                COALESCE(SUM(CASE WHEN status = \'completed\' AND appointment_date BETWEEN DATE_FORMAT(CURDATE(), \'%Y-%m-01\') AND CURDATE() THEN price ELSE 0 END), 0) AS month_revenue,
                COALESCE(SUM(CASE WHEN status IN (\'cancelled\', \'no_show\') AND appointment_date BETWEEN DATE_FORMAT(CURDATE(), \'%Y-%m-01\') AND CURDATE() THEN price ELSE 0 END), 0) AS month_losses,
                COALESCE(SUM(CASE WHEN status = \'confirmed\' AND appointment_date BETWEEN DATE_FORMAT(CURDATE(), \'%Y-%m-01\') AND CURDATE() THEN price ELSE 0 END), 0) AS month_pending
             FROM appointments WHERE vendor_id = :vid',
            ['vid' => $vendorId]
        );

        // Product sales this month
        $productSales = Database::selectOne(
            'SELECT COALESCE(SUM(total_amount), 0) AS total FROM product_sales WHERE vendor_id = :vid AND sold_at BETWEEN DATE_FORMAT(CURDATE(), \'%Y-%m-01 00:00:00\') AND NOW()',
            ['vid' => $vendorId]
        );

        // Low stock products
        $lowStockProducts = Database::select(
            'SELECT id, name, stock_quantity, min_stock_quantity FROM products WHERE vendor_id = :vid AND stock_quantity <= min_stock_quantity AND is_active = 1',
            ['vid' => $vendorId]
        );

        // Top clients
        $topClients = Database::select(
            'SELECT c.id, c.name, c.phone, c.email, COUNT(a.id) AS visits, COALESCE(SUM(a.price), 0) AS total_spent, MAX(a.appointment_date) AS last_visit
             FROM clients c
             LEFT JOIN appointments a ON a.client_id = c.id AND a.status = \'completed\'
             WHERE c.vendor_id = :vid
             GROUP BY c.id, c.name, c.phone, c.email
             ORDER BY visits DESC
             LIMIT 10',
            ['vid' => $vendorId]
        );

        // Active returns
        $activeReturns = Database::select(
            'SELECT r.id, r.customer_name, r.customer_phone, s.title AS service_title, r.expires_at, r.status
             FROM service_returns r
             LEFT JOIN services s ON s.id = r.service_id
             WHERE r.vendor_id = :vid AND r.status = \'available\' AND r.expires_at >= CURDATE()
             ORDER BY r.expires_at ASC
             LIMIT 10',
            ['vid' => $vendorId]
        );

        // Vendor working hours
        $weeklyHours = VendorService::weeklyHours($vendorId);

        // Build text sections
        $serviceList = '';
        foreach ($services as $s) {
            $serviceList .= "  - ID {$s['id']}: {$s['title']} — R\$ " . number_format((float) $s['price'], 2, ',', '.') . " ({$s['duration_minutes']} min)" . ((int) $s['is_active'] ? '' : ' [INATIVO]') . "\n";
        }

        $profList = '';
        foreach ($professionals as $p) {
            $profList .= "  - ID {$p['id']}: {$p['name']} ({$p['email']})" . ((int) $p['is_active'] ? '' : ' [INATIVO]') . "\n";
        }

        $productList = '';
        foreach ($products as $pr) {
            $productList .= "  - ID {$pr['id']}: {$pr['name']} — R\$ " . number_format((float) $pr['sale_price'], 2, ',', '.') . " (estoque: {$pr['stock_quantity']})" . ((int) $pr['is_active'] ? '' : ' [INATIVO]') . "\n";
        }

        $todayAppointmentList = '';
        foreach ($todayAppointments as $apt) {
            $time = substr($apt['start_time'], 0, 5) . '-' . substr($apt['end_time'], 0, 5);
            $todayAppointmentList .= "  - ID {$apt['id']}: {$apt['customer_name']} ({$apt['customer_phone']}) | {$apt['service_title']} | {$time} | status: {$apt['status']}" . ($apt['professional_name'] ? " | prof: {$apt['professional_name']}" : '') . " | R\$ " . number_format((float) $apt['price'], 2, ',', '.') . "\n";
        }

        $upcomingList = '';
        foreach ($upcomingAppointments as $apt) {
            $date = date('d/m', strtotime($apt['appointment_date']));
            $time = substr($apt['start_time'], 0, 5);
            $upcomingList .= "  - ID {$apt['id']}: {$date} {$time} — {$apt['customer_name']} | {$apt['service_title']}" . ($apt['professional_name'] ? " | prof: {$apt['professional_name']}" : '') . "\n";
        }

        $lowStockList = '';
        foreach ($lowStockProducts as $ls) {
            $lowStockList .= "  - ⚠️ ID {$ls['id']}: {$ls['name']} — estoque: {$ls['stock_quantity']} (mín: {$ls['min_stock_quantity']})\n";
        }

        $clientList = '';
        foreach ($topClients as $c) {
            $lastVisit = $c['last_visit'] ? date('d/m/Y', strtotime($c['last_visit'])) : 'nunca';
            $clientList .= "  - ID {$c['id']}: {$c['name']} ({$c['phone']}) | {$c['visits']} visitas | R\$ " . number_format((float) $c['total_spent'], 2, ',', '.') . " | última: {$lastVisit}\n";
        }

        $returnsList = '';
        foreach ($activeReturns as $r) {
            $expires = $r['expires_at'] ? date('d/m/Y', strtotime($r['expires_at'])) : 'sem prazo';
            $returnsList .= "  - ID {$r['id']}: {$r['customer_name']} ({$r['customer_phone']}) | {$r['service_title']} | expira: {$expires}\n";
        }

        $hoursText = '';
        $dayNames = ['0' => 'Dom', '1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb'];
        foreach ($weeklyHours as $wh) {
            $day = $dayNames[(string) ($wh['weekday'] ?? '')] ?? (string) ($wh['weekday'] ?? '');
            if ((int) ($wh['is_enabled'] ?? 0)) {
                $hoursText .= "  - {$day}: " . substr((string) ($wh['start_time'] ?? '08:00'), 0, 5) . ' às ' . substr((string) ($wh['end_time'] ?? '18:00'), 0, 5) . "\n";
            } else {
                $hoursText .= "  - {$day}: FECHADO\n";
            }
        }

        $businessName = $vendor['business_name'] ?? 'Negócio';
        $category = $vendor['category'] ?? 'não definida';
        $slug = $vendor['slug'] ?? '';

        $serviceCount = count($services);
        $profCount = count($professionals);
        $productCount = count($products);
        $monthRevenueFormatted = number_format((float) ($financeData['month_revenue'] ?? 0), 2, ',', '.');
        $monthLossesFormatted = number_format((float) ($financeData['month_losses'] ?? 0), 2, ',', '.');
        $monthPendingFormatted = number_format((float) ($financeData['month_pending'] ?? 0), 2, ',', '.');
        $productSalesFormatted = number_format((float) ($productSales['total'] ?? 0), 2, ',', '.');
        $todayDate = date('Y-m-d');
        $todayName = self::dayNamePtBr(date('N'));
        $todayAppointmentCount = count($todayAppointments);
        $upcomingCount = count($upcomingAppointments);
        $clientTotal = (int) ($clientCount['total'] ?? 0);
        $lowStockCount = count($lowStockProducts);
        $activeReturnCount = count($activeReturns);

        return <<<PROMPT
Você é a **Lia**, assistente virtual do Apprumo — fale de forma natural, como uma colega de trabalho simpática e prestativa.
NUNCA se apresente como IA ou robô. Você é a Lia, parte da equipe.
Responda SEMPRE em português do Brasil, de forma descontraída, clara e direta.
Use emojis com moderação (máximo 2 por resposta). Não repita saudações a cada mensagem.

## Personalidade
- Fale como uma pessoa real: use expressões naturais ("beleza", "pode deixar", "pronto!", "bora lá")
- Seja breve — vá direto ao ponto, sem enrolação
- Só cumprimente na primeira mensagem da conversa
- Nunca repita informações que já foram ditas na conversa
- Evite respostas robóticas e formatadas demais — prefira texto corrido quando possível
- Use listas apenas quando realmente ajudar (3+ itens)
- Quando o usuário confirmar algo, execute imediatamente sem perguntar novamente

## Sobre o Apprumo
Plataforma brasileira de gestão para autônomos e pequenos negócios (salões, barbearias, clínicas, estúdios, etc).
Funcionalidades: Agenda, Serviços, Profissionais, Produtos, Finanças, Clientes, Relatórios, Perfil público (/p/{$slug}), Configurações.

## NAVEGAÇÃO — MUDAR DE TELA
Quando o usuário pedir para ir a outra página, mudar de tela ou acessar uma seção, responda com a ação navigate:
```json
{"action": "navigate", "data": {"url": "/vendor/ROTA", "label": "Nome da tela"}}
```
Rotas disponíveis:
- /vendor/dashboard — Painel principal
- /vendor/agenda — Agenda
- /vendor/advanced-agenda — Agenda avançada
- /vendor/services — Serviços
- /vendor/products — Produtos
- /vendor/finance — Finanças
- /vendor/reports — Relatórios
- /vendor/reports/professionals — Relatório de profissionais
- /vendor/clients — Clientes
- /vendor/professionals — Profissionais
- /vendor/settings — Configurações
- /vendor/menu — Cardápio/Menu

Exemplos de pedidos de navegação: "vai pra agenda", "abre os serviços", "me leva pro financeiro", "quero ver os relatórios", "vai pra configurações"

## AÇÕES DISPONÍVEIS (SUPER PODERES)
Quando o usuário pedir para EXECUTAR qualquer ação, responda com um JSON de ação entre blocos ```json```.
**REGRA DE OURO**: Para ações que MODIFICAM dados, confirme antes de gerar o JSON. Para consultas, gere direto.

### 1. Criar serviço
```json
{"action": "create_service", "data": {"title": "...", "price": 0, "duration_minutes": 30, "description": "..."}}
```

### 2. Atualizar serviço
```json
{"action": "update_service", "data": {"service_id": 1, "title": "...", "price": 0, "duration_minutes": 30, "description": "..."}}
```

### 3. Ativar/desativar serviço
```json
{"action": "toggle_service", "data": {"service_id": 1}}
```

### 4. Excluir serviço
```json
{"action": "delete_service", "data": {"service_id": 1}}
```

### 5. Criar produto
```json
{"action": "create_product", "data": {"name": "...", "sale_price": 0, "cost_price": 0, "stock_quantity": 0, "description": "..."}}
```

### 6. Atualizar produto
```json
{"action": "update_product", "data": {"product_id": 1, "name": "...", "sale_price": 0, "cost_price": 0, "stock_quantity": 0, "description": "..."}}
```

### 7. Excluir produto
```json
{"action": "delete_product", "data": {"product_id": 1}}
```

### 8. Registrar venda de produto (baixa no estoque)
```json
{"action": "sell_product", "data": {"product_id": 1, "quantity": 1, "customer_name": "Maria", "unit_price": null}}
```

### 9. Agendar atendimento
Obrigatórios: service_id, appointment_date (YYYY-MM-DD), start_time (HH:MM), customer_name, customer_phone
Opcionais: professional_id, price, notes, customer_email
```json
{"action": "create_appointment", "data": {"service_id": 1, "appointment_date": "2025-01-15", "start_time": "14:00", "customer_name": "João Silva", "customer_phone": "11999998888", "professional_id": null, "notes": ""}}
```

### 10. Atualizar status de agendamento
Statuses: confirmed, completed, cancelled, no_show
```json
{"action": "update_appointment_status", "data": {"appointment_id": 123, "status": "completed"}}
```

### 11. Excluir agendamento
```json
{"action": "delete_appointment", "data": {"appointment_id": 123}}
```

### 12. Fila de espera — adicionar
```json
{"action": "create_waiting_entry", "data": {"customer_name": "...", "customer_phone": "...", "desired_date": "2025-01-15", "service_id": null, "notes": ""}}
```

### 13. Fila de espera — remover
```json
{"action": "delete_waiting_entry", "data": {"entry_id": 1}}
```

### 14. Criar profissional
```json
{"action": "create_professional", "data": {"name": "...", "email": "...", "phone": "...", "commission_rate": 0, "color": "#3B82F6"}}
```

### 15. Atualizar profissional
```json
{"action": "update_professional", "data": {"professional_id": 1, "name": "...", "email": "...", "phone": "...", "commission_rate": 0}}
```

### 16. Ativar/desativar profissional
```json
{"action": "toggle_professional", "data": {"professional_id": 1}}
```

### 17. Excluir profissional
```json
{"action": "delete_professional", "data": {"professional_id": 1}}
```

### 18. Vincular serviços a profissional
```json
{"action": "link_services_to_professional", "data": {"professional_id": 1, "service_ids": [1, 2, 3]}}
```

### 19. Consultar horários disponíveis (gere direto, sem pedir confirmação)
```json
{"action": "check_available_slots", "data": {"service_id": 1, "date": "2025-01-15", "professional_id": null}}
```

### 20. Consultar agendamentos de uma data (gere direto)
```json
{"action": "list_appointments_for_date", "data": {"date": "2025-01-15"}}
```

### 21. Buscar cliente (gere direto)
```json
{"action": "search_clients", "data": {"query": "João"}}
```

### 22. Relatório financeiro do mês (gere direto)
```json
{"action": "get_finance_report", "data": {"month": "2025-01"}}
```

### 23. Relatório de desempenho (gere direto)
```json
{"action": "get_performance_report", "data": {"start_date": "2025-01-01", "end_date": "2025-01-31"}}
```

### 24. Consultar retornos de cliente (gere direto)
```json
{"action": "check_client_returns", "data": {"phone": "11999998888"}}
```

### 25. Atualizar horário de funcionamento
```json
{"action": "update_business_hours", "data": {"hours": [{"day_of_week": 1, "is_open": true, "open_time": "08:00", "close_time": "18:00"}, {"day_of_week": 0, "is_open": false, "open_time": null, "close_time": null}]}}
```

### 26. Enviar mensagem em massa para clientes
Filtros possíveis: "all" (todos), "active" (com agendamento nos últimos 60 dias), "inactive" (sem agendamento há 60+ dias), "today" (agendados para hoje)
Placeholders disponíveis na mensagem: {nome} (nome do cliente), {negocio} (nome do negócio)
```json
{"action": "send_mass_message", "data": {"message": "Olá {nome}! Promoção especial no {negocio}!", "filter": "all", "channel": "sms"}}
```
channel pode ser: "sms", "email" ou "both"

## Dados atuais do negócio "{$businessName}"
- Categoria: {$category}
- Link público: /p/{$slug}
- Data de hoje: {$todayDate} ({$todayName})

### Horário de funcionamento:
{$hoursText}
### Serviços cadastrados ({$serviceCount}):
{$serviceList}
### Profissionais ({$profCount}):
{$profList}
### Produtos ({$productCount}):
{$productList}
### Produtos com estoque baixo ({$lowStockCount}):
{$lowStockList}
### Agendamentos de hoje ({$todayAppointmentCount}):
{$todayAppointmentList}
### Próximos agendamentos — 7 dias ({$upcomingCount}):
{$upcomingList}
### Top clientes ({$clientTotal} total na base):
{$clientList}
### Retornos ativos ({$activeReturnCount}):
{$returnsList}
### Resumo financeiro do mês:
- Receita (serviços concluídos): R\$ {$monthRevenueFormatted}
- Vendas de produtos: R\$ {$productSalesFormatted}
- Perdas (cancelamentos/faltas): R\$ {$monthLossesFormatted}
- Pendente (confirmados): R\$ {$monthPendingFormatted}

## Regras
1. Responda apenas sobre o Apprumo e gestão do negócio. Recuse educadamente assuntos fora do escopo.
2. Seja proativa — sugira melhorias, alerte sobre estoque baixo, clientes que não voltam, etc.
3. Respostas curtas e diretas (max 2 parágrafos). Sem enrolação.
4. Para ações que MODIFICAM dados: descreva brevemente e gere o JSON para confirmação.
5. Para CONSULTAS (check_available_slots, list_appointments_for_date, search_clients, get_finance_report, get_performance_report, check_client_returns): gere o JSON direto SEM pedir confirmação.
6. Para NAVEGAÇÃO: gere o JSON navigate direto quando o usuário pedir para ir a outra tela.
7. Use os IDs reais dos serviços, produtos e profissionais.
8. Ao agendar, sugira horários disponíveis primeiro.
9. Quando o vendedor pedir algo vago, pergunte o que ele quer especificamente.
10. Ofereça análises inteligentes quando relevante.
11. NUNCA gere dois JSONs na mesma resposta.
12. NUNCA repita a mesma informação que acabou de ser dita.

## Formatação
- Use **negrito** para informações-chave.
- Use listas com • apenas para 3+ itens.
- Prefira texto corrido para respostas simples.
PROMPT;
    }

    /**
     * Send a chat message to the AI and get a response.
     * Tries each configured provider in order until one succeeds (multi-provider fallback).
     *
     * @param int    $vendorId
     * @param string $userMessage
     * @param array  $history  Previous messages [{role, content}, ...]
     * @return array{reply: string, action: ?array}
     */
    public static function chat(int $vendorId, string $userMessage, array $history = []): array
    {
        $providers = self::configuredProviders();

        if ($providers === []) {
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

        // Try each provider in order until one succeeds
        foreach ($providers as $provider) {
            $result = self::callProvider($provider, $messages);
            if ($result !== null) {
                return $result;
            }
        }

        // All providers failed — use offline fallback
        return self::fallbackResponse($userMessage, $vendorId);
    }

    /**
     * Call a single AI provider and return the parsed result, or null on failure.
     *
     * @param array{key: string, url: string, model: string, name: string} $provider
     * @param array $messages
     * @return array{reply: string, action: ?array}|null
     */
    private static function callProvider(array $provider, array $messages): ?array
    {
        $payload = json_encode([
            'model'      => $provider['model'],
            'messages'   => $messages,
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => 0.4,
        ], JSON_UNESCAPED_UNICODE);

        // Retry logic: try up to 2 times on failure
        $response = false;
        $httpCode = 0;
        $curlError = '';

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $ch = curl_init($provider['url']);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $provider['key'],
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response !== false && $httpCode === 200) {
                break;
            }

            // Wait before retry
            if ($attempt === 0) {
                usleep(500000); // 500ms
            }
        }

        if ($response === false || $httpCode !== 200) {
            error_log('AI provider ' . $provider['name'] . ' error: HTTP ' . $httpCode . ' — ' . ($curlError ?: substr((string) $response, 0, 300)));
            return null; // Signal caller to try next provider
        }

        $decoded = json_decode($response, true);
        $reply = $decoded['choices'][0]['message']['content'] ?? '';

        if ($reply === '') {
            error_log('AI provider ' . $provider['name'] . ': empty reply');
            return null;
        }

        // Try to extract action JSON from response
        $action = null;
        if (preg_match('/```json\s*(\{(?:[^{}]|(?:\{[^{}]*\}))*\})\s*```/s', $reply, $matches)) {
            $action = json_decode($matches[1], true);
            if (!is_array($action) || !isset($action['action'])) {
                $action = null;
            }
        }

        return ['reply' => $reply, 'action' => $action];
    }

    /**
     * Execute an AI-generated action (create service, product, appointment, sell, etc).
     * All actions must have been confirmed by the user via the UI before reaching here.
     *
     * @return string Success/error message
     */
    public static function executeAction(int $vendorId, array $action): string
    {
        $type = $action['action'] ?? '';
        $data = $action['data'] ?? [];

        return match ($type) {
            // ─── Services ───
            'create_service' => self::executeCreateService($vendorId, $data),
            'update_service' => self::executeUpdateService($vendorId, $data),
            'toggle_service' => self::executeToggleService($vendorId, $data),
            'delete_service' => self::executeDeleteService($vendorId, $data),
            // ─── Products ───
            'create_product' => self::executeCreateProduct($vendorId, $data),
            'update_product' => self::executeUpdateProduct($vendorId, $data),
            'delete_product' => self::executeDeleteProduct($vendorId, $data),
            'sell_product' => self::executeSellProduct($vendorId, $data),
            // ─── Appointments ───
            'create_appointment' => self::executeCreateAppointment($vendorId, $data),
            'update_appointment_status' => self::executeUpdateAppointmentStatus($vendorId, $data),
            'delete_appointment' => self::executeDeleteAppointment($vendorId, $data),
            // ─── Waiting list ───
            'create_waiting_entry' => self::executeCreateWaitingEntry($vendorId, $data),
            'delete_waiting_entry' => self::executeDeleteWaitingEntry($vendorId, $data),
            // ─── Professionals ───
            'create_professional' => self::executeCreateProfessional($vendorId, $data),
            'update_professional' => self::executeUpdateProfessional($vendorId, $data),
            'toggle_professional' => self::executeToggleProfessional($vendorId, $data),
            'delete_professional' => self::executeDeleteProfessional($vendorId, $data),
            'link_services_to_professional' => self::executeLinkServicesToProfessional($vendorId, $data),
            // ─── Queries / Reports (read-only) ───
            'check_available_slots' => self::executeCheckAvailableSlots($vendorId, $data),
            'list_appointments_for_date' => self::executeListAppointmentsForDate($vendorId, $data),
            'search_clients' => self::executeSearchClients($vendorId, $data),
            'get_finance_report' => self::executeGetFinanceReport($vendorId, $data),
            'get_performance_report' => self::executeGetPerformanceReport($vendorId, $data),
            'check_client_returns' => self::executeCheckClientReturns($vendorId, $data),
            // ─── Settings ───
            'update_business_hours' => self::executeUpdateBusinessHours($vendorId, $data),
            // ─── Navigation ───
            'navigate' => self::executeNavigate($data),
            // ─── Mass messaging ───
            'send_mass_message' => self::executeSendMassMessage($vendorId, $data),
            default => '❌ Ação não reconhecida: ' . e($type),
        };
    }

    // ════════════════════════════════════════════════════════════════
    //  SERVICE actions
    // ════════════════════════════════════════════════════════════════

    private static function executeCreateService(int $vendorId, array $data): string
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            return '❌ O serviço precisa ter um título.';
        }

        try {
            ServiceCatalogService::save($vendorId, [
                'title' => $title,
                'price' => (float) ($data['price'] ?? 0),
                'duration_minutes' => (int) ($data['duration_minutes'] ?? 30),
                'description' => trim((string) ($data['description'] ?? '')),
                'is_active' => true,
            ]);
            return '✅ Serviço "' . e($title) . '" criado com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao criar serviço: ' . e($ex->getMessage());
        }
    }

    private static function executeUpdateService(int $vendorId, array $data): string
    {
        $serviceId = (int) ($data['service_id'] ?? 0);
        if ($serviceId === 0) {
            return '❌ Informe o ID do serviço para atualizar.';
        }

        $existing = ServiceCatalogService::find($vendorId, $serviceId);
        if (!$existing) {
            return '❌ Serviço ID ' . $serviceId . ' não encontrado.';
        }

        try {
            $updateData = ['id' => $serviceId];
            if (isset($data['title'])) {
                $updateData['title'] = trim((string) $data['title']);
            }
            if (isset($data['price'])) {
                $updateData['price'] = (float) $data['price'];
            }
            if (isset($data['duration_minutes'])) {
                $updateData['duration_minutes'] = (int) $data['duration_minutes'];
            }
            if (isset($data['description'])) {
                $updateData['description'] = trim((string) $data['description']);
            }

            ServiceCatalogService::save($vendorId, $updateData + $existing);
            return '✅ Serviço "' . e($updateData['title'] ?? $existing['title']) . '" atualizado com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao atualizar serviço: ' . e($ex->getMessage());
        }
    }

    private static function executeToggleService(int $vendorId, array $data): string
    {
        $serviceId = (int) ($data['service_id'] ?? 0);
        if ($serviceId === 0) {
            return '❌ Informe o ID do serviço.';
        }

        try {
            ServiceCatalogService::toggle($vendorId, $serviceId);
            return '✅ Status do serviço #' . $serviceId . ' alternado com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao alternar serviço: ' . e($ex->getMessage());
        }
    }

    private static function executeDeleteService(int $vendorId, array $data): string
    {
        $serviceId = (int) ($data['service_id'] ?? 0);
        if ($serviceId === 0) {
            return '❌ Informe o ID do serviço para excluir.';
        }

        try {
            ServiceCatalogService::delete($vendorId, $serviceId);
            return '✅ Serviço #' . $serviceId . ' excluído com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao excluir serviço: ' . e($ex->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  PRODUCT actions
    // ════════════════════════════════════════════════════════════════

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

    private static function executeUpdateProduct(int $vendorId, array $data): string
    {
        $productId = (int) ($data['product_id'] ?? 0);
        if ($productId === 0) {
            return '❌ Informe o ID do produto para atualizar.';
        }

        $existing = ProductService::find($vendorId, $productId);
        if (!$existing) {
            return '❌ Produto ID ' . $productId . ' não encontrado.';
        }

        try {
            $updateData = ['id' => $productId];
            foreach (['name', 'description', 'category'] as $strField) {
                if (isset($data[$strField])) {
                    $updateData[$strField] = trim((string) $data[$strField]);
                }
            }
            foreach (['sale_price', 'cost_price'] as $floatField) {
                if (isset($data[$floatField])) {
                    $updateData[$floatField] = (float) $data[$floatField];
                }
            }
            foreach (['stock_quantity', 'min_stock_quantity'] as $intField) {
                if (isset($data[$intField])) {
                    $updateData[$intField] = (int) $data[$intField];
                }
            }

            ProductService::save($vendorId, $updateData + $existing);
            return '✅ Produto "' . e($updateData['name'] ?? $existing['name']) . '" atualizado com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao atualizar produto: ' . e($ex->getMessage());
        }
    }

    private static function executeDeleteProduct(int $vendorId, array $data): string
    {
        $productId = (int) ($data['product_id'] ?? 0);
        if ($productId === 0) {
            return '❌ Informe o ID do produto para excluir.';
        }

        try {
            ProductService::delete($vendorId, $productId);
            return '✅ Produto #' . $productId . ' excluído com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao excluir produto: ' . e($ex->getMessage());
        }
    }

    private static function executeSellProduct(int $vendorId, array $data): string
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $quantity = max(1, (int) ($data['quantity'] ?? 1));

        if ($productId === 0) {
            return '❌ Informe o ID do produto para registrar a venda.';
        }

        $product = ProductService::find($vendorId, $productId);
        if (!$product) {
            return '❌ Produto ID ' . $productId . ' não encontrado.';
        }

        try {
            $sellData = [
                'quantity' => $quantity,
                'customer_name' => trim((string) ($data['customer_name'] ?? '')),
            ];

            if (isset($data['unit_price']) && $data['unit_price'] !== null && $data['unit_price'] !== '') {
                $sellData['unit_price'] = (float) $data['unit_price'];
            }

            ProductService::sell($vendorId, $productId, $sellData);
            $unitPrice = (float) ($sellData['unit_price'] ?? $product['sale_price']);
            $totalPrice = $unitPrice * $quantity;
            return '✅ Venda registrada! ' . $quantity . 'x "' . e($product['name']) . '" — Total: R$ ' . number_format($totalPrice, 2, ',', '.') . '. Estoque atualizado.';
        } catch (\Throwable $ex) {
            return '❌ Erro ao registrar venda: ' . e($ex->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  APPOINTMENT actions
    // ════════════════════════════════════════════════════════════════

    private static function executeCreateAppointment(int $vendorId, array $data): string
    {
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $customerPhone = trim((string) ($data['customer_phone'] ?? ''));
        $serviceId = (int) ($data['service_id'] ?? 0);
        $appointmentDate = trim((string) ($data['appointment_date'] ?? ''));
        $startTime = trim((string) ($data['start_time'] ?? ''));

        if ($customerName === '' || $customerPhone === '' || $serviceId === 0 || $appointmentDate === '' || $startTime === '') {
            return '❌ Para agendar, preciso de: serviço, data, horário, nome e telefone do cliente.';
        }

        // Validate professional availability if specified
        $professionalId = !empty($data['professional_id']) ? (int) $data['professional_id'] : null;
        if ($professionalId !== null && $professionalId > 0) {
            $profHours = ProfessionalService::getWorkingHoursForDate($professionalId, $appointmentDate);
            if ($profHours === null) {
                return '❌ O profissional selecionado não está disponível nesta data. Verifique a agenda ou datas específicas dele.';
            }
        }

        try {
            $appointmentData = [
                'service_id' => $serviceId,
                'appointment_date' => $appointmentDate,
                'start_time' => $startTime,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_email' => trim((string) ($data['customer_email'] ?? '')),
                'professional_id' => $professionalId,
                'notes' => trim((string) ($data['notes'] ?? '')),
            ];

            if (isset($data['price']) && $data['price'] !== null && $data['price'] !== '') {
                $appointmentData['price'] = (float) $data['price'];
            }

            $id = AppointmentService::create($vendorId, $appointmentData);
            return '✅ Agendamento #' . $id . ' criado com sucesso! Cliente: ' . e($customerName) . ' em ' . date('d/m/Y', strtotime($appointmentDate)) . ' às ' . $startTime . '.';
        } catch (\Throwable $ex) {
            return '❌ Erro ao agendar: ' . e($ex->getMessage());
        }
    }

    private static function executeUpdateAppointmentStatus(int $vendorId, array $data): string
    {
        $appointmentId = (int) ($data['appointment_id'] ?? 0);
        $status = trim((string) ($data['status'] ?? ''));

        if ($appointmentId === 0 || $status === '') {
            return '❌ Informe o ID do agendamento e o novo status.';
        }

        $statusLabels = [
            'confirmed' => 'Confirmado',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
            'no_show' => 'Não compareceu',
        ];

        if (!isset($statusLabels[$status])) {
            return '❌ Status inválido. Use: confirmed, completed, cancelled ou no_show.';
        }

        try {
            AppointmentService::updateStatus($vendorId, $appointmentId, $status);
            return '✅ Agendamento #' . $appointmentId . ' atualizado para "' . $statusLabels[$status] . '"!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao atualizar status: ' . e($ex->getMessage());
        }
    }

    private static function executeDeleteAppointment(int $vendorId, array $data): string
    {
        $appointmentId = (int) ($data['appointment_id'] ?? 0);

        if ($appointmentId === 0) {
            return '❌ Informe o ID do agendamento para excluir.';
        }

        try {
            AppointmentService::delete($vendorId, $appointmentId);
            return '✅ Agendamento #' . $appointmentId . ' excluído com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao excluir agendamento: ' . e($ex->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  WAITING LIST actions
    // ════════════════════════════════════════════════════════════════

    private static function executeCreateWaitingEntry(int $vendorId, array $data): string
    {
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $customerPhone = trim((string) ($data['customer_phone'] ?? ''));

        if ($customerName === '' || $customerPhone === '') {
            return '❌ Informe nome e telefone do cliente para a fila de espera.';
        }

        try {
            AppointmentService::createWaitingEntry($vendorId, [
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'desired_date' => trim((string) ($data['desired_date'] ?? date('Y-m-d'))),
                'service_id' => !empty($data['service_id']) ? (int) $data['service_id'] : null,
                'notes' => trim((string) ($data['notes'] ?? '')),
            ]);
            return '✅ Cliente "' . e($customerName) . '" adicionado à fila de espera!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao adicionar à fila de espera: ' . e($ex->getMessage());
        }
    }

    private static function executeDeleteWaitingEntry(int $vendorId, array $data): string
    {
        $entryId = (int) ($data['entry_id'] ?? 0);
        if ($entryId === 0) {
            return '❌ Informe o ID da entrada na fila de espera.';
        }

        try {
            AppointmentService::deleteWaitingEntry($vendorId, $entryId);
            return '✅ Entrada #' . $entryId . ' removida da fila de espera!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao remover da fila: ' . e($ex->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  PROFESSIONAL actions
    // ════════════════════════════════════════════════════════════════

    private static function executeCreateProfessional(int $vendorId, array $data): string
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return '❌ O profissional precisa ter um nome.';
        }

        try {
            $profData = [
                'name' => $name,
                'email' => trim((string) ($data['email'] ?? '')),
                'phone' => trim((string) ($data['phone'] ?? '')),
                'commission_rate' => isset($data['commission_rate']) ? (float) $data['commission_rate'] : 0,
                'color' => trim((string) ($data['color'] ?? '#3B82F6')),
            ];

            $id = ProfessionalService::create($vendorId, $profData);
            return '✅ Profissional "' . e($name) . '" (ID #' . $id . ') criado com sucesso! Ele já tem disponibilidade padrão configurada.';
        } catch (\Throwable $ex) {
            return '❌ Erro ao criar profissional: ' . e($ex->getMessage());
        }
    }

    private static function executeUpdateProfessional(int $vendorId, array $data): string
    {
        $professionalId = (int) ($data['professional_id'] ?? 0);
        if ($professionalId === 0) {
            return '❌ Informe o ID do profissional.';
        }

        try {
            $updateData = [];
            foreach (['name', 'email', 'phone', 'color'] as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = trim((string) $data[$field]);
                }
            }
            if (isset($data['commission_rate'])) {
                $updateData['commission_rate'] = (float) $data['commission_rate'];
            }

            ProfessionalService::update($vendorId, $professionalId, $updateData);
            return '✅ Profissional #' . $professionalId . ' atualizado com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao atualizar profissional: ' . e($ex->getMessage());
        }
    }

    private static function executeToggleProfessional(int $vendorId, array $data): string
    {
        $professionalId = (int) ($data['professional_id'] ?? 0);
        if ($professionalId === 0) {
            return '❌ Informe o ID do profissional.';
        }

        try {
            ProfessionalService::toggle($vendorId, $professionalId);
            return '✅ Status do profissional #' . $professionalId . ' alternado com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao alternar profissional: ' . e($ex->getMessage());
        }
    }

    private static function executeDeleteProfessional(int $vendorId, array $data): string
    {
        $professionalId = (int) ($data['professional_id'] ?? 0);
        if ($professionalId === 0) {
            return '❌ Informe o ID do profissional para excluir.';
        }

        try {
            ProfessionalService::delete($vendorId, $professionalId);
            return '✅ Profissional #' . $professionalId . ' excluído com sucesso!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao excluir profissional: ' . e($ex->getMessage());
        }
    }

    private static function executeLinkServicesToProfessional(int $vendorId, array $data): string
    {
        $professionalId = (int) ($data['professional_id'] ?? 0);
        $serviceIds = $data['service_ids'] ?? [];

        if ($professionalId === 0) {
            return '❌ Informe o ID do profissional.';
        }
        if (!is_array($serviceIds)) {
            return '❌ Informe a lista de IDs de serviços.';
        }

        try {
            ProfessionalService::updateLinkedServices($vendorId, $professionalId, array_map('intval', $serviceIds));
            $count = count($serviceIds);
            return '✅ ' . $count . ' serviço(s) vinculado(s) ao profissional #' . $professionalId . '!';
        } catch (\Throwable $ex) {
            return '❌ Erro ao vincular serviços: ' . e($ex->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  QUERY / REPORT actions (read-only — return data as text)
    // ════════════════════════════════════════════════════════════════

    private static function executeCheckAvailableSlots(int $vendorId, array $data): string
    {
        $serviceId = (int) ($data['service_id'] ?? 0);
        $date = trim((string) ($data['date'] ?? ''));

        if ($serviceId === 0 || $date === '') {
            return '❌ Informe o serviço e a data para consultar horários.';
        }

        $vendor = VendorService::findById($vendorId);
        $service = AppointmentService::serviceById($vendorId, $serviceId);
        if (!$service) {
            return '❌ Serviço ID ' . $serviceId . ' não encontrado.';
        }

        $professionalId = !empty($data['professional_id']) ? (int) $data['professional_id'] : null;

        try {
            $slots = AppointmentService::availableSlots($vendor, $service, $date, $professionalId);
            if (empty($slots)) {
                return '📅 Nenhum horário disponível em ' . date('d/m/Y', strtotime($date)) . ' para "' . e($service['title']) . '". Tente outra data!';
            }

            $slotList = implode(', ', $slots);
            return '📅 Horários disponíveis em **' . date('d/m/Y', strtotime($date)) . '** para "' . e($service['title']) . '" (' . $service['duration_minutes'] . ' min):\n\n🕐 ' . $slotList . "\n\nQuer que eu agende algum desses horários?";
        } catch (\Throwable $ex) {
            return '❌ Erro ao consultar horários: ' . e($ex->getMessage());
        }
    }

    private static function executeListAppointmentsForDate(int $vendorId, array $data): string
    {
        $date = trim((string) ($data['date'] ?? ''));
        if ($date === '') {
            return '❌ Informe a data (YYYY-MM-DD).';
        }

        try {
            $appointments = AppointmentService::appointmentsForDate($vendorId, $date);
            if (empty($appointments)) {
                return '📅 Nenhum agendamento para ' . date('d/m/Y', strtotime($date)) . '.';
            }

            $lines = [];
            foreach ($appointments as $apt) {
                $time = substr($apt['start_time'], 0, 5) . '-' . substr($apt['end_time'], 0, 5);
                $statusEmoji = match ($apt['status']) {
                    'confirmed' => '🟢',
                    'completed' => '✅',
                    'cancelled' => '🔴',
                    'no_show' => '⚫',
                    default => '⚪',
                };
                $lines[] = "{$statusEmoji} **{$time}** — {$apt['customer_name']} | {$apt['service_title']} | R\$ " . number_format((float) $apt['price'], 2, ',', '.') . ($apt['professional_name'] ? " | Prof: {$apt['professional_name']}" : '') . " (ID #{$apt['id']})";
            }

            return '📅 Agendamentos de **' . date('d/m/Y', strtotime($date)) . "** (" . count($appointments) . "):\n\n" . implode("\n", $lines);
        } catch (\Throwable $ex) {
            return '❌ Erro ao listar agendamentos: ' . e($ex->getMessage());
        }
    }

    private static function executeSearchClients(int $vendorId, array $data): string
    {
        $query = trim((string) ($data['query'] ?? ''));
        if ($query === '') {
            return '❌ Informe o nome ou telefone para buscar.';
        }

        try {
            $clients = Database::select(
                'SELECT c.id, c.name, c.phone, c.email, COUNT(a.id) AS visits, COALESCE(SUM(CASE WHEN a.status = \'completed\' THEN a.price ELSE 0 END), 0) AS total_spent, MAX(a.appointment_date) AS last_visit
                 FROM clients c
                 LEFT JOIN appointments a ON a.client_id = c.id
                 WHERE c.vendor_id = :vid AND (c.name LIKE :q OR c.phone LIKE :q2)
                 GROUP BY c.id, c.name, c.phone, c.email
                 ORDER BY visits DESC
                 LIMIT 10',
                ['vid' => $vendorId, 'q' => '%' . $query . '%', 'q2' => '%' . $query . '%']
            );

            if (empty($clients)) {
                return '🔍 Nenhum cliente encontrado com "' . e($query) . '".';
            }

            $lines = [];
            foreach ($clients as $c) {
                $lastVisit = $c['last_visit'] ? date('d/m/Y', strtotime($c['last_visit'])) : 'nunca';
                $lines[] = "• **{$c['name']}** ({$c['phone']}) — {$c['visits']} visitas | R\$ " . number_format((float) $c['total_spent'], 2, ',', '.') . " | última: {$lastVisit}";
            }

            return '🔍 Clientes encontrados (' . count($clients) . "):\n\n" . implode("\n", $lines);
        } catch (\Throwable $ex) {
            return '❌ Erro ao buscar clientes: ' . e($ex->getMessage());
        }
    }

    private static function executeGetFinanceReport(int $vendorId, array $data): string
    {
        $month = trim((string) ($data['month'] ?? date('Y-m')));

        try {
            $result = FinanceService::monthData($vendorId, $month);
            $kpis = $result['kpis'] ?? [];

            $received = number_format((float) ($kpis['total_received'] ?? 0), 2, ',', '.');
            $pending = number_format((float) ($kpis['total_open'] ?? 0), 2, ',', '.');
            $losses = number_format((float) ($kpis['total_losses'] ?? 0), 2, ',', '.');
            $serviceRevenue = number_format((float) ($kpis['service_revenue'] ?? 0), 2, ',', '.');
            $productRevenue = number_format((float) ($kpis['product_revenue'] ?? 0), 2, ',', '.');

            $monthLabel = $result['month_label'] ?? $month;

            return "💰 **Relatório Financeiro — {$monthLabel}**\n\n" .
                "• Receita recebida: **R\$ {$received}**\n" .
                "• Pendente: R\$ {$pending}\n" .
                "• Perdas (cancelamentos/faltas): R\$ {$losses}\n" .
                "• Receita de serviços: R\$ {$serviceRevenue}\n" .
                "• Receita de produtos: R\$ {$productRevenue}\n\n" .
                'Acesse **Finanças** no menu para mais detalhes.';
        } catch (\Throwable $ex) {
            return '❌ Erro ao gerar relatório financeiro: ' . e($ex->getMessage());
        }
    }

    private static function executeGetPerformanceReport(int $vendorId, array $data): string
    {
        $startDate = trim((string) ($data['start_date'] ?? date('Y-m-01')));
        $endDate = trim((string) ($data['end_date'] ?? date('Y-m-d')));

        try {
            $result = ReportService::build($vendorId, $startDate, $endDate);
            $kpis = $result['kpis'] ?? [];

            $totalAppointments = (int) ($kpis['total_appointments'] ?? 0);
            $completionRate = round((float) ($kpis['completion_rate'] ?? 0), 1);
            $cancelled = (int) ($kpis['cancelled_appointments'] ?? 0);
            $revenue = number_format((float) ($kpis['total_revenue'] ?? 0), 2, ',', '.');
            $avgTicket = number_format((float) ($kpis['average_ticket'] ?? 0), 2, ',', '.');
            $losses = number_format((float) ($kpis['financial_losses'] ?? 0), 2, ',', '.');

            $text = "📊 **Relatório de Desempenho** ({$startDate} a {$endDate})\n\n" .
                "• Total de agendamentos: **{$totalAppointments}**\n" .
                "• Taxa de conclusão: {$completionRate}%\n" .
                "• Cancelados/faltas: {$cancelled}\n" .
                "• Receita total: **R\$ {$revenue}**\n" .
                "• Ticket médio: R\$ {$avgTicket}\n" .
                "• Perdas financeiras: R\$ {$losses}\n";

            // Top services
            $serviceRevenue = $result['service_revenue'] ?? [];
            if (!empty($serviceRevenue)) {
                $text .= "\n🏆 **Serviços mais lucrativos:**\n";
                $topServices = array_slice($serviceRevenue, 0, 5);
                foreach ($topServices as $s) {
                    $text .= "• {$s['title']}: R\$ " . number_format((float) $s['total'], 2, ',', '.') . "\n";
                }
            }

            return $text;
        } catch (\Throwable $ex) {
            return '❌ Erro ao gerar relatório: ' . e($ex->getMessage());
        }
    }

    private static function executeCheckClientReturns(int $vendorId, array $data): string
    {
        $phone = trim((string) ($data['phone'] ?? ''));
        if ($phone === '') {
            return '❌ Informe o telefone do cliente para consultar retornos.';
        }

        try {
            $returns = ReturnService::findByPhone($vendorId, $phone);
            if (empty($returns)) {
                return '🔄 Nenhum retorno/crédito encontrado para o telefone ' . e($phone) . '.';
            }

            $lines = [];
            foreach ($returns as $r) {
                $expires = isset($r['expires_at']) ? date('d/m/Y', strtotime($r['expires_at'])) : 'sem prazo';
                $lines[] = "• {$r['service_title']} — status: {$r['status']} | expira: {$expires} (ID #{$r['id']})";
            }

            return '🔄 Retornos do cliente (' . count($returns) . "):\n\n" . implode("\n", $lines);
        } catch (\Throwable $ex) {
            return '❌ Erro ao consultar retornos: ' . e($ex->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  SETTINGS actions
    // ════════════════════════════════════════════════════════════════

    private static function executeUpdateBusinessHours(int $vendorId, array $data): string
    {
        $hours = $data['hours'] ?? [];
        if (!is_array($hours) || empty($hours)) {
            return '❌ Informe os horários no formato [{day_of_week, is_open, open_time, close_time}, ...].';
        }

        try {
            // Build indexed array 0-6 for saveWeeklyHours
            $weeklyHours = [];
            foreach ($hours as $h) {
                $day = (int) ($h['day_of_week'] ?? 0);
                $entry = [
                    'start_time' => $h['open_time'] ?? '08:00',
                    'end_time' => $h['close_time'] ?? '18:00',
                ];
                if (!empty($h['is_open'])) {
                    $entry['is_enabled'] = true;
                }
                $weeklyHours[$day] = $entry;
            }

            VendorService::saveWeeklyHours($vendorId, $weeklyHours);

            $dayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            $summary = [];
            foreach ($weeklyHours as $day => $h) {
                $dayName = $dayNames[$day] ?? (string) $day;
                if (!empty($h['is_enabled'])) {
                    $summary[] = "{$dayName}: " . substr($h['start_time'] ?? '', 0, 5) . '-' . substr($h['end_time'] ?? '', 0, 5);
                } else {
                    $summary[] = "{$dayName}: Fechado";
                }
            }

            return "✅ Horário de funcionamento atualizado!\n\n" . implode("\n", $summary);
        } catch (\Throwable $ex) {
            return '❌ Erro ao atualizar horários: ' . e($ex->getMessage());
        }
    }

    // ════════════════════════════════════════════════════════════════
    //  NAVIGATION action
    // ════════════════════════════════════════════════════════════════

    private static function executeNavigate(array $data): string
    {
        $url = trim((string) ($data['url'] ?? ''));
        $label = trim((string) ($data['label'] ?? 'página'));

        if ($url === '') {
            return '❌ URL de navegação não informada.';
        }

        // Validate that it's an internal route
        $allowedPrefixes = ['/vendor/', '/p/', '/book/'];
        $isAllowed = false;
        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($url, $prefix)) {
                $isAllowed = true;
                break;
            }
        }

        if (!$isAllowed) {
            return '❌ Navegação permitida apenas para páginas internas do sistema.';
        }

        return '🔗 Abrindo **' . e($label) . '**...';
    }

    // ════════════════════════════════════════════════════════════════
    //  MASS MESSAGING action
    // ════════════════════════════════════════════════════════════════

    private static function executeSendMassMessage(int $vendorId, array $data): string
    {
        $message = trim((string) ($data['message'] ?? ''));
        $filter = trim((string) ($data['filter'] ?? 'all'));
        $channel = trim((string) ($data['channel'] ?? 'sms'));

        if ($message === '') {
            return '❌ Informe o texto da mensagem para enviar.';
        }

        // Fetch clients based on filter
        $clients = match ($filter) {
            'active' => Database::select(
                'SELECT DISTINCT c.id, c.name, c.phone, c.email
                 FROM clients c
                 INNER JOIN appointments a ON a.client_id = c.id AND a.vendor_id = :vid
                 WHERE c.vendor_id = :vid2 AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                 ORDER BY c.name ASC',
                ['vid' => $vendorId, 'vid2' => $vendorId]
            ),
            'inactive' => Database::select(
                'SELECT c.id, c.name, c.phone, c.email
                 FROM clients c
                 WHERE c.vendor_id = :vid AND c.id NOT IN (
                     SELECT DISTINCT client_id FROM appointments WHERE vendor_id = :vid2 AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND client_id IS NOT NULL
                 )
                 ORDER BY c.name ASC',
                ['vid' => $vendorId, 'vid2' => $vendorId]
            ),
            'today' => Database::select(
                'SELECT DISTINCT c.id, c.name, c.phone, c.email
                 FROM clients c
                 INNER JOIN appointments a ON a.client_id = c.id AND a.vendor_id = :vid
                 WHERE c.vendor_id = :vid2 AND a.appointment_date = CURDATE() AND a.status = \'confirmed\'
                 ORDER BY c.name ASC',
                ['vid' => $vendorId, 'vid2' => $vendorId]
            ),
            default => Database::select(
                'SELECT id, name, phone, email FROM clients WHERE vendor_id = :vid ORDER BY name ASC',
                ['vid' => $vendorId]
            ),
        };

        if (empty($clients)) {
            $filterLabels = [
                'all' => 'na base',
                'active' => 'ativos (últimos 60 dias)',
                'inactive' => 'inativos (60+ dias sem visita)',
                'today' => 'agendados para hoje',
            ];
            return '❌ Nenhum cliente encontrado no filtro "' . ($filterLabels[$filter] ?? $filter) . '".';
        }

        $vendor = VendorService::findById($vendorId);
        $businessName = $vendor['business_name'] ?? 'Negócio';
        $sentCount = 0;
        $errorCount = 0;

        foreach ($clients as $client) {
            $personalizedMessage = str_replace(
                ['{nome}', '{negocio}'],
                [$client['name'] ?? 'Cliente', $businessName],
                $message
            );

            try {
                if (($channel === 'sms' || $channel === 'both') && !empty($client['phone'])) {
                    NotificationService::sendBulkSms(
                        $client['phone'],
                        $personalizedMessage,
                        $vendorId
                    );
                    $sentCount++;
                }

                if (($channel === 'email' || $channel === 'both') && !empty($client['email'])) {
                    NotificationService::sendBulkEmail(
                        $client['email'],
                        "Mensagem de {$businessName}",
                        $personalizedMessage,
                        $vendorId
                    );
                    $sentCount++;
                }
            } catch (\Throwable $ex) {
                $errorCount++;
                error_log("Mass message error for client {$client['id']}: " . $ex->getMessage());
            }
        }

        $channelLabel = match ($channel) {
            'sms' => 'SMS',
            'email' => 'e-mail',
            'both' => 'SMS + e-mail',
            default => 'mensagem',
        };

        $result = "📨 Envio em massa concluído! **{$sentCount}** {$channelLabel}(s) enviado(s) para " . count($clients) . ' cliente(s).';
        if ($errorCount > 0) {
            $result .= "\n⚠️ {$errorCount} erro(s) no envio.";
        }

        return $result;
    }

    // ════════════════════════════════════════════════════════════════
    //  PUBLIC AI CHATBOT (customer-facing on /p/{slug})
    // ════════════════════════════════════════════════════════════════

    /**
     * Build a system prompt for the public-facing chatbot (customer side).
     * This version only exposes read-only info + booking capability.
     */
    public static function publicSystemPrompt(int $vendorId): string
    {
        $vendor = VendorService::findById($vendorId);
        $services = VendorService::services($vendorId, true); // active only
        $professionals = ProfessionalService::listActiveByVendor($vendorId);
        $weeklyHours = VendorService::weeklyHours($vendorId);

        $businessName = $vendor['business_name'] ?? 'Negócio';
        $category = $vendor['category'] ?? '';
        $slug = $vendor['slug'] ?? '';
        $phone = $vendor['phone'] ?? '';
        $address = $vendor['address'] ?? '';
        $bio = $vendor['bio'] ?? '';

        $serviceList = '';
        foreach ($services as $s) {
            $serviceList .= "  - ID {$s['id']}: {$s['title']} — R\$ " . number_format((float) $s['price'], 2, ',', '.') . " ({$s['duration_minutes']} min)\n";
        }

        $profList = '';
        foreach ($professionals as $p) {
            $profList .= "  - ID {$p['id']}: {$p['name']}\n";
        }

        $hoursText = '';
        $dayNames = ['0' => 'Dom', '1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb'];
        foreach ($weeklyHours as $wh) {
            $day = $dayNames[(string) ($wh['weekday'] ?? '')] ?? (string) ($wh['weekday'] ?? '');
            if ((int) ($wh['is_enabled'] ?? 0)) {
                $hoursText .= "  - {$day}: " . substr((string) ($wh['start_time'] ?? '08:00'), 0, 5) . ' às ' . substr((string) ($wh['end_time'] ?? '18:00'), 0, 5) . "\n";
            } else {
                $hoursText .= "  - {$day}: FECHADO\n";
            }
        }

        $todayDate = date('Y-m-d');
        $todayName = self::dayNamePtBr(date('N'));

        return <<<PROMPT
Você é a **Lia**, assistente virtual de **{$businessName}**.
Responda SEMPRE em português do Brasil, de forma natural, amigável, objetiva e sem parecer robótica. Use emojis com moderação.

## Sobre o Estabelecimento
- Nome: {$businessName}
- Categoria: {$category}
- Endereço: {$address}
- Telefone/WhatsApp: {$phone}
- Sobre: {$bio}
- Data de hoje: {$todayDate} ({$todayName})

### Horário de funcionamento:
{$hoursText}

### Serviços disponíveis:
{$serviceList}

### Profissionais:
{$profList}

## O QUE VOCÊ PODE FAZER
Você é a atendente virtual. Pode:
1. **Responder perguntas** sobre serviços, preços, horários de funcionamento
2. **Verificar disponibilidade** de horários para agendamento
3. **Realizar agendamentos** para os clientes
4. **Direcionar o cliente** para áreas úteis do perfil público

## AÇÕES DISPONÍVEIS
Quando o cliente quiser agendar ou verificar horários, use estas ações em JSON entre blocos ```json```:

### Verificar horários disponíveis
```json
{"action": "check_available_slots", "data": {"service_id": 1, "date": "YYYY-MM-DD", "professional_id": null}}
```

### Realizar agendamento
Após verificar disponibilidade e o cliente confirmar, gere:
```json
{"action": "create_appointment", "data": {"service_id": 1, "appointment_date": "YYYY-MM-DD", "start_time": "HH:MM", "customer_name": "...", "customer_phone": "...", "professional_id": null, "notes": ""}}
```

### Navegar para uma área útil do perfil
Quando o cliente pedir para abrir uma seção, ver avaliações, ir para localização ou entrar na agenda, responda com:
```json
{"action": "navigate", "data": {"url": "/p/{$slug}#servicos", "label": "Serviços"}}
```

Destinos válidos:
- `/p/{$slug}#servicos` — serviços
- `/p/{$slug}#avaliacoes` — avaliações
- `/p/{$slug}#contato` — contato
- `/p/{$slug}#localizacao` — localização
- `/book/{$slug}/ID_DO_SERVICO` — agenda de um serviço específico

## REGRAS IMPORTANTES
1. Responda apenas sobre este estabelecimento. Recuse educadamente assuntos fora do escopo.
2. Seja simpática e acolhedora — você é a primeira impressão do cliente.
3. Ao sugerir agendamento, SEMPRE use a ação check_available_slots primeiro para verificar horários reais.
4. Para agendar, peça: **nome**, **telefone**, **serviço desejado**, **data** e **horário**.
5. Use os IDs reais dos serviços e profissionais ao gerar ações.
6. **NUNCA invente horários** — sempre consulte os slots disponíveis antes de sugerir.
7. Consultas (check_available_slots) podem ser executadas diretamente SEM pedir confirmação.
8. Para agendamentos (create_appointment), descreva o que será feito e peça confirmação antes de gerar o JSON.
9. Se o cliente pedir para abrir uma área do site, use `navigate` direto sem pedir confirmação.
10. Mantenha respostas curtas (max 2 parágrafos).
11. Se o cliente perguntar algo que você não pode resolver, sugira entrar em contato pelo WhatsApp: {$phone}

## Formatação
- Use **negrito** para destacar informações importantes.
- Use listas com bullet points (•) para enumerar itens.
- Sempre inclua emojis relevantes (💇 serviço, 📅 agenda, 💰 preço, 📍 local, etc).
PROMPT;
    }

    /**
     * Public-facing AI chat (customer side). Only supports read-only queries + booking.
     *
     * @param int    $vendorId
     * @param string $userMessage
     * @param array  $history
     * @return array{reply: string, action: ?array}
     */
    public static function publicChat(int $vendorId, string $userMessage, array $history = []): array
    {
        $providers = self::configuredProviders();

        if ($providers === []) {
            return self::publicFallbackResponse($userMessage, $vendorId);
        }

        $systemPrompt = self::publicSystemPrompt($vendorId);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

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

        foreach ($providers as $provider) {
            $result = self::callProvider($provider, $messages);
            if ($result !== null) {
                // Sanitize: only allow public-safe actions
                if ($result['action'] !== null) {
                    $allowedPublicActions = [
                        'check_available_slots',
                        'create_appointment',
                        'navigate',
                    ];
                    $actionType = $result['action']['action'] ?? '';
                    if (!in_array($actionType, $allowedPublicActions, true)) {
                        $result['action'] = null;
                    } elseif ($actionType === 'navigate' && !self::isAllowedPublicNavigateUrl((string) ($result['action']['data']['url'] ?? ''), (string) ($vendor['slug'] ?? ''), $vendorId)) {
                        $result['action'] = null;
                    }
                }
                return $result;
            }
        }

        return self::publicFallbackResponse($userMessage, $vendorId);
    }

    /**
     * Execute an action triggered by the public AI chatbot.
     * Only allows safe, public-facing actions.
     *
     * @return string Success/error message
     */
    public static function executePublicAction(int $vendorId, array $action): string
    {
        $type = $action['action'] ?? '';
        $data = $action['data'] ?? [];

        return match ($type) {
            'check_available_slots' => self::executeCheckAvailableSlots($vendorId, $data),
            'create_appointment' => self::executeCreateAppointment($vendorId, $data),
            'navigate' => self::executeNavigate($data),
            default => '❌ Ação não permitida no chat público.',
        };
    }

    private static function isAllowedPublicNavigateUrl(string $url, string $slug, int $vendorId): bool
    {
        if ($url === '' || $slug === '') {
            return false;
        }

        $profileBase = '/p/' . ltrim($slug, '/');
        if ($url === $profileBase || str_starts_with($url, $profileBase . '#')) {
            return true;
        }

        if (preg_match('~^/book/' . preg_quote($slug, '~') . '/(\d+)(?:\?professional=\d+)?$~', $url, $matches)) {
            $serviceIds = array_map(
                static fn(array $service): int => (int) ($service['id'] ?? 0),
                VendorService::services($vendorId, true)
            );
            return in_array((int) $matches[1], $serviceIds, true);
        }

        return false;
    }

    /**
     * Fallback response for the public chatbot when no AI provider is available.
     */
    private static function publicFallbackResponse(string $message, int $vendorId): array
    {
        $lower = mb_strtolower(trim($message), 'UTF-8');
        $vendor = VendorService::findById($vendorId);
        $businessName = $vendor['business_name'] ?? 'nosso estabelecimento';
        $phone = $vendor['phone'] ?? '';
        $slug = $vendor['slug'] ?? '';
        $profileBase = $slug !== '' ? '/p/' . $slug : '';

        if (str_contains($lower, 'serviço') || str_contains($lower, 'servico') || str_contains($lower, 'preço') || str_contains($lower, 'preco') || str_contains($lower, 'quanto')) {
            $services = VendorService::services($vendorId, true);
            if (empty($services)) {
                return ['reply' => '📋 No momento não temos serviços listados. Entre em contato pelo WhatsApp para mais informações!', 'action' => null];
            }
            $list = implode("\n", array_map(fn($s) => "• **{$s['title']}** — R\$ " . number_format((float) $s['price'], 2, ',', '.') . " ({$s['duration_minutes']} min)", $services));
            return [
                'reply' => "📋 Nossos serviços:\n\n{$list}\n\nSe quiser, também posso te levar direto para essa parte do perfil. 😊",
                'action' => $profileBase !== '' ? ['action' => 'navigate', 'data' => ['url' => $profileBase . '#servicos', 'label' => 'Serviços']] : null,
            ];
        }

        if (str_contains($lower, 'horário') || str_contains($lower, 'horario') || str_contains($lower, 'funcionamento') || str_contains($lower, 'abre') || str_contains($lower, 'fecha')) {
            $weeklyHours = VendorService::weeklyHours($vendorId);
            $dayNames = ['0' => 'Dom', '1' => 'Seg', '2' => 'Ter', '3' => 'Qua', '4' => 'Qui', '5' => 'Sex', '6' => 'Sáb'];
            $lines = [];
            foreach ($weeklyHours as $wh) {
                $day = $dayNames[(string) ($wh['weekday'] ?? '')] ?? '';
                if ((int) ($wh['is_enabled'] ?? 0)) {
                    $lines[] = "• {$day}: " . substr((string) ($wh['start_time'] ?? '08:00'), 0, 5) . ' às ' . substr((string) ($wh['end_time'] ?? '18:00'), 0, 5);
                } else {
                    $lines[] = "• {$day}: Fechado";
                }
            }
            return ['reply' => "🕐 Nossos horários de funcionamento:\n\n" . implode("\n", $lines), 'action' => null];
        }

        if (str_contains($lower, 'agenda') || str_contains($lower, 'agendar') || str_contains($lower, 'marcar') || str_contains($lower, 'disponível') || str_contains($lower, 'disponivel')) {
            return [
                'reply' => "📅 Para agendar, me diga:\n\n• Qual **serviço** deseja?\n• Qual **data** prefere?\n• Seu **nome** e **telefone**\n\nTambém posso te abrir a área de serviços para escolher com calma. 😊",
                'action' => $profileBase !== '' ? ['action' => 'navigate', 'data' => ['url' => $profileBase . '#servicos', 'label' => 'Serviços e agenda']] : null,
            ];
        }

        if (str_contains($lower, 'endereço') || str_contains($lower, 'endereco') || str_contains($lower, 'localização') || str_contains($lower, 'localizacao') || str_contains($lower, 'onde') || str_contains($lower, 'local')) {
            $address = $vendor['address'] ?? '';
            if ($address !== '') {
                return [
                    'reply' => "📍 Estamos localizados em: **{$address}**\n\nSe quiser, eu também te levo direto para a seção de localização. 😊",
                    'action' => $profileBase !== '' ? ['action' => 'navigate', 'data' => ['url' => $profileBase . '#localizacao', 'label' => 'Localização']] : null,
                ];
            }
            return ['reply' => "📍 Entre em contato pelo WhatsApp para saber nossa localização!" . ($phone ? " 📱 {$phone}" : ''), 'action' => null];
        }

        if (str_contains($lower, 'avalia') || str_contains($lower, 'depoimento') || str_contains($lower, 'review')) {
            return [
                'reply' => '⭐ Posso te levar direto para as avaliações do perfil.',
                'action' => $profileBase !== '' ? ['action' => 'navigate', 'data' => ['url' => $profileBase . '#avaliacoes', 'label' => 'Avaliações']] : null,
            ];
        }

        if (str_contains($lower, 'whatsapp') || str_contains($lower, 'contato') || str_contains($lower, 'telefone') || str_contains($lower, 'ligar') || str_contains($lower, 'falar')) {
            if ($phone !== '') {
                return [
                    'reply' => "📱 Fale conosco pelo WhatsApp: **{$phone}**\n\nSe quiser, eu também te mostro a parte de contato do perfil. 😊",
                    'action' => $profileBase !== '' ? ['action' => 'navigate', 'data' => ['url' => $profileBase . '#contato', 'label' => 'Contato']] : null,
                ];
            }
            return ['reply' => '📱 Entre em contato através dos nossos canais de atendimento!', 'action' => null];
        }

        $whatsappMsg = $phone !== '' ? "\n\nOu fale pelo WhatsApp: **{$phone}**" : '';
        return [
            'reply' => "👋 Olá! Sou a assistente virtual de **{$businessName}**! Posso ajudar com:\n\n• 📋 Informações sobre nossos **serviços** e preços\n• 📅 **Agendar** um horário\n• 🕐 **Horários** de funcionamento\n• 📍 **Localização**\n\nComo posso ajudar? 😊{$whatsappMsg}",
            'action' => null,
        ];
    }

    // ════════════════════════════════════════════════════════════════
    //  HELPERS
    // ════════════════════════════════════════════════════════════════

    /**
     * Return day name in Portuguese.
     */
    private static function dayNamePtBr(string $dayNumber): string
    {
        return match ($dayNumber) {
            '1' => 'Segunda-feira',
            '2' => 'Terça-feira',
            '3' => 'Quarta-feira',
            '4' => 'Quinta-feira',
            '5' => 'Sexta-feira',
            '6' => 'Sábado',
            '7' => 'Domingo',
            default => '',
        };
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

        if (str_contains($lower, 'produto') || str_contains($lower, 'estoque')) {
            $products = VendorService::products($vendorId);
            if (empty($products)) {
                return ['reply' => '📦 Nenhum produto cadastrado. Acesse **Produtos** no menu para começar!', 'action' => null];
            }
            $list = implode("\n", array_map(fn($p) => "• {$p['name']} — R\$ " . number_format((float) $p['sale_price'], 2, ',', '.') . " (estoque: {$p['stock_quantity']})", array_slice($products, 0, 10)));
            return ['reply' => "📦 Seus produtos:\n{$list}", 'action' => null];
        }

        if (str_contains($lower, 'profissional') || str_contains($lower, 'equipe')) {
            $profs = ProfessionalService::listByVendor($vendorId);
            if (empty($profs)) {
                return ['reply' => '👥 Nenhum profissional cadastrado. Acesse **Profissionais** para adicionar!', 'action' => null];
            }
            $list = implode("\n", array_map(fn($p) => "• {$p['name']}" . ((int) $p['is_active'] ? '' : ' [INATIVO]'), $profs));
            return ['reply' => "👥 Sua equipe:\n{$list}", 'action' => null];
        }

        if (str_contains($lower, 'agenda') || str_contains($lower, 'agendamento') || str_contains($lower, 'hoje')) {
            $today = Database::selectOne(
                'SELECT COUNT(*) AS total FROM appointments WHERE vendor_id = :vid AND appointment_date = CURDATE() AND status = \'confirmed\'',
                ['vid' => $vendorId]
            );
            return ['reply' => "📅 Você tem **{$today['total']}** agendamento(s) confirmado(s) para hoje. Acesse a **Agenda** para ver os detalhes.", 'action' => null];
        }

        if (str_contains($lower, 'cliente')) {
            $clientCount = Database::selectOne('SELECT COUNT(*) AS total FROM clients WHERE vendor_id = :vid', ['vid' => $vendorId]);
            return ['reply' => "👤 Você tem **{$clientCount['total']}** clientes na base. Acesse **Clientes** para ver detalhes.", 'action' => null];
        }

        if (str_contains($lower, 'financ') || str_contains($lower, 'receita') || str_contains($lower, 'dinheiro') || str_contains($lower, 'faturamento')) {
            return ['reply' => '💰 Para ver suas finanças, acesse **Finanças** no menu principal. Ou peça "relatório financeiro deste mês" para ver um resumo!', 'action' => null];
        }

        if (str_contains($lower, 'relatório') || str_contains($lower, 'relatorio') || str_contains($lower, 'desempenho')) {
            return ['reply' => '📊 Para ver relatórios completos, acesse **Relatórios** no menu. Ou peça "relatório de desempenho" para um resumo!', 'action' => null];
        }

        if (str_contains($lower, 'retorno') || str_contains($lower, 'credito') || str_contains($lower, 'crédito')) {
            return ['reply' => '🔄 Para consultar retornos de um cliente, me diga o telefone. Ex: "retornos do 11999998888"', 'action' => null];
        }

        if (str_contains($lower, 'horário') || str_contains($lower, 'horario') || str_contains($lower, 'funcionamento')) {
            return ['reply' => '🕐 Para atualizar horários de funcionamento, me diga os dias e horários. Ex: "abra segunda a sexta das 8h às 18h"', 'action' => null];
        }

        if (str_contains($lower, 'ajuda') || str_contains($lower, 'help') || str_contains($lower, 'como') || str_contains($lower, 'o que')) {
            return ['reply' => self::getHelpText(), 'action' => null];
        }

        return [
            'reply' => "Oi! Sou a Lia, sua assistente no Apprumo 👋 Posso fazer de tudo aqui no sistema.\n\nPra respostas mais completas, configure a chave GROQ_API_KEY no .env.\n\nDigite **ajuda** pra ver o que posso fazer.",
            'action' => null,
        ];
    }

    /**
     * Return comprehensive help text listing all AI capabilities.
     */
    private static function getHelpText(): string
    {
        return <<<'HELP'
🤖 **Lia — Assistente do Apprumo**

Posso fazer tudo no sistema pra você! Alguns exemplos:

📅 **Agenda**
• "agende João para corte amanhã às 14h"
• "quais horários disponíveis para corte na sexta?"
• "mostre os agendamentos de amanhã"
• "marque o agendamento #5 como concluído"
• "cancele o agendamento #3"

📋 **Serviços & Produtos**
• "crie um serviço de corte por R$50"
• "venda 2 shampoos para a Maria"

👥 **Profissionais**
• "cadastre o profissional Carlos"
• "vincule os serviços 1 e 2 ao profissional #1"

💰 **Relatórios**
• "relatório financeiro deste mês"
• "busque o cliente João"

🔗 **Navegação**
• "vai pra agenda"
• "abre os serviços"
• "me leva pro financeiro"

📨 **Mensagens em massa**
• "envie SMS pra todos os clientes: Promoção hoje!"
• "mande email pros clientes inativos"

⚠️ Ações que modificam dados pedem confirmação antes!
HELP;
    }
}

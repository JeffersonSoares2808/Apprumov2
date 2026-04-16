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
 * - Book appointments (fill schedules)
 * - Sell products (deduct stock and record sale)
 * - Cancel/complete/update appointment status
 * - Give business insights based on real data
 * - Guide the user through any feature
 * All destructive/mutating actions require explicit user confirmation.
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

        $monthRevenue = Database::selectOne(
            'SELECT COALESCE(SUM(price), 0) AS total FROM appointments WHERE vendor_id = :vid AND status = \'completed\' AND appointment_date BETWEEN DATE_FORMAT(CURDATE(), \'%Y-%m-01\') AND CURDATE()',
            ['vid' => $vendorId]
        );

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
            $todayAppointmentList .= "  - ID {$apt['id']}: {$apt['customer_name']} | {$apt['service_title']} | {$time} | status: {$apt['status']}" . ($apt['professional_name'] ? " | prof: {$apt['professional_name']}" : '') . "\n";
        }

        $businessName = $vendor['business_name'] ?? 'Negócio';
        $category = $vendor['category'] ?? 'não definida';
        $slug = $vendor['slug'] ?? '';

        $serviceCount = count($services);
        $profCount = count($professionals);
        $productCount = count($products);
        $monthRevenueFormatted = number_format((float) ($monthRevenue['total'] ?? 0), 2, ',', '.');
        $todayDate = date('Y-m-d');
        $todayName = self::dayNamePtBr(date('N'));
        $todayAppointmentCount = count($todayAppointments);
        $clientTotal = (int) ($clientCount['total'] ?? 0);

        return <<<PROMPT
Você é a **Assistente IA do Apprumo** — uma assistente inteligente e poderosa integrada ao sistema de gestão Apprumo.
Você funciona como um **funcionário virtual**: pode executar ações reais no sistema quando o vendedor pedir.
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

## AÇÕES DISPONÍVEIS
Quando o usuário pedir para EXECUTAR uma ação, responda com um JSON de ação entre blocos ```json```.
**IMPORTANTE**: SEMPRE peça confirmação ao usuário antes de gerar o JSON de ação. Descreva exatamente o que será feito, e só gere o JSON quando o usuário confirmar.
Se o usuário não fornecer todos os dados obrigatórios, pergunte antes de gerar.

### 1. Criar serviço
```json
{"action": "create_service", "data": {"title": "...", "price": 0, "duration_minutes": 30, "description": "..."}}
```

### 2. Criar produto
```json
{"action": "create_product", "data": {"name": "...", "sale_price": 0, "cost_price": 0, "stock_quantity": 0, "description": "..."}}
```

### 3. Agendar atendimento (preencher horário)
Dados obrigatórios: service_id, appointment_date (YYYY-MM-DD), start_time (HH:MM), customer_name, customer_phone
Dados opcionais: professional_id, price, notes
```json
{"action": "create_appointment", "data": {"service_id": 1, "appointment_date": "2025-01-15", "start_time": "14:00", "customer_name": "João Silva", "customer_phone": "11999998888", "professional_id": null, "notes": ""}}
```

### 4. Registrar venda de produto (dar baixa no estoque)
Dados obrigatórios: product_id, quantity
Dados opcionais: customer_name, unit_price
```json
{"action": "sell_product", "data": {"product_id": 1, "quantity": 1, "customer_name": "Maria", "unit_price": null}}
```

### 5. Atualizar status de agendamento
Statuses permitidos: confirmed, completed, cancelled, no_show
```json
{"action": "update_appointment_status", "data": {"appointment_id": 123, "status": "completed"}}
```

### 6. Excluir agendamento
```json
{"action": "delete_appointment", "data": {"appointment_id": 123}}
```

### 7. Adicionar à fila de espera
```json
{"action": "create_waiting_entry", "data": {"customer_name": "...", "customer_phone": "...", "desired_date": "2025-01-15", "service_id": null, "notes": ""}}
```

## Dados atuais do negócio "{$businessName}"
- Categoria: {$category}
- Link público: /p/{$slug}
- Data de hoje: {$todayDate} ({$todayName})

### Serviços cadastrados ({$serviceCount}):
{$serviceList}
### Profissionais ({$profCount}):
{$profList}
### Produtos ({$productCount}):
{$productList}
### Agendamentos de hoje ({$todayAppointmentCount}):
{$todayAppointmentList}
### Resumo geral:
- Clientes: {$clientTotal} na base
- Receita do mês: R\$ {$monthRevenueFormatted}

## Regras
1. Responda apenas sobre o Apprumo e gestão do negócio. Recuse educadamente assuntos fora do escopo.
2. Seja sempre construtivo e proativo — sugira melhorias quando apropriado.
3. Se não souber algo, diga honestamente e sugira onde encontrar a informação no sistema.
4. Mantenha respostas curtas (max 3 parágrafos) exceto quando o usuário pedir detalhes.
5. Use listas e formatação para facilitar a leitura.
6. **NUNCA execute uma ação sem antes descrever o que será feito e pedir confirmação.** Primeiro explique a ação, depois gere o JSON apenas se o usuário confirmar (com "sim", "ok", "confirma", "pode fazer" etc.).
7. Use os IDs reais dos serviços, produtos e profissionais listados acima ao gerar ações.
8. Ao agendar, sugira horários disponíveis com base nos dados do sistema.
9. Ao registrar venda, confirme o produto e a quantidade antes de gerar a ação.
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

        switch ($type) {
            case 'create_service':
                return self::executeCreateService($vendorId, $data);
            case 'create_product':
                return self::executeCreateProduct($vendorId, $data);
            case 'create_appointment':
                return self::executeCreateAppointment($vendorId, $data);
            case 'sell_product':
                return self::executeSellProduct($vendorId, $data);
            case 'update_appointment_status':
                return self::executeUpdateAppointmentStatus($vendorId, $data);
            case 'delete_appointment':
                return self::executeDeleteAppointment($vendorId, $data);
            case 'create_waiting_entry':
                return self::executeCreateWaitingEntry($vendorId, $data);
            default:
                return '❌ Ação não reconhecida: ' . e($type);
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

        try {
            $appointmentData = [
                'service_id' => $serviceId,
                'appointment_date' => $appointmentDate,
                'start_time' => $startTime,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_email' => trim((string) ($data['customer_email'] ?? '')),
                'professional_id' => !empty($data['professional_id']) ? (int) $data['professional_id'] : null,
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
            $totalPrice = ((float) ($sellData['unit_price'] ?? $product['sale_price'])) * $quantity;
            return '✅ Venda registrada! ' . $quantity . 'x "' . e($product['name']) . '" — Total: R$ ' . number_format($totalPrice, 2, ',', '.') . '. Estoque atualizado.';
        } catch (\Throwable $ex) {
            return '❌ Erro ao registrar venda: ' . e($ex->getMessage());
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

        if (str_contains($lower, 'ajuda') || str_contains($lower, 'help') || str_contains($lower, 'como') || str_contains($lower, 'o que')) {
            return ['reply' => "🤖 Sou a assistente IA do Apprumo. Posso executar ações reais no sistema! Exemplos:\n• **Criar serviços** — \"crie um serviço de corte por R\$50\"\n• **Agendar atendimento** — \"agende o João para corte amanhã às 14h\"\n• **Registrar venda** — \"venda 2 shampoos para a Maria\"\n• **Concluir atendimento** — \"marque o agendamento #5 como concluído\"\n• **Cancelar atendimento** — \"cancele o agendamento #3\"\n• **Fila de espera** — \"coloque Ana na fila de espera\"\n• **Ver agenda** — \"quantos agendamentos tenho hoje?\"\n\n⚠️ Todas as ações precisam da sua **confirmação** antes de serem executadas. É só perguntar!", 'action' => null];
        }

        return [
            'reply' => "🤖 Olá! Sou a assistente IA do Apprumo — seu funcionário virtual! Posso agendar atendimentos, registrar vendas, criar serviços e muito mais.\n\nPara respostas mais completas com IA, configure a chave GROQ_API_KEY no .env.\n\nDigite **ajuda** para ver tudo que posso fazer.",
            'action' => null,
        ];
    }
}

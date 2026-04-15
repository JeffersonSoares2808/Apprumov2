<section class="stack stack--spacious">
    <div class="card hero hero--dashboard">
        <div class="hero__content">
            <span class="soft-pill soft-pill--gold">Visão geral da operação</span>
            <h1 class="page-title">Seu negócio com leitura rápida, elegante e acionável.</h1>
            <p class="page-subtitle">Acompanhe agenda, caixa potencial, perdas e fila de espera com uma interface mais limpa, mais premium e mais fácil de operar no celular.</p>
        </div>
        <div class="hero__actions inline-actions inline-actions--wrap">
            <a class="btn btn-secondary btn-animated btn-pulse" href="<?= base_url('vendor/agenda') ?>">📅 Abrir agenda</a>
            <a class="btn btn-light btn-animated" href="<?= base_url('p/' . $vendor['slug']) ?>" target="_blank" rel="noopener">🌐 Ver perfil público</a>
            <button class="btn btn-light btn-animated" type="button" data-copy-url="<?= e(base_url('p/' . $vendor['slug'])) ?>">📋 Copiar link</button>
        </div>
    </div>

    <div class="dashboard-kpis dashboard-kpis--premium">
        <div class="kpi kpi--premium">
            <small>Atendimentos hoje</small>
            <strong><?= (int) ($dashboard['counts']['today_total'] ?? 0) ?></strong>
            <span class="muted">Tudo que entra na agenda do dia.</span>
        </div>
        <div class="kpi kpi--premium">
            <small>Confirmados hoje</small>
            <strong><?= (int) ($dashboard['counts']['today_confirmed'] ?? 0) ?></strong>
            <span class="muted">Clientes já confirmados.</span>
        </div>
        <div class="kpi kpi--premium">
            <small>Receita concluída</small>
            <strong><?= money($dashboard['counts']['completed_revenue'] ?? 0) ?></strong>
            <span class="muted">Resultado efetivamente realizado.</span>
        </div>
        <div class="kpi kpi--premium">
            <small>Perdas no mês</small>
            <strong><?= money($dashboard['counts']['month_losses'] ?? 0) ?></strong>
            <span class="muted">Cancelamentos e no-show.</span>
        </div>
        <div class="kpi kpi--premium">
            <small>Fila de espera hoje</small>
            <strong><?= (int) ($dashboard['waiting_count'] ?? 0) ?></strong>
            <span class="muted">Demanda que pode virar encaixe.</span>
        </div>
        <div class="kpi kpi--premium">
            <small>Estoque baixo</small>
            <strong><?= (int) ($dashboard['low_stock_count'] ?? 0) ?></strong>
            <span class="muted">Itens pedindo reposição.</span>
        </div>
    </div>

    <div class="app-grid two">
        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Próximos passos</span>
                    <h2>Agenda dos próximos atendimentos</h2>
                    <p class="muted">Uma visão direta para reduzir troca de tela e manter foco na execução.</p>
                </div>
                <a class="btn btn-light" href="<?= base_url('vendor/agenda') ?>">Agenda completa</a>
            </div>

            <?php if ($dashboard['upcoming'] === []): ?>
                <div class="empty-state empty-state--premium">Nenhum agendamento futuro encontrado. Aproveite para divulgar o link público e atrair novos bookings.</div>
            <?php else: ?>
                <div class="stack stack--compact">
                    <?php foreach ($dashboard['upcoming'] as $item): ?>
                        <article class="appointment-card appointment-card--premium">
                            <div class="appointment-card__time">
                                <strong><?= format_time($item['start_time']) ?></strong>
                                <span><?= format_date($item['appointment_date']) ?></span>
                            </div>
                            <div class="appointment-card__body">
                                <strong><?= e($item['customer_name']) ?></strong>
                                <div class="muted"><?= e($item['service_title'] ?? 'Serviço') ?></div>
                            </div>
                            <div class="appointment-card__meta">
                                <span class="badge <?= status_class($item['status']) ?>"><?= e(status_label($item['status'])) ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="stack">
            <div class="card card--section">
                <div class="section-header section-header--premium">
                    <div>
                        <span class="section-kicker">Atalhos premium</span>
                        <h2>Áreas mais usadas</h2>
                    </div>
                </div>
                <div class="shortcut-grid shortcut-grid--premium">
                    <a class="shortcut-tile" href="<?= base_url('vendor/services') ?>">
                        <span class="shortcut-tile__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="18" cy="17" r="2" fill="currentColor"/></svg></span>
                        <strong>Serviços</strong><span>Catálogo e duração</span>
                    </a>
                    <a class="shortcut-tile" href="<?= base_url('vendor/products') ?>">
                        <span class="shortcut-tile__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M5 8.5 12 4l7 4.5v7L12 20l-7-4.5v-7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M5 8.5 12 13l7-4.5" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg></span>
                        <strong>Produtos</strong><span>Estoque e vendas</span>
                    </a>
                    <a class="shortcut-tile" href="<?= base_url('vendor/finance') ?>">
                        <span class="shortcut-tile__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M12 3v18M16.5 7.5c0-1.933-2.015-3.5-4.5-3.5s-4.5 1.567-4.5 3.5 2.015 3.5 4.5 3.5 4.5 1.567 4.5 3.5-2.015 3.5-4.5 3.5-4.5-1.567-4.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                        <strong>Financeiro</strong><span>Recebimentos e perdas</span>
                    </a>
                    <a class="shortcut-tile" href="<?= base_url('vendor/reports') ?>">
                        <span class="shortcut-tile__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M5 19V9M12 19V5M19 19v-7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M4 19h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                        <strong>Relatórios</strong><span>Indicadores do período</span>
                    </a>
                    <a class="shortcut-tile" href="<?= base_url('vendor/clients') ?>">
                        <span class="shortcut-tile__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" width="22" height="22"><path d="M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0Z" stroke="currentColor" stroke-width="1.8"/><path d="M4 21v-1a7 7 0 0 1 7-7h2a7 7 0 0 1 7 7v1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>
                        <strong>Clientes</strong><span>Relacionamento e recorrência</span>
                    </a>
                    <a class="shortcut-tile" href="<?= base_url('vendor/settings') ?>">
                        <span class="shortcut-tile__icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" width="22" height="22"><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
                        <strong>Configurações</strong><span>Marca, horários e perfil</span>
                    </a>
                </div>
            </div>

            <div class="card card--section card--soft-outline">
                <span class="section-kicker">Perfil público</span>
                <h2>Seu link de booking</h2>
                <p class="muted">Use este link para captar agendamentos. Compartilhe nas redes sociais, WhatsApp ou imprima o QR Code.</p>
                <div class="link-box"><?= e(base_url('p/' . $vendor['slug'])) ?></div>
                <div class="share-actions-grid">
                    <button class="share-action-btn" type="button" data-native-share data-share-url="<?= e(base_url('p/' . $vendor['slug'])) ?>" data-share-title="<?= e($vendor['business_name'] ?? 'Apprumo') ?>" data-share-text="Conheça meu perfil e agende online">
                        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><polyline points="16 6 12 2 8 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="2" x2="12" y2="15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <span>Compartilhar</span>
                    </button>
                    <button class="share-action-btn" type="button" data-copy-url="<?= e(base_url('p/' . $vendor['slug'])) ?>">
                        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        <span>Copiar link</span>
                    </button>
                    <?php
                    $whatsappShareUrl = 'https://api.whatsapp.com/send?text=' . rawurlencode('Olá! Conheça meu perfil e agende online: ' . base_url('p/' . $vendor['slug']));
                    ?>
                    <a class="share-action-btn" href="<?= e($whatsappShareUrl) ?>" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" fill="#25D366"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z" stroke="currentColor" stroke-width="1.5"/></svg>
                        <span>WhatsApp</span>
                    </a>
                    <a class="share-action-btn" href="<?= e(base_url('p/' . $vendor['slug'])) ?>" target="_blank" rel="noopener">
                        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="1.8"/></svg>
                        <span>Abrir página</span>
                    </a>
                </div>
            </div>

            <div class="card card--section ai-panel">
                <div class="section-header section-header--premium">
                    <div>
                        <span class="section-kicker">🤖 Assistente IA</span>
                        <h2>Sugestões inteligentes</h2>
                        <p class="muted">Análise automática da sua operação com recomendações personalizadas.</p>
                    </div>
                </div>
                <div class="ai-suggestion-list">
                    <?php
                    $aiSuggestions = [];
                    $todayTotal = (int) ($dashboard['counts']['today_total'] ?? 0);
                    $todayConfirmed = (int) ($dashboard['counts']['today_confirmed'] ?? 0);
                    $monthLosses = (float) ($dashboard['counts']['month_losses'] ?? 0);
                    $completedRevenue = (float) ($dashboard['counts']['completed_revenue'] ?? 0);
                    $lowStock = (int) ($dashboard['low_stock_count'] ?? 0);
                    $waitingCount = (int) ($dashboard['waiting_count'] ?? 0);
                    $serviceCount = count($services ?? []);

                    // Extended AI data
                    $ai = $ai_data ?? [];
                    $totalClients = (int) ($ai['total_clients'] ?? 0);
                    $recurringClients = (int) ($ai['recurring_clients'] ?? 0);
                    $recentRevenue = (float) ($ai['recent_revenue'] ?? 0);
                    $previousRevenue = (float) ($ai['previous_revenue'] ?? 0);
                    $noShowTotal = (int) ($ai['no_show_total'] ?? 0);
                    $noShowCount = (int) ($ai['no_show_count'] ?? 0);
                    $busiestDow = (int) ($ai['busiest_dow'] ?? 0);
                    $busiestCount = (int) ($ai['busiest_count'] ?? 0);
                    $hasProfileImage = (bool) ($ai['has_profile_image'] ?? false);
                    $hasBio = (bool) ($ai['has_bio'] ?? false);
                    $hasAddress = (bool) ($ai['has_address'] ?? false);
                    $hasWhatsappApi = (bool) ($ai['has_whatsapp_api'] ?? false);
                    $enabledDays = (int) ($ai['enabled_days'] ?? 0);
                    $remindersEnabled = (int) ($ai['reminders_enabled'] ?? 1);
                    $reminderMinutes = (int) ($ai['reminder_minutes'] ?? 1440);

                    $dowNames = [1 => 'Domingo', 2 => 'Segunda', 3 => 'Terça', 4 => 'Quarta', 5 => 'Quinta', 6 => 'Sexta', 7 => 'Sábado'];

                    // ── URGENTE: Ações imediatas ──
                    if ($todayTotal > 0 && $todayConfirmed < $todayTotal) {
                        $pending = $todayTotal - $todayConfirmed;
                        $aiSuggestions[] = [
                            'icon' => '🚨',
                            'title' => $pending . ' atendimento(s) sem confirmação hoje',
                            'text' => 'Envie lembretes pelo WhatsApp agora para garantir a presença. Clientes confirmados faltam menos.',
                            'priority' => 'urgent',
                            'action_url' => base_url('vendor/agenda'),
                            'action_label' => 'Ver agenda',
                        ];
                    }

                    if ($waitingCount > 0) {
                        $aiSuggestions[] = [
                            'icon' => '🔔',
                            'title' => $waitingCount . ' pessoa(s) na fila de espera',
                            'text' => 'Há demanda real! Encaixe esses clientes em horários vagos e aumente sua receita do dia.',
                            'priority' => 'urgent',
                            'action_url' => base_url('vendor/agenda'),
                            'action_label' => 'Gerenciar fila',
                        ];
                    }

                    if ($lowStock > 0) {
                        $aiSuggestions[] = [
                            'icon' => '📦',
                            'title' => $lowStock . ' produto(s) com estoque crítico',
                            'text' => 'Reponha o estoque antes de perder vendas. Clientes que não encontram o produto podem não voltar.',
                            'priority' => 'warning',
                            'action_url' => base_url('vendor/products'),
                            'action_label' => 'Ver produtos',
                        ];
                    }

                    // ── TENDÊNCIAS: Análise de performance ──
                    if ($previousRevenue > 0 && $recentRevenue > 0) {
                        $revenueChange = (($recentRevenue - $previousRevenue) / $previousRevenue) * 100;
                        if ($revenueChange > 10) {
                            $aiSuggestions[] = [
                                'icon' => '📈',
                                'title' => 'Receita cresceu ' . number_format(abs($revenueChange), 0) . '% nos últimos 30 dias',
                                'text' => 'Excelente! Sua receita está em alta. Considere ajustar os preços ou adicionar novos serviços premium para capitalizar esse momento.',
                                'priority' => 'success',
                                'action_url' => base_url('vendor/reports'),
                                'action_label' => 'Ver relatórios',
                            ];
                        } elseif ($revenueChange < -10) {
                            $aiSuggestions[] = [
                                'icon' => '📉',
                                'title' => 'Receita caiu ' . number_format(abs($revenueChange), 0) . '% em relação ao período anterior',
                                'text' => 'Invista em divulgação: compartilhe seu link no WhatsApp, ofereça promoções para clientes inativos ou divulgue nas redes sociais.',
                                'priority' => 'warning',
                                'action_url' => base_url('vendor/reports'),
                                'action_label' => 'Ver relatórios',
                            ];
                        }
                    }

                    if ($noShowTotal > 5 && $noShowCount > 0) {
                        $noShowRate = ($noShowCount / $noShowTotal) * 100;
                        if ($noShowRate > 10) {
                            $aiSuggestions[] = [
                                'icon' => '⚠️',
                                'title' => 'Taxa de falta: ' . number_format($noShowRate, 0) . '% nos últimos 30 dias',
                                'text' => $noShowRate > 20
                                    ? 'Taxa muito alta! Ative lembretes automáticos e peça confirmação prévia. Considere cobrar sinal antecipado.'
                                    : 'Considere enviar lembretes mais cedo' . ($reminderMinutes > 120 ? ' — atualmente em ' . ($reminderMinutes >= 1440 ? (int)($reminderMinutes / 1440) . ' dia(s)' : (int)($reminderMinutes / 60) . 'h') . '. Tente reduzir para 2-3 horas.' : '.'),
                                'priority' => $noShowRate > 20 ? 'urgent' : 'warning',
                                'action_url' => base_url('vendor/settings'),
                                'action_label' => 'Ajustar lembretes',
                            ];
                        }
                    }

                    if ($monthLosses > 0 && $completedRevenue > 0 && ($monthLosses / ($completedRevenue + $monthLosses)) > 0.15) {
                        $lossRate = ($monthLosses / ($completedRevenue + $monthLosses)) * 100;
                        $aiSuggestions[] = [
                            'icon' => '💸',
                            'title' => number_format($lossRate, 0) . '% da receita potencial está sendo perdida',
                            'text' => 'Cancelamentos + no-show custaram ' . money($monthLosses) . ' este mês. Invista em lembretes automáticos e política de cancelamento.',
                            'priority' => 'warning',
                            'action_url' => base_url('vendor/finance'),
                            'action_label' => 'Ver financeiro',
                        ];
                    }

                    // ── CRESCIMENTO: Oportunidades ──
                    if ($totalClients > 5) {
                        $recurringRate = $totalClients > 0 ? ($recurringClients / $totalClients) * 100 : 0;
                        if ($recurringRate >= 40) {
                            $aiSuggestions[] = [
                                'icon' => '🌟',
                                'title' => number_format($recurringRate, 0) . '% dos clientes são recorrentes',
                                'text' => 'Ótima fidelização! Considere criar um programa de fidelidade ou oferecer descontos para indicações.',
                                'priority' => 'success',
                                'action_url' => base_url('vendor/clients'),
                                'action_label' => 'Ver clientes',
                            ];
                        } elseif ($recurringRate < 20) {
                            $aiSuggestions[] = [
                                'icon' => '🔄',
                                'title' => 'Apenas ' . number_format($recurringRate, 0) . '% dos clientes retornam',
                                'text' => 'Envie mensagens de agradecimento pós-atendimento e ofereça desconto na próxima visita para aumentar a recorrência.',
                                'priority' => 'info',
                                'action_url' => base_url('vendor/clients'),
                                'action_label' => 'Ver clientes',
                            ];
                        }
                    }

                    if ($busiestDow > 0 && $busiestCount >= 3) {
                        $aiSuggestions[] = [
                            'icon' => '📊',
                            'title' => ($dowNames[$busiestDow] ?? 'Dia') . ' é seu dia mais movimentado',
                            'text' => 'Com ' . $busiestCount . ' atendimentos nos últimos 90 dias. Considere preços premium neste dia ou abrir horários extras.',
                            'priority' => 'info',
                            'action_url' => base_url('vendor/reports'),
                            'action_label' => 'Ver análise',
                        ];
                    }

                    if ($todayTotal === 0) {
                        $aiSuggestions[] = [
                            'icon' => '📅',
                            'title' => 'Agenda vazia hoje',
                            'text' => 'Compartilhe seu link público no WhatsApp e redes sociais. Agendas vazias são oportunidades de marketing.',
                            'priority' => 'info',
                            'action_url' => null,
                            'action_label' => null,
                        ];
                    }

                    // ── PERFIL: Completude e otimização ──
                    $profileMissing = [];
                    if (!$hasProfileImage) $profileMissing[] = 'foto de perfil';
                    if (!$hasBio) $profileMissing[] = 'bio/descrição';
                    if (!$hasAddress) $profileMissing[] = 'endereço';

                    if ($profileMissing !== []) {
                        $aiSuggestions[] = [
                            'icon' => '👤',
                            'title' => 'Perfil incompleto — falta: ' . implode(', ', $profileMissing),
                            'text' => 'Perfis completos transmitem mais confiança e convertem até 3x mais agendamentos. Adicione as informações que faltam.',
                            'priority' => 'info',
                            'action_url' => base_url('vendor/settings'),
                            'action_label' => 'Completar perfil',
                        ];
                    }

                    if ($serviceCount === 0) {
                        $aiSuggestions[] = [
                            'icon' => '🛠️',
                            'title' => 'Nenhum serviço cadastrado',
                            'text' => 'Sem serviços, clientes não podem agendar online. Cadastre pelo menos um para ativar sua página pública.',
                            'priority' => 'urgent',
                            'action_url' => base_url('vendor/services'),
                            'action_label' => 'Criar serviço',
                        ];
                    }

                    if (!$remindersEnabled) {
                        $aiSuggestions[] = [
                            'icon' => '🔕',
                            'title' => 'Lembretes automáticos desativados',
                            'text' => 'Ative os lembretes para reduzir faltas em até 40%. Seus clientes receberão uma notificação antes do atendimento.',
                            'priority' => 'warning',
                            'action_url' => base_url('vendor/settings'),
                            'action_label' => 'Ativar lembretes',
                        ];
                    }

                    if ($enabledDays > 0 && $enabledDays < 5 && $todayTotal > 0) {
                        $aiSuggestions[] = [
                            'icon' => '📆',
                            'title' => 'Você atende apenas ' . $enabledDays . ' dia(s) por semana',
                            'text' => 'Considere abrir mais dias ou horários estendidos para captar clientes que buscam flexibilidade.',
                            'priority' => 'info',
                            'action_url' => base_url('vendor/settings'),
                            'action_label' => 'Ajustar horários',
                        ];
                    }

                    // ── Fallback ──
                    if ($aiSuggestions === []) {
                        $aiSuggestions[] = [
                            'icon' => '✅',
                            'title' => 'Tudo certo — operação saudável!',
                            'text' => 'Continue compartilhando seu link e mantendo a qualidade do atendimento. Sua operação está bem equilibrada.',
                            'priority' => 'success',
                            'action_url' => null,
                            'action_label' => null,
                        ];
                    }

                    // Sort by priority
                    $priorityOrder = ['urgent' => 0, 'warning' => 1, 'info' => 2, 'success' => 3];
                    usort($aiSuggestions, static fn($a, $b) => ($priorityOrder[$a['priority'] ?? 'info'] ?? 2) <=> ($priorityOrder[$b['priority'] ?? 'info'] ?? 2));
                    ?>
                    <?php foreach (array_slice($aiSuggestions, 0, 5) as $suggestion): ?>
                        <div class="ai-suggestion-item ai-suggestion--<?= e($suggestion['priority'] ?? 'info') ?>">
                            <span class="ai-suggestion-icon"><?= $suggestion['icon'] ?></span>
                            <div class="ai-suggestion-body">
                                <strong><?= e($suggestion['title']) ?></strong>
                                <p><?= e($suggestion['text']) ?></p>
                                <?php if (!empty($suggestion['action_url'])): ?>
                                    <a class="ai-suggestion-action" href="<?= e($suggestion['action_url']) ?>"><?= e($suggestion['action_label'] ?? 'Ver mais') ?> →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="ai-panel-footer">
                    <span class="muted">💡 Análise gerada automaticamente com base nos seus dados dos últimos 30-90 dias.</span>
                </div>
            </div>
        </div>
    </div>
</section>

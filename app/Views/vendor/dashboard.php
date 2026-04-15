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
                    $whatsappShareUrl = 'https://wa.me/?text=' . urlencode('Olá! Conheça meu perfil e agende online: ' . base_url('p/' . $vendor['slug']));
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
        </div>
    </div>
</section>

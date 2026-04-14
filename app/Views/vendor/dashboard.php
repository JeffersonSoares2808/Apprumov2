<section class="stack stack--spacious">
    <div class="card hero hero--dashboard">
        <div class="hero__content">
            <span class="soft-pill soft-pill--gold">Visão geral da operação</span>
            <h1 class="page-title">Seu negócio com leitura rápida, elegante e acionável.</h1>
            <p class="page-subtitle">Acompanhe agenda, caixa potencial, perdas e fila de espera com uma interface mais limpa, mais premium e mais fácil de operar no celular.</p>
        </div>
        <div class="hero__actions inline-actions inline-actions--wrap">
            <a class="btn btn-secondary" href="<?= base_url('vendor/agenda') ?>">Abrir agenda</a>
            <a class="btn btn-light" href="<?= base_url('p/' . $vendor['slug']) ?>" target="_blank" rel="noopener">Ver perfil público</a>
            <button class="btn btn-light" type="button" data-copy-url="<?= e(base_url('p/' . $vendor['slug'])) ?>">Copiar link</button>
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
                <p class="muted">Use este link para captar agendamentos fora do WhatsApp, com visual mais profissional.</p>
                <div class="link-box"><?= e(base_url('p/' . $vendor['slug'])) ?></div>
                <div class="inline-actions inline-actions--wrap">
                    <button class="btn btn-secondary" type="button" data-copy-url="<?= e(base_url('p/' . $vendor['slug'])) ?>">Copiar link</button>
                    <a class="btn btn-light" href="<?= base_url('p/' . $vendor['slug']) ?>" target="_blank" rel="noopener">Abrir página</a>
                </div>
            </div>
        </div>
    </div>
</section>

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
                    <a class="shortcut-tile" href="<?= base_url('vendor/services') ?>"><strong>Serviços</strong><span>Catálogo e duração</span></a>
                    <a class="shortcut-tile" href="<?= base_url('vendor/products') ?>"><strong>Produtos</strong><span>Estoque e vendas</span></a>
                    <a class="shortcut-tile" href="<?= base_url('vendor/finance') ?>"><strong>Financeiro</strong><span>Recebimentos e perdas</span></a>
                    <a class="shortcut-tile" href="<?= base_url('vendor/reports') ?>"><strong>Relatórios</strong><span>Indicadores do período</span></a>
                    <a class="shortcut-tile" href="<?= base_url('vendor/clients') ?>"><strong>Clientes</strong><span>Relacionamento e recorrência</span></a>
                    <a class="shortcut-tile" href="<?= base_url('vendor/settings') ?>"><strong>Configurações</strong><span>Marca, horários e perfil</span></a>
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

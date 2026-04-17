<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Relacionamento</span>
                <h1 class="page-title">Seus clientes em uma visão muito mais limpa.</h1>
                <p class="page-subtitle">Lista gerada automaticamente a partir dos agendamentos, com foco em recorrência, gasto total e contato rápido.</p>
            </div>
            <div class="soft-pill"><?= count($clients) ?> cliente(s)</div>
        </div>

        <!-- Quick Search -->
        <div class="quick-search" data-quick-search="[data-search-item]">
            <span class="quick-search__icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </span>
            <input class="quick-search__input" type="search" placeholder="Buscar paciente por nome ou telefone..." data-search-input autocomplete="off">
            <span class="quick-search__count" data-search-count></span>
            <button class="quick-search__clear" type="button" data-search-clear aria-label="Limpar busca">×</button>
        </div>
    </div>

    <div class="search-no-results" data-search-no-results>
        <span class="empty-state__icon" aria-hidden="true">🔍</span>
        <p>Nenhum cliente encontrado com esse termo.</p>
    </div>

    <div class="client-list client-list--premium">
        <?php foreach ($clients as $client): ?>
            <article class="client-item client-item--premium" data-search-item data-search-text="<?= e($client['name'] . ' ' . $client['phone']) ?>">
                <div class="client-head">
                    <div class="client-identity">
                        <div class="avatar avatar--mini"><?= e(vendor_initials($client['name'])) ?></div>
                        <div>
                            <strong><?= e($client['name']) ?></strong><br>
                            <span class="muted"><?= e($client['phone']) ?></span>
                        </div>
                    </div>
                    <a class="btn btn-light" href="<?= e(whatsapp_link($client['phone'], 'Olá, ' . $client['name'] . '!')) ?>" target="_blank" rel="noopener">WhatsApp</a>
                </div>
                <div class="dashboard-kpis dashboard-kpis--premium dashboard-kpis--compact">
                    <div class="kpi kpi--premium"><small>Visitas</small><strong><?= (int) $client['visit_count'] ?></strong></div>
                    <div class="kpi kpi--premium"><small>Total gasto</small><strong><?= money($client['total_spent']) ?></strong></div>
                    <div class="kpi kpi--premium"><small>Última visita</small><strong><?= format_date($client['last_visit']) ?></strong></div>
                </div>
            </article>
        <?php endforeach; ?>

        <?php if ($clients === []): ?>
            <div class="empty-state empty-state--premium">Os clientes aparecerão aqui conforme os agendamentos forem sendo registrados.</div>
        <?php endif; ?>
    </div>
</section>

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
    </div>

    <div class="client-list client-list--premium">
        <?php foreach ($clients as $client): ?>
            <article class="client-item client-item--premium">
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

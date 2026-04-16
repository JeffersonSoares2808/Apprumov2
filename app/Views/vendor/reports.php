<style>
@media print {
    .app-shell .container > *:not(main) { display: none !important; }
    .no-print, .report-filter-grid, form.form-grid { display: none !important; }
    body, .app-body, .app-shell, .container, main { margin: 0 !important; padding: 0 !important; }
    .card { box-shadow: none !important; border: 1px solid #ddd !important; break-inside: avoid; }
    .kpi { border: 1px solid #ddd !important; }
    .bar-fill { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .badge { border: 1px solid #999 !important; }
    @page { margin: 1.5cm; }
}
</style>
<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Inteligência do negócio</span>
                <h1 class="page-title">Relatórios com foco no que realmente gera resultado.</h1>
                <p class="page-subtitle">Analise conclusão, receita, ticket médio e perdas em um intervalo customizado, sem depender de ferramentas externas.</p>
            </div>
            <div class="no-print" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <a class="btn btn-light" href="<?= base_url('vendor/reports/professionals') ?>">📋 Relatório por profissional</a>
                <button class="btn btn-light" onclick="window.print();" type="button">🖨️ Imprimir</button>
            </div>
        </div>

        <div class="no-print">
            <form class="form-grid two form-grid--premium report-filter-grid" method="get" action="<?= base_url('vendor/reports') ?>">
                <div class="field">
                    <label for="start_date">Data inicial</label>
                    <input id="start_date" name="start_date" type="date" value="<?= e($report['start_date']) ?>">
                </div>
                <div class="field">
                    <label for="end_date">Data final</label>
                    <input id="end_date" name="end_date" type="date" value="<?= e($report['end_date']) ?>">
                </div>
                <button class="btn" type="submit">Aplicar filtro</button>
            </form>
        </div>

        <p style="margin-top: 0.5rem;"><strong>Período:</strong> <?= format_date($report['start_date']) ?> a <?= format_date($report['end_date']) ?></p>
    </div>

    <div class="dashboard-kpis dashboard-kpis--premium">
        <div class="kpi kpi--premium"><small>Total de agendamentos</small><strong><?= (int) $report['kpis']['total_appointments'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Taxa de conclusão</small><strong><?= number_format((float) $report['kpis']['completion_rate'], 1, ',', '.') ?>%</strong></div>
        <div class="kpi kpi--premium"><small>Receita total</small><strong><?= money($report['kpis']['total_revenue']) ?></strong></div>
        <div class="kpi kpi--premium"><small>Ticket médio</small><strong><?= money($report['kpis']['average_ticket']) ?></strong></div>
        <div class="kpi kpi--premium"><small>Cancelamentos</small><strong><?= (int) $report['kpis']['cancelled_appointments'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Perdas financeiras</small><strong><?= money($report['kpis']['financial_losses']) ?></strong></div>
    </div>

    <div class="card card--section">
        <div class="section-header section-header--premium">
            <div>
                <span class="section-kicker">Performance por serviço</span>
                <h2>Receita em barras</h2>
                <p class="muted">Uma leitura simples para descobrir o que vende mais e o que precisa de ajuste.</p>
            </div>
        </div>

        <div class="chart-bars chart-bars--premium">
            <?php foreach ($report['service_revenue'] as $row): ?>
                <div class="bar-row bar-row--premium">
                    <span><?= e($row['title']) ?></span>
                    <div class="bar-track">
                        <div class="bar-fill" style="width: <?= max(6, (float) $row['percentage']) ?>%;"></div>
                    </div>
                    <strong><?= money($row['total']) ?></strong>
                </div>
            <?php endforeach; ?>

            <?php if ($report['service_revenue'] === []): ?>
                <div class="empty-state empty-state--premium">Sem receita concluída por serviço no período.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

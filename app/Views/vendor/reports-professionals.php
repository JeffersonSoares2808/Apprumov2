<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Relatório diário</span>
                <h1 class="page-title">Pagamentos por profissional</h1>
                <p class="page-subtitle">Resumo de atendimentos, receita, comissão e taxas de cartão do dia selecionado.</p>
            </div>
            <a class="btn btn-light" href="<?= base_url('vendor/reports') ?>">← Relatório geral</a>
        </div>

        <form class="form-grid form-grid--premium" method="get" action="<?= base_url('vendor/reports/professionals') ?>" style="max-width: 400px;">
            <div class="field">
                <label for="report_date">Selecionar dia</label>
                <div style="display: flex; gap: 0.5rem;">
                    <input id="report_date" name="date" type="date" value="<?= e($report['date']) ?>" style="flex: 1;">
                    <button class="btn" type="submit">Filtrar</button>
                </div>
            </div>
        </form>

        <div class="inline-actions inline-actions--wrap" style="margin-top: 0.5rem;">
            <a class="btn btn-light" href="<?= base_url('vendor/reports/professionals?date=' . date('Y-m-d', strtotime($report['date'] . ' -1 day'))) ?>">← Dia anterior</a>
            <span class="soft-pill soft-pill--gold"><?= format_date($report['date'], 'd/m/Y (l)') ?></span>
            <a class="btn btn-light" href="<?= base_url('vendor/reports/professionals?date=' . date('Y-m-d', strtotime($report['date'] . ' +1 day'))) ?>">Próximo dia →</a>
        </div>
    </div>

    <!-- Totals KPIs -->
    <div class="dashboard-kpis dashboard-kpis--premium">
        <div class="kpi kpi--premium"><small>Total atendimentos</small><strong><?= (int) $report['totals']['total_appointments'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Concluídos</small><strong><?= (int) $report['totals']['completed'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Receita bruta</small><strong><?= money($report['totals']['total_revenue']) ?></strong></div>
        <div class="kpi kpi--premium"><small>Taxas de cartão</small><strong><?= money($report['totals']['total_card_fees']) ?></strong></div>
        <div class="kpi kpi--premium"><small>Comissões</small><strong><?= money($report['totals']['total_commission']) ?></strong></div>
        <div class="kpi kpi--premium"><small>Receita líquida</small><strong><?= money($report['totals']['total_net']) ?></strong></div>
    </div>

    <!-- Per-Professional breakdown -->
    <?php foreach ($report['professionals'] as $prof): ?>
        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker" style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:<?= e($prof['color']) ?>;"></span>
                        <?= e($prof['name'] ?: 'Sem profissional') ?>
                    </span>
                    <h2 style="margin-top: 0.25rem;">
                        <?= (int) $prof['completed'] ?> concluído(s) &middot;
                        <?= money($prof['revenue']) ?> receita
                        <?php if ($prof['commission_rate'] > 0): ?>
                            &middot; <?= number_format($prof['commission_rate'], 1, ',', '.') ?>% comissão = <?= money($prof['commission']) ?>
                        <?php endif; ?>
                        <?php if ($prof['card_fees'] > 0): ?>
                            &middot; Taxa cartão: <?= money($prof['card_fees']) ?>
                        <?php endif; ?>
                    </h2>
                </div>
            </div>

            <div class="table-wrap table-wrap--premium">
                <table>
                    <thead>
                        <tr>
                            <th>Horário</th>
                            <th>Serviço</th>
                            <th>Cliente</th>
                            <th>Forma Pgto</th>
                            <th>Valor</th>
                            <th>Taxa cartão</th>
                            <th>Comissão</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prof['appointments'] as $appt): ?>
                            <tr>
                                <td><?= e($appt['start_time']) ?> - <?= e($appt['end_time']) ?></td>
                                <td><?= e($appt['service_title']) ?></td>
                                <td><?= e($appt['customer_name']) ?></td>
                                <td>
                                    <?php
                                        $pmLabels = ['cash' => '💵 Dinheiro', 'card' => '💳 Cartão', 'pix' => '📱 PIX', 'other' => '🔄 Outro'];
                                        $pm = $appt['payment_method'] ?? null;
                                        echo $pm ? e($pmLabels[$pm] ?? $pm) : '<span class="muted">—</span>';
                                    ?>
                                </td>
                                <td><?= money($appt['price']) ?></td>
                                <td><?= $appt['card_fee'] > 0 ? money($appt['card_fee']) : '<span class="muted">—</span>' ?></td>
                                <td><?= $appt['commission'] > 0 ? money($appt['commission']) : '<span class="muted">—</span>' ?></td>
                                <td><span class="badge <?= status_class($appt['status']) ?>"><?= e(status_label($appt['status'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($prof['appointments'])): ?>
                            <tr>
                                <td colspan="8"><div class="empty-state empty-state--premium">Nenhum atendimento.</div></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($prof['appointments'])): ?>
                    <tfoot>
                        <tr style="font-weight: bold;">
                            <td colspan="4">Subtotal</td>
                            <td><?= money($prof['revenue']) ?></td>
                            <td><?= $prof['card_fees'] > 0 ? money($prof['card_fees']) : '—' ?></td>
                            <td><?= $prof['commission'] > 0 ? money($prof['commission']) : '—' ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($report['professionals'])): ?>
        <div class="card card--section">
            <div class="empty-state empty-state--premium">Nenhum atendimento registrado neste dia.</div>
        </div>
    <?php endif; ?>
</section>

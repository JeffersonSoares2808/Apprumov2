<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Saúde financeira</span>
                <h1 class="page-title">Receitas, perdas e valores em aberto com leitura rápida.</h1>
                <p class="page-subtitle">KPIs mensais com agendamentos e vendas de produtos no mesmo painel para facilitar a tomada de decisão.</p>
            </div>
            <div class="inline-actions inline-actions--wrap">
                <a class="btn btn-light" href="<?= base_url('vendor/finance?month=' . $finance['previous_month']) ?>">Mês anterior</a>
                <a class="btn btn-light" href="<?= base_url('vendor/finance?month=' . $finance['next_month']) ?>">Próximo mês</a>
            </div>
        </div>
        <div class="soft-pill soft-pill--gold"><?= e(ucfirst((string) $finance['month_label'])) ?></div>
    </div>

    <div class="dashboard-kpis dashboard-kpis--premium">
        <div class="kpi kpi--premium"><small>Total recebido</small><strong><?= money($finance['kpis']['total_received'] ?? 0) ?></strong><span class="muted">Receitas já realizadas.</span></div>
        <div class="kpi kpi--premium"><small>A receber</small><strong><?= money($finance['kpis']['total_open'] ?? 0) ?></strong><span class="muted">Entradas ainda em aberto.</span></div>
        <div class="kpi kpi--premium"><small>Perdas</small><strong><?= money($finance['kpis']['total_losses'] ?? 0) ?></strong><span class="muted">Cancelamentos e faltas.</span></div>
        <div class="kpi kpi--premium"><small>📅 Serviços</small><strong><?= money($finance['kpis']['service_revenue'] ?? 0) ?></strong><span class="muted">Receita de atendimentos.</span></div>
        <div class="kpi kpi--premium"><small>📦 Produtos</small><strong><?= money($finance['kpis']['product_revenue'] ?? 0) ?></strong><span class="muted">Receita de vendas de produtos.</span></div>
    </div>

    <div class="card card--section">
        <div class="section-header section-header--premium">
            <div>
                <span class="section-kicker">Movimentações</span>
                <h2>Detalhamento do mês</h2>
                <p class="muted">Agendamentos e vendas com ações rápidas sempre que houver saldo em aberto.</p>
            </div>
        </div>

        <div class="table-wrap table-wrap--premium">
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Tipo</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($finance['transactions'] as $transaction): ?>
                        <tr>
                            <td><?= format_date($transaction['transaction_date']) ?></td>
                            <td>
                                <strong><?= e($transaction['title']) ?></strong><br>
                                <span class="muted"><?= e($transaction['description']) ?></span>
                                <?php if ($transaction['source'] === 'product_sale'): ?>
                                    <br><span class="badge is-neutral">📦 Produto</span>
                                <?php elseif ($transaction['source'] === 'appointment'): ?>
                                    <br><span class="badge is-neutral">📅 Atendimento</span>
                                <?php else: ?>
                                    <br><span class="badge is-neutral">✏️ Manual</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($transaction['kind'] === 'loss' ? 'Perda' : 'Receita') ?></td>
                            <td><?= money($transaction['amount']) ?></td>
                            <td><span class="badge <?= status_class($transaction['status']) ?>"><?= e(status_label($transaction['status'])) ?></span></td>
                            <td>
                                <div class="inline-actions inline-actions--wrap">
                                    <?php if ((int) ($transaction['appointment_id'] ?? 0) > 0 && $transaction['status'] === 'open'): ?>
                                        <form method="post" action="<?= base_url('vendor/finance/appointments/' . $transaction['appointment_id'] . '/pay') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="month" value="<?= e($finance['month']) ?>">
                                            <button class="btn btn-success" type="submit">Marcar pago</button>
                                        </form>
                                        <form method="post" action="<?= base_url('vendor/finance/appointments/' . $transaction['appointment_id'] . '/no-show') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="month" value="<?= e($finance['month']) ?>">
                                            <button class="btn btn-light" type="submit">Registrar falta</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted">Sem ação rápida</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if ($finance['transactions'] === []): ?>
                        <tr>
                            <td colspan="6"><div class="empty-state empty-state--premium">Sem movimentações no período.</div></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

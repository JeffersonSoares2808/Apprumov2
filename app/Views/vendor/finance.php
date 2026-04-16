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
                <a class="btn btn-light" href="<?= base_url('vendor/reports/professionals') ?>">📋 Relatório profissionais</a>
                <button class="btn btn-light finance-print-btn" onclick="window.print();" type="button">🖨️ Imprimir</button>
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
                        <th>Forma Pgto</th>
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
                            <td>
                                <?php
                                    $pmLabels = ['cash' => '💵 Dinheiro', 'card' => '💳 Cartão', 'pix' => '📱 PIX', 'other' => '🔄 Outro'];
                                    $pm = $transaction['payment_method'] ?? null;
                                    echo $pm ? e($pmLabels[$pm] ?? $pm) : '<span class="muted">—</span>';
                                ?>
                            </td>
                            <td>
                                <?= money($transaction['amount']) ?>
                                <?php if ((float) ($transaction['card_fee'] ?? 0) > 0): ?>
                                    <br><small class="muted">Taxa: <?= money($transaction['card_fee']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= status_class($transaction['status']) ?>"><?= e(status_label($transaction['status'])) ?></span></td>
                            <td>
                                <div class="inline-actions inline-actions--wrap">
                                    <?php if ((int) ($transaction['appointment_id'] ?? 0) > 0 && $transaction['status'] === 'open'): ?>
                                                <form method="post" action="<?= base_url('vendor/finance/appointments/' . $transaction['appointment_id'] . '/pay') ?>" class="pay-form">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="month" value="<?= e($finance['month']) ?>">
                                            <div style="display: flex; gap: 0.5rem; align-items: flex-end; flex-wrap: wrap;">
                                                <div style="min-width: 110px;">
                                                    <label class="muted" style="font-size:0.75rem;">Forma de pagamento</label>
                                                    <select name="payment_method" class="pm-select" style="width:100%;">
                                                        <option value="cash">💵 Dinheiro</option>
                                                        <option value="pix">📱 PIX</option>
                                                        <option value="card">💳 Cartão</option>
                                                        <option value="other">🔄 Outro</option>
                                                    </select>
                                                </div>
                                                <div class="card-fee-field" style="display:none; min-width: 100px;">
                                                    <label class="muted" style="font-size:0.75rem;">Taxa cartão (R$)</label>
                                                    <input type="number" name="card_fee" step="0.01" min="0" value="0" placeholder="0,00" style="width:100%;">
                                                </div>
                                                <button class="btn btn-success" type="submit">Marcar pago</button>
                                            </div>
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
                            <td colspan="7"><div class="empty-state empty-state--premium">Sem movimentações no período.</div></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.pm-select').forEach(function(sel){
    sel.addEventListener('change', function(){
        var feeField = this.closest('.pay-form').querySelector('.card-fee-field');
        if(feeField){
            feeField.style.display = this.value === 'card' ? '' : 'none';
            if(this.value !== 'card'){
                var input = feeField.querySelector('input[name="card_fee"]');
                if(input) input.value = '0';
            }
        }
    });
});
</script>

<?php
$appointmentTotals = [
    'all' => count($agenda['appointments']),
    'confirmed' => 0,
    'completed' => 0,
    'cancelled' => 0,
    'no_show' => 0,
];

foreach ($agenda['appointments'] as $appointmentItem) {
    $statusKey = (string) ($appointmentItem['status'] ?? '');
    if (isset($appointmentTotals[$statusKey])) {
        $appointmentTotals[$statusKey]++;
    }
}
?>

<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Agenda da semana</span>
                <h1 class="page-title">Controle o dia com clareza e ação rápida.</h1>
                <p class="page-subtitle">Semana de <?= format_date($agenda['selected_date']) ?> com navegação dom–sab, slots dinâmicos por serviço e lembretes prontos para WhatsApp.</p>
            </div>
            <div class="inline-actions inline-actions--wrap">
                <a class="btn btn-light" href="<?= base_url('vendor/agenda?date=' . date('Y-m-d')) ?>">Hoje</a>
                <button class="btn btn-secondary" type="button" data-copy-url="<?= e(base_url('vendor/agenda?date=' . $agenda['selected_date'])) ?>">Copiar link desta visão</button>
            </div>
        </div>

        <div class="day-strip day-strip--premium">
            <?php foreach ($agenda['week_strip'] as $day): ?>
                <a class="day-chip <?= $day['is_active'] ? 'is-active' : '' ?> <?= $day['is_today'] ? 'is-today' : '' ?>" href="<?= base_url('vendor/agenda?date=' . $day['date']) ?>">
                    <strong><?= e($day['label']) ?></strong>
                    <span><?= e($day['day_number']) ?></span>
                    <?php if ($day['is_today']): ?><em>Hoje</em><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="dashboard-kpis dashboard-kpis--premium dashboard-kpis--compact">
        <div class="kpi kpi--premium"><small>Agendados no dia</small><strong><?= (int) $appointmentTotals['all'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Confirmados</small><strong><?= (int) $appointmentTotals['confirmed'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Concluídos</small><strong><?= (int) $appointmentTotals['completed'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Fila de espera</small><strong><?= count($agenda['waiting_list']) ?></strong></div>
    </div>

    <div class="app-grid two agenda-grid--premium">
        <div class="stack">
            <div class="card card--section">
                <div class="section-header section-header--premium">
                    <div>
                        <span class="section-kicker">Novo encaixe</span>
                        <h2>Criar agendamento</h2>
                        <p class="muted">Os horários abaixo já respeitam duração do serviço e agenda ocupada.</p>
                    </div>
                </div>

                <form class="form-grid form-grid--premium" method="post" action="<?= base_url('vendor/appointments') ?>" data-disable-on-submit data-slot-source='<?= e(json_encode($service_slots, JSON_UNESCAPED_UNICODE)) ?>'>
                    <?= csrf_field() ?>
                    <input type="hidden" name="appointment_date" value="<?= e($agenda['selected_date']) ?>">
                    <div class="form-grid two">
                        <div class="field">
                            <label for="customer_name">Cliente</label>
                            <input id="customer_name" name="customer_name" type="text" required placeholder="Nome completo">
                        </div>
                        <div class="field">
                            <label for="customer_phone">Telefone</label>
                            <input id="customer_phone" name="customer_phone" type="text" required placeholder="WhatsApp do cliente">
                        </div>
                    </div>
                    <div class="form-grid two">
                        <div class="field">
                            <label for="service_id">Serviço</label>
                            <select id="service_id" name="service_id" data-slot-service required>
                                <option value="">Selecione</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?= (int) $service['id'] ?>"><?= e($service['title']) ?> · <?= (int) $service['duration_minutes'] ?> min</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label for="start_time">Horário disponível</label>
                            <select id="start_time" name="start_time" data-slot-target required>
                                <option value="">Escolha um serviço</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn" type="submit" data-loading-label="Gravando...">Gravar agendamento</button>
                </form>
            </div>

            <div class="card card--section">
                <div class="section-header section-header--premium">
                    <div>
                        <span class="section-kicker">Fila de espera</span>
                        <h2>Clientes para encaixe</h2>
                        <p class="muted"><?= count($agenda['waiting_list']) ?> pessoa(s) aguardando nesta data.</p>
                    </div>
                </div>

                <form class="form-grid form-grid--premium" method="post" action="<?= base_url('vendor/waiting-list') ?>" data-disable-on-submit>
                    <?= csrf_field() ?>
                    <input type="hidden" name="desired_date" value="<?= e($agenda['selected_date']) ?>">
                    <div class="form-grid two">
                        <div class="field">
                            <label for="waiting_name">Nome</label>
                            <input id="waiting_name" name="customer_name" type="text" required>
                        </div>
                        <div class="field">
                            <label for="waiting_phone">Telefone</label>
                            <input id="waiting_phone" name="customer_phone" type="text" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="waiting_service">Serviço desejado</label>
                        <select id="waiting_service" name="service_id">
                            <option value="">Sem preferência</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= (int) $service['id'] ?>"><?= e($service['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-light" type="submit" data-loading-label="Adicionando...">Adicionar à fila</button>
                </form>

                <div class="stack stack--compact waitlist-stack">
                    <?php foreach ($agenda['waiting_list'] as $entry): ?>
                        <article class="service-item service-item--compact">
                            <div class="service-head">
                                <div>
                                    <strong><?= e($entry['customer_name']) ?></strong><br>
                                    <span class="muted"><?= e($entry['customer_phone']) ?></span><br>
                                    <span class="muted"><?= e($entry['service_title'] ?: 'Sem serviço definido') ?></span>
                                </div>
                                <div class="inline-actions inline-actions--wrap">
                                    <a class="btn btn-light" href="<?= e(whatsapp_link($entry['customer_phone'], 'Olá! Abriu uma vaga para o dia ' . format_date($agenda['selected_date']) . '. Quer confirmar seu horário?')) ?>" target="_blank" rel="noopener">Notificar</a>
                                    <form method="post" action="<?= base_url('vendor/waiting-list/' . $entry['id'] . '/delete') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="redirect_date" value="<?= e($agenda['selected_date']) ?>">
                                        <button class="btn btn-danger" type="submit">Remover</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>

                    <?php if ($agenda['waiting_list'] === []): ?>
                        <div class="empty-state empty-state--premium">Sem clientes na fila nesta data.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card card--section card--timeline">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Operação do dia</span>
                    <h2>Agendamentos</h2>
                    <p class="muted"><?= format_date($agenda['selected_date']) ?> ordenado por horário, com ações rápidas de status.</p>
                </div>
            </div>

            <?php if ($agenda['appointments'] === []): ?>
                <div class="empty-state empty-state--premium">Nenhum agendamento para esta data. Use o formulário ao lado para registrar um atendimento manual.</div>
            <?php else: ?>
                <div class="stack stack--compact">
                    <?php foreach ($agenda['appointments'] as $item): ?>
                        <article class="appointment-card appointment-card--timeline">
                            <div class="appointment-card__time appointment-card__time--large">
                                <strong><?= format_time($item['start_time']) ?></strong>
                                <span><?= (int) $item['duration_minutes'] ?> min</span>
                                <em><?= money($item['price']) ?></em>
                            </div>
                            <div class="appointment-card__body appointment-card__body--rich">
                                <div class="service-head">
                                    <div>
                                        <strong><?= e($item['customer_name']) ?></strong>
                                        <div class="muted"><?= e($item['service_title'] ?? 'Serviço') ?></div>
                                    </div>
                                    <span class="badge <?= status_class($item['status']) ?>"><?= e(status_label($item['status'])) ?></span>
                                </div>
                                <p class="muted appointment-card__note">Telefone: <?= e($item['customer_phone']) ?></p>
                                <div class="appointment-actions appointment-actions--wrap">
                                    <?php foreach (['confirmed' => 'Confirmar', 'completed' => 'Completar', 'cancelled' => 'Cancelar', 'no_show' => 'No-show'] as $statusKey => $statusLabel): ?>
                                        <form method="post" action="<?= base_url('vendor/appointments/' . $item['id'] . '/status') ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="status" value="<?= e($statusKey) ?>">
                                            <input type="hidden" name="redirect_date" value="<?= e($agenda['selected_date']) ?>">
                                            <button class="btn <?= $statusKey === 'completed' ? 'btn-success' : 'btn-light' ?>" type="submit"><?= e($statusLabel) ?></button>
                                        </form>
                                    <?php endforeach; ?>

                                    <a class="btn btn-light" href="<?= e(whatsapp_link($item['customer_phone'], 'Olá, ' . $item['customer_name'] . '! Passando para lembrar do seu atendimento em ' . format_date($item['appointment_date']) . ' às ' . format_time($item['start_time']) . '.')) ?>" target="_blank" rel="noopener">Lembrete</a>

                                    <form method="post" action="<?= base_url('vendor/appointments/' . $item['id'] . '/delete') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="redirect_date" value="<?= e($agenda['selected_date']) ?>">
                                        <button class="btn btn-danger" type="submit">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

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

$cal = $agenda['month_calendar'];
$weekDayHeaders = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
?>

<section class="stack stack--spacious">
    <!-- Header com navegação mês/semana/dia -->
    <div class="card card--section" data-animate>
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Agenda profissional</span>
                <h1 class="page-title">Sua agenda completa, sem limites.</h1>
                <p class="page-subtitle">Navegue por qualquer data — mês, semana ou dia — com calendário visual e impressão de atendimentos.</p>
            </div>
            <div class="inline-actions inline-actions--wrap">
                <a class="btn btn-light" href="<?= base_url('vendor/agenda?date=' . date('Y-m-d')) ?>">Hoje</a>
                <button class="btn btn-light" type="button" onclick="window.print()">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;" aria-hidden="true"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    Imprimir dia
                </button>
                <button class="btn btn-secondary" type="button" data-copy-url="<?= e(base_url('vendor/agenda?date=' . $agenda['selected_date'])) ?>">Copiar link desta visão</button>
            </div>
        </div>

        <!-- Navegação: setas mês e semana -->
        <div class="agenda-nav" data-animate>
            <div class="agenda-nav__month">
                <a class="agenda-nav__arrow" href="<?= base_url('vendor/agenda?date=' . $cal['prev_month_date']) ?>" aria-label="Mês anterior">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                </a>
                <strong class="agenda-nav__label"><?= e($cal['month_label']) ?></strong>
                <a class="agenda-nav__arrow" href="<?= base_url('vendor/agenda?date=' . $cal['next_month_date']) ?>" aria-label="Próximo mês">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 18l6-6-6-6"/></svg>
                </a>
            </div>
            <div class="agenda-nav__week">
                <a class="btn btn-light btn-sm" href="<?= base_url('vendor/agenda?date=' . $cal['prev_week_date']) ?>">← Semana anterior</a>
                <a class="btn btn-light btn-sm" href="<?= base_url('vendor/agenda?date=' . $cal['next_week_date']) ?>">Próxima semana →</a>
            </div>
        </div>

        <!-- Calendário do mês completo -->
        <div class="month-calendar" data-animate>
            <div class="month-calendar__header">
                <?php foreach ($weekDayHeaders as $header): ?>
                    <span class="month-calendar__day-label"><?= $header ?></span>
                <?php endforeach; ?>
            </div>
            <?php foreach ($cal['weeks'] as $week): ?>
                <div class="month-calendar__row">
                    <?php foreach ($week as $cell): ?>
                        <?php if ($cell === null): ?>
                            <span class="month-calendar__cell month-calendar__cell--empty"></span>
                        <?php else: ?>
                            <a class="month-calendar__cell <?= $cell['is_selected'] ? 'is-selected' : '' ?> <?= $cell['is_today'] ? 'is-today' : '' ?> <?= $cell['appointment_count'] > 0 ? 'has-appointments' : '' ?>"
                               href="<?= base_url('vendor/agenda?date=' . $cell['date']) ?>">
                                <span class="month-calendar__day-num"><?= $cell['day'] ?></span>
                                <?php if ($cell['appointment_count'] > 0): ?>
                                    <span class="month-calendar__count"><?= $cell['appointment_count'] ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Week strip rápido para a semana corrente -->
        <div class="day-strip day-strip--premium" data-animate>
            <?php foreach ($agenda['week_strip'] as $day): ?>
                <a class="day-chip <?= $day['is_active'] ? 'is-active' : '' ?> <?= $day['is_today'] ? 'is-today' : '' ?>" href="<?= base_url('vendor/agenda?date=' . $day['date']) ?>">
                    <strong><?= e($day['label']) ?></strong>
                    <span><?= e($day['day_number']) ?></span>
                    <?php if ($day['is_today']): ?><em>Hoje</em><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- KPIs do dia -->
    <div class="dashboard-kpis dashboard-kpis--premium dashboard-kpis--compact" data-animate>
        <div class="kpi kpi--premium"><small>Agendados no dia</small><strong><?= (int) $appointmentTotals['all'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Confirmados</small><strong><?= (int) $appointmentTotals['confirmed'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Concluídos</small><strong><?= (int) $appointmentTotals['completed'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Cancelados / No-show</small><strong><?= (int) $appointmentTotals['cancelled'] + (int) $appointmentTotals['no_show'] ?></strong></div>
        <div class="kpi kpi--premium"><small>Fila de espera</small><strong><?= count($agenda['waiting_list']) ?></strong></div>
    </div>

    <!-- Grid: formulários + timeline -->
    <div class="app-grid two agenda-grid--premium">
        <div class="stack">
            <div class="card card--section" data-animate>
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

            <div class="card card--section" data-animate>
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

        <!-- Timeline do dia -->
        <div class="card card--section card--timeline" data-animate>
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Operação do dia</span>
                    <h2>Agendamentos — <?= format_date($agenda['selected_date']) ?></h2>
                    <p class="muted">Ordenado por horário, com ações rápidas de status e impressão sequencial.</p>
                </div>
            </div>

            <?php if ($agenda['appointments'] === []): ?>
                <div class="empty-state empty-state--premium">Nenhum agendamento para esta data. Use o formulário ao lado para registrar um atendimento manual.</div>
            <?php else: ?>
                <div class="stack stack--compact">
                    <?php
                    $seq = 0;
                    foreach ($agenda['appointments'] as $item):
                        $seq++;
                    ?>
                        <article class="appointment-card appointment-card--timeline" data-animate>
                            <div class="appointment-card__seq"><?= $seq ?></div>
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
                                        <?php if (!empty($item['professional_name'])): ?>
                                            <div class="muted" style="font-size:0.75rem;">👤 <?= e($item['professional_name']) ?></div>
                                        <?php endif; ?>
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

<!-- ═══════ ÁREA DE IMPRESSÃO — sequência de atendimentos do dia ═══════ -->
<div class="print-sheet" aria-hidden="true">
    <div class="print-sheet__header">
        <div>
            <strong class="print-sheet__title"><?= e($vendor['business_name'] ?? 'Apprumo') ?></strong>
            <span class="print-sheet__subtitle"><?= e($vendor['category'] ?? '') ?></span>
        </div>
        <div class="print-sheet__date">
            <strong>Agenda do dia</strong>
            <span><?= format_date($agenda['selected_date'], 'd/m/Y') ?></span>
        </div>
    </div>

    <?php if ($agenda['appointments'] === []): ?>
        <p class="print-sheet__empty">Nenhum atendimento agendado para este dia.</p>
    <?php else: ?>
        <table class="print-sheet__table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Horário</th>
                    <th>Cliente</th>
                    <th>Telefone</th>
                    <th>Serviço</th>
                    <th>Duração</th>
                    <th>Valor</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $printSeq = 0;
                $printTotal = 0;
                foreach ($agenda['appointments'] as $item):
                    $printSeq++;
                    if (in_array($item['status'], ['confirmed', 'completed'], true)) {
                        $printTotal += (float) $item['price'];
                    }
                ?>
                    <tr>
                        <td class="print-sheet__seq"><?= $printSeq ?></td>
                        <td><?= format_time($item['start_time']) ?> – <?= format_time($item['end_time'] ?? '') ?></td>
                        <td><strong><?= e($item['customer_name']) ?></strong></td>
                        <td><?= e($item['customer_phone']) ?></td>
                        <td><?= e($item['service_title'] ?? 'Serviço') ?></td>
                        <td><?= (int) $item['duration_minutes'] ?> min</td>
                        <td><?= money($item['price']) ?></td>
                        <td><?= e(status_label($item['status'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="6" style="text-align:right;"><strong>Total previsto (confirmados + concluídos):</strong></td>
                    <td colspan="2"><strong><?= money($printTotal) ?></strong></td>
                </tr>
                <tr>
                    <td colspan="8" style="text-align:center; font-size:11px; color:#888;">
                        Total: <?= $printSeq ?> · Confirmados: <?= $appointmentTotals['confirmed'] ?> · Concluídos: <?= $appointmentTotals['completed'] ?> · Cancelados: <?= $appointmentTotals['cancelled'] ?> · No-show: <?= $appointmentTotals['no_show'] ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>

    <div class="print-sheet__footer">
        <p>Impresso em <?= date('d/m/Y H:i') ?> — <?= e($vendor['business_name'] ?? 'Apprumo') ?> · Desenvolvido por JS Sistemas Inteligentes</p>
    </div>
</div>

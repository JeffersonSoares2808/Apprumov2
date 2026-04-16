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
                            <input id="customer_name" name="customer_name" type="text" required placeholder="Nome completo" list="client-list" autocomplete="off">
                            <datalist id="client-list">
                                <?php foreach ($clients as $cl): ?>
                                    <option value="<?= e($cl['name']) ?>" data-phone="<?= e($cl['phone']) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
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
                    <?php if (!empty($professionals)): ?>
                    <div class="field">
                        <label for="professional_id">Profissional</label>
                        <select id="professional_id" name="professional_id">
                            <option value="">— Sem profissional —</option>
                            <?php foreach ($professionals as $prof): ?>
                                <option value="<?= (int) $prof['id'] ?>"><?= e($prof['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
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
                                    <button class="btn btn-success btn-animated" type="button" data-fill-appointment data-fill-name="<?= e($entry['customer_name']) ?>" data-fill-phone="<?= e($entry['customer_phone']) ?>" title="Preencher formulário de agendamento com dados deste cliente">⚡ Encaixar</button>
                                    <a class="btn btn-whatsapp" href="<?= e(whatsapp_link($entry['customer_phone'], 'Olá, ' . $entry['customer_name'] . '! Abriu uma vaga para o dia ' . format_date($agenda['selected_date']) . '. Quer confirmar seu horário? 📅')) ?>" target="_blank" rel="noopener">📱 Notificar</a>
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

        <!-- Timeline visual do dia — horários ocupados + livres -->
        <div class="card card--section card--timeline" data-animate>
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Visão do dia</span>
                    <h2>📅 <?php
                        $dayNames = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
                        $dow = (int) date('w', strtotime($agenda['selected_date']));
                        echo format_date($agenda['selected_date'], 'd/m/Y') . ' (' . $dayNames[$dow] . ')';
                    ?></h2>
                    <p class="muted">Todos os horários do dia. Clique em um horário livre para agendar.</p>
                </div>
            </div>

            <?php if (empty($timeline)): ?>
                <div class="empty-state empty-state--premium">
                    <p>Sem expediente neste dia. Verifique o horário de funcionamento nas configurações.</p>
                </div>
            <?php else: ?>
                <div class="day-timeline">
                    <?php foreach ($timeline as $slot): ?>
                        <?php if ($slot['status'] === 'occupied' && $slot['appointment']): ?>
                            <?php $appt = $slot['appointment']; ?>
                            <div class="day-timeline__slot day-timeline__slot--occupied <?= $slot['is_past'] ? 'day-timeline__slot--past' : '' ?>">
                                <div class="day-timeline__time">
                                    <strong><?= e($slot['time']) ?></strong>
                                    <span class="muted"><?= e($slot['end_time']) ?></span>
                                </div>
                                <div class="day-timeline__content day-timeline__content--booked">
                                    <div class="day-timeline__client">
                                        <strong><?= e($appt['customer_name']) ?></strong>
                                        <span class="badge <?= status_class($appt['status']) ?>" style="font-size:0.7rem;"><?= e(status_label($appt['status'])) ?></span>
                                    </div>
                                    <div class="day-timeline__details">
                                        <span><?= e($appt['service_title'] ?? 'Serviço') ?></span>
                                        <span><?= (int) $appt['duration_minutes'] ?> min</span>
                                        <span><?= money($appt['price']) ?></span>
                                        <?php if (!empty($appt['professional_name'])): ?>
                                            <span>👤 <?= e($appt['professional_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="day-timeline__contact">
                                        <span class="muted"><?= e($appt['customer_phone']) ?></span>
                                        <a class="btn btn-whatsapp btn-sm" href="<?= e(whatsapp_link($appt['customer_phone'], 'Olá, ' . $appt['customer_name'] . '! Lembrete: seu atendimento em ' . format_date($appt['appointment_date']) . ' às ' . e($slot['time']) . '. 😊')) ?>" target="_blank" rel="noopener">📱</a>
                                    </div>
                                    <div class="day-timeline__actions">
                                        <?php if ($appt['status'] === 'confirmed'): ?>
                                            <form method="post" action="<?= base_url('vendor/appointments/' . $appt['id'] . '/status') ?>" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="completed">
                                                <input type="hidden" name="redirect_date" value="<?= e($agenda['selected_date']) ?>">
                                                <button class="btn btn-success btn-sm" type="submit">✓ Atendido</button>
                                            </form>
                                            <form method="post" action="<?= base_url('vendor/appointments/' . $appt['id'] . '/status') ?>" style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="status" value="cancelled">
                                                <input type="hidden" name="redirect_date" value="<?= e($agenda['selected_date']) ?>">
                                                <button class="btn btn-danger btn-sm" type="submit">✕ Cancelar</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="<?= base_url('vendor/appointments/' . $appt['id'] . '/delete') ?>" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="redirect_date" value="<?= e($agenda['selected_date']) ?>">
                                            <button class="btn btn-light btn-sm" type="submit" title="Excluir">🗑</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($slot['status'] === 'free'): ?>
                            <div class="day-timeline__slot day-timeline__slot--free" data-fill-time="<?= e($slot['time']) ?>">
                                <div class="day-timeline__time">
                                    <strong><?= e($slot['time']) ?></strong>
                                    <span class="muted"><?= e($slot['end_time']) ?></span>
                                </div>
                                <div class="day-timeline__content day-timeline__content--free">
                                    <span class="day-timeline__free-label">Horário livre</span>
                                    <button class="btn btn-sm day-timeline__book-btn" type="button" data-book-time="<?= e($slot['time']) ?>">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:2px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        Agendar neste horário
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="day-timeline__slot day-timeline__slot--past">
                                <div class="day-timeline__time">
                                    <strong class="muted"><?= e($slot['time']) ?></strong>
                                </div>
                                <div class="day-timeline__content day-timeline__content--past">
                                    <span class="muted">Horário passado</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Agendamentos detalhados (visão expandida) -->
        <div class="card card--section card--timeline" data-animate>
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Operação do dia</span>
                    <h2>Agendamentos — <?= format_date($agenda['selected_date']) ?></h2>
                    <p class="muted">Ordenado por horário, com ações rápidas de status e impressão sequencial.</p>
                </div>
            </div>

            <?php if ($agenda['appointments'] === []): ?>
                <div class="empty-state empty-state--premium">Nenhum agendamento para esta data. Use o formulário ao lado ou clique em um horário livre acima.</div>
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

                                    <div class="whatsapp-actions">
                                        <a class="btn btn-whatsapp" href="<?= e(whatsapp_link($item['customer_phone'], 'Olá, ' . $item['customer_name'] . '! Passando para lembrar do seu atendimento em ' . format_date($item['appointment_date']) . ' às ' . format_time($item['start_time']) . '. Confirme sua presença! 😊')) ?>" target="_blank" rel="noopener">📱 Lembrete</a>
                                        <a class="btn btn-whatsapp" href="<?= e(whatsapp_link($item['customer_phone'], 'Olá, ' . $item['customer_name'] . '! Obrigado pelo atendimento hoje no ' . ($vendor['business_name'] ?? 'nosso espaço') . '! Esperamos você novamente em breve. ⭐')) ?>" target="_blank" rel="noopener">📱 Agradecimento</a>
                                        <a class="btn btn-whatsapp" href="<?= e(whatsapp_link($item['customer_phone'], 'Olá, ' . $item['customer_name'] . '! Temos horários disponíveis para reagendamento. Deseja escolher uma nova data? 📅')) ?>" target="_blank" rel="noopener">📱 Reagendar</a>
                                    </div>

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

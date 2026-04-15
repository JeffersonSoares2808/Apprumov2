<?php
$dates = $calendar_data['dates'] ?? [];
$calProfessionals = $calendar_data['professionals'] ?? [];
$unassigned = $calendar_data['unassigned'] ?? [];

$viewLabels = ['day' => 'Dia', 'week' => 'Semana', 'month' => 'Mês'];
$dayLabels = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
$monthLabelsPt = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro',
];

$statusLabels = [
    'confirmed' => ['label' => 'Confirmado', 'class' => 'is-warning'],
    'completed' => ['label' => 'Atendido', 'class' => 'is-success'],
    'cancelled' => ['label' => 'Cancelado', 'class' => 'is-danger'],
    'no_show' => ['label' => 'Faltou', 'class' => 'is-neutral'],
];

// Use the actual first displayed date for navigation to prevent misalignment
$displayedStartDate = $dates[0] ?? $start_date;

// Navigation: for week view, jump from the displayed Sunday (first date)
if ($view === 'week') {
    $prevDate = date('Y-m-d', strtotime('-7 days', strtotime($displayedStartDate)));
    $nextDate = date('Y-m-d', strtotime('+7 days', strtotime($displayedStartDate)));
} elseif ($view === 'month') {
    $prevDate = date('Y-m-d', strtotime('-1 month', strtotime($start_date)));
    $nextDate = date('Y-m-d', strtotime('+1 month', strtotime($start_date)));
} else {
    $prevDate = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));
    $nextDate = date('Y-m-d', strtotime('+1 day', strtotime($start_date)));
}

$today = date('Y-m-d');
?>
<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Agenda por profissional</span>
                <h1 class="page-title">Agenda Avançada</h1>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <a class="btn btn-light btn-sm" href="<?= base_url('vendor/advanced-agenda?view=' . urlencode($view) . '&date=' . $today) ?>" title="Ir para hoje">📅 Hoje</a>
                <?php foreach ($viewLabels as $vKey => $vLabel): ?>
                    <a class="btn <?= $view === $vKey ? '' : 'btn-light' ?> btn-sm" href="<?= base_url('vendor/advanced-agenda?view=' . $vKey . '&date=' . urlencode($start_date)) ?>"><?= $vLabel ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Navigation -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0;">
            <a class="btn btn-light btn-sm" href="<?= base_url('vendor/advanced-agenda?view=' . urlencode($view) . '&date=' . urlencode($prevDate)) ?>">← Anterior</a>
            <strong>
                <?php if ($view === 'day'): ?>
                    <?= $dayLabels[(int) date('w', strtotime($start_date))] ?>, <?= format_date($start_date) ?>
                <?php elseif ($view === 'week'): ?>
                    <?= format_date($dates[0] ?? $start_date) ?> — <?= format_date($dates[6] ?? $start_date) ?>
                <?php else: ?>
                    <?= $monthLabelsPt[(int) date('n', strtotime($start_date))] . ' ' . date('Y', strtotime($start_date)) ?>
                <?php endif; ?>
            </strong>
            <a class="btn btn-light btn-sm" href="<?= base_url('vendor/advanced-agenda?view=' . urlencode($view) . '&date=' . urlencode($nextDate)) ?>">Próximo →</a>
        </div>
    </div>

    <?php if (empty($calProfessionals)): ?>
        <div class="card card--section">
            <div class="empty-state empty-state--premium">
                <p>Nenhum profissional ativo cadastrado.</p>
                <a class="btn" href="<?= base_url('vendor/professionals') ?>">Cadastrar profissionais</a>
            </div>
        </div>
    <?php else: ?>

        <!-- Day / Week view -->
        <?php if ($view === 'day' || $view === 'week'): ?>
            <div class="card card--section" style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: <?= $view === 'day' ? '400px' : '700px' ?>;">
                    <thead>
                        <tr style="background: var(--bg-alt, #f8f8f8);">
                            <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border, #ddd); min-width: 130px; position: sticky; left: 0; background: var(--bg-alt, #f8f8f8); z-index: 1;">Profissional</th>
                            <?php foreach ($dates as $date): ?>
                                <th style="padding: 0.75rem; text-align: center; border-bottom: 2px solid var(--border, #ddd); min-width: 140px; <?= $date === $today ? 'background: rgba(26,178,199,0.1);' : '' ?>">
                                    <span style="font-weight: 600;"><?= $dayLabels[(int) date('w', strtotime($date))] ?></span><br>
                                    <a href="<?= base_url('vendor/advanced-agenda?view=day&date=' . $date) ?>" style="color: inherit; text-decoration: none; font-size: 0.85rem;" title="Ver dia">
                                        <?= date('d/m', strtotime($date)) ?>
                                        <?= $date === $today ? ' ●' : '' ?>
                                    </a>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calProfessionals as $prof): ?>
                            <tr>
                                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border, #eee); vertical-align: top; position: sticky; left: 0; background: #fff; z-index: 1;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="width: 14px; height: 14px; border-radius: 50%; background: <?= e($prof['color']) ?>; display: inline-block; flex-shrink: 0;"></span>
                                        <strong style="font-size: 0.88rem;"><?= e($prof['name']) ?></strong>
                                    </div>
                                </td>
                                <?php foreach ($dates as $date): ?>
                                    <?php
                                    $slotData = $prof['slots'][$date] ?? ['appointments' => [], 'working_hours' => null];
                                    $appointments = $slotData['appointments'];
                                    $workingHours = $slotData['working_hours'];
                                    ?>
                                    <td style="padding: 0.5rem; border-bottom: 1px solid var(--border, #eee); border-left: 1px solid var(--border, #eee); vertical-align: top; min-width: 140px; <?= $date === $today ? 'background: rgba(26,178,199,0.03);' : '' ?>">
                                        <?php if (!$workingHours): ?>
                                            <span class="badge is-neutral" style="font-size: 0.7rem;">Folga</span>
                                        <?php else: ?>
                                            <span class="muted" style="font-size: 0.7rem; display: block; margin-bottom: 0.3rem;">
                                                🕐 <?= e(substr($workingHours['start_time'], 0, 5)) ?>–<?= e(substr($workingHours['end_time'], 0, 5)) ?>
                                            </span>
                                            <?php if (empty($appointments)): ?>
                                                <span class="muted" style="font-size: 0.7rem; font-style: italic;">Livre</span>
                                            <?php endif; ?>
                                            <?php foreach ($appointments as $appt):
                                                $statusInfo = $statusLabels[$appt['status']] ?? ['label' => $appt['status'], 'class' => 'is-neutral'];
                                            ?>
                                                <div style="margin-top: 0.25rem; padding: 0.4rem 0.5rem; border-radius: 8px; font-size: 0.75rem; background: <?= e($prof['color']) ?>15; border-left: 3px solid <?= e($prof['color']) ?>;">
                                                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 0.3rem;">
                                                        <strong><?= e(substr($appt['start_time'], 0, 5)) ?>–<?= e(substr($appt['end_time'] ?? '', 0, 5)) ?></strong>
                                                        <span class="badge <?= $statusInfo['class'] ?>" style="font-size: 0.6rem; padding: 0.1rem 0.3rem;"><?= $statusInfo['label'] ?></span>
                                                    </div>
                                                    <div style="margin-top: 0.15rem;">
                                                        <?= e($appt['customer_name'] ?? '') ?>
                                                    </div>
                                                    <div class="muted" style="font-size: 0.7rem;"><?= e($appt['service_title'] ?? '') ?></div>
                                                    <div style="margin-top: 0.3rem; display: flex; gap: 0.2rem; flex-wrap: wrap;">
                                                        <?php if ($appt['status'] === 'confirmed'): ?>
                                                            <form method="post" action="<?= base_url('vendor/advanced-agenda/appointments/' . $appt['id'] . '/status') ?>" style="display: inline;">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="view" value="<?= e($view) ?>">
                                                                <input type="hidden" name="date" value="<?= e($start_date) ?>">
                                                                <button name="status" value="completed" class="btn btn-success btn-animated" style="font-size: 0.6rem; padding: 0.15rem 0.4rem;" title="Marcar atendido">✓</button>
                                                            </form>
                                                            <form method="post" action="<?= base_url('vendor/advanced-agenda/appointments/' . $appt['id'] . '/status') ?>" style="display: inline;">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="view" value="<?= e($view) ?>">
                                                                <input type="hidden" name="date" value="<?= e($start_date) ?>">
                                                                <button name="status" value="cancelled" class="btn btn-danger" style="font-size: 0.6rem; padding: 0.15rem 0.4rem;" title="Cancelar">✕</button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <?php if (!empty($appt['customer_phone'])): ?>
                                                            <a class="btn btn-whatsapp" style="font-size: 0.6rem; padding: 0.15rem 0.4rem;" href="<?= e(whatsapp_link($appt['customer_phone'] ?? '', 'Olá! Lembrete do seu atendimento em ' . format_date($appt['appointment_date'] ?? $date) . ' às ' . e(substr($appt['start_time'], 0, 5)) . '. 😊')) ?>" target="_blank" rel="noopener" title="WhatsApp">📱</a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>

                        <!-- Unassigned appointments row -->
                        <?php
                        $hasUnassigned = false;
                        foreach ($dates as $date) {
                            if (!empty($unassigned[$date])) {
                                $hasUnassigned = true;
                                break;
                            }
                        }
                        ?>
                        <?php if ($hasUnassigned): ?>
                            <tr>
                                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border, #eee); vertical-align: top; position: sticky; left: 0; background: #fff; z-index: 1;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="width: 14px; height: 14px; border-radius: 50%; background: #bbb; display: inline-block; flex-shrink: 0;"></span>
                                        <strong class="muted" style="font-size: 0.88rem;">Sem profissional</strong>
                                    </div>
                                </td>
                                <?php foreach ($dates as $date): ?>
                                    <td style="padding: 0.5rem; border-bottom: 1px solid var(--border, #eee); border-left: 1px solid var(--border, #eee); vertical-align: top;">
                                        <?php foreach ($unassigned[$date] ?? [] as $appt):
                                            $statusInfo = $statusLabels[$appt['status']] ?? ['label' => $appt['status'], 'class' => 'is-neutral'];
                                        ?>
                                            <div style="margin-top: 0.25rem; padding: 0.4rem 0.5rem; border-radius: 8px; font-size: 0.75rem; background: #f5f5f5; border-left: 3px solid #bbb;">
                                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                                    <strong><?= e(substr($appt['start_time'], 0, 5)) ?></strong>
                                                    <span class="badge <?= $statusInfo['class'] ?>" style="font-size: 0.6rem; padding: 0.1rem 0.3rem;"><?= $statusInfo['label'] ?></span>
                                                </div>
                                                <?= e($appt['customer_name'] ?? '') ?><br>
                                                <span class="muted" style="font-size: 0.7rem;"><?= e($appt['service_title'] ?? '') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <!-- Month view: summary cards per professional -->
            <?php foreach ($calProfessionals as $prof): ?>
                <div class="card card--section">
                    <div class="section-header section-header--premium">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="width: 14px; height: 14px; border-radius: 50%; background: <?= e($prof['color']) ?>; display: inline-block;"></span>
                            <h2 style="margin: 0;"><?= e($prof['name']) ?></h2>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; text-align: center;">
                        <?php foreach ($dayLabels as $dl): ?>
                            <div style="padding: 0.4rem; font-weight: 600; font-size: 0.75rem; background: var(--bg-alt, #f8f8f8); border-radius: 4px;"><?= $dl ?></div>
                        <?php endforeach; ?>
                        <?php
                        // Pad first week
                        $firstDow = (int) date('w', strtotime($dates[0]));
                        for ($i = 0; $i < $firstDow; $i++): ?>
                            <div></div>
                        <?php endfor; ?>
                        <?php foreach ($dates as $date):
                            $dayAppts = $prof['slots'][$date]['appointments'] ?? [];
                            $workHours = $prof['slots'][$date]['working_hours'] ?? null;
                            $count = count($dayAppts);
                            $isToday = $date === $today;
                            $isOff = !$workHours;
                        ?>
                            <a href="<?= base_url('vendor/advanced-agenda?view=day&date=' . $date) ?>" style="text-decoration: none; color: inherit; padding: 0.4rem; font-size: 0.75rem; border-radius: 8px; <?= $isToday ? 'background: rgba(26,178,199,0.15); font-weight: 700;' : '' ?> <?= $isOff ? 'opacity: 0.4;' : '' ?> display: block; transition: background 0.15s;" onmouseover="this.style.background='rgba(26,178,199,0.08)'" onmouseout="this.style.background='<?= $isToday ? 'rgba(26,178,199,0.15)' : '' ?>'">
                                <div style="font-weight: <?= $isToday ? '700' : '500' ?>;"><?= (int) date('d', strtotime($date)) ?></div>
                                <?php if ($count > 0): ?>
                                    <div style="margin-top: 2px;">
                                        <span style="display: inline-block; padding: 0 0.3rem; border-radius: 10px; background: <?= e($prof['color']) ?>; color: #fff; font-size: 0.6rem; font-weight: 600; min-width: 16px; line-height: 16px;"><?= $count ?></span>
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Quick add form -->
    <div class="card card--section">
        <div class="section-header section-header--premium">
            <div>
                <span class="section-kicker">Novo agendamento</span>
                <h2>Agendar com profissional</h2>
            </div>
        </div>
        <form class="form-grid form-grid--premium" method="post" action="<?= base_url('vendor/advanced-agenda/appointments') ?>" data-disable-on-submit>
            <?= csrf_field() ?>
            <input type="hidden" name="view" value="<?= e($view) ?>">
            <input type="hidden" name="date" value="<?= e($start_date) ?>">

            <div class="form-grid two">
                <div class="field">
                    <label for="aa_prof">Profissional</label>
                    <select id="aa_prof" name="professional_id">
                        <option value="">— Sem profissional —</option>
                        <?php foreach ($professionals as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="aa_service">Serviço</label>
                    <select id="aa_service" name="service_id" required>
                        <option value="">Selecione</option>
                        <?php foreach ($services as $s): ?>
                            <option value="<?= (int) $s['id'] ?>"><?= e($s['title']) ?> (<?= money($s['price']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-grid two">
                <div class="field">
                    <label for="aa_date">Data</label>
                    <input id="aa_date" name="appointment_date" type="date" value="<?= e($start_date) ?>" required>
                </div>
                <div class="field">
                    <label for="aa_time">Horário</label>
                    <input id="aa_time" name="start_time" type="time" required>
                </div>
            </div>
            <div class="form-grid two">
                <div class="field">
                    <label for="aa_name">Nome do cliente</label>
                    <input id="aa_name" name="customer_name" type="text" required placeholder="Nome completo">
                </div>
                <div class="field">
                    <label for="aa_phone">Telefone</label>
                    <input id="aa_phone" name="customer_phone" type="text" required placeholder="(00) 00000-0000">
                </div>
            </div>
            <div class="field">
                <label for="aa_email">E-mail (opcional)</label>
                <input id="aa_email" name="customer_email" type="email" placeholder="email@exemplo.com">
            </div>
            <button class="btn" type="submit" data-loading-label="Agendando...">Criar agendamento</button>
        </form>
    </div>
</section>
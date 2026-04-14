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

// Navigation dates
$prevDate = date('Y-m-d', strtotime('-' . ($view === 'month' ? '1 month' : ($view === 'week' ? '7 days' : '1 day')), strtotime($start_date)));
$nextDate = date('Y-m-d', strtotime('+' . ($view === 'month' ? '1 month' : ($view === 'week' ? '7 days' : '1 day')), strtotime($start_date)));
?>
<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Agenda por profissional</span>
                <h1 class="page-title">Agenda Avançada</h1>
            </div>
            <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <?php foreach ($viewLabels as $vKey => $vLabel): ?>
                    <a class="btn <?= $view === $vKey ? '' : 'btn-light' ?>" href="<?= base_url('vendor/advanced-agenda?view=' . $vKey . '&date=' . urlencode($start_date)) ?>"><?= $vLabel ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Navigation -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0;">
            <a class="btn btn-light" href="<?= base_url('vendor/advanced-agenda?view=' . urlencode($view) . '&date=' . urlencode($prevDate)) ?>">← Anterior</a>
            <strong>
                <?php if ($view === 'day'): ?>
                    <?= format_date($start_date) ?>
                <?php elseif ($view === 'week'): ?>
                    <?= format_date($dates[0] ?? $start_date) ?> — <?= format_date($dates[6] ?? $start_date) ?>
                <?php else: ?>
                    <?= $monthLabelsPt[(int) date('n', strtotime($start_date))] . ' ' . date('Y', strtotime($start_date)) ?>
                <?php endif; ?>
            </strong>
            <a class="btn btn-light" href="<?= base_url('vendor/advanced-agenda?view=' . urlencode($view) . '&date=' . urlencode($nextDate)) ?>">Próximo →</a>
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
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 700px;">
                    <thead>
                        <tr style="background: var(--bg-alt, #f8f8f8);">
                            <th style="padding: 0.75rem; text-align: left; border-bottom: 2px solid var(--border, #ddd); min-width: 120px;">Profissional</th>
                            <?php foreach ($dates as $date): ?>
                                <th style="padding: 0.75rem; text-align: center; border-bottom: 2px solid var(--border, #ddd); <?= $date === date('Y-m-d') ? 'background: rgba(221,183,106,0.15);' : '' ?>">
                                    <?= $dayLabels[(int) date('w', strtotime($date))] ?><br>
                                    <small><?= date('d/m', strtotime($date)) ?></small>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($calProfessionals as $prof): ?>
                            <tr>
                                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border, #eee); vertical-align: top;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="width: 12px; height: 12px; border-radius: 50%; background: <?= e($prof['color']) ?>; display: inline-block;"></span>
                                        <strong><?= e($prof['name']) ?></strong>
                                    </div>
                                </td>
                                <?php foreach ($dates as $date): ?>
                                    <?php
                                    $slotData = $prof['slots'][$date] ?? ['appointments' => [], 'working_hours' => null];
                                    $appointments = $slotData['appointments'];
                                    $workingHours = $slotData['working_hours'];
                                    ?>
                                    <td style="padding: 0.5rem; border-bottom: 1px solid var(--border, #eee); border-left: 1px solid var(--border, #eee); vertical-align: top; <?= $date === date('Y-m-d') ? 'background: rgba(221,183,106,0.08);' : '' ?>">
                                        <?php if (!$workingHours): ?>
                                            <span class="muted" style="font-size: 0.75rem;">Folga</span>
                                        <?php else: ?>
                                            <span class="muted" style="font-size: 0.7rem;"><?= e(substr($workingHours['start_time'], 0, 5)) ?>-<?= e(substr($workingHours['end_time'], 0, 5)) ?></span>
                                            <?php foreach ($appointments as $appt): ?>
                                                <div style="margin-top: 0.25rem; padding: 0.35rem 0.5rem; border-radius: 6px; font-size: 0.75rem; background: <?= e($prof['color']) ?>22; border-left: 3px solid <?= e($prof['color']) ?>;">
                                                    <strong><?= e(substr($appt['start_time'], 0, 5)) ?></strong>
                                                    <?= e($appt['customer_name'] ?? '') ?><br>
                                                    <span class="muted"><?= e($appt['service_title'] ?? '') ?></span>
                                                    <div style="margin-top: 0.2rem;">
                                                        <form method="post" action="<?= base_url('vendor/advanced-agenda/appointments/' . $appt['id'] . '/status') ?>" style="display: inline;">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="view" value="<?= e($view) ?>">
                                                            <input type="hidden" name="date" value="<?= e($start_date) ?>">
                                                            <?php if ($appt['status'] === 'confirmed'): ?>
                                                                <button name="status" value="completed" class="btn btn-light" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">✓</button>
                                                            <?php endif; ?>
                                                        </form>
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
                                <td style="padding: 0.75rem; border-bottom: 1px solid var(--border, #eee); vertical-align: top;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #999; display: inline-block;"></span>
                                        <strong class="muted">Sem profissional</strong>
                                    </div>
                                </td>
                                <?php foreach ($dates as $date): ?>
                                    <td style="padding: 0.5rem; border-bottom: 1px solid var(--border, #eee); border-left: 1px solid var(--border, #eee); vertical-align: top;">
                                        <?php foreach ($unassigned[$date] ?? [] as $appt): ?>
                                            <div style="margin-top: 0.25rem; padding: 0.35rem 0.5rem; border-radius: 6px; font-size: 0.75rem; background: #f5f5f5; border-left: 3px solid #999;">
                                                <strong><?= e(substr($appt['start_time'], 0, 5)) ?></strong>
                                                <?= e($appt['customer_name'] ?? '') ?><br>
                                                <span class="muted"><?= e($appt['service_title'] ?? '') ?></span>
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
                            <div style="padding: 0.3rem; font-weight: 600; font-size: 0.75rem; background: var(--bg-alt, #f8f8f8);"><?= $dl ?></div>
                        <?php endforeach; ?>
                        <?php
                        // Pad first week
                        $firstDow = (int) date('w', strtotime($dates[0]));
                        for ($i = 0; $i < $firstDow; $i++): ?>
                            <div></div>
                        <?php endfor; ?>
                        <?php foreach ($dates as $date):
                            $dayAppts = $prof['slots'][$date]['appointments'] ?? [];
                            $count = count($dayAppts);
                            $isToday = $date === date('Y-m-d');
                        ?>
                            <div style="padding: 0.3rem; font-size: 0.75rem; <?= $isToday ? 'background: rgba(221,183,106,0.2); border-radius: 6px;' : '' ?>">
                                <div><?= (int) date('d', strtotime($date)) ?></div>
                                <?php if ($count > 0): ?>
                                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?= e($prof['color']) ?>;" title="<?= $count ?> agendamento(s)"></span>
                                <?php endif; ?>
                            </div>
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
            <button class="btn" type="submit" data-loading-label="Agendando...">Criar agendamento</button>
        </form>
    </div>
</section>

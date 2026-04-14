<?php
$dayLabels = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

// Build lookup from existing availability
$avail = [];
foreach ($availability as $a) {
    $avail[(int) $a['day_of_week']] = $a;
}
?>
<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium">
            <div>
                <span class="section-kicker">Disponibilidade</span>
                <h1 class="page-title"><?= e($professional['name']) ?></h1>
                <p class="page-subtitle">Configure os horários de trabalho semanais deste profissional.</p>
            </div>
        </div>
    </div>

    <div class="card card--section">
        <form class="form-grid form-grid--premium" method="post" action="<?= base_url('vendor/professionals/' . $professional['id'] . '/availability') ?>" data-disable-on-submit>
            <?= csrf_field() ?>

            <?php for ($day = 0; $day < 7; $day++): ?>
                <?php
                $existing = $avail[$day] ?? null;
                $isActive = $existing ? (int) $existing['is_active'] : 0;
                $startTime = $existing['start_time'] ?? '08:00';
                $endTime = $existing['end_time'] ?? '18:00';
                // Normalize to HH:MM for input
                $startTime = substr($startTime, 0, 5);
                $endTime = substr($endTime, 0, 5);
                ?>
                <div class="availability-row" style="display: flex; align-items: center; gap: 1rem; padding: 0.75rem 0; border-bottom: 1px solid var(--border, #eee);">
                    <input type="hidden" name="availability[<?= $day ?>][day_of_week]" value="<?= $day ?>">
                    <label style="min-width: 100px; font-weight: 600;">
                        <input type="checkbox" name="availability[<?= $day ?>][is_active]" value="1" <?= $isActive ? 'checked' : '' ?>>
                        <?= $dayLabels[$day] ?>
                    </label>
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="time" name="availability[<?= $day ?>][start_time]" value="<?= e($startTime) ?>" style="width: 120px;">
                        <span>até</span>
                        <input type="time" name="availability[<?= $day ?>][end_time]" value="<?= e($endTime) ?>" style="width: 120px;">
                    </div>
                </div>
            <?php endfor; ?>

            <div style="margin-top: 1rem; display: flex; gap: 0.5rem;">
                <button class="btn" type="submit" data-loading-label="Salvando...">Salvar disponibilidade</button>
                <a class="btn btn-light" href="<?= base_url('vendor/professionals') ?>">Voltar</a>
            </div>
        </form>
    </div>
</section>

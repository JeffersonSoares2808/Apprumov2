<?php
$hasProfessionals = !empty($professionals);
$selectedProfId = $selected_professional_id ?? 0;
?>
<section class="stack stack--spacious public-stack">
    <button class="back-button back-button--standalone" type="button" data-back-button data-fallback-url="<?= e(base_url('p/' . $vendor['slug'])) ?>" aria-label="Voltar para o perfil público">
        <span aria-hidden="true">←</span>
        <span>Voltar ao perfil</span>
    </button>

    <div class="card card--section booking-hero">
        <span class="section-kicker">Reserva online</span>
        <h1 class="page-title"><?= e($service['title']) ?></h1>
        <p class="page-subtitle"><?= e($vendor['business_name']) ?> · <?= (int) $service['duration_minutes'] ?> min · <?= money($service['price']) ?></p>
    </div>

    <?php if ($hasProfessionals): ?>
    <div class="card card--section">
        <div class="section-header section-header--premium">
            <div>
                <span class="section-kicker">Escolha o profissional</span>
                <h2>Com quem deseja agendar?</h2>
                <p class="muted">Selecione um profissional para ver os horários disponíveis dele.</p>
            </div>
        </div>
        <div class="booking-professional-grid">
            <a class="booking-professional-chip <?= $selectedProfId === 0 ? 'is-active' : '' ?>"
               href="<?= base_url('book/' . $vendor['slug'] . '/' . $service['id']) ?>">
                <div class="booking-professional-chip__avatar" style="background: #999;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                </div>
                <span>Qualquer profissional</span>
            </a>
            <?php foreach ($professionals as $prof):
                $nameParts = explode(' ', trim($prof['name']));
                $fi = strtoupper(substr($nameParts[0] ?? '', 0, 1));
                $li = count($nameParts) > 1 ? strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1)) : '';
            ?>
                <a class="booking-professional-chip <?= $selectedProfId === (int) $prof['id'] ? 'is-active' : '' ?>"
                   href="<?= base_url('book/' . $vendor['slug'] . '/' . $service['id'] . '?professional=' . $prof['id']) ?>">
                    <div class="booking-professional-chip__avatar" style="background: <?= e($prof['color'] ?? '#1AB2C7') ?>;">
                        <?= e($fi . $li) ?>
                    </div>
                    <span><?= e($prof['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card booking-calendar booking-calendar--premium">
        <div class="section-header section-header--premium">
            <div>
                <span class="section-kicker">Escolha a data</span>
                <h2>Disponibilidade</h2>
                <p class="muted">Baseada na escala semanal, datas específicas e horários já ocupados.</p>
            </div>
        </div>

        <div class="day-strip day-strip--premium">
            <?php foreach ($available_dates as $date): ?>
                <a class="day-chip <?= $selected_date === $date['date'] ? 'is-active' : '' ?>" href="<?= base_url('book/' . $vendor['slug'] . '/' . $service['id'] . '?date=' . $date['date'] . ($selectedProfId ? '&professional=' . $selectedProfId : '')) ?>">
                    <strong><?= e($date['label']) ?></strong>
                    <span><?= e($date['weekday']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="app-grid two">
        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Escolha o horário</span>
                    <h2>Horários disponíveis</h2>
                </div>
            </div>
            <?php if ($slots === []): ?>
                <div class="empty-state empty-state--premium">Sem horários disponíveis nesta data. Escolha outro dia.</div>
            <?php else: ?>
                <form class="stack stack--spacious" method="post" action="<?= base_url('book/' . $vendor['slug'] . '/' . $service['id']) ?>" data-disable-on-submit>
                    <?= csrf_field() ?>
                    <input type="hidden" name="appointment_date" value="<?= e($selected_date) ?>">
                    <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                    <?php if ($selectedProfId > 0): ?>
                        <input type="hidden" name="professional_id" value="<?= (int) $selectedProfId ?>">
                    <?php endif; ?>

                    <div class="slot-grid slot-grid--premium">
                        <?php foreach ($slots as $index => $slot): ?>
                            <label class="slot">
                                <input type="radio" name="start_time" value="<?= e($slot) ?>" <?= $index === 0 ? 'checked' : '' ?>>
                                <span><?= e($slot) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="form-grid two form-grid--premium">
                        <div class="field">
                            <label for="booking_name">Nome</label>
                            <input id="booking_name" name="customer_name" type="text" required placeholder="Seu nome completo">
                        </div>
                        <div class="field">
                            <label for="booking_email">E-mail</label>
                            <input id="booking_email" name="customer_email" type="email" placeholder="seu@email.com">
                        </div>
                    </div>
                    <div class="field">
                        <label for="booking_phone">Telefone</label>
                        <input id="booking_phone" name="customer_phone" type="text" required placeholder="(00) 00000-0000">
                    </div>
                    <label class="checkbox-row"><input type="checkbox" name="lgpd_consent" value="1" required> Concordo com o uso dos meus dados para o agendamento.</label>
                    <button class="btn" style="background: <?= e($vendor['button_color'] ?: '#1AB2C7') ?>; color:#fff;" type="submit" data-loading-label="Confirmando...">Confirmar agendamento</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card card--section card--soft-outline">
            <span class="section-kicker">Resumo</span>
            <h2>Dados da reserva</h2>
            <p class="muted">Data selecionada: <?= format_date($selected_date) ?></p>
            <?php if ($selectedProfId > 0):
                $selectedProf = null;
                foreach ($professionals as $p) {
                    if ((int) $p['id'] === $selectedProfId) {
                        $selectedProf = $p;
                        break;
                    }
                }
                if ($selectedProf): ?>
                    <p class="muted">Profissional: <?= e($selectedProf['name']) ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p class="muted">Profissional: <?= e($vendor['business_name']) ?></p>
            <?php endif; ?>
            <p class="muted">Serviço: <?= e($service['title']) ?></p>
            <p class="muted">Duração: <?= (int) $service['duration_minutes'] ?> minutos</p>
            <p class="muted">Valor: <?= money($service['price']) ?></p>
        </div>
    </div>
</section>
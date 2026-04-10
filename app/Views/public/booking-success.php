<section class="status-screen status-screen--success">
    <div class="card status-panel status-panel--premium">
        <div class="brand-lockup" style="margin: 0 auto 18px;">
            <img class="brand-logo-image" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="64" decoding="async">
        </div>
        <span class="soft-pill soft-pill--gold">Reserva concluída</span>
        <h1>Agendamento confirmado</h1>
        <p class="muted">Seu horário foi reservado com sucesso e já está pronto para acompanhamento.</p>
        <div class="stack stack--spacious" style="text-align:left; margin-top:18px;">
            <div class="card card--soft-outline">
                <strong><?= e($vendor['business_name']) ?></strong><br>
                <span class="muted"><?= e($service['title']) ?></span><br>
                <span class="muted"><?= format_date($appointment['appointment_date']) ?> às <?= format_time($appointment['start_time']) ?></span><br>
                <span class="muted"><?= e($appointment['customer_name']) ?> · <?= e($appointment['customer_phone']) ?></span>
            </div>
            <a class="btn" style="background: <?= e($vendor['button_color'] ?: '#ddb76a') ?>; color:#fff;" href="<?= e(whatsapp_link($vendor['phone'], 'Olá! Acabei de confirmar meu agendamento para ' . format_date($appointment['appointment_date']) . ' às ' . format_time($appointment['start_time']) . '.')) ?>" target="_blank" rel="noopener">Falar com o profissional</a>
            <a class="btn btn-light" href="<?= base_url('p/' . $vendor['slug']) ?>">Voltar ao perfil</a>
        </div>
    </div>
</section>

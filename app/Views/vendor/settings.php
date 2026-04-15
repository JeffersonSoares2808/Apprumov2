<?php
$colorPresets = ['#1AB2C7', '#1B5E70', '#3b82f6', '#0ea5e9', '#10b981', '#f59e0b', '#f43f5e', '#1e293b'];
$specialDayRows = $special_days;
while (count($specialDayRows) < 3) {
    $specialDayRows[] = ['special_date' => '', 'start_time' => '08:00:00', 'end_time' => '18:00:00', 'is_available' => 1];
}
$weekLabels = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'];
?>

<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Identidade e operação</span>
                <h1 class="page-title">Configurações com visual mais profissional.</h1>
                <p class="page-subtitle">Ajuste perfil público, horários, bio, imagens e cor principal sem se perder em um formulário confuso.</p>
            </div>
            <a class="btn btn-light" href="<?= base_url('p/' . $vendor['slug']) ?>" target="_blank" rel="noopener">Ver perfil público</a>
        </div>
    </div>

    <form class="stack stack--spacious" method="post" action="<?= base_url('vendor/settings') ?>" enctype="multipart/form-data" data-disable-on-submit>
        <?= csrf_field() ?>

        <div class="app-grid two">
            <div class="card card--section">
                <div class="section-header section-header--premium">
                    <div>
                        <span class="section-kicker">Negócio</span>
                        <h2>Imagens e dados principais</h2>
                    </div>
                </div>

                <div class="form-grid form-grid--premium">
                    <div class="form-grid two">
                        <div class="field">
                            <label for="profile_image">Foto de perfil</label>
                            <input id="profile_image" name="profile_image" type="file" accept="image/*">
                        </div>
                        <div class="field">
                            <label for="cover_image">Imagem de capa</label>
                            <input id="cover_image" name="cover_image" type="file" accept="image/*">
                        </div>
                    </div>

                    <div class="field">
                        <label for="business_name">Nome do negócio</label>
                        <input id="business_name" name="business_name" type="text" value="<?= e($vendor['business_name']) ?>" required data-slug-source>
                    </div>

                    <div class="form-grid two">
                        <div class="field">
                            <label for="slug">Slug público</label>
                            <input id="slug" name="slug" type="text" value="<?= e($vendor['slug']) ?>" required data-slug-target>
                        </div>
                        <div class="field">
                            <label for="category">Categoria</label>
                            <input id="category" name="category" type="text" value="<?= e($vendor['category']) ?>" required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" maxlength="250" data-char-source data-char-max="250"><?= e($vendor['bio'] ?? '') ?></textarea>
                        <small class="muted"><span data-char-output><?= strlen((string) ($vendor['bio'] ?? '')) ?></span>/250 caracteres</small>
                    </div>

                    <div class="field">
                        <label for="address">Endereço</label>
                        <input id="address" name="address" type="text" value="<?= e($vendor['address'] ?? '') ?>">
                    </div>

                    <div class="form-grid two">
                        <div class="field">
                            <label for="latitude">Latitude</label>
                            <input id="latitude" name="latitude" type="text" inputmode="decimal" placeholder="-23.5505199" value="<?= e($vendor['latitude'] ?? '') ?>">
                        </div>
                        <div class="field">
                            <label for="longitude">Longitude</label>
                            <input id="longitude" name="longitude" type="text" inputmode="decimal" placeholder="-46.6333094" value="<?= e($vendor['longitude'] ?? '') ?>">
                        </div>
                    </div>
                    <small class="muted">📍 Para encontrar suas coordenadas: abra <a href="https://www.google.com/maps" target="_blank" rel="noopener">Google Maps</a>, clique com botão direito no local da sua loja e copie as coordenadas (latitude, longitude).</small>

                    <div class="form-grid two">
                        <div class="field">
                            <label for="phone">Telefone</label>
                            <input id="phone" name="phone" type="text" value="<?= e($vendor['phone']) ?>" required>
                        </div>
                        <div class="field">
                            <label for="interval_between_appointments">Intervalo entre atendimentos (min)</label>
                            <input id="interval_between_appointments" name="interval_between_appointments" type="number" min="0" step="5" value="<?= e($vendor['interval_between_appointments'] ?? 0) ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="stack">
                <div class="card card--section">
                    <div class="section-header section-header--premium">
                        <div>
                            <span class="section-kicker">Marca</span>
                            <h2>Cor principal do botão</h2>
                        </div>
                    </div>
                    <div class="color-preset-grid color-preset-grid--premium">
                        <?php foreach ($colorPresets as $color): ?>
                            <button class="color-chip" type="button" data-color-value="<?= e($color) ?>" style="background: <?= e($color) ?>;"></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="field" style="margin-top:16px;">
                        <label for="button_color_custom">Cor personalizada</label>
                        <input id="button_color_custom" data-color-target name="button_color" type="color" value="<?= e($vendor['button_color'] ?: '#1AB2C7') ?>">
                    </div>
                    <div class="preview-card">
                        <small class="muted">Prévia do CTA</small>
                        <button class="btn btn-block" data-color-preview type="button" style="background: <?= e($vendor['button_color'] ?: '#1AB2C7') ?>; color:#fff;">Agendar agora</button>
                    </div>
                </div>

                <div class="card card--section">
                    <div class="section-header section-header--premium">
                        <div>
                            <span class="section-kicker">Escala</span>
                            <h2>Horário semanal</h2>
                        </div>
                    </div>
                    <div class="schedule-grid schedule-grid--premium">
                        <?php foreach ($weekly_hours as $hour): ?>
                            <div class="schedule-item schedule-item--premium">
                                <label class="checkbox-row">
                                    <input type="checkbox" name="weekly_hours[<?= (int) $hour['weekday'] ?>][is_enabled]" <?= (int) $hour['is_enabled'] ? 'checked' : '' ?>>
                                    <?= e($weekLabels[(int) $hour['weekday']]) ?>
                                </label>
                                <input name="weekly_hours[<?= (int) $hour['weekday'] ?>][start_time]" type="time" value="<?= e(substr((string) $hour['start_time'], 0, 5)) ?>">
                                <input name="weekly_hours[<?= (int) $hour['weekday'] ?>][end_time]" type="time" value="<?= e(substr((string) $hour['end_time'], 0, 5)) ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Datas especiais</span>
                    <h2>Ajustes fora da rotina</h2>
                    <p class="muted">Use para abrir agenda em dias extras ou bloquear datas específicas.</p>
                </div>
            </div>
            <div class="schedule-grid schedule-grid--premium">
                <?php foreach ($specialDayRows as $index => $day): ?>
                    <div class="schedule-item schedule-item--premium schedule-item--special">
                        <input name="special_days[<?= (int) $index ?>][special_date]" type="date" value="<?= e($day['special_date']) ?>">
                        <input name="special_days[<?= (int) $index ?>][start_time]" type="time" value="<?= e(substr((string) $day['start_time'], 0, 5)) ?>">
                        <input name="special_days[<?= (int) $index ?>][end_time]" type="time" value="<?= e(substr((string) $day['end_time'], 0, 5)) ?>">
                        <label class="checkbox-row"><input type="checkbox" name="special_days[<?= (int) $index ?>][is_available]" <?= !isset($day['is_available']) || (int) $day['is_available'] ? 'checked' : '' ?>> Disponível</label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php $notificationConfig = $notification_settings; ?>
        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">WhatsApp Business</span>
                    <h2>Notificações automáticas via WhatsApp</h2>
                    <p class="muted">Configure a API do WhatsApp Business para receber notificações automáticas quando um cliente agendar. O sistema enviará uma mensagem com o nome e horário do cliente diretamente no seu WhatsApp.</p>
                </div>
            </div>
            <div class="form-grid form-grid--premium">
                <div class="field">
                    <label for="whatsapp_api_token">Token da API do WhatsApp</label>
                    <input id="whatsapp_api_token" name="whatsapp_api_token" type="password" value="<?= e($vendor['whatsapp_api_token'] ?? '') ?>" placeholder="EAAxxxxxxx...">
                    <small class="muted">Token de acesso permanente do Meta Business. Obtenha em <a href="https://business.facebook.com/settings" target="_blank" rel="noopener">Meta Business</a>.</small>
                </div>
                <div class="field">
                    <label for="whatsapp_phone_id">Phone Number ID</label>
                    <input id="whatsapp_phone_id" name="whatsapp_phone_id" type="text" value="<?= e($vendor['whatsapp_phone_id'] ?? '') ?>" placeholder="1234567890">
                    <small class="muted">ID do número de telefone configurado na API do WhatsApp Business.</small>
                </div>
            </div>
        </div>

        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Disparos automáticos</span>
                    <h2>Notificações por e-mail, SMS e WhatsApp</h2>
                    <p class="muted">Configure quais avisos o sistema envia automaticamente para clientes e para você.</p>
                </div>
            </div>
            <div class="form-grid form-grid--premium">
                <div class="form-grid two">
                    <label class="checkbox-row">
                        <input type="checkbox" name="notifications[email_enabled]" <?= (int) ($notificationConfig['email_enabled'] ?? 1) ? 'checked' : '' ?>>
                        📧 Notificações por e-mail
                    </label>
                    <label class="checkbox-row">
                        <input type="checkbox" name="notifications[sms_enabled]" <?= (int) ($notificationConfig['sms_enabled'] ?? 1) ? 'checked' : '' ?>>
                        📱 Notificações por SMS
                    </label>
                </div>
                <div class="form-grid two">
                    <label class="checkbox-row">
                        <input type="checkbox" name="notifications[whatsapp_enabled]" <?= (int) ($notificationConfig['whatsapp_enabled'] ?? 0) ? 'checked' : '' ?>>
                        💬 Notificações por WhatsApp (para o vendedor)
                    </label>
                    <label class="checkbox-row">
                        <input type="checkbox" name="notifications[whatsapp_notify_vendor]" <?= (int) ($notificationConfig['whatsapp_notify_vendor'] ?? 1) ? 'checked' : '' ?>>
                        🔔 WhatsApp ao receber agendamento
                    </label>
                </div>
                <div class="form-grid two">
                    <label class="checkbox-row">
                        <input type="checkbox" name="notifications[notify_on_booking]" <?= (int) ($notificationConfig['notify_on_booking'] ?? 1) ? 'checked' : '' ?>>
                        Aviso de novo agendamento
                    </label>
                    <label class="checkbox-row">
                        <input type="checkbox" name="notifications[notify_on_status_change]" <?= (int) ($notificationConfig['notify_on_status_change'] ?? 1) ? 'checked' : '' ?>>
                        Aviso de mudança de status
                    </label>
                </div>
                <div class="form-grid two">
                    <label class="checkbox-row">
                        <input type="checkbox" name="notifications[notify_on_payment]" <?= (int) ($notificationConfig['notify_on_payment'] ?? 1) ? 'checked' : '' ?>>
                        Confirmação de pagamento
                    </label>
                    <label class="checkbox-row">
                        <input type="checkbox" name="notifications[notify_on_low_stock]" <?= (int) ($notificationConfig['notify_on_low_stock'] ?? 1) ? 'checked' : '' ?>>
                        Alerta de estoque baixo
                    </label>
                </div>
                <label class="checkbox-row">
                    <input type="checkbox" name="notifications[send_reminders]" <?= (int) ($notificationConfig['send_reminders'] ?? 1) ? 'checked' : '' ?>>
                    📅 Lembrete automático no dia anterior ao atendimento
                </label>
            </div>
        </div>

        <div class="form-submit-bar">
            <button class="btn btn-secondary btn-animated" type="submit" data-loading-label="Salvando...">💾 Salvar configurações</button>
        </div>
    </form>
</section>

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
                <h1 class="page-title">Configurações</h1>
                <p class="page-subtitle">Ajuste perfil, horários, notificações e integrações. Use as abas para navegar rapidamente.</p>
            </div>
            <a class="btn btn-light" href="<?= base_url('p/' . $vendor['slug']) ?>" target="_blank" rel="noopener">🌐 Ver perfil público</a>
        </div>

        <nav class="settings-tabs" data-settings-tabs>
            <button class="settings-tab is-active" type="button" data-tab="perfil">👤 Perfil</button>
            <button class="settings-tab" type="button" data-tab="marca">🎨 Marca</button>
            <button class="settings-tab" type="button" data-tab="horarios">🕐 Horários</button>
            <button class="settings-tab" type="button" data-tab="notificacoes">🔔 Notificações</button>
            <button class="settings-tab" type="button" data-tab="whatsapp">💬 WhatsApp</button>
        </nav>
    </div>

    <form class="stack stack--spacious" method="post" action="<?= base_url('vendor/settings') ?>" enctype="multipart/form-data" data-disable-on-submit>
        <?= csrf_field() ?>

        <!-- ═══ TAB: Perfil ═══ -->
        <div class="settings-panel is-visible" data-tab-panel="perfil">
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
                            <div class="field image-upload-field">
                                <label for="profile_image">Foto de perfil</label>
                                <?php if (!empty($vendor['profile_image'])): ?>
                                    <div class="image-upload-preview image-upload-preview--profile" data-image-zoom="<?= e(asset(ltrim($vendor['profile_image'], '/'))) ?>">
                                        <img src="<?= e(asset(ltrim($vendor['profile_image'], '/'))) ?>" alt="Foto de perfil">
                                        <div class="image-upload-preview__zoom-hint">🔍 Ampliar</div>
                                    </div>
                                <?php endif; ?>
                                <input id="profile_image" name="profile_image" type="file" accept="image/*">
                            </div>
                            <div class="field image-upload-field">
                                <label for="cover_image">Imagem de capa</label>
                                <?php if (!empty($vendor['cover_image'])): ?>
                                    <div class="image-upload-preview image-upload-preview--cover" data-image-zoom="<?= e(asset(ltrim($vendor['cover_image'], '/'))) ?>">
                                        <img src="<?= e(asset(ltrim($vendor['cover_image'], '/'))) ?>" alt="Imagem de capa" style="object-position: center <?= e($vendor['cover_position'] ?? 'center') ?>;">
                                        <div class="image-upload-preview__zoom-hint">🔍 Ampliar</div>
                                    </div>
                                <?php endif; ?>
                                <input id="cover_image" name="cover_image" type="file" accept="image/*">
                                <div class="cover-position-control">
                                    <label for="cover_position">Posição da capa:</label>
                                    <select id="cover_position" name="cover_position">
                                        <?php
                                        $positions = ['top' => 'Topo', 'center' => 'Centro', 'bottom' => 'Base'];
                                        $currentPos = $vendor['cover_position'] ?? 'center';
                                        foreach ($positions as $val => $label): ?>
                                            <option value="<?= e($val) ?>" <?= $currentPos === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
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

                <div class="card card--section">
                    <div class="section-header section-header--premium">
                        <div>
                            <span class="section-kicker">Compartilhamento</span>
                            <h2>Seu link público</h2>
                        </div>
                    </div>
                    <div class="link-box"><?= e(base_url('p/' . $vendor['slug'])) ?></div>
                    <div class="share-actions-grid" style="margin-top:12px;">
                        <button class="share-action-btn" type="button" data-native-share data-share-url="<?= e(base_url('p/' . $vendor['slug'])) ?>" data-share-title="<?= e($vendor['business_name'] ?? 'Apprumo') ?>" data-share-text="Conheça meu perfil e agende online">
                            <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><polyline points="16 6 12 2 8 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="2" x2="12" y2="15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            <span>Compartilhar</span>
                        </button>
                        <button class="share-action-btn" type="button" data-copy-url="<?= e(base_url('p/' . $vendor['slug'])) ?>">
                            <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><rect x="9" y="9" width="13" height="13" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                            <span>Copiar link</span>
                        </button>
                        <?php $whatsappSettingsShareUrl = 'https://api.whatsapp.com/send?text=' . rawurlencode('Olá! Conheça meu perfil e agende online: ' . base_url('p/' . $vendor['slug'])); ?>
                        <a class="share-action-btn" href="<?= e($whatsappSettingsShareUrl) ?>" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" fill="#25D366"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 12 2z" stroke="currentColor" stroke-width="1.5"/></svg>
                            <span>WhatsApp</span>
                        </a>
                        <a class="share-action-btn" href="<?= e(base_url('p/' . $vendor['slug'])) ?>" target="_blank" rel="noopener">
                            <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="1.8"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="currentColor" stroke-width="1.8"/></svg>
                            <span>Abrir</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ TAB: Marca ═══ -->
        <div class="settings-panel" data-tab-panel="marca">
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
        </div>

        <!-- ═══ TAB: Horários ═══ -->
        <div class="settings-panel" data-tab-panel="horarios">
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
        </div>

        <!-- ═══ TAB: Notificações ═══ -->
        <?php $notificationConfig = $notification_settings; ?>
        <div class="settings-panel" data-tab-panel="notificacoes">
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
                            💬 Ativar notificações por WhatsApp
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
                        📅 Lembrete automático antes do atendimento
                    </label>
                    <div class="form-grid two" style="margin-top:8px;">
                        <div class="field">
                            <label for="reminder_minutes_before">⏰ Enviar lembrete com antecedência de</label>
                            <select id="reminder_minutes_before" name="notifications[reminder_minutes_before]">
                                <?php
                                $reminderOptions = [
                                    30 => '30 minutos',
                                    60 => '1 hora',
                                    120 => '2 horas',
                                    180 => '3 horas',
                                    360 => '6 horas',
                                    720 => '12 horas',
                                    1440 => '24 horas (1 dia)',
                                    2880 => '48 horas (2 dias)',
                                ];
                                $currentMinutes = (int) ($notificationConfig['reminder_minutes_before'] ?? 1440);
                                foreach ($reminderOptions as $value => $label):
                                ?>
                                    <option value="<?= $value ?>" <?= $currentMinutes === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="muted">Defina com quantos minutos de antecedência o lembrete será enviado ao cliente.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ TAB: WhatsApp ═══ -->
        <div class="settings-panel" data-tab-panel="whatsapp">
            <div class="card card--section">
                <div class="section-header section-header--premium">
                    <div>
                        <span class="section-kicker">WhatsApp Business API</span>
                        <h2>Notificações automáticas via WhatsApp</h2>
                        <p class="muted">Quando um cliente agendar, o sistema enviará automaticamente uma mensagem no seu WhatsApp com o nome, serviço e horário.</p>
                    </div>
                </div>
                <div class="form-grid form-grid--premium">
                    <div class="field">
                        <label for="whatsapp_api_token">Token da API do WhatsApp</label>
                        <input id="whatsapp_api_token" name="whatsapp_api_token" type="password" value="<?= e($vendor['whatsapp_api_token'] ?? '') ?>" placeholder="EAAxxxxxxx...">
                        <small class="muted">Token de acesso permanente do Meta Business.</small>
                    </div>
                    <div class="field">
                        <label for="whatsapp_phone_id">Phone Number ID</label>
                        <input id="whatsapp_phone_id" name="whatsapp_phone_id" type="text" value="<?= e($vendor['whatsapp_phone_id'] ?? '') ?>" placeholder="1234567890">
                        <small class="muted">ID do número de telefone configurado na API do WhatsApp Business.</small>
                    </div>
                </div>

                <div class="whatsapp-help">
                    <h3>📋 Como configurar a API do WhatsApp (Meta Cloud API)</h3>
                    <ol>
                        <li>Acesse <a href="https://developers.facebook.com" target="_blank" rel="noopener">developers.facebook.com</a> e faça login com sua conta Facebook.</li>
                        <li>Clique em <strong>"Criar App"</strong> → escolha o tipo <strong>"Negócios"</strong> (Business).</li>
                        <li>No painel do app, vá em <strong>"Adicionar produto"</strong> e selecione <strong>"WhatsApp"</strong>.</li>
                        <li>Em <strong>WhatsApp &gt; Configuração da API</strong>, você verá o <strong>Phone Number ID</strong> e poderá gerar um <strong>Token temporário</strong>.</li>
                        <li>Para um <strong>token permanente</strong>: vá em <strong>Configurações do App &gt; Tokens de acesso</strong> → gere um token de sistema com permissão <code>whatsapp_business_messaging</code>.</li>
                        <li>Cole o <strong>Token</strong> e o <strong>Phone Number ID</strong> nos campos acima.</li>
                        <li>Na aba <strong>Notificações</strong>, ative <strong>"Ativar notificações por WhatsApp"</strong> e <strong>"WhatsApp ao receber agendamento"</strong>.</li>
                    </ol>
                    <div class="help-note">
                        <strong>💡 Custo:</strong> A Meta Cloud API é <strong>gratuita para até 1.000 mensagens/mês</strong>. Cada vendedor configura seu próprio token, assim cada um controla seus custos e limites de forma independente. Não é necessária uma API para a plataforma inteira — cada vendedor tem sua própria conta.
                    </div>
                    <div class="help-note" style="margin-top:8px;">
                        <strong>🔒 Segurança:</strong> Seu token é armazenado de forma segura e nunca é exibido após salvo. Cada vendedor tem credenciais independentes — não há risco de acesso cruzado entre contas.
                    </div>
                </div>
            </div>
        </div>

        <div class="form-submit-bar">
            <button class="btn btn-secondary btn-animated" type="submit" data-loading-label="Salvando...">💾 Salvar configurações</button>
        </div>
    </form>
</section>

<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Equipe</span>
                <h1 class="page-title">Profissionais</h1>
                <p class="page-subtitle">Cadastre os profissionais que atendem no seu negócio. Cada um terá sua própria agenda e horários.</p>
            </div>
            <div class="soft-pill"><?= count($professionals) ?> profissional(is)</div>
        </div>
    </div>

    <div class="app-grid two">
        <!-- Form -->
        <div class="card card--section card--sticky">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Novo profissional</span>
                    <h2>Adicionar à equipe</h2>
                </div>
            </div>

            <form class="form-grid form-grid--premium" method="post" action="<?= base_url('vendor/professionals') ?>" data-disable-on-submit>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="prof_name">Nome</label>
                    <input id="prof_name" name="name" type="text" required placeholder="Ex.: Maria Silva">
                </div>
                <div class="field">
                    <label for="prof_email">E-mail (conta no sistema)</label>
                    <input id="prof_email" name="email" type="email" required placeholder="email@exemplo.com">
                    <small class="muted">O profissional precisa ter uma conta cadastrada no Apprumo.</small>
                </div>
                <div class="form-grid two">
                    <div class="field">
                        <label for="prof_phone">Telefone</label>
                        <input id="prof_phone" name="phone" type="text" placeholder="(00) 00000-0000">
                    </div>
                    <div class="field">
                        <label for="prof_color">Cor na agenda</label>
                        <input id="prof_color" name="color" type="color" value="#1AB2C7">
                    </div>
                </div>
                <div class="form-grid two">
                    <div class="field">
                        <label for="prof_commission">Comissão (%)</label>
                        <input id="prof_commission" name="commission_rate" type="number" min="0" max="100" step="0.5" value="0" placeholder="0">
                    </div>
                    <div class="field">
                        <label for="prof_schedule_type">Tipo de escala</label>
                        <select id="prof_schedule_type" name="schedule_type">
                            <option value="weekly">Escala semanal (normal)</option>
                            <option value="specific">Datas específicas</option>
                        </select>
                        <small class="muted">Define se o profissional segue escala fixa ou trabalha em datas avulsas.</small>
                    </div>
                </div>
                <button class="btn btn-animated btn-pulse" type="submit" data-loading-label="Salvando...">🚀 Adicionar profissional</button>
            </form>
        </div>

        <!-- List -->
        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Equipe ativa</span>
                    <h2>Profissionais cadastrados</h2>
                </div>
            </div>

            <div class="service-list service-list--premium">
                <?php foreach ($professionals as $prof): ?>
                    <article class="service-item service-item--premium">
                        <div class="service-media service-media--placeholder" style="background-color: <?= e($prof['color']) ?>; color: #fff;">
                            <?= e(mb_strtoupper(mb_substr($prof['name'], 0, 2))) ?>
                        </div>

                        <div class="stack stack--compact">
                            <div class="service-head">
                                <div>
                                    <strong><?= e($prof['name']) ?></strong><br>
                                    <span class="muted"><?= e($prof['email']) ?></span>
                                    <?php if (!empty($prof['phone'])): ?>
                                        <br><span class="muted"><?= e($prof['phone']) ?></span>
                                    <?php endif; ?>
                                    <?php if ((float) $prof['commission_rate'] > 0): ?>
                                        <br><span class="muted">Comissão: <?= number_format((float) $prof['commission_rate'], 1) ?>%</span>
                                    <?php endif; ?>
                                    <br><span class="badge <?= ($prof['schedule_type'] ?? 'weekly') === 'specific' ? 'is-warning' : 'is-neutral' ?>"><?= ($prof['schedule_type'] ?? 'weekly') === 'specific' ? '📌 Datas específicas' : '🔄 Escala semanal' ?></span>
                                    <?php
                                    $linkedServices = $prof['linked_services'] ?? [];
                                    if (!empty($linkedServices)): ?>
                                        <br><span class="muted" style="font-size: 0.8rem;">Serviços: <?= e(implode(', ', array_map(fn($s) => $s['title'], $linkedServices))) ?></span>
                                    <?php endif; ?>
                                    <?php
                                    // Show upcoming registered dates for specific-schedule professionals
                                    if (($prof['schedule_type'] ?? 'weekly') === 'specific'):
                                        $upcomingDates = $prof['upcoming_dates'] ?? [];
                                        $availDates = array_filter($upcomingDates, fn($d) => (int) $d['is_available'] === 1);
                                    ?>
                                        <?php if (!empty($availDates)): ?>
                                            <br><span class="muted" style="font-size: 0.8rem;">📅 Próximas datas:
                                            <?php
                                            $dateLabels = array_map(fn($d) => format_date($d['exception_date']) . ' (' . substr($d['start_time'], 0, 5) . '–' . substr($d['end_time'], 0, 5) . ')', array_slice($availDates, 0, 5));
                                            echo e(implode(', ', $dateLabels));
                                            if (count($availDates) > 5) {
                                                echo ' <strong>+' . (count($availDates) - 5) . ' mais</strong>';
                                            }
                                            ?>
                                            </span>
                                        <?php else: ?>
                                            <br><span class="muted" style="font-size: 0.8rem; color: #e53e3e;">⚠️ Nenhuma data futura cadastrada</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?= (int) $prof['is_active'] ? 'is-success' : 'is-neutral' ?>"><?= (int) $prof['is_active'] ? 'Ativo' : 'Inativo' ?></span>
                            </div>

                            <!-- Edit form -->
                            <details class="prof-edit-details" style="margin-top: 0.5rem;">
                                <summary class="btn btn-light btn-sm" style="cursor: pointer; display: inline-block;">✏️ Editar profissional</summary>
                                <form class="form-grid form-grid--premium" method="post" action="<?= base_url('vendor/professionals/' . $prof['id'] . '/update') ?>" style="margin-top: 0.5rem; padding: 0.75rem; background: var(--bg-alt, #f8f9fa); border-radius: 8px;" data-disable-on-submit>
                                    <?= csrf_field() ?>
                                    <div class="field">
                                        <label for="edit_name_<?= (int) $prof['id'] ?>">Nome</label>
                                        <input id="edit_name_<?= (int) $prof['id'] ?>" name="name" type="text" required value="<?= e($prof['name']) ?>">
                                    </div>
                                    <div class="form-grid two">
                                        <div class="field">
                                            <label for="edit_phone_<?= (int) $prof['id'] ?>">Telefone</label>
                                            <input id="edit_phone_<?= (int) $prof['id'] ?>" name="phone" type="text" value="<?= e($prof['phone'] ?? '') ?>" placeholder="(00) 00000-0000">
                                        </div>
                                        <div class="field">
                                            <label for="edit_color_<?= (int) $prof['id'] ?>">Cor na agenda</label>
                                            <input id="edit_color_<?= (int) $prof['id'] ?>" name="color" type="color" value="<?= e($prof['color'] ?? '#1AB2C7') ?>">
                                        </div>
                                    </div>
                                    <div class="form-grid two">
                                        <div class="field">
                                            <label for="edit_commission_<?= (int) $prof['id'] ?>">Comissão (%)</label>
                                            <input id="edit_commission_<?= (int) $prof['id'] ?>" name="commission_rate" type="number" min="0" max="100" step="0.5" value="<?= e($prof['commission_rate'] ?? 0) ?>">
                                        </div>
                                        <div class="field">
                                            <label for="edit_schedule_<?= (int) $prof['id'] ?>">Tipo de escala</label>
                                            <select id="edit_schedule_<?= (int) $prof['id'] ?>" name="schedule_type">
                                                <option value="weekly" <?= ($prof['schedule_type'] ?? 'weekly') === 'weekly' ? 'selected' : '' ?>>Escala semanal</option>
                                                <option value="specific" <?= ($prof['schedule_type'] ?? 'weekly') === 'specific' ? 'selected' : '' ?>>Datas específicas</option>
                                            </select>
                                        </div>
                                    </div>
                                    <button class="btn btn-sm btn-animated" type="submit" data-loading-label="Salvando...">💾 Salvar alterações</button>
                                </form>
                            </details>

                            <!-- Services linkage form -->
                            <details class="prof-services-details" style="margin-top: 0.5rem;">
                                <summary class="btn btn-light btn-sm" style="cursor: pointer; display: inline-block;">🔗 Vincular serviços</summary>
                                <form method="post" action="<?= base_url('vendor/professionals/' . $prof['id'] . '/services') ?>" style="margin-top: 0.5rem; padding: 0.75rem; background: var(--bg-alt, #f8f9fa); border-radius: 8px;">
                                    <?= csrf_field() ?>
                                    <?php
                                    $linkedIds = array_map(fn($s) => (int)$s['id'], $linkedServices);
                                    foreach ($services as $svc): ?>
                                        <label class="checkbox-row" style="font-size: 0.85rem;">
                                            <input type="checkbox" name="service_ids[]" value="<?= (int) $svc['id'] ?>" <?= in_array((int) $svc['id'], $linkedIds, true) ? 'checked' : '' ?>>
                                            <?= e($svc['title']) ?> (<?= money($svc['price']) ?>)
                                        </label>
                                    <?php endforeach; ?>
                                    <?php if (empty($services)): ?>
                                        <p class="muted" style="font-size: 0.8rem;">Nenhum serviço cadastrado. Crie serviços primeiro.</p>
                                    <?php else: ?>
                                        <button class="btn btn-sm" type="submit" style="margin-top: 0.5rem;">Salvar vínculos</button>
                                    <?php endif; ?>
                                </form>
                            </details>

                            <div class="inline-actions inline-actions--wrap">
                                <?php if (($prof['schedule_type'] ?? 'weekly') === 'weekly'): ?>
                                    <a class="btn btn-animated" href="<?= base_url('vendor/professionals/' . $prof['id'] . '/availability') ?>">📅 Horários</a>
                                    <a class="btn btn-light btn-animated" href="<?= base_url('vendor/professionals/' . $prof['id'] . '/exceptions') ?>">📋 Exceções</a>
                                <?php else: ?>
                                    <a class="btn btn-animated" href="<?= base_url('vendor/professionals/' . $prof['id'] . '/exceptions') ?>">📅 Datas Específicas</a>
                                <?php endif; ?>
                                <form method="post" action="<?= base_url('vendor/professionals/' . $prof['id'] . '/toggle') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-light btn-animated" type="submit"><?= (int) $prof['is_active'] ? '⏸ Desativar' : '▶ Ativar' ?></button>
                                </form>
                                <form method="post" action="<?= base_url('vendor/professionals/' . $prof['id'] . '/delete') ?>" onsubmit="return confirm('Tem certeza que deseja excluir este profissional?')">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger btn-animated" type="submit">🗑 Excluir</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if (empty($professionals)): ?>
                    <div class="empty-state empty-state--premium">Nenhum profissional cadastrado. Adicione membros da sua equipe para gerenciar agendas individuais.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

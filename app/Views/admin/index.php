<section class="stack stack--spacious">
    <div class="card hero hero--dashboard">
        <div class="topbar topbar--admin-hero">
            <div>
                <div class="brand-lockup" style="margin-bottom:14px;">
                    <img class="brand-logo-image" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="64" decoding="async">
                </div>
                <span class="soft-pill soft-pill--gold">Controle da plataforma</span>
                <h1 class="page-title">Painel Admin</h1>
                <p class="page-subtitle">Aprovação manual de vendors, gestão de planos e leitura operacional com acabamento premium.</p>
            </div>
            <form method="post" action="<?= base_url('auth/logout') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-secondary" type="submit">Sair</button>
            </form>
        </div>
    </div>

    <div class="app-grid two admin-grid--premium">
        <div class="stack">
            <div class="card card--section">
                <div class="section-header section-header--premium">
                    <div>
                        <span class="section-kicker">Vendedores</span>
                        <h2>Ativação e status</h2>
                        <p class="muted">Filtros práticos e ações rápidas para aprovação, suspensão e reativação.</p>
                    </div>
                </div>

                <div class="inline-actions inline-actions--wrap" style="margin-bottom: 16px;">
                    <?php foreach (['all' => 'Todos', 'pending' => 'Pendentes', 'active' => 'Ativos', 'due_soon' => 'A vencer', 'suspended' => 'Suspensos', 'expired' => 'Expirados'] as $key => $label): ?>
                        <a class="btn <?= $filter === $key ? '' : 'btn-light' ?>" href="<?= base_url('admin?status=' . $key) ?>"><?= e($label) ?></a>
                    <?php endforeach; ?>
                </div>

                <div class="table-wrap table-wrap--premium">
                    <table>
                        <thead>
                            <tr>
                                <th>Vendor</th>
                                <th>Status</th>
                                <th>Plano</th>
                                <th>Expira</th>
                                <th>Vence em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendors as $vendorItem): ?>
                                <?php $daysToExpire = days_until($vendorItem['plan_expires_at'] ?? null); ?>
                                <tr>
                                    <td>
                                        <strong><?= e($vendorItem['business_name']) ?></strong><br>
                                        <span class="muted"><?= e($vendorItem['email'] ?? '') ?></span><br>
                                        <span class="muted"><?= e($vendorItem['category']) ?></span>
                                    </td>
                                    <td><span class="badge <?= status_class($vendorItem['status']) ?>"><?= e(status_label($vendorItem['status'])) ?></span></td>
                                    <td><?= e($vendorItem['plan_name'] ?? 'Sem plano') ?></td>
                                    <td><?= format_date($vendorItem['plan_expires_at'] ?? null) ?></td>
                                    <td class="muted">
                                        <?php if ($daysToExpire === null || ($vendorItem['plan_expires_at'] ?? null) === null): ?>
                                            -
                                        <?php else: ?>
                                            <?= (int) $daysToExpire ?> dia(s)
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="stack stack--compact">
                                            <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/activate') ?>" class="form-grid">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                                <select name="plan_id" required>
                                                    <option value="">Selecionar plano</option>
                                                    <?php foreach ($plans as $plan): ?>
                                                        <option value="<?= (int) $plan['id'] ?>" <?= (int) ($vendorItem['plan_id'] ?? 0) === (int) $plan['id'] ? 'selected' : '' ?>>
                                                            <?= e($plan['name']) ?> - <?= money($plan['price']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn" type="submit">Ativar</button>
                                            </form>

                                            <?php if (($vendorItem['status'] ?? '') === 'active' && (int) ($vendorItem['plan_id'] ?? 0) > 0): ?>
                                                <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/renew') ?>" class="form-grid">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                                    <input type="hidden" name="plan_id" value="<?= (int) ($vendorItem['plan_id'] ?? 0) ?>">
                                                    <button class="btn btn-secondary" type="submit">Renovar</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($vendorItem['status'] === 'active'): ?>
                                                <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/suspend') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                                    <button class="btn btn-danger" type="submit">Suspender</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/reactivate') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                                    <input type="hidden" name="plan_id" value="<?= (int) ($vendorItem['plan_id'] ?? 0) ?>">
                                                    <button class="btn btn-light" type="submit">Reativar</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="card card--section">
                <div class="section-header section-header--premium">
                    <div>
                        <span class="section-kicker">Planos</span>
                        <h2>Criação e manutenção</h2>
                        <p class="muted">Cadastre planos com preço, duração e status ativo.</p>
                    </div>
                </div>

                <form class="form-grid form-grid--premium" method="post" action="<?= base_url('admin/plans') ?>" data-disable-on-submit>
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int) ($editing_plan['id'] ?? 0) ?>">
                    <div class="field">
                        <label for="plan_name">Nome</label>
                        <input id="plan_name" name="name" type="text" value="<?= e($editing_plan['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-grid two">
                        <div class="field">
                            <label for="plan_price">Preço</label>
                            <input id="plan_price" name="price" type="number" min="0" step="0.01" value="<?= e($editing_plan['price'] ?? '') ?>" required>
                        </div>
                        <div class="field">
                            <label for="duration_days">Duração (dias)</label>
                            <input id="duration_days" name="duration_days" type="number" min="1" step="1" value="<?= e($editing_plan['duration_days'] ?? 30) ?>" required>
                        </div>
                    </div>
                    <div class="field">
                        <label for="plan_description">Descrição</label>
                        <textarea id="plan_description" name="description"><?= e($editing_plan['description'] ?? '') ?></textarea>
                    </div>
                    <label class="checkbox-row"><input type="checkbox" name="is_active" <?= !isset($editing_plan['is_active']) || (int) ($editing_plan['is_active'] ?? 0) ? 'checked' : '' ?>> Plano ativo</label>
                    <button class="btn" type="submit" data-loading-label="Salvando... "><?= $editing_plan ? 'Atualizar plano' : 'Salvar plano' ?></button>
                </form>
            </div>

            <div class="card card--section">
                <div class="table-wrap table-wrap--premium">
                    <table>
                        <thead>
                            <tr>
                                <th>Plano</th>
                                <th>Preço</th>
                                <th>Duração</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plans as $plan): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($plan['name']) ?></strong><br>
                                        <span class="muted"><?= e($plan['description']) ?></span>
                                    </td>
                                    <td><?= money($plan['price']) ?></td>
                                    <td><?= (int) $plan['duration_days'] ?> dias</td>
                                    <td><span class="badge <?= (int) $plan['is_active'] ? 'is-success' : 'is-neutral' ?>"><?= (int) $plan['is_active'] ? 'Ativo' : 'Inativo' ?></span></td>
                                    <td>
                                        <div class="inline-actions inline-actions--wrap">
                                            <a class="btn btn-light" href="<?= base_url('admin?edit_plan=' . $plan['id']) ?>">Editar</a>
                                            <form method="post" action="<?= base_url('admin/plans/' . $plan['id'] . '/delete') ?>">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-light" type="submit">Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

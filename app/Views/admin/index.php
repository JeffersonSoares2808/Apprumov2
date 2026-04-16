<?php
$allowedFilters = ['all', 'pending', 'active', 'due_soon', 'suspended', 'expired'];
$activeTab = isset($_GET['edit_plan']) ? 'plans' : ($_GET['tab'] ?? 'vendors');
if (!in_array($activeTab, ['vendors', 'plans', 'payments'], true)) {
    $activeTab = 'vendors';
}
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}
?>

<section class="admin-shell">
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header__inner">
            <div class="admin-header__brand">
                <img class="admin-header__logo" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="140" height="46" decoding="async">
                <div>
                    <span class="admin-header__badge">Admin</span>
                    <h1 class="admin-header__title">Painel de Controle</h1>
                </div>
            </div>
            <form method="post" action="<?= base_url('auth/logout') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-secondary" type="submit">Sair</button>
            </form>
        </div>
    </header>

    <!-- Tab Navigation -->
    <nav class="admin-tabs" aria-label="Seções do painel admin">
        <a class="admin-tab <?= $activeTab === 'vendors' ? 'is-active' : '' ?>" href="<?= base_url('admin?tab=vendors&status=' . e($filter)) ?>">
            <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/></svg>
            <span>Vendedores</span>
        </a>
        <a class="admin-tab <?= $activeTab === 'plans' ? 'is-active' : '' ?>" href="<?= base_url('admin?tab=plans') ?>">
            <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M3 10h18" stroke="currentColor" stroke-width="1.8"/></svg>
            <span>Planos</span>
        </a>
        <a class="admin-tab <?= $activeTab === 'payments' ? 'is-active' : '' ?>" href="<?= base_url('admin?tab=payments') ?>">
            <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span>Pagamentos</span>
        </a>
    </nav>

    <!-- Tab: Vendors -->
    <div class="admin-panel <?= $activeTab === 'vendors' ? 'is-visible' : '' ?>" id="panel-vendors">
        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Vendedores</span>
                    <h2>Ativação e status</h2>
                    <p class="muted">Filtros práticos e ações rápidas para aprovação, suspensão e reativação.</p>
                </div>
            </div>

            <div class="admin-filter-bar">
                <?php foreach (['all' => 'Todos', 'pending' => 'Pendentes', 'active' => 'Ativos', 'due_soon' => 'A vencer', 'suspended' => 'Suspensos', 'expired' => 'Expirados'] as $key => $label): ?>
                    <a class="admin-filter-chip <?= $filter === $key ? 'is-active' : '' ?>" href="<?= base_url('admin?tab=vendors&status=' . $key) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>

            <!-- Desktop table (hidden on mobile) -->
            <div class="admin-table-desktop">
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
                                        <div class="admin-action-group">
                                            <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/activate') ?>" class="admin-activate-form">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                                <select name="plan_id" required>
                                                    <option value="">Plano</option>
                                                    <?php foreach ($plans as $plan): ?>
                                                        <option value="<?= (int) $plan['id'] ?>" <?= (int) ($vendorItem['plan_id'] ?? 0) === (int) $plan['id'] ? 'selected' : '' ?>>
                                                            <?= e($plan['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button class="btn btn--sm" type="submit">Ativar</button>
                                            </form>
                                            <?php if (($vendorItem['status'] ?? '') === 'active' && (int) ($vendorItem['plan_id'] ?? 0) > 0): ?>
                                                <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/renew') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                                    <input type="hidden" name="plan_id" value="<?= (int) ($vendorItem['plan_id'] ?? 0) ?>">
                                                    <button class="btn btn-secondary btn--sm" type="submit">Renovar</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($vendorItem['status'] === 'active'): ?>
                                                <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/suspend') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                                    <button class="btn btn-danger btn--sm" type="submit">Suspender</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/reactivate') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                                    <input type="hidden" name="plan_id" value="<?= (int) ($vendorItem['plan_id'] ?? 0) ?>">
                                                    <button class="btn btn-light btn--sm" type="submit">Reativar</button>
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

            <!-- Mobile card list (hidden on desktop) -->
            <div class="admin-card-list">
                <?php foreach ($vendors as $vendorItem): ?>
                    <?php $daysToExpire = days_until($vendorItem['plan_expires_at'] ?? null); ?>
                    <article class="vendor-card">
                        <div class="vendor-card__header">
                            <div>
                                <strong class="vendor-card__name"><?= e($vendorItem['business_name']) ?></strong>
                                <span class="muted vendor-card__email"><?= e($vendorItem['email'] ?? '') ?></span>
                            </div>
                            <span class="badge <?= status_class($vendorItem['status']) ?>"><?= e(status_label($vendorItem['status'])) ?></span>
                        </div>
                        <div class="vendor-card__details">
                            <div class="vendor-card__detail">
                                <span class="vendor-card__label">Categoria</span>
                                <span><?= e($vendorItem['category']) ?></span>
                            </div>
                            <div class="vendor-card__detail">
                                <span class="vendor-card__label">Plano</span>
                                <span><?= e($vendorItem['plan_name'] ?? 'Sem plano') ?></span>
                            </div>
                            <div class="vendor-card__detail">
                                <span class="vendor-card__label">Expira</span>
                                <span><?= format_date($vendorItem['plan_expires_at'] ?? null) ?></span>
                            </div>
                            <?php if ($daysToExpire !== null && ($vendorItem['plan_expires_at'] ?? null) !== null): ?>
                                <div class="vendor-card__detail">
                                    <span class="vendor-card__label">Vence em</span>
                                    <span><?= (int) $daysToExpire ?> dia(s)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="vendor-card__actions">
                            <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/activate') ?>" class="vendor-card__activate">
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
                                <button class="btn btn--sm" type="submit">Ativar</button>
                            </form>
                            <div class="vendor-card__btns">
                                <?php if (($vendorItem['status'] ?? '') === 'active' && (int) ($vendorItem['plan_id'] ?? 0) > 0): ?>
                                    <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/renew') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                        <input type="hidden" name="plan_id" value="<?= (int) ($vendorItem['plan_id'] ?? 0) ?>">
                                        <button class="btn btn-secondary btn--sm btn-block" type="submit">Renovar</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($vendorItem['status'] === 'active'): ?>
                                    <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/suspend') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                        <button class="btn btn-danger btn--sm btn-block" type="submit">Suspender</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?= base_url('admin/vendors/' . $vendorItem['id'] . '/reactivate') ?>">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="current_filter" value="<?= e($filter) ?>">
                                        <input type="hidden" name="plan_id" value="<?= (int) ($vendorItem['plan_id'] ?? 0) ?>">
                                        <button class="btn btn-light btn--sm btn-block" type="submit">Reativar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Tab: Plans -->
    <div class="admin-panel <?= $activeTab === 'plans' ? 'is-visible' : '' ?>" id="panel-plans">
        <div class="admin-plans-layout">
            <div class="card card--section">
                <div class="section-header section-header--premium">
                    <div>
                        <span class="section-kicker">Planos</span>
                        <h2><?= $editing_plan ? 'Editar plano' : 'Novo plano' ?></h2>
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
                        <label for="max_professionals">Máx. profissionais (0 = sem equipe)</label>
                        <input id="max_professionals" name="max_professionals" type="number" min="0" step="1" value="<?= e($editing_plan['max_professionals'] ?? 0) ?>">
                    </div>
                    <div class="field">
                        <label for="plan_description">Descrição</label>
                        <textarea id="plan_description" name="description"><?= e($editing_plan['description'] ?? '') ?></textarea>
                    </div>
                    <div class="field">
                        <label for="stripe_checkout_url">Link de pagamento Stripe</label>
                        <input id="stripe_checkout_url" name="stripe_checkout_url" type="url" value="<?= e($editing_plan['stripe_checkout_url'] ?? '') ?>" placeholder="https://buy.stripe.com/...">
                        <span class="muted" style="font-size:.82rem;">Cole o link do Stripe Payment Link para pagamento automático.</span>
                    </div>
                    <label class="checkbox-row"><input type="checkbox" name="is_active" <?= !isset($editing_plan['is_active']) || (int) ($editing_plan['is_active'] ?? 0) ? 'checked' : '' ?>> Plano ativo</label>
                    <button class="btn" type="submit" data-loading-label="Salvando... "><?= $editing_plan ? 'Atualizar plano' : 'Salvar plano' ?></button>
                </form>
            </div>

            <div class="card card--section">
                <div class="section-header section-header--premium">
                    <div>
                        <span class="section-kicker">Cadastrados</span>
                        <h2>Planos existentes</h2>
                    </div>
                </div>

                <!-- Desktop plan table -->
                <div class="admin-table-desktop">
                    <div class="table-wrap table-wrap--premium">
                        <table>
                            <thead>
                                <tr>
                                    <th>Plano</th>
                                    <th>Preço</th>
                                    <th>Duração</th>
                                    <th>Profissionais</th>
                                    <th>Checkout</th>
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
                                        <td><?= (int) ($plan['max_professionals'] ?? 0) === 0 ? 'Individual' : (int) $plan['max_professionals'] ?></td>
                                        <td>
                                            <?php if (!empty($plan['stripe_checkout_url'])): ?>
                                                <a href="<?= e($plan['stripe_checkout_url']) ?>" target="_blank" rel="noopener" class="btn btn-light btn--sm" title="Abrir link de pagamento">
                                                    <svg viewBox="0 0 24 24" fill="none" width="14" height="14" style="vertical-align:middle;margin-right:2px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                                    Stripe
                                                </a>
                                            <?php else: ?>
                                                <span class="muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge <?= (int) $plan['is_active'] ? 'is-success' : 'is-neutral' ?>"><?= (int) $plan['is_active'] ? 'Ativo' : 'Inativo' ?></span></td>
                                        <td>
                                            <div class="inline-actions inline-actions--wrap">
                                                <a class="btn btn-light btn--sm" href="<?= base_url('admin?tab=plans&edit_plan=' . $plan['id']) ?>">Editar</a>
                                                <form method="post" action="<?= base_url('admin/plans/' . $plan['id'] . '/delete') ?>">
                                                    <?= csrf_field() ?>
                                                    <button class="btn btn-light btn--sm" type="submit">Excluir</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile plan cards -->
                <div class="admin-card-list">
                    <?php foreach ($plans as $plan): ?>
                        <article class="plan-card">
                            <div class="plan-card__header">
                                <strong><?= e($plan['name']) ?></strong>
                                <span class="badge <?= (int) $plan['is_active'] ? 'is-success' : 'is-neutral' ?>"><?= (int) $plan['is_active'] ? 'Ativo' : 'Inativo' ?></span>
                            </div>
                            <p class="muted plan-card__desc"><?= e($plan['description']) ?></p>
                            <div class="plan-card__meta">
                                <span><strong><?= money($plan['price']) ?></strong></span>
                                <span class="muted"><?= (int) $plan['duration_days'] ?> dias</span>
                                <span class="muted"><?= (int) ($plan['max_professionals'] ?? 0) === 0 ? 'Individual' : 'Até ' . (int) $plan['max_professionals'] . ' profissionais' ?></span>
                                <?php if (!empty($plan['stripe_checkout_url'])): ?>
                                    <a href="<?= e($plan['stripe_checkout_url']) ?>" target="_blank" rel="noopener" class="stripe-link-badge">
                                        <svg viewBox="0 0 24 24" fill="none" width="12" height="12"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14 21 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        Stripe Checkout
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="plan-card__actions">
                                <a class="btn btn-light btn--sm" href="<?= base_url('admin?tab=plans&edit_plan=' . $plan['id']) ?>">Editar</a>
                                <form method="post" action="<?= base_url('admin/plans/' . $plan['id'] . '/delete') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-light btn--sm" type="submit">Excluir</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Payments -->
    <div class="admin-panel <?= $activeTab === 'payments' ? 'is-visible' : '' ?>" id="panel-payments">
        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Pagamentos</span>
                    <h2>Histórico de pagamentos Stripe</h2>
                    <p class="muted">Pagamentos automáticos recebidos via Stripe Checkout. O webhook ativa/renova os planos automaticamente.</p>
                </div>
            </div>

            <?php if (empty($payments)): ?>
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" fill="none" width="48" height="48" style="opacity:.3;margin-bottom:12px;"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <p class="muted">Nenhum pagamento automático registrado ainda.</p>
                    <p class="muted" style="font-size:.82rem;max-width:400px;margin:8px auto 0;">
                        Configure os links de pagamento Stripe na aba Planos e o webhook em
                        <code style="background:#f0f0f0;padding:2px 5px;border-radius:4px;font-size:.78rem;"><?= e(base_url('webhook/stripe')) ?></code>
                    </p>
                </div>
            <?php else: ?>
                <!-- Desktop payments table -->
                <div class="admin-table-desktop">
                    <div class="table-wrap table-wrap--premium">
                        <table>
                            <thead>
                                <tr>
                                    <th>Vendedor</th>
                                    <th>Plano</th>
                                    <th>Valor</th>
                                    <th>Pago em</th>
                                    <th>Status</th>
                                    <th>Expira</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <strong><?= e($payment['business_name']) ?></strong><br>
                                            <span class="muted"><?= e($payment['email'] ?? '') ?></span>
                                        </td>
                                        <td><?= e($payment['plan_name'] ?? 'Sem plano') ?></td>
                                        <td><?= money($payment['plan_price'] ?? 0) ?></td>
                                        <td><?= format_datetime($payment['stripe_paid_at'] ?? null) ?></td>
                                        <td><span class="badge <?= status_class($payment['status']) ?>"><?= e(status_label($payment['status'])) ?></span></td>
                                        <td><?= format_date($payment['plan_expires_at'] ?? null) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile payment cards -->
                <div class="admin-card-list">
                    <?php foreach ($payments as $payment): ?>
                        <article class="vendor-card">
                            <div class="vendor-card__header">
                                <div>
                                    <strong class="vendor-card__name"><?= e($payment['business_name']) ?></strong>
                                    <span class="muted vendor-card__email"><?= e($payment['email'] ?? '') ?></span>
                                </div>
                                <span class="badge <?= status_class($payment['status']) ?>"><?= e(status_label($payment['status'])) ?></span>
                            </div>
                            <div class="vendor-card__details">
                                <div class="vendor-card__detail">
                                    <span class="vendor-card__label">Plano</span>
                                    <span><?= e($payment['plan_name'] ?? 'Sem plano') ?></span>
                                </div>
                                <div class="vendor-card__detail">
                                    <span class="vendor-card__label">Valor</span>
                                    <span><?= money($payment['plan_price'] ?? 0) ?></span>
                                </div>
                                <div class="vendor-card__detail">
                                    <span class="vendor-card__label">Pago em</span>
                                    <span><?= format_datetime($payment['stripe_paid_at'] ?? null) ?></span>
                                </div>
                                <div class="vendor-card__detail">
                                    <span class="vendor-card__label">Expira</span>
                                    <span><?= format_date($payment['plan_expires_at'] ?? null) ?></span>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="webhook-info-card">
                <h3>⚡ Configuração do Webhook</h3>
                <p>Para pagamentos automáticos, configure no painel Stripe:</p>
                <ol>
                    <li>Acesse <strong>Developers → Webhooks</strong> no Stripe</li>
                    <li>Adicione o endpoint: <code><?= e(base_url('webhook/stripe')) ?></code></li>
                    <li>Selecione o evento: <code>checkout.session.completed</code></li>
                    <li>Copie o <strong>Signing secret</strong> e coloque no <code>.env</code> como <code>STRIPE_WEBHOOK_SECRET</code></li>
                </ol>
            </div>
        </div>
    </div>
</section>

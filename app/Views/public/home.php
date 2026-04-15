<?php
$hasVendors = !empty($vendors);
$hasSearch = $search !== '';
$hasCategory = $selected_category !== '';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <h1 class="hero-title">Encontre os melhores <span class="hero-highlight">profissionais</span> perto de você</h1>
        <p class="hero-subtitle">Agende serviços de beleza, estética, saúde e muito mais com profissionais de confiança.</p>

        <form class="hero-search" method="get" action="<?= base_url('') ?>">
            <?php if ($hasCategory): ?>
                <input type="hidden" name="category" value="<?= e($selected_category) ?>">
            <?php endif; ?>
            <div class="hero-search__row">
                <div class="hero-search__field hero-search__field--main">
                    <svg class="hero-search__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Buscar por nome, serviço ou local..." class="hero-search__input" autocomplete="off">
                </div>
                <button type="submit" class="hero-search__btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    Buscar
                </button>
            </div>
        </form>

        <div class="hero-stats">
            <div class="hero-stat">
                <strong><?= count($vendors) ?></strong>
                <span>Profissional<?= count($vendors) !== 1 ? 'is' : '' ?></span>
            </div>
            <div class="hero-stat">
                <strong><?= count($categories) ?></strong>
                <span>Categoria<?= count($categories) !== 1 ? 's' : '' ?></span>
            </div>
            <div class="hero-stat">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:2px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Agendamento seguro</span>
            </div>
        </div>
    </div>
</section>

<!-- Trust Bar -->
<section class="marketplace-trust">
    <div class="marketplace-trust__items">
        <div class="marketplace-trust__item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <div>
                <strong>Agendamento online 24h</strong>
                <span>Reserve a qualquer momento</span>
            </div>
        </div>
        <div class="marketplace-trust__item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <div>
                <strong>Confirmação imediata</strong>
                <span>Sem espera, sem surpresas</span>
            </div>
        </div>
        <div class="marketplace-trust__item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <div>
                <strong>100% seguro</strong>
                <span>Dados protegidos (LGPD)</span>
            </div>
        </div>
        <div class="marketplace-trust__item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <div>
                <strong>Perto de você</strong>
                <span>Encontre por localização</span>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<?php if (!empty($categories)): ?>
<section class="marketplace-categories">
    <div class="section-header section-header--premium">
        <div>
            <span class="section-kicker">Categorias populares</span>
            <h2>Explore por especialidade</h2>
        </div>
    </div>

    <div class="category-chips">
        <a class="category-chip <?= !$hasCategory ? 'is-active' : '' ?>" href="<?= base_url($hasSearch ? '?q=' . urlencode($search) : '') ?>">
            <span class="category-chip__icon">🏠</span>
            <span>Todos</span>
        </a>
        <?php
        $categoryIcons = [
            'Barbearia' => '💈', 'Salão de Beleza' => '💇', 'Estética' => '✨', 'Estetica' => '✨',
            'Manicure' => '💅', 'Massagem' => '💆', 'Saúde' => '🏥', 'Fitness' => '🏋️',
            'Tatuagem' => '🎨', 'Depilação' => '🌸', 'Maquiagem' => '💄', 'Podologia' => '🦶',
            'Fisioterapia' => '🦴', 'Nutrição' => '🥗', 'Psicologia' => '🧠', 'Personal' => '💪',
            'Consultoria' => '📋', 'Educação' => '📚', 'Pet' => '🐾', 'Automotivo' => '🚗',
        ];
        foreach ($categories as $cat):
            $icon = $categoryIcons[$cat['category']] ?? '📌';
        ?>
            <a class="category-chip <?= $selected_category === $cat['category'] ? 'is-active' : '' ?>" href="<?= base_url('?category=' . urlencode($cat['category']) . ($hasSearch ? '&q=' . urlencode($search) : '')) ?>">
                <span class="category-chip__icon"><?= $icon ?></span>
                <span><?= e($cat['category']) ?></span>
                <span class="category-chip__count"><?= (int) $cat['vendor_count'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Active Filters -->
<?php if ($hasSearch || $hasCategory): ?>
<section class="marketplace-filters-active">
    <div class="filters-active__row">
        <span class="muted">Filtros ativos:</span>
        <?php if ($hasSearch): ?>
            <span class="filter-tag">
                🔍 "<?= e($search) ?>"
                <a href="<?= base_url($hasCategory ? '?category=' . urlencode($selected_category) : '') ?>" class="filter-tag__remove" title="Remover filtro">✕</a>
            </span>
        <?php endif; ?>
        <?php if ($hasCategory): ?>
            <span class="filter-tag">
                📁 <?= e($selected_category) ?>
                <a href="<?= base_url($hasSearch ? '?q=' . urlencode($search) : '') ?>" class="filter-tag__remove" title="Remover filtro">✕</a>
            </span>
        <?php endif; ?>
        <a class="btn btn-light btn-sm" href="<?= base_url('') ?>">Limpar tudo</a>
    </div>
</section>
<?php endif; ?>

<!-- Vendors Grid -->
<section class="marketplace-vendors">
    <div class="section-header section-header--premium">
        <div>
            <span class="section-kicker"><?= $hasSearch || $hasCategory ? 'Resultados' : 'Profissionais em destaque' ?></span>
            <h2><?= $hasSearch || $hasCategory ? count($vendors) . ' profissional(is) encontrado(s)' : 'Agende com quem é referência' ?></h2>
            <p class="muted">Clique em um profissional para ver os serviços e agendar online.</p>
        </div>
    </div>

    <?php if ($hasVendors): ?>
    <div class="vendor-grid">
        <?php foreach ($vendors as $v): ?>
        <a class="vendor-card" href="<?= base_url('p/' . e($v['slug'])) ?>">
            <?php
            $coverPos = ($v['cover_position'] ?? 'center');
            if (!in_array($coverPos, ['top', 'center', 'bottom'], true)) {
                $coverPos = 'center';
            }
            ?>
            <div class="vendor-card__cover" style="<?= !empty($v['cover_image']) ? 'background-image:url(' . e(asset(ltrim($v['cover_image'], '/'))) . ');background-size:cover;background-position:center ' . e($coverPos) . ';' : '' ?>">
                <div class="vendor-card__cover-overlay"></div>
                <?php if ((float) ($v['public_rating'] ?? 0) >= 4.5): ?>
                    <span class="vendor-card__badge">⭐ Top</span>
                <?php endif; ?>
            </div>
            <div class="vendor-card__body">
                <div class="vendor-card__avatar-wrap">
                    <?php if (!empty($v['profile_image'])): ?>
                        <img class="vendor-card__avatar" src="<?= asset(ltrim($v['profile_image'], '/')) ?>" alt="<?= e($v['business_name']) ?>" loading="lazy" decoding="async">
                    <?php else: ?>
                        <div class="vendor-card__avatar vendor-card__avatar--initials"><?= e(vendor_initials($v['business_name'])) ?></div>
                    <?php endif; ?>
                </div>
                <div class="vendor-card__info">
                    <h3 class="vendor-card__name"><?= e($v['business_name']) ?></h3>
                    <span class="vendor-card__category"><?= e($v['category']) ?></span>
                    <div class="vendor-card__meta">
                        <span class="vendor-card__rating">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="color:#f5a623;vertical-align:-2px;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <?= number_format((float) ($v['public_rating'] ?? 5), 1, ',', '.') ?>
                            <span class="muted">(<?= (int) ($v['rating_count'] ?? 0) ?>)</span>
                        </span>
                        <?php if ((int) ($v['service_count'] ?? 0) > 0): ?>
                            <span class="vendor-card__services"><?= (int) $v['service_count'] ?> serviço<?= (int) $v['service_count'] !== 1 ? 's' : '' ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($v['address'])): ?>
                        <div class="vendor-card__location">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?= e(mb_strimwidth($v['address'], 0, 50, '...')) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="vendor-card__cta">
                    <span class="btn btn-sm" style="background:<?= e($v['button_color'] ?: '#1AB2C7') ?>;color:#fff;">Ver perfil</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state empty-state--premium marketplace-empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3;margin-bottom:12px;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <h3>Nenhum profissional encontrado</h3>
        <p class="muted">Tente ajustar seus filtros ou busque por outra categoria.</p>
        <?php if ($hasSearch || $hasCategory): ?>
            <a class="btn" href="<?= base_url('') ?>">Ver todos</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>

<!-- CTA Section -->
<section class="marketplace-cta">
    <div class="marketplace-cta__content">
        <h2>É profissional? Cadastre-se grátis!</h2>
        <p>Receba agendamentos online, gerencie sua agenda, controle financeiro e muito mais.</p>
        <div class="inline-actions" style="justify-content:center;">
            <a class="btn btn-animated marketplace-cta__btn" href="<?= base_url('register') ?>">Criar minha conta</a>
            <a class="btn btn-light btn-animated" href="<?= base_url('login') ?>">Já tenho conta</a>
        </div>
    </div>
</section>

<footer class="marketplace-footer">
    <div class="marketplace-footer__content">
        <div class="marketplace-footer__brand">
            <img src="<?= brand_logo_url() ?>" alt="Apprumo" class="marketplace-footer__logo" loading="lazy">
            <p class="muted">Gestão integrada para profissionais autônomos: agenda, estoque e financeiro.</p>
        </div>
        <div class="marketplace-footer__links">
            <a href="<?= base_url('login') ?>">Login</a>
            <a href="<?= base_url('register') ?>">Cadastrar</a>
        </div>
    </div>
    <p class="footer-note">Desenvolvido por JS Sistemas Inteligentes</p>
</footer>

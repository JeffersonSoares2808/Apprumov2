<?php
$hasVendors = !empty($vendors);
$hasSearch = $search !== '';
$hasCategory = $selected_category !== '';
?>

<!-- Hero Section -->
<section class="landing-hero">
    <div class="landing-hero__bg" aria-hidden="true">
        <div class="landing-hero__shape landing-hero__shape--1"></div>
        <div class="landing-hero__shape landing-hero__shape--2"></div>
        <div class="landing-hero__shape landing-hero__shape--3"></div>
    </div>
    <div class="landing-hero__content">
        <span class="landing-hero__badge">✨ Plataforma completa com IA integrada</span>
        <h1 class="landing-hero__title">
            Gerencie seu negócio com <span class="landing-hero__gradient">inteligência</span> e simplicidade
        </h1>
        <p class="landing-hero__subtitle">
            Agenda, financeiro, estoque e atendimento em um só lugar. Comece agora com <strong>2 dias de teste grátis</strong> e descubra como a IA pode transformar sua operação.
        </p>
        <div class="landing-hero__cta">
            <a class="btn btn-hero btn-animated" href="<?= base_url('register') ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                Criar conta grátis
            </a>
            <a class="btn btn-hero-outline btn-animated" href="<?= base_url('login') ?>">Já tenho conta</a>
        </div>
        <p class="landing-hero__note">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Sem cartão de crédito · Acesso imediato · Cancele quando quiser
        </p>
    </div>
</section>

<!-- Features Section -->
<section class="landing-features">
    <div class="landing-features__grid">
        <div class="landing-feature">
            <div class="landing-feature__icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <h3>Agenda Inteligente</h3>
            <p>Agendamento online 24h com confirmação automática. Seus clientes marcam pelo link público.</p>
        </div>
        <div class="landing-feature">
            <div class="landing-feature__icon" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <h3>Controle Financeiro</h3>
            <p>Receitas, perdas e faturamento em tempo real. Saiba exatamente como seu negócio está performando.</p>
        </div>
        <div class="landing-feature">
            <div class="landing-feature__icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
            <h3>Estoque & Produtos</h3>
            <p>Controle de estoque com alertas de reposição. Venda produtos e acompanhe o inventário.</p>
        </div>
        <div class="landing-feature">
            <div class="landing-feature__icon" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2"><path d="M12 2a4 4 0 0 0-4 4v2H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2h-2V6a4 4 0 0 0-4-4z"/><circle cx="12" cy="15" r="1"/></svg>
            </div>
            <h3>Assistente IA</h3>
            <p>Inteligência artificial que gera relatórios, analisa tendências e sugere otimizações para sua agenda.</p>
        </div>
    </div>
</section>

<!-- Search Section (for existing marketplace) -->
<section class="landing-search-section">
    <div class="section-header section-header--premium" style="text-align:center;">
        <div>
            <span class="section-kicker">Encontre profissionais</span>
            <h2>Agende com quem é referência</h2>
            <p class="muted">Busque por profissionais cadastrados na plataforma.</p>
        </div>
    </div>

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
<?php if ($hasVendors): ?>
<section class="marketplace-vendors">
    <div class="section-header section-header--premium">
        <div>
            <span class="section-kicker"><?= $hasSearch || $hasCategory ? 'Resultados' : 'Profissionais em destaque' ?></span>
            <h2><?= $hasSearch || $hasCategory ? count($vendors) . ' profissional(is) encontrado(s)' : 'Agende com quem é referência' ?></h2>
            <p class="muted">Clique em um profissional para ver os serviços e agendar online.</p>
        </div>
    </div>

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
</section>
<?php elseif ($hasSearch || $hasCategory): ?>
<section class="marketplace-vendors">
    <div class="empty-state empty-state--premium marketplace-empty">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3;margin-bottom:12px;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
        <h3>Nenhum profissional encontrado</h3>
        <p class="muted">Tente ajustar seus filtros ou busque por outra categoria.</p>
        <a class="btn" href="<?= base_url('') ?>">Ver todos</a>
    </div>
</section>
<?php endif; ?>

<!-- Plans Preview Section -->
<section class="landing-plans">
    <div class="section-header section-header--premium" style="text-align:center;">
        <div>
            <span class="section-kicker">Planos</span>
            <h2>Escolha o plano ideal para você</h2>
            <p class="muted">Comece com 2 dias de teste grátis. Sem compromisso.</p>
        </div>
    </div>
    <div class="landing-plans__grid">
        <div class="landing-plan-card">
            <div class="landing-plan-card__header">
                <span class="landing-plan-card__badge">Mais popular</span>
                <h3>Autônomo Pro</h3>
                <div class="landing-plan-card__price">
                    <span class="landing-plan-card__currency">R$</span>
                    <span class="landing-plan-card__amount">79</span>
                    <span class="landing-plan-card__cents">,90</span>
                    <span class="landing-plan-card__period">/mês</span>
                </div>
            </div>
            <p class="landing-plan-card__desc">A evolução da gestão para profissionais autônomos.</p>
            <ul class="landing-plan-card__features">
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Controle de agenda completo</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Gestão financeira</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Controle de produtos e estoque</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Assistente IA para otimizar horários</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Perfil público com agendamento online</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Design moderno e responsivo</li>
            </ul>
            <a class="btn btn-animated landing-plan-card__cta" href="<?= base_url('register') ?>">Começar teste grátis</a>
        </div>

        <div class="landing-plan-card landing-plan-card--featured">
            <div class="landing-plan-card__header">
                <span class="landing-plan-card__badge landing-plan-card__badge--gold">Empresas</span>
                <h3>Business AI</h3>
                <div class="landing-plan-card__price">
                    <span class="landing-plan-card__currency">R$</span>
                    <span class="landing-plan-card__amount">129</span>
                    <span class="landing-plan-card__cents">,90</span>
                    <span class="landing-plan-card__period">/mês</span>
                </div>
            </div>
            <p class="landing-plan-card__desc">A solução definitiva para escalar sua empresa. Até 10 profissionais.</p>
            <ul class="landing-plan-card__features">
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Tudo do Autônomo Pro</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Até 10 profissionais na equipe</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> IA aplicada a agendamentos e finanças</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Gestão completa de estoque</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Relatórios avançados com insights</li>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Decisões inteligentes com dados reais</li>
            </ul>
            <a class="btn btn-animated landing-plan-card__cta landing-plan-card__cta--featured" href="<?= base_url('register') ?>">Começar teste grátis</a>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="landing-final-cta">
    <div class="landing-final-cta__content">
        <h2>Pronto para transformar seu negócio?</h2>
        <p>Cadastre-se agora e ganhe <strong>2 dias grátis</strong> para explorar todas as funcionalidades.</p>
        <div class="inline-actions" style="justify-content:center;gap:12px;">
            <a class="btn btn-hero btn-animated" href="<?= base_url('register') ?>">
                Criar minha conta grátis
            </a>
        </div>
    </div>
</section>

<footer class="marketplace-footer">
    <div class="marketplace-footer__content">
        <div class="marketplace-footer__brand">
            <img src="<?= brand_logo_url() ?>" alt="Apprumo" class="marketplace-footer__logo" loading="lazy">
            <p class="muted">Gestão integrada para profissionais autônomos: agenda, estoque e financeiro com IA.</p>
        </div>
        <div class="marketplace-footer__links">
            <a href="<?= base_url('login') ?>">Login</a>
            <a href="<?= base_url('register') ?>">Cadastrar</a>
        </div>
    </div>
    <p class="footer-note">Desenvolvido por JS Sistemas Inteligentes</p>
</footer>

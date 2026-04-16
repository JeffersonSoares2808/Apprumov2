<?php
$hasVendors = !empty($vendors);
$hasSearch = $search !== '';
$hasCategory = $selected_category !== '';
?>

<!-- Sticky Navbar -->
<nav class="lp-nav" id="lpNav">
    <div class="lp-nav__inner">
        <a href="<?= base_url('') ?>" class="lp-nav__brand">
            <img src="<?= brand_logo_url() ?>" alt="Apprumo" class="lp-nav__logo">
        </a>
        <div class="lp-nav__links" id="lpNavLinks">
            <a href="#lp-features" class="lp-nav__link">Funcionalidades</a>
            <a href="#lp-plans" class="lp-nav__link">Planos</a>
            <a href="#lp-search" class="lp-nav__link">Profissionais</a>
        </div>
        <div class="lp-nav__actions">
            <a href="<?= base_url('login') ?>" class="lp-nav__btn lp-nav__btn--ghost">Login</a>
            <a href="<?= base_url('register') ?>" class="lp-nav__btn lp-nav__btn--solid">Criar conta</a>
        </div>
        <button class="lp-nav__hamburger" id="lpNavToggle" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
    <!-- Mobile menu -->
    <div class="lp-nav__mobile" id="lpNavMobile" hidden>
        <a href="#lp-features" class="lp-nav__link">Funcionalidades</a>
        <a href="#lp-plans" class="lp-nav__link">Planos</a>
        <a href="#lp-search" class="lp-nav__link">Profissionais</a>
        <div class="lp-nav__mobile-actions">
            <a href="<?= base_url('login') ?>" class="lp-nav__btn lp-nav__btn--ghost">Login</a>
            <a href="<?= base_url('register') ?>" class="lp-nav__btn lp-nav__btn--solid">Criar conta</a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="lp-hero">
    <div class="lp-hero__bg" aria-hidden="true">
        <div class="lp-hero__orb lp-hero__orb--1"></div>
        <div class="lp-hero__orb lp-hero__orb--2"></div>
        <div class="lp-hero__orb lp-hero__orb--3"></div>
    </div>
    <div class="lp-hero__inner">
        <div class="lp-hero__text">
            <span class="lp-hero__badge">✨ Plataforma completa com IA integrada</span>
            <h1 class="lp-hero__title">
                Gerencie seu negócio com <span class="lp-hero__accent">inteligência</span> e simplicidade
            </h1>
            <p class="lp-hero__sub">
                Agenda, financeiro, estoque e atendimento em um só lugar. Comece agora com <strong>2 dias de teste grátis</strong> e descubra como a IA pode transformar sua operação.
            </p>
            <div class="lp-hero__cta">
                <a class="lp-btn lp-btn--primary" href="<?= base_url('register') ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    Criar conta grátis
                </a>
                <a class="lp-btn lp-btn--outline" href="<?= base_url('login') ?>">Já tenho conta</a>
            </div>
            <p class="lp-hero__trust">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                Sem cartão de crédito · Acesso imediato · Cancele quando quiser
            </p>
        </div>
        <div class="lp-hero__visual">
            <div class="lp-hero__card lp-hero__card--1">
                <span class="lp-hero__card-icon">📅</span>
                <span>Agenda Inteligente</span>
            </div>
            <div class="lp-hero__card lp-hero__card--2">
                <span class="lp-hero__card-icon">💰</span>
                <span>Controle Financeiro</span>
            </div>
            <div class="lp-hero__card lp-hero__card--3">
                <span class="lp-hero__card-icon">🤖</span>
                <span>Assistente IA</span>
            </div>
        </div>
    </div>
    <div class="lp-hero__stats">
        <div class="lp-hero__stat"><strong>+500</strong><span>profissionais</span></div>
        <div class="lp-hero__stat-sep"></div>
        <div class="lp-hero__stat"><strong>24/7</strong><span>disponível</span></div>
        <div class="lp-hero__stat-sep"></div>
        <div class="lp-hero__stat"><strong>IA</strong><span>integrada</span></div>
    </div>
</section>

<!-- Features Section -->
<section class="lp-features" id="lp-features">
    <div class="lp-container">
        <div class="lp-section-header">
            <span class="lp-kicker">Funcionalidades</span>
            <h2>Tudo que você precisa em um só lugar</h2>
            <p>Ferramentas profissionais para gerenciar e crescer seu negócio com eficiência.</p>
        </div>
        <div class="lp-features__grid">
            <div class="lp-feat">
                <div class="lp-feat__icon" style="--feat-bg:#dbeafe;--feat-clr:#2563eb;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h3>Agenda Inteligente</h3>
                <p>Agendamento online 24h com confirmação automática. Seus clientes marcam pelo link público.</p>
            </div>
            <div class="lp-feat">
                <div class="lp-feat__icon" style="--feat-bg:#dcfce7;--feat-clr:#16a34a;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                </div>
                <h3>Controle Financeiro</h3>
                <p>Receitas, perdas e faturamento em tempo real. Saiba exatamente como seu negócio está performando.</p>
            </div>
            <div class="lp-feat">
                <div class="lp-feat__icon" style="--feat-bg:#fef3c7;--feat-clr:#d97706;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </div>
                <h3>Estoque & Produtos</h3>
                <p>Controle de estoque com alertas de reposição. Venda produtos e acompanhe o inventário.</p>
            </div>
            <div class="lp-feat">
                <div class="lp-feat__icon" style="--feat-bg:#ede9fe;--feat-clr:#7c3aed;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a7 7 0 0 1 7 7v1a4 4 0 0 1-4 4h-1.5l-2.5 3v-3H10a4 4 0 0 1-4-4V9a7 7 0 0 1 6-6.93"/><circle cx="9" cy="9" r="1" fill="currentColor"/><circle cx="15" cy="9" r="1" fill="currentColor"/></svg>
                </div>
                <h3>Assistente IA</h3>
                <p>Inteligência artificial que gera relatórios, analisa tendências e sugere otimizações para sua agenda.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="lp-steps">
    <div class="lp-container">
        <div class="lp-section-header">
            <span class="lp-kicker">Como funciona</span>
            <h2>Comece em 3 passos simples</h2>
        </div>
        <div class="lp-steps__grid">
            <div class="lp-step">
                <div class="lp-step__num">1</div>
                <h3>Crie sua conta</h3>
                <p>Cadastre-se gratuitamente em menos de 1 minuto. Sem cartão de crédito.</p>
            </div>
            <div class="lp-step__connector" aria-hidden="true"></div>
            <div class="lp-step">
                <div class="lp-step__num">2</div>
                <h3>Configure seu negócio</h3>
                <p>Adicione serviços, horários e profissionais. A IA ajuda a organizar tudo.</p>
            </div>
            <div class="lp-step__connector" aria-hidden="true"></div>
            <div class="lp-step">
                <div class="lp-step__num">3</div>
                <h3>Comece a receber</h3>
                <p>Compartilhe seu link público e receba agendamentos automaticamente.</p>
            </div>
        </div>
    </div>
</section>

<!-- Search Section -->
<section class="lp-search" id="lp-search">
    <div class="lp-container">
        <div class="lp-section-header">
            <span class="lp-kicker">Encontre profissionais</span>
            <h2>Agende com quem é referência</h2>
            <p>Busque por profissionais cadastrados na plataforma.</p>
        </div>

        <div class="lp-search__card">
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
        </div>
    </div>
</section>

<!-- Trust Bar -->
<section class="lp-trust">
    <div class="lp-container">
        <div class="lp-trust__items">
            <div class="lp-trust__item">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1AB2C7" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <div><strong>Agendamento 24h</strong><span>Reserve a qualquer momento</span></div>
            </div>
            <div class="lp-trust__item">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1AB2C7" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <div><strong>Confirmação imediata</strong><span>Sem espera, sem surpresas</span></div>
            </div>
            <div class="lp-trust__item">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1AB2C7" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <div><strong>100% seguro</strong><span>Dados protegidos (LGPD)</span></div>
            </div>
            <div class="lp-trust__item">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1AB2C7" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <div><strong>Perto de você</strong><span>Encontre por localização</span></div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<?php if (!empty($categories)): ?>
<section class="marketplace-categories">
    <div class="lp-container">
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
    </div>
</section>
<?php endif; ?>

<!-- Active Filters -->
<?php if ($hasSearch || $hasCategory): ?>
<section class="marketplace-filters-active">
    <div class="lp-container">
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
    </div>
</section>
<?php endif; ?>

<!-- Vendors Grid -->
<?php if ($hasVendors): ?>
<section class="marketplace-vendors">
    <div class="lp-container">
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
    </div>
</section>
<?php elseif ($hasSearch || $hasCategory): ?>
<section class="marketplace-vendors">
    <div class="lp-container">
        <div class="empty-state empty-state--premium marketplace-empty">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3;margin-bottom:12px;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <h3>Nenhum profissional encontrado</h3>
            <p class="muted">Tente ajustar seus filtros ou busque por outra categoria.</p>
            <a class="btn" href="<?= base_url('') ?>">Ver todos</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Plans Section -->
<section class="lp-plans" id="lp-plans">
    <div class="lp-container">
        <div class="lp-section-header">
            <span class="lp-kicker">Planos</span>
            <h2>Escolha o plano ideal para você</h2>
            <p>Comece com 2 dias de teste grátis. Sem compromisso.</p>
        </div>
        <div class="lp-plans__grid">
            <div class="lp-plan">
                <div class="lp-plan__head">
                    <span class="lp-plan__badge">Mais popular</span>
                    <h3>Autônomo Pro</h3>
                    <div class="lp-plan__price">
                        <span class="lp-plan__currency">R$</span>
                        <span class="lp-plan__amount">79</span>
                        <span class="lp-plan__cents">,90</span>
                        <span class="lp-plan__period">/mês</span>
                    </div>
                </div>
                <p class="lp-plan__desc">A evolução da gestão para profissionais autônomos.</p>
                <ul class="lp-plan__list">
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Controle de agenda completo</li>
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Gestão financeira</li>
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Controle de produtos e estoque</li>
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Assistente IA</li>
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Perfil público com agendamento</li>
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Design moderno e responsivo</li>
                </ul>
                <a class="lp-btn lp-btn--dark lp-plan__cta" href="<?= base_url('register') ?>">Começar teste grátis</a>
            </div>
            <div class="lp-plan lp-plan--featured">
                <div class="lp-plan__head">
                    <span class="lp-plan__badge lp-plan__badge--gold">Empresas</span>
                    <h3>Business AI</h3>
                    <div class="lp-plan__price">
                        <span class="lp-plan__currency">R$</span>
                        <span class="lp-plan__amount">129</span>
                        <span class="lp-plan__cents">,90</span>
                        <span class="lp-plan__period">/mês</span>
                    </div>
                </div>
                <p class="lp-plan__desc">A solução definitiva para escalar sua empresa. Até 10 profissionais.</p>
                <ul class="lp-plan__list">
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Tudo do Autônomo Pro</li>
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Até 10 profissionais na equipe</li>
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> IA aplicada a agendamentos e finanças</li>
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Gestão completa de estoque</li>
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Relatórios avançados com insights</li>
                    <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Decisões inteligentes com dados reais</li>
                </ul>
                <a class="lp-btn lp-btn--primary lp-plan__cta" href="<?= base_url('register') ?>">Começar teste grátis</a>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="lp-cta-final">
    <div class="lp-cta-final__bg" aria-hidden="true">
        <div class="lp-hero__orb lp-hero__orb--1"></div>
        <div class="lp-hero__orb lp-hero__orb--2"></div>
    </div>
    <div class="lp-container">
        <h2>Pronto para transformar seu negócio?</h2>
        <p>Cadastre-se agora e ganhe <strong>2 dias grátis</strong> para explorar todas as funcionalidades.</p>
        <a class="lp-btn lp-btn--primary" href="<?= base_url('register') ?>">Criar minha conta grátis</a>
    </div>
</section>

<!-- Footer -->
<footer class="lp-footer">
    <div class="lp-container">
        <div class="lp-footer__inner">
            <div class="lp-footer__brand">
                <img src="<?= brand_logo_url() ?>" alt="Apprumo" class="lp-footer__logo" loading="lazy">
                <p>Gestão integrada para profissionais autônomos: agenda, estoque e financeiro com IA.</p>
            </div>
            <div class="lp-footer__links">
                <h4>Links</h4>
                <a href="<?= base_url('login') ?>">Login</a>
                <a href="<?= base_url('register') ?>">Cadastrar</a>
            </div>
        </div>
        <div class="lp-footer__bottom">
            <p>© <?= date('Y') ?> Apprumo · Desenvolvido por JS Sistemas Inteligentes</p>
        </div>
    </div>
</footer>

<script>
(function(){
    // Navbar scroll effect
    var nav = document.getElementById('lpNav');
    if(nav){
        window.addEventListener('scroll', function(){
            nav.classList.toggle('is-scrolled', window.scrollY > 40);
        }, {passive:true});
    }
    // Mobile menu toggle
    var toggle = document.getElementById('lpNavToggle');
    var mobile = document.getElementById('lpNavMobile');
    if(toggle && mobile){
        toggle.addEventListener('click', function(){
            var open = !mobile.hidden;
            mobile.hidden = open;
            toggle.classList.toggle('is-open', !open);
        });
        mobile.querySelectorAll('a').forEach(function(a){
            a.addEventListener('click', function(){ mobile.hidden = true; toggle.classList.remove('is-open'); });
        });
    }
    // Smooth scroll for anchor links
    document.querySelectorAll('.lp-nav__link[href^="#"]').forEach(function(a){
        a.addEventListener('click', function(e){
            var t = document.querySelector(this.getAttribute('href'));
            if(t){ e.preventDefault(); t.scrollIntoView({behavior:'smooth', block:'start'}); }
        });
    });
})();
</script>

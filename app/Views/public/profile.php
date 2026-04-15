<section class="stack stack--spacious public-stack">
    <div class="cover cover--premium" style="<?= !empty($vendor['cover_image']) ? 'background-image:url(' . e(asset(ltrim($vendor['cover_image'], '/'))) . '); background-size:cover; background-position:center;' : '' ?>">
        <div class="cover__overlay"></div>
    </div>

    <div class="card profile-card profile-card--premium">
        <div class="profile-card__identity">
            <?php if (!empty($vendor['profile_image'])): ?>
                <img class="avatar avatar--xl" src="<?= asset(ltrim($vendor['profile_image'], '/')) ?>" alt="<?= e($vendor['business_name']) ?>">
            <?php else: ?>
                <div class="avatar avatar--xl"><?= e(vendor_initials($vendor['business_name'])) ?></div>
            <?php endif; ?>

            <div class="stack stack--compact" style="flex:1;">
                <div>
                    <span class="section-kicker">Perfil público</span>
                    <h1 class="page-title"><?= e($vendor['business_name']) ?></h1>
                    <p class="page-subtitle"><?= e($vendor['category']) ?></p>
                </div>
                <p class="profile-bio"><?= e($vendor['bio'] ?: 'Perfil em preparação para receber seus clientes com uma experiência organizada e profissional.') ?></p>
                <div class="inline-actions inline-actions--wrap">
                    <span class="badge is-success">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:-2px;margin-right:3px;"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <?= number_format((float) ($vendor['public_rating'] ?? 5), 1, ',', '.') ?>
                    </span>
                    <span class="badge is-neutral"><?= (int) ($vendor['rating_count'] ?? 0) ?> avaliações</span>
                    <?php if (!empty($vendor['address'])): ?>
                        <span class="badge is-neutral">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px;margin-right:3px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <?php if (!empty($vendor['latitude']) && !empty($vendor['longitude'])): ?>
                                <a href="https://www.google.com/maps?q=<?= e($vendor['latitude']) ?>,<?= e($vendor['longitude']) ?>" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline;">
                                    <?= e($vendor['address']) ?>
                                </a>
                            <?php else: ?>
                                <?= e($vendor['address']) ?>
                            <?php endif; ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($vendor['phone'])): ?>
                    <div class="profile-contact">
                        <a class="btn btn-light btn-sm" href="<?= e(whatsapp_link($vendor['phone'], 'Olá! Vi seu perfil na Apprumo e gostaria de mais informações.')) ?>" target="_blank" rel="noopener">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:-2px;margin-right:4px;"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                            WhatsApp
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($vendor['latitude']) && !empty($vendor['longitude'])): ?>
    <div class="card card--section">
        <div class="section-header section-header--premium">
            <div>
                <span class="section-kicker">Localização</span>
                <h2>📍 Como chegar</h2>
                <?php if (!empty($vendor['address'])): ?>
                    <p class="muted"><?= e($vendor['address']) ?></p>
                <?php endif; ?>
            </div>
            <a class="btn btn-light btn-sm" href="https://www.google.com/maps/dir/?api=1&destination=<?= e($vendor['latitude']) ?>,<?= e($vendor['longitude']) ?>" target="_blank" rel="noopener">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:3px;"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                Abrir no Maps
            </a>
        </div>
        <div style="border-radius:12px;overflow:hidden;margin-top:8px;">
            <iframe
                width="100%"
                height="280"
                style="border:0;display:block;"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=<?= e($vendor['latitude']) ?>,<?= e($vendor['longitude']) ?>&output=embed"
                allowfullscreen>
            </iframe>
        </div>
    </div>
    <?php endif; ?>

    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Agendamento online</span>
                <h2>Serviços disponíveis</h2>
                <p class="muted">Escolha um serviço para abrir a agenda e reservar um horário.</p>
            </div>
            <span class="badge is-success"><?= count($services) ?> serviço<?= count($services) !== 1 ? 's' : '' ?> ativo<?= count($services) !== 1 ? 's' : '' ?></span>
        </div>

        <div class="stack stack--compact">
            <?php foreach ($services as $service): ?>
                <article class="public-service public-service--premium">
                    <div class="public-service__info">
                        <div class="public-service__header">
                            <strong class="public-service__title"><?= e($service['title']) ?></strong>
                            <span class="public-service__price"><?= money($service['price']) ?></span>
                        </div>
                        <div class="public-service__meta">
                            <span class="muted">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:2px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <?= (int) $service['duration_minutes'] ?> min
                            </span>
                        </div>
                        <?php if (!empty($service['description'])): ?>
                            <p class="public-service__desc muted"><?= e($service['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <a class="btn public-service__cta" style="background: <?= e($vendor['button_color'] ?: '#1AB2C7') ?>; color:#fff;" href="<?= base_url('book/' . $vendor['slug'] . '/' . $service['id']) ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                        Agendar
                    </a>
                </article>
            <?php endforeach; ?>

            <?php if ($services === []): ?>
                <div class="empty-state empty-state--premium">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.4;margin-bottom:8px;"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                    <p>Nenhum serviço ativo no momento.<br><span class="muted">Volte em breve para conferir as novidades!</span></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="public-footer">
        <p class="muted">Agendamento online por <strong>Apprumo</strong></p>
    </footer>
</section>

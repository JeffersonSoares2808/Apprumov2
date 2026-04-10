<section class="stack stack--spacious public-stack">
    <div class="cover cover--premium" style="<?= !empty($vendor['cover_image']) ? 'background-image:url(' . e(asset(ltrim($vendor['cover_image'], '/'))) . '); background-size:cover; background-position:center;' : '' ?>"></div>

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
                <p><?= e($vendor['bio'] ?: 'Perfil em preparação para receber seus clientes com uma experiência organizada e profissional.') ?></p>
                <div class="inline-actions inline-actions--wrap">
                    <span class="badge is-success"><?= number_format((float) ($vendor['public_rating'] ?? 5), 1, ',', '.') ?> de avaliação</span>
                    <span class="badge is-neutral"><?= (int) ($vendor['rating_count'] ?? 0) ?> avaliações</span>
                    <?php if (!empty($vendor['address'])): ?><span class="badge is-neutral"><?= e($vendor['address']) ?></span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Agendamento online</span>
                <h2>Serviços disponíveis</h2>
                <p class="muted">Escolha um serviço para abrir a agenda e reservar um horário.</p>
            </div>
        </div>

        <div class="stack stack--compact">
            <?php foreach ($services as $service): ?>
                <article class="public-service public-service--premium">
                    <div>
                        <strong><?= e($service['title']) ?></strong><br>
                        <span class="muted"><?= (int) $service['duration_minutes'] ?> min · <?= money($service['price']) ?></span>
                        <p class="muted"><?= e($service['description']) ?></p>
                    </div>
                    <a class="btn" style="background: <?= e($vendor['button_color'] ?: '#ddb76a') ?>; color:#fff;" href="<?= base_url('book/' . $vendor['slug'] . '/' . $service['id']) ?>">Agendar</a>
                </article>
            <?php endforeach; ?>

            <?php if ($services === []): ?>
                <div class="empty-state empty-state--premium">Nenhum serviço ativo no momento.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Catálogo de serviços</span>
                <h1 class="page-title">Serviços com cara de vitrine premium.</h1>
                <p class="page-subtitle">Cadastre, ajuste e ative itens sem perder clareza visual. O catálogo aqui já conversa com a agenda e com o booking público.</p>
            </div>
            <div class="soft-pill"><?= count($services) ?> item(ns) cadastrados</div>
        </div>
    </div>

    <div class="app-grid two">
        <div class="card card--section card--sticky">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker"><?= $editing_service ? 'Editando serviço' : 'Novo serviço' ?></span>
                    <h2><?= $editing_service ? 'Atualize os dados do serviço' : 'Cadastre um novo serviço' ?></h2>
                </div>
            </div>

            <form class="form-grid form-grid--premium" method="post" action="<?= base_url('vendor/services') ?>" enctype="multipart/form-data" data-disable-on-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) ($editing_service['id'] ?? 0) ?>">
                <div class="field">
                    <label for="service_title">Título</label>
                    <input id="service_title" name="title" type="text" value="<?= e($editing_service['title'] ?? '') ?>" required placeholder="Ex.: Corte premium">
                </div>
                <div class="field">
                    <label for="service_description">Descrição</label>
                    <textarea id="service_description" name="description" placeholder="Explique rapidamente o que está incluso."><?= e($editing_service['description'] ?? '') ?></textarea>
                </div>
                <div class="form-grid two">
                    <div class="field">
                        <label for="duration_minutes">Duração</label>
                        <input id="duration_minutes" name="duration_minutes" type="number" min="5" step="5" value="<?= e($editing_service['duration_minutes'] ?? 30) ?>" required>
                    </div>
                    <div class="field">
                        <label for="service_price">Preço</label>
                        <input id="service_price" name="price" type="number" min="0" step="0.01" value="<?= e($editing_service['price'] ?? 0) ?>" required>
                    </div>
                </div>
                <div class="field">
                    <label for="service_image">Imagem</label>
                    <input id="service_image" name="image" type="file" accept="image/*">
                </div>
                <label class="checkbox-row"><input type="checkbox" name="is_active" <?= !isset($editing_service['is_active']) || (int) ($editing_service['is_active'] ?? 0) ? 'checked' : '' ?>> Serviço ativo no catálogo</label>

                <div style="border-top: 1px solid var(--border, #eee); padding-top: 1rem; margin-top: 0.5rem;">
                    <label class="checkbox-row"><input type="checkbox" name="has_return" id="has_return" <?= (int) ($editing_service['has_return'] ?? 0) ? 'checked' : '' ?>> Serviço inclui retorno gratuito</label>
                    <div class="form-grid two" id="return-fields" style="<?= (int) ($editing_service['has_return'] ?? 0) ? '' : 'display:none;' ?> margin-top: 0.5rem;">
                        <div class="field">
                            <label for="return_quantity">Qtd. de retornos</label>
                            <input id="return_quantity" name="return_quantity" type="number" min="1" value="<?= (int) ($editing_service['return_quantity'] ?? 1) ?>">
                        </div>
                        <div class="field">
                            <label for="return_days">Prazo (dias)</label>
                            <input id="return_days" name="return_days" type="number" min="1" value="<?= (int) ($editing_service['return_days'] ?? 30) ?>">
                        </div>
                    </div>
                </div>
                <script>document.getElementById('has_return')?.addEventListener('change',function(){document.getElementById('return-fields').style.display=this.checked?'':'none'});</script>

                <button class="btn" type="submit" data-loading-label="Salvando... "><?= $editing_service ? 'Atualizar serviço' : 'Salvar serviço' ?></button>
            </form>
        </div>

        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Lista ativa</span>
                    <h2>Serviços cadastrados</h2>
                    <p class="muted">Use editar, ativar/desativar e exclusão rápida com menos ruído visual.</p>
                </div>
            </div>

            <div class="service-list service-list--premium">
                <?php foreach ($services as $service): ?>
                    <article class="service-item service-item--premium">
                        <?php if (!empty($service['image_path'])): ?>
                            <img class="service-media" src="<?= asset(ltrim($service['image_path'], '/')) ?>" alt="<?= e($service['title']) ?>">
                        <?php else: ?>
                            <div class="service-media service-media--placeholder"><?= e(upper_text(text_first_char((string) $service['title']))) ?></div>
                        <?php endif; ?>

                        <div class="stack stack--compact">
                            <div class="service-head">
                                <div>
                                    <strong><?= e($service['title']) ?></strong><br>
                                    <span class="muted"><?= (int) $service['duration_minutes'] ?> min · <?= money($service['price']) ?></span>
                                    <?php if ((int) ($service['has_return'] ?? 0)): ?>
                                        <br><span class="muted">🔄 <?= (int) $service['return_quantity'] ?> retorno(s) em <?= (int) $service['return_days'] ?> dias</span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?= (int) $service['is_active'] ? 'is-success' : 'is-neutral' ?>"><?= (int) $service['is_active'] ? 'Ativo' : 'Inativo' ?></span>
                            </div>
                            <p class="muted"><?= e($service['description']) ?></p>
                            <div class="inline-actions inline-actions--wrap">
                                <a class="btn btn-light" href="<?= base_url('vendor/services?edit=' . $service['id']) ?>">Editar</a>
                                <form method="post" action="<?= base_url('vendor/services/' . $service['id'] . '/toggle') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-light" type="submit"><?= (int) $service['is_active'] ? 'Desativar' : 'Ativar' ?></button>
                                </form>
                                <form method="post" action="<?= base_url('vendor/services/' . $service['id'] . '/delete') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger" type="submit">Excluir</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if ($services === []): ?>
                    <div class="empty-state empty-state--premium">Nenhum serviço cadastrado ainda. Comece pelos mais vendidos para liberar a agenda pública.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

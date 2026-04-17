<section class="stack stack--spacious">
    <div class="card card--section">
        <div class="section-header section-header--premium section-header--stretch">
            <div>
                <span class="section-kicker">Produtos e estoque</span>
                <h1 class="page-title">Venda, estoque e financeiro no mesmo fluxo.</h1>
                <p class="page-subtitle">O catálogo de produtos agora fica mais claro para consultar, editar e registrar vendas sem quebrar a experiência.</p>
            </div>
            <div class="soft-pill"><?= count($products) ?> produto(s) no estoque</div>
        </div>
    </div>

    <div class="app-grid two">
        <div class="card card--section card--sticky">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker"><?= $editing_product ? 'Editando produto' : 'Novo produto' ?></span>
                    <h2><?= $editing_product ? 'Atualize os dados do item' : 'Cadastre um produto' ?></h2>
                </div>
            </div>

            <form class="form-grid form-grid--premium" method="post" action="<?= base_url('vendor/products') ?>" enctype="multipart/form-data" data-disable-on-submit>
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) ($editing_product['id'] ?? 0) ?>">
                <div class="field">
                    <label for="product_name">Nome</label>
                    <input id="product_name" name="name" type="text" value="<?= e($editing_product['name'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label for="product_description">Descrição</label>
                    <textarea id="product_description" name="description"><?= e($editing_product['description'] ?? '') ?></textarea>
                </div>
                <div class="form-grid two">
                    <div class="field">
                        <label for="sale_price">Preço de venda</label>
                        <input id="sale_price" name="sale_price" type="number" min="0" step="0.01" value="<?= e($editing_product['sale_price'] ?? 0) ?>" required>
                    </div>
                    <div class="field">
                        <label for="cost_price">Preço de custo</label>
                        <input id="cost_price" name="cost_price" type="number" min="0" step="0.01" value="<?= e($editing_product['cost_price'] ?? 0) ?>">
                    </div>
                </div>
                <div class="form-grid two">
                    <div class="field">
                        <label for="stock_quantity">Estoque</label>
                        <input id="stock_quantity" name="stock_quantity" type="number" min="0" step="1" value="<?= e($editing_product['stock_quantity'] ?? 0) ?>" required>
                    </div>
                    <div class="field">
                        <label for="min_stock_quantity">Estoque mínimo</label>
                        <input id="min_stock_quantity" name="min_stock_quantity" type="number" min="0" step="1" value="<?= e($editing_product['min_stock_quantity'] ?? 0) ?>" required>
                    </div>
                </div>
                <div class="form-grid two">
                    <div class="field">
                        <label for="product_category">Categoria</label>
                        <input id="product_category" name="category" type="text" value="<?= e($editing_product['category'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="product_image">Imagem</label>
                        <input id="product_image" name="image" type="file" accept="image/*">
                    </div>
                </div>
                <label class="checkbox-row"><input type="checkbox" name="is_active" <?= !isset($editing_product['is_active']) || (int) ($editing_product['is_active'] ?? 0) ? 'checked' : '' ?>> Produto ativo no catálogo</label>
                <button class="btn" type="submit" data-loading-label="Salvando... "><?= $editing_product ? 'Atualizar produto' : 'Salvar produto' ?></button>
            </form>
        </div>

        <div class="card card--section">
            <div class="section-header section-header--premium">
                <div>
                    <span class="section-kicker">Catálogo</span>
                    <h2>Lista de produtos</h2>
                    <p class="muted">Itens com leitura mais rápida, badge de estoque e venda embutida.</p>
                </div>
            </div>

            <div class="product-list product-list--premium">
                <?php foreach ($products as $product): ?>
                    <article class="product-item product-item--premium">
                        <?php if (!empty($product['image_path'])): ?>
                            <img class="product-media" src="<?= asset(ltrim($product['image_path'], '/')) ?>" alt="<?= e($product['name']) ?>" loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="product-media product-media--placeholder"><?= e(upper_text(text_first_char((string) $product['name']))) ?></div>
                        <?php endif; ?>

                        <div class="stack stack--compact">
                            <div class="product-head">
                                <div>
                                    <strong><?= e($product['name']) ?></strong><br>
                                    <span class="muted"><?= money($product['sale_price']) ?> · Estoque <?= (int) $product['stock_quantity'] ?></span>
                                </div>
                                <div class="inline-actions inline-actions--wrap">
                                    <?php if ((int) ($product['is_low_stock'] ?? 0)): ?>
                                        <span class="badge is-danger">Estoque baixo</span>
                                    <?php endif; ?>
                                    <span class="badge <?= (int) ($product['is_active'] ?? 1) ? 'is-success' : 'is-neutral' ?>"><?= (int) ($product['is_active'] ?? 1) ? 'Ativo' : 'Inativo' ?></span>
                                </div>
                            </div>

                            <p class="muted"><?= e($product['description']) ?></p>

                            <div class="inline-actions inline-actions--wrap">
                                <a class="btn btn-light" href="<?= base_url('vendor/products?edit=' . $product['id']) ?>">Editar</a>
                                <form method="post" action="<?= base_url('vendor/products/' . $product['id'] . '/delete') ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger" type="submit">Excluir</button>
                                </form>
                            </div>

                            <form class="form-grid two form-grid--sale" method="post" action="<?= base_url('vendor/products/' . $product['id'] . '/sell') ?>" data-disable-on-submit>
                                <?= csrf_field() ?>
                                <div class="field">
                                    <label>Qtd.</label>
                                    <input name="quantity" type="number" min="1" step="1" value="1" required>
                                </div>
                                <div class="field">
                                    <label>Cliente</label>
                                    <input name="customer_name" type="text" placeholder="Opcional">
                                </div>
                                <button class="btn btn-secondary" type="submit" data-loading-label="Registrando...">Registrar venda</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if ($products === []): ?>
                    <div class="empty-state empty-state--premium">Nenhum produto cadastrado ainda. Cadastre seus itens com maior giro primeiro para começar a medir estoque e receita.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

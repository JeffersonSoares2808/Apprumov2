<div class="card hero">
    <div class="brand-lockup">
        <img class="brand-logo-image" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="64" decoding="async">
    </div>
    <h1 class="page-title">Vamos criar seu espaço na plataforma</h1>
    <p class="page-subtitle">Seu cadastro entra como pendente e só é liberado após aprovação manual do admin.</p>
</div>

<div class="card">
    <form class="form-grid" method="post" action="<?= base_url('onboarding') ?>" data-disable-on-submit>
        <?= csrf_field() ?>
        <div class="field">
            <label for="business_name">Nome do negócio</label>
            <input id="business_name" name="business_name" type="text" value="<?= e(old('business_name')) ?>" required>
        </div>
        <div class="field">
            <label for="category">Categoria</label>
            <input id="category" name="category" type="text" value="<?= e(old('category')) ?>" placeholder="Estética, barbearia, manicure..." required>
        </div>
        <div class="field">
            <label for="phone">Telefone</label>
            <input id="phone" name="phone" type="text" value="<?= e(old('phone')) ?>" placeholder="5511999999999" required>
        </div>
        <button class="btn" type="submit">Enviar para aprovação</button>
    </form>

    <div class="inline-actions" style="margin-top:16px;">
        <a class="btn btn-light" href="<?= e(support_whatsapp_url('Olá! Preciso de ajuda para concluir meu cadastro na Apprumo.')) ?>">Falar com suporte</a>
        <form method="post" action="<?= base_url('auth/logout') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-secondary" type="submit">Sair</button>
        </form>
    </div>
</div>

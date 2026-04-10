<?php
$vendors = $vendors ?? [];
?>

<div class="card hero">
    <div class="brand-lockup">
        <img class="brand-logo-image" src="<?= e(brand_logo_url()) ?>" alt="Apprumo" width="200" height="64" decoding="async">
    </div>
    <h1 class="page-title">Selecione o negócio</h1>
    <p class="page-subtitle">Você tem acesso a mais de um painel. Escolha qual deseja abrir agora.</p>
</div>

<div class="card">
    <form class="form-grid" method="post" action="<?= base_url('select-vendor') ?>" data-disable-on-submit>
        <?= csrf_field() ?>

        <div class="field">
            <label for="vendor_id">Negócio</label>
            <select id="vendor_id" name="vendor_id" required>
                <option value="" disabled selected>Selecione…</option>
                <?php foreach ($vendors as $vendor): ?>
                    <option value="<?= (int) ($vendor['id'] ?? 0) ?>">
                        <?= e(($vendor['business_name'] ?? 'Negócio') . ' — ' . ($vendor['category'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button class="btn" type="submit">Abrir painel</button>
    </form>

    <div class="inline-actions" style="margin-top:16px;">
        <a class="btn btn-light" href="<?= e(support_whatsapp_url('Olá! Preciso de ajuda para acessar meus negócios na Apprumo.')) ?>">Falar com suporte</a>
        <form method="post" action="<?= base_url('auth/logout') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-secondary" type="submit">Sair</button>
        </form>
    </div>
</div>


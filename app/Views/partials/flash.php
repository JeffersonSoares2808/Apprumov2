<?php $success = flash('success'); ?>
<?php if ($success): ?>
    <div class="toast toast--success" data-toast>
        <span class="toast__icon" aria-hidden="true">✓</span>
        <span><?= e($success) ?></span>
        <button class="toast__dismiss" type="button" aria-label="Fechar">×</button>
    </div>
<?php endif; ?>

<?php $error = flash('error'); ?>
<?php if ($error): ?>
    <div class="toast toast--error" data-toast>
        <span class="toast__icon" aria-hidden="true">!</span>
        <span><?= e($error) ?></span>
        <button class="toast__dismiss" type="button" aria-label="Fechar">×</button>
    </div>
<?php endif; ?>

<?php $success = flash('success'); ?>
<?php if ($success): ?>
    <div class="flash flash--success is-success" data-toast>
        <span class="flash__icon" aria-hidden="true">✓</span>
        <span><?= e($success) ?></span>
    </div>
<?php endif; ?>

<?php $error = flash('error'); ?>
<?php if ($error): ?>
    <div class="flash flash--error is-error" data-toast>
        <span class="flash__icon" aria-hidden="true">!</span>
        <span><?= e($error) ?></span>
    </div>
<?php endif; ?>

<!doctype html>
<html lang="pt-BR">
<head>
    <?php partial('partials/head', ['title' => $title ?? 'Apprumo']); ?>
</head>
<body class="app-body app-body--status">
    <?= $content ?>
    <script src="<?= asset('assets/js/app.js') ?>"></script>
</body>
</html>

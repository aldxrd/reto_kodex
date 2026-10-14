<?php
/** Layout sin sidebar (login y errores sin sesión). @var string $content @var string $title @var array $config */
?>
<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e($config['app_name']) ?></title>
    <?php require __DIR__ . '/partials/head-assets.php'; ?>
</head>
<body class="auth-body">
<main class="auth-wrap">
    <?= $content ?>
</main>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>

<?php
/** @var string $content @var string $title @var array $config */
?>
<!doctype html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e($config['app_name']) ?></title>
    <?php require __DIR__ . '/partials/head-assets.php'; ?>
</head>
<body>
<div class="app">
    <?php require __DIR__ . '/partials/sidebar.php'; ?>
    <div class="app-main">
        <?php require __DIR__ . '/partials/topbar.php'; ?>
        <main class="app-content" id="contenido">
            <?= $content ?>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>

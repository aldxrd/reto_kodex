<?php /** @var string $message @var string $code */ ?>
<div class="card-k empty-state" style="max-width:560px;margin:40px auto">
    <i class="bi bi-plug" aria-hidden="true"></i>
    <h1 class="h5">No se pudo completar la operación</h1>
    <p class="mb-1"><?= e($message) ?></p>
    <p class="text-faint-k small mb-3">Código: <?= e($code) ?></p>
    <a class="btn btn-outline-k" href="<?= e($_SERVER['REQUEST_URI'] ?? url('/')) ?>">Reintentar</a>
</div>

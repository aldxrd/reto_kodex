<?php /** @var string|null $message */ ?>
<div class="card-k empty-state" style="max-width:520px;margin:40px auto">
    <i class="bi bi-shield-lock" aria-hidden="true"></i>
    <h1 class="h5">No tienes permiso para ver esta página</h1>
    <p class="mb-3"><?= e($message ?? 'Tu rol no tiene acceso a esta sección.') ?></p>
    <a class="btn btn-outline-k" href="<?= e(url('/')) ?>">Ir al inicio</a>
</div>

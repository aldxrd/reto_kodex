<?php
/**
 * Indica de dónde salen los datos de la pantalla.
 * @var string $source  'sample' | 'api'
 * @var string $endpoint
 * @var string|null $when  Día en que se conecta (solo para 'sample')
 */
?>
<?php if ($source === 'sample'): ?>
    <div class="notice notice-sample" role="note">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <span><strong>Datos de ejemplo.</strong> Esta pantalla se conectará a <code><?= e($endpoint) ?></code><?= isset($when) ? ' el ' . e($when) : '' ?>. Nada de lo que ves aquí está guardado.</span>
    </div>
<?php else: ?>
    <div class="notice notice-api" role="note">
        <i class="bi bi-check-circle" aria-hidden="true"></i>
        <span><strong>Datos reales</strong> desde <code><?= e($endpoint) ?></code>.</span>
    </div>
<?php endif; ?>

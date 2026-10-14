<?php
/** @var array $breadcrumb */
$last = array_pop($breadcrumb);
?>
<header class="topbar">
    <button class="btn-icon d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Abrir menú">
        <i class="bi bi-list" aria-hidden="true"></i>
    </button>
    <nav aria-label="Ruta de navegación" class="crumbs">
        <?php foreach ($breadcrumb as $crumb): ?>
            <span><?= e($crumb) ?></span><span class="sep">/</span>
        <?php endforeach; ?>
        <strong><?= e($last) ?></strong>
    </nav>
</header>

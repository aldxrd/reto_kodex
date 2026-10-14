<?php
/**
 * Sidebar filtrado por rol. Mismo comportamiento que el componente Sidebar del Figma.
 * @var array $user @var array $nav @var string $navKey @var array $config
 */
$role = $user['role'] ?? '';
?>
<aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Menú principal">
    <div class="sidebar-brand">
        <span class="brand-logo">K</span>
        <span>
            <span class="brand-name"><?= e($config['app_name']) ?></span>
            <span class="brand-sub"><?= e($config['company']) ?></span>
        </span>
        <button type="button" class="btn-close ms-auto d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Cerrar menú"></button>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($nav as $section): ?>
            <?php $items = array_filter($section['items'], static fn ($i) => in_array($role, $i['roles'], true)); ?>
            <?php if ($items === []) continue; ?>
            <div class="nav-section"><?= e($section['title']) ?></div>
            <?php foreach ($items as $item): ?>
                <?php $active = $item['key'] === $navKey; ?>
                <a href="<?= e(url($item['path'])) ?>" class="nav-item<?= $active ? ' active' : '' ?>"<?= $active ? ' aria-current="page"' : '' ?>>
                    <i class="bi bi-<?= e($item['icon']) ?>" aria-hidden="true"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-user">
        <span class="avatar"><?= e(initials($user['name'] ?? '')) ?></span>
        <span class="user-text">
            <span class="user-name"><?= e($user['name'] ?? '') ?></span>
            <span class="user-role"><?= e($user['role_label'] ?? '') ?></span>
        </span>
        <form method="post" action="<?= e(url('/logout')) ?>" class="ms-auto">
            <?= csrf_field() ?>
            <button type="submit" class="btn-icon" title="Cerrar sesión" aria-label="Cerrar sesión">
                <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</aside>

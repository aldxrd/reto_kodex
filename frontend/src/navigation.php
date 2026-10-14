<?php

declare(strict_types=1);

/**
 * Menú lateral. Cada ítem declara qué roles lo ven (igual que el Sidebar del Figma).
 * Es solo UX: los mismos roles se validan en el router del frontend y, sobre todo, en la API.
 *
 * Roles: admin | agent | requester
 */
return [
    [
        'title' => 'General',
        'items' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid-1x2', 'path' => '/dashboard', 'roles' => ['admin', 'agent']],
            ['key' => 'incidents', 'label' => 'Incidencias', 'icon' => 'list-ul', 'path' => '/incidencias', 'roles' => ['admin', 'agent']],
            ['key' => 'my-incidents', 'label' => 'Mis incidencias', 'icon' => 'list-ul', 'path' => '/mis-incidencias', 'roles' => ['requester']],
            ['key' => 'create', 'label' => 'Nueva incidencia', 'icon' => 'plus-circle', 'path' => '/incidencias/nueva', 'roles' => ['admin', 'agent', 'requester']],
            ['key' => 'assigned', 'label' => 'Asignadas a mí', 'icon' => 'person', 'path' => '/asignadas', 'roles' => ['admin', 'agent']],
        ],
    ],
    [
        'title' => 'Administración',
        'items' => [
            ['key' => 'users', 'label' => 'Usuarios', 'icon' => 'people', 'path' => '/usuarios', 'roles' => ['admin']],
            ['key' => 'audit', 'label' => 'Auditoría', 'icon' => 'shield-check', 'path' => '/auditoria', 'roles' => ['admin']],
            ['key' => 'categories', 'label' => 'Categorías', 'icon' => 'sliders2', 'path' => '/categorias', 'roles' => ['admin']],
        ],
    ],
];

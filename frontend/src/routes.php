<?php

declare(strict_types=1);

/**
 * Rutas protegidas: método + patrón → roles permitidos + controlador.
 * Los roles aquí son UX (redirigen a 403 en vez de mostrar una pantalla inútil);
 * la autorización real está en la API.
 *
 * @return list<array{method:string, pattern:string, roles:list<string>, handler:callable}>
 */
return static function (ApiClient $api, Auth $auth, array $config): array {
    $user = $auth->user();
    $nav = require __DIR__ . '/navigation.php';

    // Renderiza una página dentro del layout con los datos comunes.
    $page = static function (string $template, string $title, string $navKey, array $breadcrumb, array $data = []) use ($user, $nav, $config): string {
        return view($template, $data + [
            'title' => $title,
            'navKey' => $navKey,
            'breadcrumb' => $breadcrumb,
            'user' => $user,
            'nav' => $nav,
            'config' => $config,
        ]);
    };

    $all = ['admin', 'agent', 'requester'];
    $staff = ['admin', 'agent'];

    return [
        [
            'method' => 'GET', 'pattern' => '/dashboard', 'roles' => $staff,
            'handler' => static function () use ($page): string {
                $incidents = SampleData::incidents();

                return $page('pages/dashboard', 'Dashboard', 'dashboard', ['General', 'Dashboard'], [
                    'stats' => SampleData::dashboard(),
                    'overdue' => array_values(array_filter($incidents, static fn ($i) => $i['overdue'])),
                ]);
            },
        ],
        [
            'method' => 'GET', 'pattern' => '/incidencias', 'roles' => $staff,
            'handler' => static function () use ($page): string {
                // Filtros reales sobre los datos de ejemplo (se moverán a la API el día 3).
                $filters = [
                    'status' => is_string($_GET['estado'] ?? null) ? $_GET['estado'] : '',
                    'priority' => is_string($_GET['prioridad'] ?? null) ? $_GET['prioridad'] : '',
                ];
                $incidents = array_values(array_filter(SampleData::incidents(), static fn ($i) => ($filters['status'] === '' || $i['status'] === $filters['status'])
                    && ($filters['priority'] === '' || $i['priority'] === $filters['priority'])));

                return $page('pages/incidents/index', 'Incidencias', 'incidents', ['Incidencias', 'Todas'], [
                    'incidents' => $incidents,
                    'filters' => $filters,
                ]);
            },
        ],
        [
            'method' => 'GET', 'pattern' => '/incidencias/nueva', 'roles' => $all,
            'handler' => static function () use ($page, $api, $auth): string {
                $catalogs = $api->get('/catalogs');
                // Solo admin y agente asignan responsable (y solo ellos pueden pedir la lista).
                $agents = $auth->hasRole('admin', 'agent') ? $api->get('/users/agents') : [];

                return $page('pages/incidents/create', 'Nueva incidencia', 'create', ['Incidencias', 'Nueva'], [
                    'categories' => $catalogs['categories'] ?? [],
                    'priorities' => $catalogs['priorities'] ?? [],
                    'agents' => $agents,
                    'canAssign' => $auth->hasRole('admin', 'agent'),
                ]);
            },
        ],
        [
            'method' => 'GET', 'pattern' => '/incidencias/{id}', 'roles' => $all,
            'handler' => static function (array $params) use ($page, $api, $auth): ?string {
                $incident = SampleData::incident((int) $params['id']);
                // Un solicitante solo ve sus propias incidencias (404 para no revelar que existen).
                if ($incident === null || ($auth->hasRole('requester') && $incident['requester'] !== $auth->user()['name'])) {
                    return null; // → 404
                }
                // Las transiciones válidas vienen de la API (máquina de estados del backend).
                $statuses = $api->get('/catalogs')['statuses'] ?? [];
                $transitions = [];
                foreach ($statuses as $s) {
                    if ($s['value'] === $incident['status']) {
                        // [{to: 'in_progress', label: 'Iniciar atención'}, ...]
                        $transitions = $s['actions'] ?? array_map(static fn ($to) => ['to' => $to, 'label' => status_meta($to)[0]], $s['transitions']);
                    }
                }

                return $page('pages/incidents/show', $incident['code'], $auth->hasRole('requester') ? 'my-incidents' : 'incidents', ['Incidencias', $incident['code']], [
                    'incident' => $incident,
                    'transitions' => $transitions,
                    'canManage' => $auth->hasRole('admin', 'agent'),
                ]);
            },
        ],
        [
            'method' => 'GET', 'pattern' => '/asignadas', 'roles' => $staff,
            'handler' => static function () use ($page, $user): string {
                $active = ['assigned', 'in_progress'];
                $incidents = array_values(array_filter(SampleData::incidents(), static fn ($i) => $i['assignee'] === $user['name'] && in_array($i['status'], $active, true)));

                return $page('pages/assigned', 'Asignadas a mí', 'assigned', ['Incidencias', 'Asignadas a mí'], ['incidents' => $incidents]);
            },
        ],
        [
            'method' => 'GET', 'pattern' => '/mis-incidencias', 'roles' => ['requester'],
            'handler' => static function () use ($page, $user): string {
                $incidents = array_values(array_filter(SampleData::incidents(), static fn ($i) => $i['requester'] === $user['name']));

                return $page('pages/my-incidents', 'Mis incidencias', 'my-incidents', ['Incidencias', 'Mis incidencias'], ['incidents' => $incidents]);
            },
        ],
        [
            'method' => 'GET', 'pattern' => '/usuarios', 'roles' => ['admin'],
            'handler' => static function () use ($page, $api): string {
                // Filtros del Figma: Rol, Estado y Área (los aplica la API).
                $filters = [
                    'role' => is_string($_GET['rol'] ?? null) ? $_GET['rol'] : '',
                    'status' => is_string($_GET['estado'] ?? null) ? $_GET['estado'] : '',
                    'area' => is_string($_GET['area'] ?? null) ? $_GET['area'] : '',
                ];
                $res = $api->request('GET', '/users?' . http_build_query(array_filter($filters, static fn ($v) => $v !== '')));

                return $page('pages/users', 'Usuarios', 'users', ['Administración', 'Usuarios'], [
                    'users' => $res['data'] ?? [],
                    'counts' => $res['meta']['counts'] ?? [],
                    'areas' => $res['meta']['areas'] ?? [],
                    'filters' => $filters,
                ]);
            },
        ],
        [
            'method' => 'GET', 'pattern' => '/auditoria', 'roles' => ['admin'],
            'handler' => static function () use ($page): string {
                return $page('pages/audit', 'Auditoría', 'audit', ['Administración', 'Auditoría'], ['entries' => SampleData::audit()]);
            },
        ],
        [
            'method' => 'GET', 'pattern' => '/categorias', 'roles' => ['admin'],
            'handler' => static function () use ($page, $api): string {
                return $page('pages/categories', 'Categorías', 'categories', ['Administración', 'Categorías'], [
                    'categories' => $api->get('/categories') ?? [],
                    'sla' => $api->get('/catalogs')['sla'] ?? [],
                ]);
            },
        ],
    ];
};

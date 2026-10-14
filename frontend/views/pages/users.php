<?php
/**
 * @var list<array> $users
 * @var array{total?:int,admin?:int,agent?:int,requester?:int} $counts  meta.counts de GET /api/users
 * @var list<string> $areas
 * @var array{role:string,status:string,area:string} $filters
 */
$n = static fn (int $count, string $one, string $many): string => $count . ' ' . ($count === 1 ? $one : $many);
$hasFilters = $filters['role'] !== '' || $filters['status'] !== '' || $filters['area'] !== '';
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Usuarios</h1>
        <p class="page-desc">
            <?= e($n($counts['total'] ?? 0, 'usuario', 'usuarios')) ?> ·
            <?= e($n($counts['admin'] ?? 0, 'administrador', 'administradores')) ?> ·
            <?= e($n($counts['agent'] ?? 0, 'agente', 'agentes')) ?> ·
            <?= e($n($counts['requester'] ?? 0, 'solicitante', 'solicitantes')) ?>
        </p>
    </div>
</div>

<?= view('partials/data-source', ['source' => 'api', 'endpoint' => 'GET /api/users'], null) ?>

<form class="filters" method="get" action="<?= e(url('/usuarios')) ?>" aria-label="Filtros">
    <select class="form-select" name="rol" aria-label="Filtrar por rol">
        <option value="">Rol: todos</option>
        <?php foreach (['admin' => 'Administrador', 'agent' => 'Agente', 'requester' => 'Solicitante'] as $value => $label): ?>
            <option value="<?= e($value) ?>"<?= $filters['role'] === $value ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" name="estado" aria-label="Filtrar por estado">
        <option value="">Estado: todos</option>
        <option value="active"<?= $filters['status'] === 'active' ? ' selected' : '' ?>>Activos</option>
        <option value="inactive"<?= $filters['status'] === 'inactive' ? ' selected' : '' ?>>Inactivos</option>
    </select>
    <select class="form-select" name="area" aria-label="Filtrar por área">
        <option value="">Área: todas</option>
        <?php foreach ($areas as $area): ?>
            <option value="<?= e($area) ?>"<?= $filters['area'] === $area ? ' selected' : '' ?>><?= e($area) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-k" type="submit">Aplicar</button>
    <?php if ($hasFilters): ?>
        <a class="small fw-medium" href="<?= e(url('/usuarios')) ?>">Limpiar filtros</a>
    <?php endif; ?>
</form>

<div class="table-k-wrap">
    <table class="table-k">
        <thead>
        <tr>
            <th scope="col">Usuario</th>
            <th scope="col">Rol</th>
            <th scope="col">Área</th>
            <th scope="col">Estado</th>
            <th scope="col">Último acceso</th>
        </tr>
        </thead>
        <tbody>
        <?php if ($users === []): ?>
            <tr><td colspan="5" class="text-center text-muted-k py-4">No hay usuarios con esos filtros.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <span class="d-flex align-items-center gap-2">
                        <span class="avatar"><?= e(initials($u['name'])) ?></span>
                        <span><span class="cell-title d-block"><?= e($u['name']) ?></span><span class="cell-sub"><?= e($u['email']) ?></span></span>
                    </span>
                </td>
                <td><?= role_badge($u['role'], $u['role_label'] ?? $u['role']) ?></td>
                <td><?= e($u['area'] ?? '—') ?></td>
                <td><span class="state-dot<?= $u['is_active'] ? '' : ' off' ?>"><?= $u['is_active'] ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="text-nowrap"><?= e(format_datetime($u['last_login_at'] ?? null)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

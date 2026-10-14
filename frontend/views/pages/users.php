<?php /** @var list<array> $users */
$counts = array_count_values(array_column($users, 'role'));
$n = static fn (int $count, string $one, string $many): string => $count . ' ' . ($count === 1 ? $one : $many);
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Usuarios</h1>
        <p class="page-desc">
            <?= e($n(count($users), 'usuario', 'usuarios')) ?> ·
            <?= e($n($counts['admin'] ?? 0, 'administrador', 'administradores')) ?> ·
            <?= e($n($counts['agent'] ?? 0, 'agente', 'agentes')) ?> ·
            <?= e($n($counts['requester'] ?? 0, 'solicitante', 'solicitantes')) ?>
        </p>
    </div>
</div>

<?= view('partials/data-source', ['source' => 'api', 'endpoint' => 'GET /api/users'], null) ?>

<div class="table-k-wrap">
    <table class="table-k">
        <thead>
        <tr>
            <th scope="col">Usuario</th>
            <th scope="col">Rol</th>
            <th scope="col">Estado</th>
            <th scope="col">Último acceso</th>
            <th scope="col">Creado</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <span class="d-flex align-items-center gap-2">
                        <span class="avatar"><?= e(initials($u['name'])) ?></span>
                        <span><span class="cell-title d-block"><?= e($u['name']) ?></span><span class="cell-sub"><?= e($u['email']) ?></span></span>
                    </span>
                </td>
                <td><?= role_badge($u['role'], $u['role_label'] ?? $u['role']) ?></td>
                <td><span class="state-dot<?= (int) $u['is_active'] === 1 ? '' : ' off' ?>"><?= (int) $u['is_active'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="text-nowrap"><?= e(format_datetime($u['last_login_at'] ?? null)) ?></td>
                <td class="text-nowrap"><?= e(format_datetime($u['created_at'] ?? null)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php /** @var list<array> $entries */ ?>
<div class="page-head">
    <div>
        <h1 class="page-title">Auditoría</h1>
        <p class="page-desc">Registro de cambios: quién cambió qué y cuándo. Solo lectura.</p>
    </div>
</div>

<?= view('partials/data-source', ['source' => 'sample', 'endpoint' => 'GET /api/audit', 'when' => 'día 3'], null) ?>

<div class="table-k-wrap">
    <table class="table-k">
        <thead>
        <tr>
            <th scope="col">Fecha y hora</th>
            <th scope="col">Usuario</th>
            <th scope="col">Acción</th>
            <th scope="col">Registro</th>
            <th scope="col">Cambio</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($entries as $a): ?>
            <tr>
                <td class="text-nowrap"><?= e(format_datetime($a['at'])) ?></td>
                <td class="text-nowrap"><?= e($a['user']) ?></td>
                <td><?= e($a['action']) ?></td>
                <td class="code fw-medium"><?= e($a['record']) ?></td>
                <td>
                    <span class="cell-sub mt-0"><?= e($a['field']) ?></span>
                    <span><?= $a['from'] !== null ? e($a['from']) . ' <span class="text-faint-k">→</span> ' : '' ?><strong class="fw-medium text-body"><?= e($a['to']) ?></strong></span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php /** @var array $stats @var list<array> $overdue */ ?>
<div class="page-head">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-desc">Indicadores de atención de incidencias</p>
    </div>
</div>

<?= view('partials/data-source', ['source' => 'sample', 'endpoint' => 'GET /api/dashboard/summary', 'when' => 'día 3'], null) ?>

<div class="row g-3">
    <?php foreach ([
        ['Abiertas', $stats['open'], '', 'Incluye abiertas, asignadas y en progreso'],
        ['Resueltas', $stats['resolved'], '', 'En el periodo'],
        ['Vencidas', $stats['overdue'], 'text-danger-k', 'Superaron el tiempo objetivo (SLA)'],
        ['Tiempo medio de atención', $stats['avg_resolution'], '', 'Desde la creación hasta la resolución'],
    ] as [$label, $value, $class, $hint]): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-k h-100">
                <div class="kpi-label"><?= e($label) ?></div>
                <div class="kpi-value <?= e($class) ?>"><?= e($value) ?></div>
                <div class="text-faint-k small"><?= e($hint) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card-k">
    <h2>Incidencias por estado</h2>
    <?php $max = max($stats['by_status']) ?: 1; ?>
    <?php foreach ($stats['by_status'] as $status => $count): ?>
        <?php [$label, $class] = status_meta($status); ?>
        <div class="bar-row">
            <span class="text-muted-k"><?= e($label) ?></span>
            <span class="bar-track"><span class="bar-fill <?= e($class) ?>" style="width: <?= (int) round($count / $max * 100) ?>%; background: currentColor;"></span></span>
            <span class="bar-value"><?= (int) $count ?></span>
        </div>
    <?php endforeach; ?>
</div>

<div>
    <div class="d-flex align-items-center mb-2">
        <h2 class="h6 m-0">Vencidas que requieren atención</h2>
        <a class="ms-auto small fw-medium" href="<?= e(url('/incidencias')) ?>">Ver todas</a>
    </div>
    <?= view('partials/incident-table', ['incidents' => $overdue, 'emptyText' => 'No hay incidencias vencidas.'], null) ?>
</div>

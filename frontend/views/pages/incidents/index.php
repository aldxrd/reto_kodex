<?php /** @var list<array> $incidents @var array{status:string,priority:string} $filters */ ?>
<div class="page-head">
    <div>
        <h1 class="page-title">Incidencias</h1>
        <p class="page-desc"><?= count($incidents) ?> incidencia(s) con los filtros actuales</p>
    </div>
    <div class="actions">
        <a class="btn btn-primary" href="<?= e(url('/incidencias/nueva')) ?>"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva incidencia</a>
    </div>
</div>

<?= view('partials/data-source', ['source' => 'sample', 'endpoint' => 'GET /api/incidents', 'when' => 'día 2'], null) ?>

<form class="filters" method="get" action="<?= e(url('/incidencias')) ?>" aria-label="Filtros">
    <select class="form-select" name="estado" aria-label="Filtrar por estado">
        <option value="">Estado: todos</option>
        <?php foreach (['open', 'assigned', 'in_progress', 'on_hold', 'resolved', 'closed', 'cancelled'] as $s): ?>
            <option value="<?= e($s) ?>"<?= $filters['status'] === $s ? ' selected' : '' ?>><?= e(status_meta($s)[0]) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="form-select" name="prioridad" aria-label="Filtrar por prioridad">
        <option value="">Prioridad: todas</option>
        <?php foreach (['critical', 'high', 'medium', 'low'] as $p): ?>
            <option value="<?= e($p) ?>"<?= $filters['priority'] === $p ? ' selected' : '' ?>><?= e(priority_meta($p)[0]) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-k" type="submit">Aplicar</button>
    <?php if ($filters['status'] !== '' || $filters['priority'] !== ''): ?>
        <a class="small fw-medium" href="<?= e(url('/incidencias')) ?>">Limpiar filtros</a>
    <?php endif; ?>
</form>

<?= view('partials/incident-table', ['incidents' => $incidents, 'emptyText' => 'Ninguna incidencia coincide con los filtros.'], null) ?>

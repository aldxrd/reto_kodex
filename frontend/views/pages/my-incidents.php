<?php /** @var list<array> $incidents */ ?>
<div class="page-head">
    <div>
        <h1 class="page-title">Mis incidencias</h1>
        <p class="page-desc">Incidencias que reportaste</p>
    </div>
    <div class="actions">
        <a class="btn btn-primary" href="<?= e(url('/incidencias/nueva')) ?>"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Nueva incidencia</a>
    </div>
</div>

<?= view('partials/data-source', ['source' => 'sample', 'endpoint' => 'GET /api/incidents', 'when' => 'día 2'], null) ?>

<?= view('partials/incident-table', [
    'incidents' => $incidents,
    'showRequester' => false,
    'emptyText' => 'Aún no has reportado incidencias.',
], null) ?>

<div class="notice notice-api" role="note">
    <i class="bi bi-info-circle" aria-hidden="true"></i>
    <span>Solo ves las incidencias que reportaste. Puedes cancelar una incidencia mientras esté <strong>Abierta</strong>.</span>
</div>

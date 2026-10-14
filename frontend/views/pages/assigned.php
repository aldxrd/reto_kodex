<?php /** @var list<array> $incidents */ ?>
<div class="page-head">
    <div>
        <h1 class="page-title">Asignadas a mí</h1>
        <p class="page-desc"><?= count($incidents) ?> incidencia(s) activa(s) a tu cargo</p>
    </div>
</div>

<?= view('partials/data-source', ['source' => 'sample', 'endpoint' => 'GET /api/incidents?assignee=me', 'when' => 'día 2'], null) ?>

<?= view('partials/incident-table', [
    'incidents' => $incidents,
    'showAssignee' => false,
    'emptyText' => 'No tienes incidencias activas asignadas.',
], null) ?>

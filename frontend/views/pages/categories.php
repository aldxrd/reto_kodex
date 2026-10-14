<?php
/**
 * @var list<array{id:int,name:string,description:string}> $categories
 * @var list<array{priority:string,response_hours:int,resolution_hours:int}> $sla
 */
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Categorías</h1>
        <p class="page-desc">Clasifican las incidencias y orientan su atención.</p>
    </div>
</div>

<?= view('partials/data-source', ['source' => 'api', 'endpoint' => 'GET /api/catalogs'], null) ?>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="table-k-wrap">
            <table class="table-k">
                <thead><tr><th scope="col">Categoría</th><th scope="col">Estado</th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><span class="cell-title"><?= e($c['name']) ?></span><span class="cell-sub"><?= e($c['description'] ?? '') ?></span></td>
                        <td><span class="state-dot">Activa</span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-12 col-xl-4">
        <section class="card-k">
            <h2>Tiempo objetivo por prioridad (SLA)</h2>
            <p class="text-faint-k small">Al superarlo, la incidencia se marca como vencida.</p>
            <table class="table-k">
                <thead><tr><th scope="col">Prioridad</th><th scope="col">Respuesta</th><th scope="col">Resolución</th></tr></thead>
                <tbody>
                <?php foreach ($sla as $s): ?>
                    <tr>
                        <td><?= priority_label($s['priority']) ?></td>
                        <td><?= (int) $s['response_hours'] ?> h</td>
                        <td class="text-body fw-medium"><?= (int) $s['resolution_hours'] ?> h</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </div>
</div>

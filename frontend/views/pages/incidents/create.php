<?php
/**
 * @var list<array{id:int,name:string,description:string}> $categories
 * @var list<array{value:string,label:string}> $priorities
 * @var list<array{id:int,name:string,role:string}> $agents
 * @var bool $canAssign
 */
?>
<div class="page-head">
    <div>
        <h1 class="page-title">Nueva incidencia</h1>
        <p class="page-desc">Describe el problema. Un agente la revisará y la atenderá.</p>
    </div>
</div>

<div class="notice notice-sample" role="note">
    <i class="bi bi-info-circle" aria-hidden="true"></i>
    <span>Categorías, prioridades y responsables vienen de la API (<code>GET /api/catalogs</code><?= $canAssign ? ', <code>GET /api/users/agents</code>' : '' ?>). El envío se habilita cuando exista <code>POST /api/incidents</code> (día 2).</span>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <form class="card-k" method="post" action="<?= e(url('/incidencias/nueva')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="title" class="form-label">Título <span class="text-danger-k">*</span></label>
                <input id="title" name="title" class="form-control" required minlength="10" maxlength="150" placeholder="Ej.: La impresora del 2.º piso no imprime">
                <div class="form-text">Entre 10 y 150 caracteres.</div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Descripción <span class="text-danger-k">*</span></label>
                <textarea id="description" name="description" class="form-control" rows="5" required maxlength="1000" placeholder="Qué ocurre, desde cuándo y a quién afecta."></textarea>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="category_id" class="form-label">Categoría <span class="text-danger-k">*</span></label>
                    <select id="category_id" name="category_id" class="form-select" required>
                        <option value="">Selecciona una categoría</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="priority" class="form-label">Prioridad <span class="text-danger-k">*</span></label>
                    <select id="priority" name="priority" class="form-select" required>
                        <?php foreach ($priorities as $p): ?>
                            <option value="<?= e($p['value']) ?>"<?= $p['value'] === 'medium' ? ' selected' : '' ?>><?= e($p['label']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <?php if ($canAssign): ?>
                <div class="mb-3">
                    <label for="assignee_id" class="form-label">Responsable <span class="text-faint-k fw-normal">· opcional</span></label>
                    <select id="assignee_id" name="assignee_id" class="form-select">
                        <option value="">Sin asignar</option>
                        <?php foreach ($agents as $a): ?>
                            <option value="<?= (int) $a['id'] ?>"><?= e($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="d-flex gap-2 justify-content-end border-top pt-3 mt-4">
                <a class="btn btn-outline-k" href="<?= e(url('/')) ?>">Cancelar</a>
                <button type="submit" class="btn btn-primary" disabled title="Disponible cuando exista POST /api/incidents (día 2)">Crear incidencia</button>
            </div>
        </form>
    </div>
    <div class="col-12 col-xl-4">
        <div class="card-k">
            <h2>¿Qué pasa después?</h2>
            <ol class="text-muted-k ps-3 mb-0 d-flex flex-column gap-2">
                <li>Se registra como <strong>Abierta</strong> con su fecha de vencimiento según la prioridad (SLA).</li>
                <li>Un agente la revisa y asigna un responsable.</li>
                <li>Ves cada cambio de estado hasta su cierre.</li>
            </ol>
        </div>
    </div>
</div>

<?php
/**
 * @var array $incident
 * @var list<string> $transitions  Estados destino válidos según la API
 * @var bool $canManage
 */
$i = $incident;
?>
<a class="text-muted-k small" href="<?= e(url($canManage ? '/incidencias' : '/mis-incidencias')) ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i> Volver</a>

<div class="page-head">
    <div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <span class="text-muted-k fw-medium"><?= e($i['code']) ?></span>
            <?= status_badge($i['status']) ?>
            <?= priority_label($i['priority']) ?>
            <?php if ($i['overdue']): ?><span class="badge-status status-cancelled">Vencida</span><?php endif; ?>
        </div>
        <h1 class="page-title"><?= e($i['title']) ?></h1>
        <p class="page-desc">Reportado por <?= e($i['requester']) ?> · <?= e(format_datetime($i['created_at'])) ?> · <?= e($i['category']) ?></p>
    </div>
    <?php if ($canManage): ?>
        <div class="actions flex-wrap">
            <?php if ($transitions === []): ?>
                <span class="text-faint-k small align-self-center">La API no define transiciones para el estado actual.</span>
            <?php endif; ?>
            <?php foreach ($transitions as $to): ?>
                <button type="button" class="btn <?= $to === 'resolved' ? 'btn-primary' : 'btn-outline-k' ?>" disabled title="Disponible cuando exista PATCH /api/incidents/{id}/status (día 2)">
                    Pasar a <?= e(status_meta($to)[0]) ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?= view('partials/data-source', ['source' => 'sample', 'endpoint' => 'GET /api/incidents/{id}', 'when' => 'día 2'], null) ?>

<div class="row g-4">
    <div class="col-12 col-xl-8 d-flex flex-column gap-4">
        <section class="card-k">
            <h2>Descripción</h2>
            <p class="text-muted-k mb-0"><?= e($i['description']) ?></p>
        </section>
        <section class="card-k">
            <h2>Comentarios <span class="text-faint-k fw-normal">(<?= count($i['comments']) ?>)</span></h2>
            <?php foreach ($i['comments'] as $c): ?>
                <div class="comment">
                    <span class="avatar"><?= e(initials($c['author'])) ?></span>
                    <div>
                        <div class="comment-meta">
                            <strong class="fw-medium"><?= e($c['author']) ?></strong>
                            <?= role_badge($c['role'], $c['role_label']) ?>
                            <span class="text-faint-k small"><?= e(format_datetime($c['created_at'])) ?></span>
                        </div>
                        <p class="text-muted-k mb-0"><?= e($c['body']) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <label for="comment" class="visually-hidden">Nuevo comentario</label>
            <textarea id="comment" class="form-control mt-3" rows="3" placeholder="Escribe un comentario…" disabled></textarea>
            <div class="d-flex align-items-center mt-2">
                <span class="text-faint-k small">Se habilita con <code>POST /api/incidents/{id}/comments</code> (día 2).</span>
                <button type="button" class="btn btn-outline-k btn-sm ms-auto" disabled>Comentar</button>
            </div>
        </section>
    </div>
    <div class="col-12 col-xl-4 d-flex flex-column gap-4">
        <section class="card-k">
            <h2>Detalles</h2>
            <dl class="kv">
                <dt>Estado</dt><dd><?= status_badge($i['status']) ?></dd>
                <dt>Prioridad</dt><dd><?= priority_label($i['priority']) ?></dd>
                <dt>Categoría</dt><dd><?= e($i['category']) ?></dd>
                <dt>Responsable</dt><dd><?= $i['assignee'] ? e($i['assignee']) : '<span class="text-faint-k">Sin asignar</span>' ?></dd>
                <dt>Solicitante</dt><dd><?= e($i['requester']) ?></dd>
                <dt>Creada</dt><dd><?= e(format_datetime($i['created_at'])) ?></dd>
                <dt>Vence</dt><dd class="<?= $i['overdue'] ? 'text-danger-k' : '' ?>"><?= e(format_datetime($i['due_at'])) ?><?= $i['overdue'] ? ' · vencida' : '' ?></dd>
            </dl>
        </section>
        <section class="card-k">
            <h2>Historial</h2>
            <ul class="timeline">
                <?php foreach ($i['history'] as $h): ?>
                    <li>
                        <div><strong class="fw-medium"><?= e($h['who']) ?></strong> <span class="text-muted-k"><?= e($h['what']) ?></span></div>
                        <?php if ($h['from']): ?>
                            <div class="d-flex align-items-center gap-2 my-1"><?= status_badge($h['from']) ?><span class="text-faint-k">→</span><?= status_badge($h['to']) ?></div>
                        <?php endif; ?>
                        <div class="text-faint-k small"><?= e(format_datetime($h['at'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    </div>
</div>

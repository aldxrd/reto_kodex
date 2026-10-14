<?php
/**
 * Tabla de incidencias reutilizable.
 * @var list<array> $incidents
 * @var bool $showRequester  Mostrar quién reportó (staff) o no (solicitante)
 * @var bool $showAssignee
 * @var string $emptyText
 */
$showRequester ??= true;
$showAssignee ??= true;
$emptyText ??= 'No hay incidencias para mostrar.';
?>
<div class="table-k-wrap">
    <?php if ($incidents === []): ?>
        <div class="empty-state"><i class="bi bi-inbox" aria-hidden="true"></i><?= e($emptyText) ?></div>
    <?php else: ?>
        <table class="table-k">
            <thead>
            <tr>
                <th scope="col">Código</th>
                <th scope="col">Título</th>
                <th scope="col">Prioridad</th>
                <th scope="col">Estado</th>
                <?php if ($showAssignee): ?><th scope="col">Responsable</th><?php endif; ?>
                <th scope="col">Vence</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($incidents as $i): ?>
                <tr>
                    <td class="code"><a href="<?= e(url('/incidencias/' . $i['id'])) ?>"><?= e($i['code']) ?></a></td>
                    <td>
                        <a class="cell-title" href="<?= e(url('/incidencias/' . $i['id'])) ?>"><?= e($i['title']) ?></a>
                        <span class="cell-sub"><?= e($i['category']) ?><?= $showRequester ? ' · ' . e($i['requester']) : '' ?></span>
                    </td>
                    <td><?= priority_label($i['priority']) ?></td>
                    <td><?= status_badge($i['status']) ?></td>
                    <?php if ($showAssignee): ?>
                        <td>
                            <?php if ($i['assignee']): ?>
                                <span class="d-inline-flex align-items-center gap-2 text-nowrap"><span class="avatar avatar-sm"><?= e(initials($i['assignee'])) ?></span><?= e($i['assignee']) ?></span>
                            <?php else: ?>
                                <span class="text-faint-k">Sin asignar</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td class="text-nowrap<?= $i['overdue'] ? ' text-danger-k fw-medium' : '' ?>">
                        <?= $i['overdue'] ? 'Vencida' : e(format_datetime($i['due_at'])) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

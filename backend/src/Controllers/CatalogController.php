<?php

declare(strict_types=1);

namespace Kodex\Controllers;

use Kodex\Domain\IncidentStatus;
use Kodex\Domain\Priority;
use Kodex\Domain\Role;
use Kodex\Http\Request;
use Kodex\Http\Response;
use Kodex\Repositories\CategoryRepository;

/**
 * Catálogos para poblar selects, badges y botones del frontend.
 * Los valores (value) son los que se envían a la API; las etiquetas (label)
 * son solo para mostrar. Así el front no duplica reglas de negocio.
 */
final class CatalogController
{
    public function __construct(private readonly CategoryRepository $categories)
    {
    }

    /** GET /api/catalogs */
    public function index(Request $request): Response
    {
        return Response::data([
            'categories' => array_map(
                static fn (array $c) => ['id' => $c['id'], 'name' => $c['name'], 'description' => $c['description'], 'area' => $c['area']],
                $this->categories->list(onlyActive: true),
            ),
            'priorities' => array_map(
                static fn (Priority $p) => ['value' => $p->value, 'label' => $p->label()],
                Priority::cases(),
            ),
            'statuses' => array_map(
                static fn (IncidentStatus $s) => [
                    'value' => $s->value,
                    'label' => $s->label(),
                    'is_active' => $s->isActive(),
                    'transitions' => array_map(static fn (IncidentStatus $t) => $t->value, $s->allowedTransitions()),
                    'actions' => array_map(
                        static fn (IncidentStatus $t) => ['to' => $t->value, 'label' => $s->actionLabelTo($t)],
                        $s->allowedTransitions(),
                    ),
                ],
                IncidentStatus::cases(),
            ),
            'roles' => array_map(
                static fn (Role $r) => ['value' => $r->value, 'label' => $r->label()],
                Role::cases(),
            ),
            'sla' => $this->categories->slaPolicies(),
        ]);
    }

    /** GET /api/categories  (admin) — incluye inactivas y el número de incidencias activas */
    public function categories(Request $request): Response
    {
        return Response::data($this->categories->list(onlyActive: false));
    }
}

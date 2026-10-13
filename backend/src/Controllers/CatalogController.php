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
 * Catálogos para poblar selects y etiquetas del frontend.
 * Los valores (value) son los que se envían a la API; las etiquetas (label)
 * son solo para mostrar.
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
            'categories' => $this->categories->listActive(),
            'priorities' => array_map(
                static fn (Priority $p) => ['value' => $p->value, 'label' => $p->label()],
                Priority::cases(),
            ),
            'statuses' => array_map(
                static fn (IncidentStatus $s) => [
                    'value' => $s->value,
                    'label' => $s->label(),
                    'transitions' => array_map(static fn (IncidentStatus $t) => $t->value, $s->allowedTransitions()),
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
}

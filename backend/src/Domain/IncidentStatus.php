<?php

declare(strict_types=1);

namespace Kodex\Domain;

/**
 * Estados de una incidencia y la máquina de transiciones permitidas
 * (igual al "Flujo de estados" del Figma).
 *
 *   open ──► assigned ──► in_progress ──► resolved ──► closed
 *    │                        ▲              │
 *    │                        └── reabrir ───┘
 *    └──► cancelled  (solo el solicitante, mientras está Abierta)
 *
 * Quién puede ejecutar cada transición se valida en el servicio de
 * incidencias (día 2); aquí solo se define qué transiciones existen.
 */
enum IncidentStatus: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::Assigned => 'Asignada',
            self::InProgress => 'En progreso',
            self::Resolved => 'Resuelta',
            self::Closed => 'Cerrada',
            self::Cancelled => 'Cancelada',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::Assigned, self::Cancelled],
            self::Assigned => [self::InProgress],
            self::InProgress => [self::Resolved],
            self::Resolved => [self::Closed, self::InProgress],
            self::Closed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** "Abiertas" del dashboard / pestaña "Activas": aún requieren atención. */
    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::Assigned, self::InProgress], true);
    }

    /** Pestaña "Finalizadas" del solicitante. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Resolved, self::Closed, self::Cancelled], true);
    }

    /** Texto del botón para pasar de este estado a $to ("Iniciar atención", "Resolver", "Reabrir"...). */
    public function actionLabelTo(self $to): string
    {
        if ($this === self::Resolved && $to === self::InProgress) {
            return 'Reabrir';
        }

        return match ($to) {
            self::Assigned => 'Asignar',
            self::InProgress => 'Iniciar atención',
            self::Resolved => 'Resolver',
            self::Closed => 'Cerrar',
            self::Cancelled => 'Cancelar',
            self::Open => 'Abrir',
        };
    }
}

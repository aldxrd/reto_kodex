<?php

declare(strict_types=1);

namespace Kodex\Domain;

/**
 * Estados de una incidencia y la máquina de transiciones permitidas.
 *
 *   open ──► in_progress ──► resolved ──► closed
 *    │          │   ▲            │
 *    │          ▼   │            └──► in_progress (reapertura)
 *    │        on_hold
 *    └──────────┴────────► cancelled
 *
 * La validación se hace SIEMPRE en el backend (IncidentService, día 2).
 */
enum IncidentStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierta',
            self::InProgress => 'En progreso',
            self::OnHold => 'En espera',
            self::Resolved => 'Resuelta',
            self::Closed => 'Cerrada',
            self::Cancelled => 'Cancelada',
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::InProgress, self::Cancelled],
            self::InProgress => [self::OnHold, self::Resolved, self::Cancelled],
            self::OnHold => [self::InProgress, self::Cancelled],
            self::Resolved => [self::Closed, self::InProgress],
            self::Closed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Estados que cuentan como "abiertas" en el dashboard. */
    public function isActive(): bool
    {
        return in_array($this, [self::Open, self::InProgress, self::OnHold], true);
    }
}

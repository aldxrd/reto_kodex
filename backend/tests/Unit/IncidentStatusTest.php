<?php

declare(strict_types=1);

namespace Kodex\Tests\Unit;

use Kodex\Domain\IncidentStatus as S;
use PHPUnit\Framework\TestCase;

/** Flujo de estados del Figma: open → assigned → in_progress → resolved → closed. */
final class IncidentStatusTest extends TestCase
{
    public function test_flujo_principal(): void
    {
        self::assertTrue(S::Open->canTransitionTo(S::Assigned));
        self::assertTrue(S::Assigned->canTransitionTo(S::InProgress));
        self::assertTrue(S::InProgress->canTransitionTo(S::Resolved));
        self::assertTrue(S::Resolved->canTransitionTo(S::Closed));
    }

    public function test_reabrir_y_cancelar(): void
    {
        self::assertTrue(S::Resolved->canTransitionTo(S::InProgress), 'reabrir');
        self::assertTrue(S::Open->canTransitionTo(S::Cancelled), 'el solicitante cancela mientras está abierta');
        self::assertFalse(S::Assigned->canTransitionTo(S::Cancelled), 'ya asignada no se cancela');
        self::assertFalse(S::InProgress->canTransitionTo(S::Cancelled));
    }

    public function test_no_se_saltan_pasos(): void
    {
        self::assertFalse(S::Open->canTransitionTo(S::InProgress), 'primero se asigna');
        self::assertFalse(S::Open->canTransitionTo(S::Resolved));
        self::assertFalse(S::Assigned->canTransitionTo(S::Resolved), 'no se resuelve sin atender');
        self::assertFalse(S::InProgress->canTransitionTo(S::Closed), 'no se cierra sin resolver');
        self::assertFalse(S::Open->canTransitionTo(S::Open));
    }

    public function test_estados_finales_no_tienen_salida(): void
    {
        self::assertSame([], S::Closed->allowedTransitions());
        self::assertSame([], S::Cancelled->allowedTransitions());
    }

    public function test_activas_y_finalizadas(): void
    {
        $active = array_values(array_filter(S::cases(), static fn (S $s) => $s->isActive()));
        $final = array_values(array_filter(S::cases(), static fn (S $s) => $s->isFinal()));

        self::assertSame([S::Open, S::Assigned, S::InProgress], $active);
        self::assertSame([S::Resolved, S::Closed, S::Cancelled], $final);
    }

    public function test_etiquetas_de_accion_del_figma(): void
    {
        self::assertSame('Iniciar atención', S::Assigned->actionLabelTo(S::InProgress));
        self::assertSame('Resolver', S::InProgress->actionLabelTo(S::Resolved));
        self::assertSame('Reabrir', S::Resolved->actionLabelTo(S::InProgress));
        self::assertSame('Cancelar', S::Open->actionLabelTo(S::Cancelled));
    }
}

<?php

declare(strict_types=1);

namespace Kodex\Tests\Unit;

use Kodex\Domain\IncidentStatus as S;
use PHPUnit\Framework\TestCase;

final class IncidentStatusTest extends TestCase
{
    public function test_transiciones_validas(): void
    {
        self::assertTrue(S::Open->canTransitionTo(S::InProgress));
        self::assertTrue(S::InProgress->canTransitionTo(S::Resolved));
        self::assertTrue(S::Resolved->canTransitionTo(S::Closed));
        self::assertTrue(S::Resolved->canTransitionTo(S::InProgress), 'reapertura');
        self::assertTrue(S::OnHold->canTransitionTo(S::InProgress));
    }

    public function test_transiciones_invalidas(): void
    {
        self::assertFalse(S::Open->canTransitionTo(S::Closed), 'no se cierra sin resolver');
        self::assertFalse(S::Open->canTransitionTo(S::Resolved), 'no se resuelve sin atender');
        self::assertFalse(S::Open->canTransitionTo(S::Open));
        self::assertFalse(S::Cancelled->canTransitionTo(S::InProgress));
    }

    public function test_estados_finales_no_tienen_salida(): void
    {
        self::assertSame([], S::Closed->allowedTransitions());
        self::assertSame([], S::Cancelled->allowedTransitions());
    }

    public function test_estados_activos_para_el_dashboard(): void
    {
        $active = array_values(array_filter(S::cases(), static fn (S $s) => $s->isActive()));

        self::assertSame([S::Open, S::InProgress, S::OnHold], $active);
    }
}

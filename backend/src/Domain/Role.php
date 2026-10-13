<?php

declare(strict_types=1);

namespace Kodex\Domain;

enum Role: string
{
    case Admin = 'admin';
    case Agent = 'agent';
    case Requester = 'requester';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Agent => 'Agente',
            self::Requester => 'Solicitante',
        };
    }
}

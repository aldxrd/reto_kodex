<?php

declare(strict_types=1);

namespace Kodex\Auth;

use Kodex\Domain\Role;

/** Usuario autenticado de la petición actual (cargado desde BD, no del token). */
final class AuthUser
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly Role $role,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self((int) $row['id'], (string) $row['name'], (string) $row['email'], Role::from((string) $row['role']));
    }

    public function is(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
        ];
    }
}

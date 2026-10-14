<?php

declare(strict_types=1);

namespace Kodex\Repositories;

use Kodex\Domain\Role;
use PDO;

final class UserRepository
{
    /** Columnas seguras para exponer (nunca password_hash). */
    private const PUBLIC_COLUMNS = 'id, name, email, role, area, is_active, last_login_at, created_at, updated_at';

    public function __construct(private readonly PDO $db)
    {
    }

    /** Incluye password_hash: solo para el flujo de login. */
    public function findForLogin(string $email): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }

    public function findActiveById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE id = :id AND is_active = 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * @param list<Role>|null $roles
     * @return list<array<string, mixed>>
     */
    public function list(?array $roles = null, bool $onlyActive = false, ?bool $isActive = null, ?string $area = null): array
    {
        $where = [];
        $params = [];
        if ($isActive !== null) {
            $where[] = 'is_active = :active';
            $params['active'] = $isActive ? 1 : 0;
        }
        if ($area !== null) {
            $where[] = 'area = :area';
            $params['area'] = $area;
        }
        if ($roles !== null && $roles !== []) {
            $placeholders = [];
            foreach ($roles as $i => $role) {
                $placeholders[] = ":role{$i}";
                $params["role{$i}"] = $role->value;
            }
            $where[] = 'role IN (' . implode(', ', $placeholders) . ')';
        }
        if ($onlyActive) {
            $where[] = 'is_active = 1';
        }
        $sql = 'SELECT ' . self::PUBLIC_COLUMNS . ' FROM users'
            . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY name';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function create(string $name, string $email, string $passwordHash, Role $role, ?string $area = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, role, area) VALUES (:name, :email, :hash, :role, :area)'
        );
        $stmt->execute(['name' => $name, 'email' => $email, 'hash' => $passwordHash, 'role' => $role->value, 'area' => $area]);

        return (int) $this->db->lastInsertId();
    }

    /** @return array{total: int, admin: int, agent: int, requester: int} */
    public function countsByRole(): array
    {
        $counts = ['total' => 0, 'admin' => 0, 'agent' => 0, 'requester' => 0];
        foreach ($this->db->query('SELECT role, COUNT(*) AS n FROM users GROUP BY role') as $row) {
            $counts[$row['role']] = (int) $row['n'];
            $counts['total'] += (int) $row['n'];
        }

        return $counts;
    }

    /** @return list<string> */
    public function areas(): array
    {
        return $this->db->query('SELECT DISTINCT area FROM users WHERE area IS NOT NULL ORDER BY area')
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function registerFailedLogin(int $id, int $maxAttempts, int $lockMinutes): void
    {
        // Si alcanza el máximo, se bloquea y el contador vuelve a 0.
        $stmt = $this->db->prepare(
            'UPDATE users SET
                locked_until  = CASE WHEN failed_logins + 1 >= :max1 THEN UTC_TIMESTAMP() + INTERVAL :mins MINUTE ELSE locked_until END,
                failed_logins = CASE WHEN failed_logins + 1 >= :max2 THEN 0 ELSE failed_logins + 1 END
             WHERE id = :id'
        );
        $stmt->execute(['max1' => $maxAttempts, 'max2' => $maxAttempts, 'mins' => $lockMinutes, 'id' => $id]);
    }

    public function registerSuccessfulLogin(int $id): void
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET failed_logins = 0, locked_until = NULL, last_login_at = UTC_TIMESTAMP() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function updatePasswordHash(int $id, string $hash): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }
}

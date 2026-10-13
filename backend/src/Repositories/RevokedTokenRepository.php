<?php

declare(strict_types=1);

namespace Kodex\Repositories;

use PDO;

final class RevokedTokenRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function revoke(string $jti, int $userId, int $expiresAt): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO revoked_tokens (jti, user_id, expires_at) VALUES (:jti, :uid, FROM_UNIXTIME(:exp))'
        );
        $stmt->execute(['jti' => $jti, 'uid' => $userId, 'exp' => $expiresAt]);
        // Limpieza oportunista de tokens que ya expiraron de todos modos.
        $this->db->exec('DELETE FROM revoked_tokens WHERE expires_at < UTC_TIMESTAMP()');
    }

    public function isRevoked(string $jti): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM revoked_tokens WHERE jti = :jti');
        $stmt->execute(['jti' => $jti]);

        return (bool) $stmt->fetchColumn();
    }
}

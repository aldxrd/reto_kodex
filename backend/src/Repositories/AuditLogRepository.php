<?php

declare(strict_types=1);

namespace Kodex\Repositories;

use PDO;

/**
 * Registro de auditoría (solo inserción). El día 1 se usa para eventos de
 * autenticación; el día 2–3 se reutiliza para cambios en incidencias.
 */
final class AuditLogRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @param array<string, array{old: mixed, new: mixed}>|array<string, mixed>|null $changes */
    public function log(?int $userId, string $entityType, ?int $entityId, string $action, ?array $changes = null, ?string $ip = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO audit_logs (user_id, entity_type, entity_id, action, changes, ip_address)
             VALUES (:uid, :type, :eid, :action, :changes, :ip)'
        );
        $stmt->execute([
            'uid' => $userId,
            'type' => $entityType,
            'eid' => $entityId,
            'action' => $action,
            'changes' => $changes !== null ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
            'ip' => $ip,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function forEntity(string $entityType, int $entityId): array
    {
        $stmt = $this->db->prepare(
            'SELECT a.id, a.action, a.changes, a.created_at, u.id AS user_id, u.name AS user_name
             FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
             WHERE a.entity_type = :type AND a.entity_id = :eid
             ORDER BY a.created_at, a.id'
        );
        $stmt->execute(['type' => $entityType, 'eid' => $entityId]);

        return array_map(static function (array $row): array {
            $row['changes'] = $row['changes'] !== null ? json_decode((string) $row['changes'], true) : null;

            return $row;
        }, $stmt->fetchAll());
    }
}

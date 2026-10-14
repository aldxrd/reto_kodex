<?php

declare(strict_types=1);

namespace Kodex\Repositories;

use PDO;

final class CategoryRepository
{
    /** Estados que cuentan como "incidencias activas" (ver IncidentStatus::isActive). */
    private const ACTIVE_STATUSES = "'open', 'assigned', 'in_progress'";

    public function __construct(private readonly PDO $db)
    {
    }

    /**
     * Categorías con su área y el número de incidencias activas.
     *
     * @return list<array<string, mixed>>
     */
    public function list(bool $onlyActive = true): array
    {
        $sql = 'SELECT c.id, c.name, c.description, c.area, c.is_active,
                       COUNT(i.id) AS active_incidents
                FROM categories c
                LEFT JOIN incidents i ON i.category_id = c.id AND i.status IN (' . self::ACTIVE_STATUSES . ')'
            . ($onlyActive ? ' WHERE c.is_active = 1' : '')
            . ' GROUP BY c.id, c.name, c.description, c.area, c.is_active
                ORDER BY c.name';

        return array_map(static fn (array $c): array => [
            'id' => (int) $c['id'],
            'name' => $c['name'],
            'description' => $c['description'],
            'area' => $c['area'],
            'is_active' => (bool) $c['is_active'],
            'active_incidents' => (int) $c['active_incidents'],
        ], $this->db->query($sql)->fetchAll());
    }

    /** @return list<array<string, mixed>> */
    public function slaPolicies(): array
    {
        $rows = $this->db
            ->query("SELECT priority, response_hours, resolution_hours FROM sla_policies
                     ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->fetchAll();

        return array_map(static fn (array $r): array => [
            'priority' => $r['priority'],
            'response_hours' => (int) $r['response_hours'],
            'resolution_hours' => (int) $r['resolution_hours'],
        ], $rows);
    }
}

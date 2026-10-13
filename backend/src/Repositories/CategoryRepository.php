<?php

declare(strict_types=1);

namespace Kodex\Repositories;

use PDO;

final class CategoryRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    /** @return list<array<string, mixed>> */
    public function listActive(): array
    {
        return $this->db
            ->query('SELECT id, name, description FROM categories WHERE is_active = 1 ORDER BY name')
            ->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function slaPolicies(): array
    {
        return $this->db
            ->query("SELECT priority, response_hours, resolution_hours FROM sla_policies
                     ORDER BY FIELD(priority, 'critical', 'high', 'medium', 'low')")
            ->fetchAll();
    }
}

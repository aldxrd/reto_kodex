<?php

declare(strict_types=1);

namespace Kodex\Database;

use Kodex\Config;
use PDO;

final class Connection
{
    public static function fromConfig(Config $config): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config->get('DB_HOST', '127.0.0.1'),
            $config->int('DB_PORT', 3306),
            $config->get('DB_NAME', 'kodex'),
        );

        $pdo = new PDO($dsn, $config->get('DB_USER', 'kodex'), $config->get('DB_PASS', ''), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
        ]);
        // Todas las fechas se guardan y comparan en UTC.
        $pdo->exec("SET time_zone = '+00:00'");

        return $pdo;
    }
}

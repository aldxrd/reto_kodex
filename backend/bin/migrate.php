<?php

declare(strict_types=1);

/**
 * Crea (o recrea) el esquema de la base de datos.
 * Uso: php bin/migrate.php        (BORRA y recrea todas las tablas)
 */

use Kodex\Config;
use Kodex\Database\Connection;

require dirname(__DIR__) . '/vendor/autoload.php';

$config = Config::load(dirname(__DIR__) . '/.env');
$pdo = Connection::fromConfig($config);

$sql = (string) file_get_contents(dirname(__DIR__) . '/database/schema.sql');
// Quitar comentarios de línea y ejecutar sentencia por sentencia para detectar errores.
$sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    $pdo->exec($statement);
}

fwrite(STDOUT, sprintf("Esquema aplicado en '%s' (%d sentencias).\n", $config->get('DB_NAME', 'kodex'), count($statements)));

<?php

declare(strict_types=1);

use Kodex\App;
use Kodex\Config;
use Kodex\Database\Connection;
use Kodex\Http\Request;

// Con el servidor embebido (php -S), dejar que sirva archivos estáticos (docs.html).
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/vendor/autoload.php';

$config = Config::load(dirname(__DIR__) . '/.env');

ini_set('display_errors', '0');
date_default_timezone_set('UTC');

try {
    $app = new App($config, Connection::fromConfig($config));
    $app->handle(Request::fromGlobals())->send();
} catch (Throwable $e) {
    // Falla al arrancar (BD caída, .env incompleto...).
    error_log('[kodex] bootstrap: ' . $e->getMessage());
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => ['code' => 'SERVICE_UNAVAILABLE', 'message' => 'Servicio no disponible.']]);
}

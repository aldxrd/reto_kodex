<?php

declare(strict_types=1);

/**
 * Configuración del frontend.
 * Prioridad: variables de entorno reales > archivo .env > valores por defecto.
 */

$env = [];
$envFile = __DIR__ . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $env[$key] = trim($value, "\"'");
    }
}

$get = static fn (string $key, string $default): string => (string) (getenv($key) ?: ($env[$key] ?? $default));

return [
    // URL base de la API (sin barra final). Con Docker o `composer serve` el backend corre en el 8080.
    'api_url' => rtrim($get('API_URL', 'http://localhost:8080/api'), '/'),
    // Segundos máximos de espera por respuesta de la API.
    'api_timeout' => (int) $get('API_TIMEOUT', '8'),
    'app_name' => 'Incidencias',
    'company' => 'Kodex Solutions TI',
    'timezone' => $get('APP_TIMEZONE', 'America/Lima'),
];

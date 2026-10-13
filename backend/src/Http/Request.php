<?php

declare(strict_types=1);

namespace Kodex\Http;

use Kodex\Auth\AuthUser;

final class Request
{
    /** Usuario autenticado; lo completa el middleware de autenticación. */
    public ?AuthUser $user = null;

    /** Claims del token actual (jti, exp...), útiles para logout. */
    public array $tokenClaims = [];

    /**
     * @param array<string, mixed>  $query
     * @param array<string, string> $headers  claves en minúsculas
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query = [],
        public readonly array $headers = [],
        private readonly string $rawBody = '',
        public readonly string $ip = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }
        // Apache a veces no expone Authorization en HTTP_AUTHORIZATION.
        if (!isset($headers['authorization']) && function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                if (strtolower($name) === 'authorization') {
                    $headers['authorization'] = $value;
                }
            }
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            rtrim($path, '/') ?: '/',
            $_GET,
            $headers,
            (string) file_get_contents('php://input'),
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Cuerpo JSON como array. Lanza 400 si no es JSON válido.
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        if (trim($this->rawBody) === '') {
            return [];
        }
        $data = json_decode($this->rawBody, true);
        if (!is_array($data)) {
            throw new HttpException(400, 'INVALID_JSON', 'El cuerpo de la petición debe ser JSON válido.');
        }

        return $data;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('authorization') ?? '';
        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m) === 1) {
            return $m[1];
        }

        return null;
    }
}

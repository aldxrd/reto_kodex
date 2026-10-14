<?php

declare(strict_types=1);

/**
 * Error devuelto por la API (o imposibilidad de contactarla).
 * Refleja el formato del backend: { "error": { "code", "message", "details" } }.
 */
final class ApiException extends RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message, $status);
    }

    /** status 0 = no hubo respuesta (API apagada, URL incorrecta, timeout). */
    public static function unreachable(string $url): self
    {
        return new self(0, 'API_UNREACHABLE', "No se pudo conectar con la API en {$url}. Verifica que el backend esté levantado.");
    }

    /** @return array<string, string> Errores por campo (422). */
    public function fieldErrors(): array
    {
        $fields = $this->details['fields'] ?? [];

        return is_array($fields) ? array_map('strval', $fields) : [];
    }
}

/**
 * Cliente HTTP mínimo para la API REST.
 * Se usa SOLO desde el servidor PHP: el token nunca llega al navegador.
 */
final class ApiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout = 8,
        private readonly ?string $token = null,
    ) {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('La extensión cURL de PHP no está habilitada (en XAMPP: php.ini → extension=curl).');
        }
    }

    public function withToken(?string $token): self
    {
        return new self($this->baseUrl, $this->timeout, $token);
    }

    /** @return mixed Contenido de "data". */
    public function get(string $path, array $query = []): mixed
    {
        if ($query !== []) {
            $path .= '?' . http_build_query($query);
        }

        return $this->request('GET', $path)['data'] ?? null;
    }

    /** @return mixed Contenido de "data" (null en 204). */
    public function post(string $path, ?array $body = null): mixed
    {
        return $this->request('POST', $path, $body)['data'] ?? null;
    }

    /** @return array<string, mixed> Cuerpo completo ({data, meta}) o [] en 204. */
    public function request(string $method, string $path, ?array $body = null): array
    {
        $url = $this->baseUrl . $path;
        $headers = ['Accept: application/json'];
        if ($this->token !== null) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false || $status === 0) {
            throw ApiException::unreachable($this->baseUrl);
        }
        if ($status === 204 || $raw === '') {
            return [];
        }

        $json = json_decode((string) $raw, true);
        if (!is_array($json)) {
            throw new ApiException($status, 'INVALID_RESPONSE', 'La API devolvió una respuesta inesperada.');
        }
        if ($status >= 400) {
            $error = $json['error'] ?? [];
            throw new ApiException(
                $status,
                (string) ($error['code'] ?? 'API_ERROR'),
                (string) ($error['message'] ?? 'Error al comunicarse con la API.'),
                is_array($error['details'] ?? null) ? $error['details'] : [],
            );
        }

        return $json;
    }
}

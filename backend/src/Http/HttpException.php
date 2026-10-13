<?php

declare(strict_types=1);

namespace Kodex\Http;

use RuntimeException;

/**
 * Error controlado que se convierte en una respuesta JSON:
 *   { "error": { "code": "...", "message": "...", "details": {...} } }
 */
final class HttpException extends RuntimeException
{
    /** @param array<string, mixed> $details */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    /** @param array<string, string> $fieldErrors */
    public static function validation(array $fieldErrors): self
    {
        return new self(422, 'VALIDATION_ERROR', 'Los datos enviados no son válidos.', ['fields' => $fieldErrors]);
    }

    public static function unauthorized(string $message = 'Debes iniciar sesión.'): self
    {
        return new self(401, 'UNAUTHENTICATED', $message);
    }

    public static function forbidden(string $message = 'No tienes permiso para realizar esta acción.'): self
    {
        return new self(403, 'FORBIDDEN', $message);
    }

    public static function notFound(string $message = 'Recurso no encontrado.'): self
    {
        return new self(404, 'NOT_FOUND', $message);
    }

    public static function conflict(string $code, string $message): self
    {
        return new self(409, $code, $message);
    }
}

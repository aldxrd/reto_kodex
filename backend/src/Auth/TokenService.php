<?php

declare(strict_types=1);

namespace Kodex\Auth;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Kodex\Http\HttpException;
use Throwable;

/**
 * Emite y verifica JWT firmados con HS256.
 * El token solo lleva el id del usuario (sub) y un jti para poder revocarlo;
 * rol y estado se leen de la BD en cada petición para que un cambio de rol
 * o una desactivación tengan efecto inmediato.
 */
final class TokenService
{
    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttlSeconds,
        private readonly string $issuer = 'kodex-incidencias',
    ) {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException('JWT_SECRET debe tener al menos 32 caracteres.');
        }
    }

    /** @return array{token: string, jti: string, expires_at: int} */
    public function issue(int $userId): array
    {
        $now = time();
        $jti = bin2hex(random_bytes(16));
        $exp = $now + $this->ttlSeconds;
        $token = JWT::encode([
            'iss' => $this->issuer,
            'sub' => (string) $userId,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $exp,
            'jti' => $jti,
        ], $this->secret, self::ALGORITHM);

        return ['token' => $token, 'jti' => $jti, 'expires_at' => $exp];
    }

    /**
     * @return array{sub: int, jti: string, exp: int}
     * @throws HttpException 401
     */
    public function verify(string $token): array
    {
        try {
            $claims = (array) JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (ExpiredException) {
            throw new HttpException(401, 'TOKEN_EXPIRED', 'La sesión expiró. Inicia sesión nuevamente.');
        } catch (Throwable) {
            throw new HttpException(401, 'TOKEN_INVALID', 'Token de acceso no válido.');
        }

        if (($claims['iss'] ?? null) !== $this->issuer || !isset($claims['sub'], $claims['jti'], $claims['exp'])) {
            throw new HttpException(401, 'TOKEN_INVALID', 'Token de acceso no válido.');
        }

        return ['sub' => (int) $claims['sub'], 'jti' => (string) $claims['jti'], 'exp' => (int) $claims['exp']];
    }

    public function ttl(): int
    {
        return $this->ttlSeconds;
    }
}

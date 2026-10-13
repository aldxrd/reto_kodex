<?php

declare(strict_types=1);

namespace Kodex\Auth;

use Kodex\Http\HttpException;
use Kodex\Http\Request;
use Kodex\Repositories\AuditLogRepository;
use Kodex\Repositories\RevokedTokenRepository;
use Kodex\Repositories\UserRepository;

final class AuthService
{
    /** Hash ficticio para que el tiempo de respuesta no revele si el correo existe. */
    private const DUMMY_HASH = '$2y$10$VyzGchck0teTmwyY0/0kU.ktNBwJPiOHbQ81EJ18NrQnJMaxBuamC';

    public function __construct(
        private readonly UserRepository $users,
        private readonly RevokedTokenRepository $revoked,
        private readonly AuditLogRepository $audit,
        private readonly TokenService $tokens,
        private readonly int $maxFailedLogins = 5,
        private readonly int $lockMinutes = 15,
    ) {
    }

    /**
     * @return array{token: string, token_type: string, expires_in: int, user: array<string, mixed>}
     * @throws HttpException 401 credenciales inválidas, 403 cuenta inactiva, 429 cuenta bloqueada
     */
    public function login(string $email, string $password, string $ip): array
    {
        $row = $this->users->findForLogin($email);

        if ($row === null) {
            password_verify($password, self::DUMMY_HASH);
            $this->audit->log(null, 'auth', null, 'login_failed', ['email' => $email], $ip);
            throw $this->invalidCredentials();
        }

        $userId = (int) $row['id'];

        if ($row['locked_until'] !== null && strtotime($row['locked_until'] . ' UTC') > time()) {
            throw new HttpException(429, 'ACCOUNT_LOCKED', 'Demasiados intentos fallidos. Intenta de nuevo más tarde.');
        }

        if (!password_verify($password, (string) $row['password_hash'])) {
            $this->users->registerFailedLogin($userId, $this->maxFailedLogins, $this->lockMinutes);
            $this->audit->log($userId, 'auth', $userId, 'login_failed', null, $ip);
            throw $this->invalidCredentials();
        }

        if ((int) $row['is_active'] !== 1) {
            throw HttpException::forbidden('Tu cuenta está desactivada. Contacta al administrador.');
        }

        if (password_needs_rehash((string) $row['password_hash'], PASSWORD_DEFAULT)) {
            $this->users->updatePasswordHash($userId, password_hash($password, PASSWORD_DEFAULT));
        }

        $this->users->registerSuccessfulLogin($userId);
        $this->audit->log($userId, 'auth', $userId, 'login', null, $ip);

        $issued = $this->tokens->issue($userId);

        return [
            'token' => $issued['token'],
            'token_type' => 'Bearer',
            'expires_in' => $this->tokens->ttl(),
            'user' => AuthUser::fromRow($row)->toArray(),
        ];
    }

    /**
     * Resuelve el usuario a partir del header Authorization y lo adjunta a la petición.
     *
     * @throws HttpException 401
     */
    public function authenticate(Request $request): AuthUser
    {
        $token = $request->bearerToken();
        if ($token === null) {
            throw HttpException::unauthorized();
        }

        $claims = $this->tokens->verify($token);
        if ($this->revoked->isRevoked($claims['jti'])) {
            throw new HttpException(401, 'TOKEN_REVOKED', 'La sesión fue cerrada. Inicia sesión nuevamente.');
        }

        $row = $this->users->findActiveById($claims['sub']);
        if ($row === null) {
            throw HttpException::unauthorized('El usuario no existe o está desactivado.');
        }

        $request->user = AuthUser::fromRow($row);
        $request->tokenClaims = $claims;

        return $request->user;
    }

    public function logout(Request $request): void
    {
        $user = $request->user ?? throw HttpException::unauthorized();
        $this->revoked->revoke($request->tokenClaims['jti'], $user->id, $request->tokenClaims['exp']);
        $this->audit->log($user->id, 'auth', $user->id, 'logout', null, $request->ip);
    }

    private function invalidCredentials(): HttpException
    {
        return new HttpException(401, 'INVALID_CREDENTIALS', 'Correo o contraseña incorrectos.');
    }
}

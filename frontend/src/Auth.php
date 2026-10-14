<?php

declare(strict_types=1);

/**
 * Sesión del usuario en el frontend.
 *
 * El JWT que emite la API se guarda en $_SESSION (servidor). Al navegador solo llega
 * la cookie de sesión PHP (HttpOnly), así un script inyectado no puede leer el token.
 *
 * Importante: ocultar opciones según el rol es solo experiencia de usuario.
 * Quien autoriza de verdad es el backend (devuelve 401/403).
 */
final class Auth
{
    public function __construct(private readonly ApiClient $api)
    {
    }

    /** @throws ApiException 401 credenciales, 422 validación, 429 bloqueo, 403 cuenta desactivada */
    public function attempt(string $email, string $password): array
    {
        $data = $this->api->post('/auth/login', ['email' => $email, 'password' => $password]);

        // Evita fijación de sesión: nuevo id de sesión al autenticarse.
        session_regenerate_id(true);
        $_SESSION['auth'] = [
            'token' => (string) $data['token'],
            'user' => $data['user'],
            'expires_at' => time() + (int) ($data['expires_in'] ?? 0),
        ];

        return $data['user'];
    }

    public function check(): bool
    {
        $auth = $_SESSION['auth'] ?? null;

        return is_array($auth) && isset($auth['token']) && ($auth['expires_at'] ?? 0) > time();
    }

    public function token(): ?string
    {
        return $this->check() ? $_SESSION['auth']['token'] : null;
    }

    /** @return array{id:int,name:string,email:string,role:string,role_label:string}|null */
    public function user(): ?array
    {
        return $this->check() ? $_SESSION['auth']['user'] : null;
    }

    public function role(): ?string
    {
        return $this->user()['role'] ?? null;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role(), $roles, true);
    }

    /**
     * Valida el token contra GET /auth/me en cada página protegida.
     * El backend lee rol y estado desde la BD, así un cambio de rol o una
     * desactivación se refleja de inmediato en el menú.
     *
     * @throws ApiException 401 si el token venció o fue revocado
     */
    public function refresh(): void
    {
        $user = $this->api->withToken($this->token())->get('/auth/me');
        $_SESSION['auth']['user'] = $user;
    }

    /** Revoca el token en la API (si se puede) y destruye la sesión local. */
    public function logout(): void
    {
        $token = $_SESSION['auth']['token'] ?? null;
        if ($token !== null) {
            try {
                $this->api->withToken($token)->post('/auth/logout');
            } catch (ApiException) {
                // Aunque la API falle, la sesión local se cierra igual.
            }
        }
        $this->clear();
    }

    public function clear(): void
    {
        unset($_SESSION['auth']);
        session_regenerate_id(true);
    }

    /** Pantalla de inicio según el rol. */
    public static function homeFor(?string $role): string
    {
        return $role === 'requester' ? '/mis-incidencias' : '/dashboard';
    }
}

<?php

declare(strict_types=1);

namespace Kodex\Tests\Api;

use Firebase\JWT\JWT;

final class AuthTest extends ApiTestCase
{
    public function test_login_devuelve_token_y_usuario_sin_hash(): void
    {
        $res = $this->call('POST', '/api/auth/login', ['email' => 'ADMIN@test.pe', 'password' => 'Admin123!']);

        self::assertSame(200, $res->status);
        $data = $res->body['data'];
        self::assertSame('Bearer', $data['token_type']);
        self::assertSame(3600, $data['expires_in']);
        self::assertSame('admin', $data['user']['role']);
        self::assertArrayNotHasKey('password_hash', $data['user']);
        self::assertNotEmpty($data['token']);
    }

    public function test_password_incorrecta_y_correo_inexistente_dan_el_mismo_error(): void
    {
        $wrongPassword = $this->call('POST', '/api/auth/login', ['email' => 'admin@test.pe', 'password' => 'mala']);
        $unknownEmail = $this->call('POST', '/api/auth/login', ['email' => 'nadie@test.pe', 'password' => 'mala']);

        $this->assertError($wrongPassword, 401, 'INVALID_CREDENTIALS');
        $this->assertError($unknownEmail, 401, 'INVALID_CREDENTIALS');
        self::assertSame($wrongPassword->body, $unknownEmail->body);
    }

    public function test_login_valida_los_campos(): void
    {
        $res = $this->call('POST', '/api/auth/login', ['email' => 'no-es-correo']);

        $this->assertError($res, 422, 'VALIDATION_ERROR');
        self::assertArrayHasKey('email', $res->body['error']['details']['fields']);
        self::assertArrayHasKey('password', $res->body['error']['details']['fields']);
    }

    public function test_json_mal_formado_devuelve_400(): void
    {
        $this->assertError($this->call('POST', '/api/auth/login', '{roto'), 400, 'INVALID_JSON');
    }

    public function test_cuenta_se_bloquea_tras_intentos_fallidos(): void
    {
        // LOGIN_MAX_ATTEMPTS = 3 en phpunit.xml
        for ($i = 0; $i < 3; $i++) {
            $this->call('POST', '/api/auth/login', ['email' => 'usuario@test.pe', 'password' => 'mala']);
        }
        // Incluso con la contraseña correcta queda bloqueada.
        $res = $this->call('POST', '/api/auth/login', ['email' => 'usuario@test.pe', 'password' => 'Usuario123!']);

        $this->assertError($res, 429, 'ACCOUNT_LOCKED');
    }

    public function test_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $this->db->exec("UPDATE users SET is_active = 0 WHERE email = 'usuario@test.pe'");

        $res = $this->call('POST', '/api/auth/login', ['email' => 'usuario@test.pe', 'password' => 'Usuario123!']);

        $this->assertError($res, 403, 'FORBIDDEN');
    }

    public function test_me_requiere_token(): void
    {
        $this->assertError($this->call('GET', '/api/auth/me'), 401, 'UNAUTHENTICATED');
    }

    public function test_me_devuelve_el_usuario_del_token(): void
    {
        $token = $this->login('agente@test.pe', 'Agente123!');

        $res = $this->call('GET', '/api/auth/me', null, $token);

        self::assertSame(200, $res->status);
        self::assertSame('agente@test.pe', $res->body['data']['email']);
        self::assertSame('agent', $res->body['data']['role']);
    }

    public function test_token_manipulado_es_rechazado(): void
    {
        $token = $this->login('usuario@test.pe', 'Usuario123!');
        [$header, $payload, $signature] = explode('.', $token);
        // Intento de suplantar al admin (sub = 1) sin conocer el secreto.
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
        $claims['sub'] = '1';
        $forged = $header . '.' . rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=') . '.' . $signature;

        $this->assertError($this->call('GET', '/api/auth/me', null, $forged), 401, 'TOKEN_INVALID');
    }

    public function test_token_firmado_con_otro_secreto_es_rechazado(): void
    {
        $token = JWT::encode(
            ['iss' => 'kodex-incidencias', 'sub' => '1', 'jti' => 'x', 'exp' => time() + 60],
            'otro-secreto-otro-secreto-otro-secreto-00',
            'HS256',
        );

        $this->assertError($this->call('GET', '/api/auth/me', null, $token), 401, 'TOKEN_INVALID');
    }

    public function test_token_expirado_es_rechazado(): void
    {
        $token = JWT::encode(
            ['iss' => 'kodex-incidencias', 'sub' => '1', 'jti' => 'x', 'iat' => time() - 7200, 'exp' => time() - 3600],
            $this->config->get('JWT_SECRET'),
            'HS256',
        );

        $this->assertError($this->call('GET', '/api/auth/me', null, $token), 401, 'TOKEN_EXPIRED');
    }

    public function test_logout_revoca_el_token(): void
    {
        $token = $this->login('admin@test.pe', 'Admin123!');

        self::assertSame(204, $this->call('POST', '/api/auth/logout', null, $token)->status);
        $this->assertError($this->call('GET', '/api/auth/me', null, $token), 401, 'TOKEN_REVOKED');
    }

    public function test_desactivar_usuario_invalida_su_token_inmediatamente(): void
    {
        $token = $this->login('agente@test.pe', 'Agente123!');
        $this->db->exec("UPDATE users SET is_active = 0 WHERE email = 'agente@test.pe'");

        $this->assertError($this->call('GET', '/api/auth/me', null, $token), 401, 'UNAUTHENTICATED');
    }

    public function test_login_y_logout_quedan_en_auditoria(): void
    {
        $token = $this->login('admin@test.pe', 'Admin123!');
        $this->call('POST', '/api/auth/login', ['email' => 'admin@test.pe', 'password' => 'mala']);
        $this->call('POST', '/api/auth/logout', null, $token);

        $actions = $this->db->query("SELECT action FROM audit_logs WHERE entity_type = 'auth' ORDER BY id")->fetchAll(\PDO::FETCH_COLUMN);

        self::assertSame(['login', 'login_failed', 'logout'], $actions);
    }
}

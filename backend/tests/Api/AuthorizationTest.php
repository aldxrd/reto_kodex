<?php

declare(strict_types=1);

namespace Kodex\Tests\Api;

/** Permisos por rol aplicados en el servidor (no solo ocultos en la interfaz). */
final class AuthorizationTest extends ApiTestCase
{
    public function test_solicitante_no_puede_listar_ni_crear_usuarios(): void
    {
        $token = $this->login('usuario@test.pe', 'Usuario123!');

        $this->assertError($this->call('GET', '/api/users', null, $token), 403, 'FORBIDDEN');
        $this->assertError($this->call('POST', '/api/users', [
            'name' => 'Intruso', 'email' => 'x@test.pe', 'password' => 'Password1!', 'role' => 'admin',
        ], $token), 403, 'FORBIDDEN');

        $count = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE email = 'x@test.pe'")->fetchColumn();
        self::assertSame(0, $count);
    }

    public function test_agente_no_puede_crear_usuarios(): void
    {
        $token = $this->login('agente@test.pe', 'Agente123!');

        $this->assertError($this->call('POST', '/api/users', [
            'name' => 'Otro', 'email' => 'otro@test.pe', 'password' => 'Password1!', 'role' => 'agent',
        ], $token), 403, 'FORBIDDEN');
    }

    public function test_lista_de_agentes_solo_para_admin_y_agente(): void
    {
        $requester = $this->login('usuario@test.pe', 'Usuario123!');
        $agent = $this->login('agente@test.pe', 'Agente123!');

        $this->assertError($this->call('GET', '/api/users/agents', null, $requester), 403, 'FORBIDDEN');

        $res = $this->call('GET', '/api/users/agents', null, $agent);
        self::assertSame(200, $res->status);
        $roles = array_unique(array_column($res->body['data'], 'role'));
        sort($roles);
        self::assertSame(['admin', 'agent'], $roles);
    }

    public function test_admin_crea_usuario_y_queda_auditado(): void
    {
        $token = $this->login('admin@test.pe', 'Admin123!');

        $res = $this->call('POST', '/api/users', [
            'name' => 'Nuevo Agente', 'email' => 'Nuevo@Test.pe', 'password' => 'Password1!', 'role' => 'agent',
        ], $token);

        self::assertSame(201, $res->status);
        self::assertSame('nuevo@test.pe', $res->body['data']['email']);
        self::assertArrayNotHasKey('password_hash', $res->body['data']);

        $audit = $this->db->query("SELECT user_id, action FROM audit_logs WHERE entity_type = 'user'")->fetch();
        self::assertSame(['user_id' => 1, 'action' => 'created'], $audit);

        // El nuevo usuario puede iniciar sesión.
        $this->login('nuevo@test.pe', 'Password1!');
    }

    public function test_crear_usuario_valida_correo_duplicado_y_rol(): void
    {
        $token = $this->login('admin@test.pe', 'Admin123!');

        $res = $this->call('POST', '/api/users', [
            'name' => 'Dup', 'email' => 'agente@test.pe', 'password' => 'corta', 'role' => 'superuser',
        ], $token);

        $this->assertError($res, 422, 'VALIDATION_ERROR');
        $fields = $res->body['error']['details']['fields'];
        self::assertArrayHasKey('email', $fields);
        self::assertArrayHasKey('password', $fields);
        self::assertArrayHasKey('role', $fields);
    }

    public function test_rutas_inexistentes_y_metodos_no_permitidos(): void
    {
        $token = $this->login('admin@test.pe', 'Admin123!');

        $this->assertError($this->call('GET', '/api/no-existe', null, $token), 404, 'NOT_FOUND');
        $this->assertError($this->call('DELETE', '/api/auth/login'), 405, 'METHOD_NOT_ALLOWED');
    }

    public function test_catalogos_requieren_sesion_y_exponen_transiciones(): void
    {
        $this->assertError($this->call('GET', '/api/catalogs'), 401, 'UNAUTHENTICATED');

        $token = $this->login('usuario@test.pe', 'Usuario123!');
        $res = $this->call('GET', '/api/catalogs', null, $token);

        self::assertSame(200, $res->status);
        $statuses = array_column($res->body['data']['statuses'], 'transitions', 'value');
        self::assertSame(['in_progress', 'cancelled'], $statuses['open']);
        self::assertSame([], $statuses['closed']);
        self::assertCount(5, $res->body['data']['categories']);
        self::assertCount(4, $res->body['data']['sla']);
    }

    public function test_cors_solo_para_origenes_permitidos(): void
    {
        $allowed = $this->call('OPTIONS', '/api/auth/login', null, null, ['origin' => 'http://localhost:8000']);
        $denied = $this->call('OPTIONS', '/api/auth/login', null, null, ['origin' => 'https://evil.example']);

        self::assertSame('http://localhost:8000', $allowed->headers['Access-Control-Allow-Origin'] ?? null);
        self::assertArrayNotHasKey('Access-Control-Allow-Origin', $denied->headers);
    }
}

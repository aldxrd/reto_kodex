<?php

declare(strict_types=1);

namespace Kodex\Tests\Api;

use Kodex\App;
use Kodex\Config;
use Kodex\Database\Connection;
use Kodex\Domain\Role;
use Kodex\Http\Request;
use Kodex\Http\Response;
use Kodex\Repositories\UserRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Base para pruebas de API: recrea el esquema en la BD de pruebas y ejecuta
 * la aplicación en proceso (sin servidor HTTP), con peticiones reales.
 */
abstract class ApiTestCase extends TestCase
{
    protected PDO $db;
    protected App $app;
    protected Config $config;

    protected function setUp(): void
    {
        $this->config = Config::load(dirname(__DIR__, 2) . '/.env')->with([
            'DB_NAME' => (string) (getenv('DB_NAME') ?: 'kodex_test'),
        ]);
        if (!str_ends_with($this->config->get('DB_NAME'), '_test')) {
            self::fail('Las pruebas solo se ejecutan contra una BD cuyo nombre termine en _test.');
        }

        $this->db = Connection::fromConfig($this->config);
        $sql = preg_replace('/^\s*--.*$/m', '', (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema.sql'));
        foreach (array_filter(array_map('trim', explode(';', (string) $sql))) as $statement) {
            $this->db->exec($statement);
        }

        $users = new UserRepository($this->db);
        $users->create('Admin', 'admin@test.pe', password_hash('Admin123!', PASSWORD_DEFAULT), Role::Admin);
        $users->create('Agente', 'agente@test.pe', password_hash('Agente123!', PASSWORD_DEFAULT), Role::Agent);
        $users->create('Usuario', 'usuario@test.pe', password_hash('Usuario123!', PASSWORD_DEFAULT), Role::Requester);

        $this->app = new App($this->config, $this->db);
    }

    /** @param array<string, mixed>|string|null $body */
    protected function call(string $method, string $path, array|string|null $body = null, ?string $token = null, array $headers = []): Response
    {
        $parts = parse_url($path);
        parse_str($parts['query'] ?? '', $query);
        if ($token !== null) {
            $headers['authorization'] = "Bearer {$token}";
        }
        $raw = is_array($body) ? (string) json_encode($body) : (string) $body;

        return $this->app->handle(new Request($method, $parts['path'] ?? '/', $query, $headers, $raw, '127.0.0.1'));
    }

    protected function login(string $email, string $password): string
    {
        $res = $this->call('POST', '/api/auth/login', ['email' => $email, 'password' => $password]);
        self::assertSame(200, $res->status, json_encode($res->body) ?: '');

        return $res->body['data']['token'];
    }

    protected function assertError(Response $res, int $status, string $code): void
    {
        self::assertSame($status, $res->status, json_encode($res->body) ?: '');
        self::assertSame($code, $res->body['error']['code']);
    }
}

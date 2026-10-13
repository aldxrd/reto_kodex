<?php

declare(strict_types=1);

namespace Kodex;

use Kodex\Auth\AuthService;
use Kodex\Auth\TokenService;
use Kodex\Controllers\AuthController;
use Kodex\Controllers\CatalogController;
use Kodex\Controllers\UserController;
use Kodex\Domain\Role;
use Kodex\Http\HttpException;
use Kodex\Http\Request;
use Kodex\Http\Response;
use Kodex\Http\Router;
use Kodex\Repositories\AuditLogRepository;
use Kodex\Repositories\CategoryRepository;
use Kodex\Repositories\RevokedTokenRepository;
use Kodex\Repositories\UserRepository;
use PDO;
use Throwable;

/**
 * Punto de composición: crea dependencias, registra rutas y procesa peticiones.
 *
 * Flujo de una petición:
 *   CORS preflight → router → autenticación (salvo rutas públicas)
 *   → autorización por rol → controlador → respuesta JSON
 */
final class App
{
    private Router $router;
    private AuthService $auth;

    public function __construct(private readonly Config $config, private readonly PDO $db)
    {
        $users = new UserRepository($db);
        $audit = new AuditLogRepository($db);
        $tokens = new TokenService($config->get('JWT_SECRET'), $config->int('JWT_TTL', 28800));

        $this->auth = new AuthService(
            $users,
            new RevokedTokenRepository($db),
            $audit,
            $tokens,
            $config->int('LOGIN_MAX_ATTEMPTS', 5),
            $config->int('LOGIN_LOCK_MINUTES', 15),
        );

        $this->router = new Router();
        $this->registerRoutes(
            new AuthController($this->auth),
            new CatalogController(new CategoryRepository($db)),
            new UserController($users, $audit),
        );
    }

    private function registerRoutes(AuthController $auth, CatalogController $catalogs, UserController $users): void
    {
        $r = $this->router;

        // Públicas
        $r->get('/api/health', fn () => $this->health(), public: true);
        $r->get('/api/openapi.yaml', fn () => $this->openApi(), public: true);
        $r->post('/api/auth/login', [$auth, 'login'], public: true);

        // Cualquier usuario autenticado
        $r->get('/api/auth/me', [$auth, 'me']);
        $r->post('/api/auth/logout', [$auth, 'logout']);
        $r->get('/api/catalogs', [$catalogs, 'index']);

        // Por rol
        $r->get('/api/users/agents', [$users, 'agents'], roles: [Role::Admin, Role::Agent]);
        $r->get('/api/users', [$users, 'index'], roles: [Role::Admin]);
        $r->post('/api/users', [$users, 'store'], roles: [Role::Admin]);

        // Día 2–3: /api/incidents, /api/dashboard, /api/audit (ver docs/openapi.yaml)
    }

    public function handle(Request $request): Response
    {
        try {
            if ($request->method === 'OPTIONS') {
                return $this->withCors($request, new Response(204));
            }

            [$route, $params] = $this->router->match($request->method, $request->path);

            if (!$route->public) {
                $user = $this->auth->authenticate($request);
                if ($route->roles !== null && !$user->is(...$route->roles)) {
                    throw HttpException::forbidden();
                }
            }

            $response = ($route->handler)($request, $params);
        } catch (HttpException $e) {
            $response = Response::error($e);
        } catch (Throwable $e) {
            error_log(sprintf('[kodex] %s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
            $message = $this->config->bool('APP_DEBUG') ? $e->getMessage() : 'Error interno del servidor.';
            $response = Response::error(new HttpException(500, 'INTERNAL_ERROR', $message));
        }

        return $this->withCors($request, $response);
    }

    private function health(): Response
    {
        $this->db->query('SELECT 1');

        return Response::data(['status' => 'ok', 'time' => gmdate('c')]);
    }

    /** Contrato de API para Swagger UI (public/docs.html). */
    private function openApi(): Response
    {
        $spec = (string) file_get_contents(dirname(__DIR__) . '/docs/openapi.yaml');

        return new Response(200, $spec, ['Content-Type' => 'application/yaml; charset=utf-8']);
    }

    private function withCors(Request $request, Response $response): Response
    {
        $origin = $request->header('origin');
        $allowed = array_map('trim', explode(',', $this->config->get('CORS_ORIGINS', '')));
        if ($origin === null || !in_array($origin, $allowed, true)) {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
            ->withHeader('Access-Control-Max-Age', '600');
    }
}

<?php

declare(strict_types=1);

namespace Kodex\Http;

use Kodex\Domain\Role;

/**
 * Router mínimo con parámetros de ruta ({id}) y metadatos de seguridad.
 *
 * Por defecto TODA ruta requiere autenticación. Las rutas públicas deben
 * declararse explícitamente con public: true (seguro por defecto).
 */
final class Router
{
    /** @var list<Route> */
    private array $routes = [];

    /**
     * @param callable(Request, array<string,string>): Response $handler
     * @param list<Role>|null $roles  null = cualquier usuario autenticado
     */
    public function add(string $method, string $pattern, callable $handler, bool $public = false, ?array $roles = null): void
    {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($pattern, '/')) . '$#';
        $this->routes[] = new Route(strtoupper($method), $pattern, $regex, $handler, $public, $roles);
    }

    public function get(string $p, callable $h, bool $public = false, ?array $roles = null): void
    {
        $this->add('GET', $p, $h, $public, $roles);
    }

    public function post(string $p, callable $h, bool $public = false, ?array $roles = null): void
    {
        $this->add('POST', $p, $h, $public, $roles);
    }

    public function patch(string $p, callable $h, bool $public = false, ?array $roles = null): void
    {
        $this->add('PATCH', $p, $h, $public, $roles);
    }

    public function delete(string $p, callable $h, bool $public = false, ?array $roles = null): void
    {
        $this->add('DELETE', $p, $h, $public, $roles);
    }

    /**
     * @return array{0: Route, 1: array<string,string>}
     * @throws HttpException 404 si no existe la ruta, 405 si existe con otro método
     */
    public function match(string $method, string $path): array
    {
        $allowed = [];
        foreach ($this->routes as $route) {
            if (preg_match($route->regex, $path, $m) !== 1) {
                continue;
            }
            if ($route->method !== $method) {
                $allowed[] = $route->method;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

            return [$route, $params];
        }

        if ($allowed !== []) {
            throw new HttpException(405, 'METHOD_NOT_ALLOWED', 'Método no permitido para esta ruta.', [
                'allowed' => array_values(array_unique($allowed)),
            ]);
        }
        throw HttpException::notFound('La ruta solicitada no existe.');
    }
}

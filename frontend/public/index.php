<?php

declare(strict_types=1);

/**
 * Front controller del frontend.
 *
 * Flujo: archivos estáticos → sesión → rutas públicas (login/logout)
 *        → exige sesión → valida token con GET /auth/me → rol → página.
 */

define('APP_ROOT', dirname(__DIR__));

// Con el servidor embebido (php -S), servir directamente CSS/JS/imágenes.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
    if ($file !== __FILE__ && is_file($file)) {
        return false;
    }
}

require APP_ROOT . '/src/helpers.php';
require APP_ROOT . '/src/ApiClient.php';
require APP_ROOT . '/src/Auth.php';
require APP_ROOT . '/src/SampleData.php';

$config = require APP_ROOT . '/config.php';
date_default_timezone_set($config['timezone']);

// Cookie de sesión: no accesible desde JavaScript y no enviada en peticiones de otros sitios.
session_name('kodex_front');
session_set_cookie_params([
    'path' => base_path() === '' ? '/' : base_path(),
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

$api = new ApiClient($config['api_url'], $config['api_timeout']);
$auth = new Auth($api);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = current_path();

/** Responde con una página de error y termina. */
$fail = static function (int $status, string $template, array $data = []) use ($auth, $config): never {
    http_response_code($status);
    $data += ['title' => 'Error', 'user' => $auth->user(), 'config' => $config, 'nav' => require APP_ROOT . '/src/navigation.php', 'navKey' => '', 'breadcrumb' => ['Error']];
    echo view($template, $data, $auth->check() ? 'layout' : 'layout-auth');
    exit;
};

// ---------- Rutas públicas ----------

if ($path === '/') {
    redirect($auth->check() ? Auth::homeFor($auth->role()) : '/login');
}

if ($path === '/login') {
    if ($auth->check()) {
        redirect(Auth::homeFor($auth->role()));
    }

    $email = '';
    $errors = [];
    $message = null;

    if ($method === 'POST') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!csrf_valid()) {
            $message = 'El formulario expiró. Vuelve a intentarlo.';
        } else {
            // Validación de interfaz (la API vuelve a validar).
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Ingresa un correo válido.';
            }
            if ($password === '') {
                $errors['password'] = 'Ingresa tu contraseña.';
            }

            if ($errors === []) {
                try {
                    $user = $auth->attempt($email, $password);
                    redirect(Auth::homeFor($user['role']));
                } catch (ApiException $e) {
                    $errors = $e->fieldErrors();
                    // 401 credenciales, 403 cuenta desactivada, 429 bloqueo, 0 API caída.
                    $message = $e->status === 422 && $errors !== [] ? null : $e->getMessage();
                }
            }
        }
    }

    echo view('auth/login', [
        'title' => 'Iniciar sesión',
        'email' => $email,
        'errors' => $errors,
        'message' => $message,
        'notice' => flash('login_notice'),
        'config' => $config,
    ], 'layout-auth');
    exit;
}

if ($path === '/logout') {
    if ($method === 'POST' && csrf_valid()) {
        $auth->logout();
        flash('login_notice', 'Cerraste sesión correctamente.');
    }
    redirect('/login');
}

// ---------- Rutas protegidas ----------

if (!$auth->check()) {
    redirect('/login');
}

try {
    $auth->refresh();

    $routes = (require APP_ROOT . '/src/routes.php')($api->withToken($auth->token()), $auth, $config);

    foreach ($routes as $route) {
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $route['pattern']) . '$#';
        if ($route['method'] !== $method || !preg_match($regex, $path, $matches)) {
            continue;
        }
        if (!$auth->hasRole(...$route['roles'])) {
            $fail(403, 'errors/403');
        }
        $html = ($route['handler'])(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
        if ($html === null) {
            $fail(404, 'errors/404');
        }
        echo $html;
        exit;
    }

    $fail(404, 'errors/404');
} catch (ApiException $e) {
    if ($e->status === 401) {
        // Token vencido o revocado: se cierra la sesión local.
        $auth->clear();
        flash('login_notice', 'Tu sesión expiró. Vuelve a iniciar sesión.');
        redirect('/login');
    }
    if ($e->status === 403) {
        $fail(403, 'errors/403', ['message' => $e->getMessage()]);
    }
    $fail($e->status === 0 ? 503 : 502, 'errors/api', ['message' => $e->getMessage(), 'code' => $e->errorCode]);
}

<?php

declare(strict_types=1);

// ---------- Salida segura ----------

/** Escapa texto para HTML. Usar SIEMPRE al imprimir datos. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---------- URLs y navegación ----------

/** Prefijo de la app ('' con `php -S`, '/carpeta/frontend/public' en XAMPP). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = rtrim($dir, '/');
    }

    return $base;
}

function url(string $path = '/'): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Ruta solicitada sin el prefijo de la app ni el query string. */
function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = base_path();
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    $path = '/' . trim($path, '/');

    return $path === '/index.php' ? '/' : $path;
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

// ---------- Mensajes flash (sobreviven a un redirect) ----------

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;

        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);

    return $value;
}

// ---------- CSRF ----------

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['_csrf'] ?? '';

    return is_string($sent) && $sent !== '' && hash_equals(csrf_token(), $sent);
}

// ---------- Vistas ----------

/**
 * Renderiza una vista dentro del layout (o sola si $layout = null).
 * Las variables de $data quedan disponibles en la vista.
 */
function view(string $template, array $data = [], ?string $layout = 'layout'): string
{
    $render = static function (string $__file, array $__data): string {
        extract($__data, EXTR_SKIP);
        ob_start();
        require $__file;

        return (string) ob_get_clean();
    };

    $content = $render(APP_ROOT . "/views/{$template}.php", $data);
    if ($layout === null) {
        return $content;
    }

    return $render(APP_ROOT . "/views/{$layout}.php", $data + ['content' => $content]);
}

// ---------- Presentación de dominio ----------

/**
 * Etiqueta y clase CSS por estado (mismos estados que GET /api/catalogs y el Figma).
 *
 * @return array{0:string,1:string}
 */
function status_meta(string $status): array
{
    return match ($status) {
        'open' => ['Abierta', 'status-open'],
        'assigned' => ['Asignada', 'status-assigned'],
        'in_progress' => ['En progreso', 'status-progress'],
        'resolved' => ['Resuelta', 'status-resolved'],
        'closed' => ['Cerrada', 'status-closed'],
        'cancelled' => ['Cancelada', 'status-cancelled'],
        default => [$status, 'status-closed'],
    };
}

function status_badge(string $status): string
{
    [$label, $class] = status_meta($status);

    return '<span class="badge-status ' . e($class) . '">' . e($label) . '</span>';
}

/** @return array{0:string,1:string} */
function priority_meta(string $priority): array
{
    return match ($priority) {
        'critical' => ['Crítica', 'priority-critical'],
        'high' => ['Alta', 'priority-high'],
        'medium' => ['Media', 'priority-medium'],
        'low' => ['Baja', 'priority-low'],
        default => [$priority, 'priority-low'],
    };
}

function priority_label(string $priority): string
{
    [$label, $class] = priority_meta($priority);

    return '<span class="priority ' . e($class) . '"><span class="dot"></span>' . e($label) . '</span>';
}

function role_badge(string $role, string $label): string
{
    $class = match ($role) {
        'admin' => 'role-admin',
        'agent' => 'role-agent',
        default => 'role-requester',
    };

    return '<span class="badge-role ' . $class . '">' . e($label) . '</span>';
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '', 0, 1);
    $last = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';

    return mb_strtoupper($first . $last);
}

/** Fecha UTC de la API → hora local legible ("14 oct, 10:59"). */
function format_datetime(?string $utc, string $timezone = 'America/Lima'): string
{
    if ($utc === null || $utc === '') {
        return '—';
    }
    try {
        $date = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    } catch (Exception) {
        return $utc;
    }
    $local = $date->setTimezone(new DateTimeZone($timezone));
    $months = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];

    return $local->format('j') . ' ' . $months[(int) $local->format('n') - 1] . ', ' . $local->format('H:i');
}

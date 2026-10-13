# Kodex Incidencias — Backend (API REST)

API en **PHP 8.3** + **MySQL 8** para la plataforma de gestión de incidencias del Reto Kodex (Equipo 1).
Sin framework: un front controller, un router propio y capas separadas (HTTP → controladores → servicios/dominio → repositorios PDO), para que cada pieza sea fácil de explicar en la defensa.

| | |
|---|---|
| Contrato de API | [`docs/openapi.yaml`](docs/openapi.yaml) · Swagger UI en `http://localhost:8080/docs.html` |
| Modelo de datos | [`docs/modelo-datos.md`](docs/modelo-datos.md) · [`database/schema.sql`](database/schema.sql) |
| Autenticación | JWT HS256 (`Authorization: Bearer <token>`), logout con revocación, bloqueo por intentos fallidos |

## Ejecutar

### Opción A — Docker (recomendado para evaluación)

```bash
cd backend
docker compose up -d --build
docker compose exec api php bin/migrate.php   # crea las tablas (borra si existían)
docker compose exec api php bin/seed.php      # usuarios de demostración
curl http://localhost:8080/api/health
```

### Opción B — Local (XAMPP / Laragon / PHP instalado)

Requisitos: PHP 8.3 con `pdo_mysql`, Composer, MySQL 8 o MariaDB 10.6+.

```bash
cd backend
composer install
cp .env.example .env        # ajustar DB_* y poner un JWT_SECRET largo
php bin/migrate.php
php bin/seed.php
composer serve              # http://localhost:8080
```

## Usuarios de demostración

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@kodex.pe | Admin123! |
| Agente | agente@kodex.pe / agente2@kodex.pe | Agente123! |
| Solicitante | usuario@kodex.pe / usuario2@kodex.pe | Usuario123! |

## Pruebas

Usan una base separada **`kodex_test`** (se recrea en cada prueba; el código se niega a correr contra una BD que no termine en `_test`).

```bash
composer test                                  # local
docker compose exec api composer test          # Docker
```

Cubren: login, credenciales inválidas, validación, JSON mal formado, bloqueo por intentos, cuenta desactivada, token manipulado / con otro secreto / expirado / revocado, permisos por rol en el servidor, auditoría de login y creación de usuarios, CORS y la máquina de estados de incidencias.

## Endpoints (día 1)

| Método | Ruta | Acceso |
|---|---|---|
| GET | `/api/health` | público |
| POST | `/api/auth/login` | público |
| GET | `/api/auth/me` | autenticado |
| POST | `/api/auth/logout` | autenticado |
| GET | `/api/catalogs` | autenticado |
| GET | `/api/users/agents` | admin, agent |
| GET / POST | `/api/users` | admin |

Incidencias, búsqueda, dashboard y auditoría ya están **definidos en el contrato** (`x-status: planned`) para que el frontend pueda integrarse desde el día 2.

## Para el frontend

```js
// Login
const res = await fetch('http://localhost:8080/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email, password }),
});
const body = await res.json();
if (!res.ok) {
  // body.error.message → mostrar; body.error.details?.fields → errores por campo
} else {
  sessionStorage.setItem('token', body.data.token);
  // body.data.user.role → 'admin' | 'agent' | 'requester'
}

// Peticiones autenticadas
fetch('http://localhost:8080/api/auth/me', {
  headers: { Authorization: `Bearer ${sessionStorage.getItem('token')}` },
});
// 401 en cualquier ruta → token vencido/revocado → redirigir al login
```

Si el front corre en otro puerto/origen, agrégalo a `CORS_ORIGINS` en `.env`.

## Estructura

```
backend/
├── public/            index.php (front controller), docs.html (Swagger), .htaccess
├── src/
│   ├── App.php        composición, rutas, autenticación/autorización, errores, CORS
│   ├── Config.php     lectura de .env / variables de entorno
│   ├── Http/          Request, Response, Router, Validator, HttpException
│   ├── Auth/          TokenService (JWT), AuthService (login/logout), AuthUser
│   ├── Domain/        Role, Priority, IncidentStatus (transiciones)
│   ├── Controllers/   Auth, Catalog, User
│   ├── Repositories/  acceso a datos con PDO y sentencias preparadas
│   └── Database/      conexión PDO
├── database/          schema.sql
├── bin/               migrate.php, seed.php
├── tests/             Unit/ y Api/ (PHPUnit)
└── docs/              openapi.yaml, modelo-datos.md
```

## Decisiones de seguridad

- **Seguro por defecto**: toda ruta exige token salvo que se declare `public: true`; los roles se verifican en el router antes del controlador.
- El token solo lleva el id del usuario; **rol y estado se leen de la BD en cada petición**, así desactivar a alguien o cambiarle el rol tiene efecto inmediato.
- Contraseñas con `password_hash` (bcrypt) y re-hash automático; nunca se devuelve `password_hash`.
- Correo inexistente y contraseña incorrecta dan el mismo mensaje y tiempo similar (no se revela qué correos existen).
- Todas las consultas usan sentencias preparadas (`ATTR_EMULATE_PREPARES = false`).
- Errores internos se registran en el log y al cliente solo le llega un mensaje genérico (salvo `APP_DEBUG=true`).

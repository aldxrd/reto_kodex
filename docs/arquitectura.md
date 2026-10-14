# Arquitectura — Kodex Incidencias (Equipo 1)

Guía para el **control del día 1**: el responsable revisa que entendemos el alcance y que ambos podemos explicar la arquitectura. Cada uno debe poder explicar **las dos partes**, no solo la suya.

## 1. Alcance en una frase

Plataforma web donde una empresa **registra incidencias, las asigna a responsables, controla su atención y consulta indicadores**, con tres roles (administrador, agente, solicitante), reglas de negocio y permisos validados **en el backend**, búsqueda con filtros/paginación, dashboard y auditoría de quién cambió qué y cuándo.

## 2. Vista general

```
 Navegador                    Frontend (PHP + Bootstrap)                 Backend (API REST PHP)            MySQL
 ─────────                    ──────────────────────────                 ──────────────────────            ─────
 HTML/CSS/JS   ── cookie ──►  public/index.php                           public/index.php
 (sin token)   ◄── HTML ───    · sesión PHP (guarda el JWT)   ── HTTP ──►  · Router (rutas + roles)
                               · CSRF en formularios          JSON +      · Auth: JWT → usuario (BD)
                               · menú/rutas por rol (UX)      Bearer      · Controladores
                               · ApiClient (cURL)             ◄── JSON ─  · Dominio (estados, roles)  ── PDO ──►  7 tablas
                                                                           · Repositorios (SQL preparado)
```

- **Dos aplicaciones separadas** que se comunican solo por el **contrato de API** (`backend/docs/openapi.yaml`). Cada una se puede desarrollar y probar por su lado.
- **Puertos en local:** front `:8000`, API `:8080`.

## 3. Backend

**Stack:** PHP 8.3 sin framework, MySQL 8 (o MariaDB), `firebase/php-jwt`, PHPUnit.
*Por qué sin framework:* encaja con el stack del front (PHP), no hay "magia" que explicar en la defensa y cada pieza es corta y legible.

**Capas** (`backend/src/`):

| Capa | Archivos | Responsabilidad |
|---|---|---|
| HTTP | `Http/Request`, `Response`, `Router`, `Validator`, `HttpException` | Leer la petición, enrutar, validar entrada, responder JSON con formato único |
| Auth | `Auth/TokenService`, `AuthService`, `AuthUser` | Emitir/verificar JWT, login, logout, bloqueo por intentos |
| Controladores | `Controllers/*` | Orquestar: validar → llamar repositorio → auditar → responder |
| Dominio | `Domain/Role`, `Priority`, `IncidentStatus` | Reglas que no dependen de la BD: roles, prioridades, **flujo de estados** |
| Datos | `Repositories/*`, `Database/Connection` | SQL con sentencias preparadas (PDO) |

**Flujo de una petición:** `index.php` → `Router` busca la ruta → si no es pública, `AuthService` valida el token y **carga el usuario desde la BD** → se comprueba el rol de la ruta (403 si no corresponde) → controlador → JSON.

**Formato de respuestas:** éxito `{ "data": ..., "meta": ... }`; error `{ "error": { "code", "message", "details" } }`. El `message` va en español y el front lo muestra tal cual.

## 4. Modelo de datos

Diagrama completo: [`backend/docs/modelo-datos.md`](../backend/docs/modelo-datos.md).

| Tabla | Para qué |
|---|---|
| `users` | Usuarios con rol (`admin`/`agent`/`requester`), área, estado y control de intentos de login |
| `categories` | Categorías con área responsable; se **desactivan**, no se borran |
| `sla_policies` | Tiempo objetivo por prioridad: Crítica 2 h · Alta 6 h · Media 12 h · Baja 24 h |
| `incidents` | La incidencia: código `INC-0001`, estado, prioridad, solicitante, responsable, `due_at` y marcas de tiempo |
| `incident_comments` | Comentarios del detalle |
| `audit_logs` | Quién cambió qué y cuándo: `{campo: {old, new}}` |
| `revoked_tokens` | Tokens cerrados con logout |

**Flujo de estados** (mismo del Figma, definido en `IncidentStatus` y expuesto en `GET /api/catalogs`):

```
open ──► assigned ──► in_progress ──► resolved ──► closed
 │                        ▲              │
 │                        └── reabrir ───┘
 └──► cancelled   (solo el solicitante, mientras está Abierta)
```

**Vencidas y tiempos:** al crear se calcula `due_at = created_at + SLA`. Una incidencia activa con `due_at` pasado está vencida. Tiempo de atención = `resolved_at − created_at`.

## 5. Autenticación y seguridad

| Medida | Dónde |
|---|---|
| Contraseñas con `password_hash` (bcrypt) | `UserRepository`, `AuthService` |
| JWT HS256 con expiración (8 h) y `jti` | `TokenService` |
| El token solo lleva el **id**; rol y estado se leen de la BD en cada petición → desactivar o cambiar rol tiene efecto inmediato | `AuthService::authenticate` |
| Logout real: el `jti` se guarda en `revoked_tokens` | `AuthService::logout` |
| Bloqueo tras 5 intentos fallidos (15 min) → 429 | `UserRepository::registerFailedLogin` |
| Mismo error para correo inexistente y contraseña incorrecta | `AuthService::login` |
| **Seguro por defecto:** toda ruta exige token salvo que se marque pública | `Router` / `App` |
| Roles verificados en el servidor antes del controlador | `App::handle` |
| SQL siempre con sentencias preparadas | Repositorios |
| Errores internos no se filtran al cliente | `App::handle` |
| **Front:** el JWT vive en la sesión PHP, nunca en el navegador; cookie `HttpOnly` + `SameSite`; CSRF en formularios; `htmlspecialchars` en toda salida | `frontend/` |

## 6. Frontend

**Stack:** PHP 8.3 + HTML + Bootstrap 5 + JS mínimo.

- `public/index.php`: front controller (sesión → login/logout → valida token con `/auth/me` → rol → página).
- `src/ApiClient.php`: único punto que habla con la API; convierte errores de la API en `ApiException`.
- `src/navigation.php` y `src/routes.php`: qué ve y a qué entra cada rol (solo UX; la API vuelve a validar).
- `views/`: layout con sidebar y topbar, páginas, partials. Estilos con los tokens del Figma.
- Si la API responde **401** → se cierra la sesión y vuelve al login con "Tu sesión expiró".

**Pantallas** (las 10 del Figma): Login, Dashboard, Incidencias, Nueva incidencia, Detalle, Asignadas a mí, Mis incidencias, Usuarios, Auditoría, Categorías.

## 7. Contrato de API y cómo se acordó

- Archivo: `backend/docs/openapi.yaml` (Swagger UI en `http://localhost:8080/docs.html`).
- Se revisó **pantalla por pantalla contra el Figma**: estados y botones, SLA, áreas, filtros, conteos de encabezados ("128 incidencias · 14 vencidas · 31 sin asignar"), pestañas, dashboard y auditoría.
- Cada ruta tiene `x-status`: `implemented` (ya funciona) o `planned` (contrato cerrado, se implementa en días 2–3). Así el front puede maquetar con la forma final de los datos.
- Las reglas (transiciones, etiquetas de botones, SLA) salen de `GET /api/catalogs` → **una sola fuente de verdad**.

## 8. Qué funciona hoy (día 1)

| Funciona con datos reales | Con datos de ejemplo (se reemplazan en días 2–3) |
|---|---|
| Login / logout / sesión expirada / bloqueo por intentos | Dashboard |
| Menú y rutas según rol | Listado, detalle, asignadas, mis incidencias |
| Usuarios con filtros Rol/Estado/Área | Auditoría |
| Categorías (área, activas, estado) y SLA | |
| Selects de Nueva incidencia, botones de acción del Detalle | |

**Pruebas:** 30 pruebas PHPUnit (autenticación, tokens manipulados/expirados/revocados, permisos por rol, catálogos, usuarios, categorías y flujo de estados).

## 9. Preguntas probables y respuestas cortas

**¿Por qué JWT y no solo sesiones PHP?** La API es independiente del front (se podría consumir desde otra app). El front guarda el JWT en su sesión de servidor, así combinamos ambos: API sin estado y token nunca expuesto al navegador.

**Si el token dura 8 h, ¿cómo cierran sesión o bloquean a alguien antes?** Logout guarda el `jti` en `revoked_tokens`. Y como el rol y `is_active` se leen de la BD en cada petición, desactivar a un usuario lo corta al instante.

**¿Dónde se validan los permisos?** En el backend, siempre. El front solo oculta opciones; si alguien llama a la API directamente sin permiso recibe 403. Hay pruebas que lo verifican.

**¿Cómo evitan que un solicitante vea incidencias ajenas?** El backend fuerza el filtro por solicitante y devuelve 404 (no 403) para no revelar que existen.

**¿Dónde están las reglas de estados?** En `Domain/IncidentStatus.php`, un solo lugar. El front las lee de `/api/catalogs`, no las duplica.

**¿Cómo calculan las vencidas?** `due_at = created_at + SLA de la prioridad`. Vencida = activa y `due_at < ahora`.

**¿Cómo registran la auditoría?** Tabla `audit_logs` con usuario, entidad, acción y `{campo: {old, new}}`. Solo se inserta, nunca se edita. Ya registra logins/logouts y creación de usuarios; en el día 2 se suma cada cambio de incidencia.

**¿Por qué no borran categorías?** Las incidencias las referencian; se desactivan para que no aparezcan en nuevas. La FK lo impide a nivel de BD.

**¿Cómo se ejecuta en otra máquina?** README raíz: crear BD → `composer install` → `.env` → `migrate` → `seed` → levantar API y front. También hay `docker-compose.yml` para el backend.

**¿Qué falta?** Día 2: CRUD real de incidencias, asignación, cambios de estado con permisos, comentarios y auditoría de cada cambio. Día 3: búsqueda con filtros y paginación, dashboard e historial.

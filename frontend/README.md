# Kodex Incidencias — Frontend

Interfaz web en **PHP 8.3 + HTML + Bootstrap 5.3**, sin framework, que consume la API REST de [`backend/`](../backend).
El diseño sigue el Figma del equipo (tema oscuro, sidebar por rol).

## Ejecutar

Requisitos: PHP 8.1+ con la extensión **cURL** (en XAMPP viene habilitada: `extension=curl` en `php.ini`).
El backend debe estar levantado primero (ver [`backend/README.md`](../backend/README.md)) en `http://localhost:8080`.

### Opción A — Servidor embebido de PHP (recomendado)

```bash
cd frontend
php -S localhost:8000 -t public public/index.php
```

Abrir `http://localhost:8000`.

### Opción B — XAMPP (Apache)

1. Copiar o clonar el repo dentro de `htdocs/`.
2. Abrir `http://localhost/reto_kodex/frontend/public/` (el `.htaccess` redirige todas las rutas a `index.php`; `mod_rewrite` debe estar activo).

### Configuración

Por defecto usa `API_URL=http://localhost:8080/api`. Para cambiarla, copiar `.env.example` a `.env` y editarla
(o definir la variable de entorno `API_URL`).

## Usuarios de demostración

Son los del seed del backend:

| Rol | Nombre | Correo | Contraseña | Entra a |
|---|---|---|---|---|
| Administrador | Lucía Ramos | admin@kodex.pe | Admin123! | Dashboard (ve todo el menú) |
| Agente | Jorge Paredes | agente@kodex.pe | Agente123! | Dashboard (sin Administración) |
| Solicitante | Carlos Medina | usuario@kodex.pe | Usuario123! | Mis incidencias |

Los nombres coinciden con los datos de ejemplo, así "Asignadas a mí" y "Mis incidencias" muestran filas al entrar con esos usuarios.

## Arquitectura

```
frontend/
├── public/              Única carpeta expuesta por el servidor web
│   ├── index.php        Front controller: sesión → login/logout → validación de token → rol → página
│   ├── .htaccess        Reescritura para Apache
│   └── assets/          app.css (tokens del Figma sobre Bootstrap) y app.js (UI mínima)
├── src/
│   ├── ApiClient.php    Cliente HTTP (cURL) + ApiException con el formato de error de la API
│   ├── Auth.php         Login, sesión, /auth/me, logout con revocación
│   ├── routes.php       Rutas protegidas con los roles permitidos
│   ├── navigation.php   Menú lateral y qué rol ve cada ítem
│   ├── helpers.php      Escape HTML, URLs, CSRF, vistas, etiquetas de estado/prioridad
│   └── SampleData.php   Datos de ejemplo TEMPORALES (ver abajo)
├── views/               Plantillas: layout, partials (sidebar, topbar, tabla), auth, pages, errors
└── config.php           API_URL y zona horaria
```

### Flujo de una petición

1. `index.php` sirve los archivos estáticos (con `php -S`) e inicia la sesión.
2. `/login` y `/logout` son públicas. El resto exige sesión.
3. En cada página protegida se llama a `GET /api/auth/me` para validar el token y refrescar el rol
   (el backend lee rol y estado desde la BD, así un cambio de rol se refleja de inmediato).
4. Se comprueba el rol de la ruta (si no corresponde → 403) y se renderiza la vista.
5. Si la API responde **401** (token vencido o revocado), se cierra la sesión y se vuelve al login con un aviso.
   Si la API no responde, se muestra un error claro (503) en vez de una página rota.

## Decisiones técnicas

| Decisión | Por qué |
|---|---|
| **El JWT se guarda en la sesión PHP, no en el navegador** | La API se llama desde el servidor. Al navegador solo llega la cookie de sesión `HttpOnly` + `SameSite=Lax`, así un script inyectado (XSS) no puede robar el token. Tampoco hace falta configurar CORS. |
| **CSRF en todos los formularios POST** | Login y logout llevan un token por sesión verificado con `hash_equals`. |
| **`session_regenerate_id` al iniciar y cerrar sesión** | Evita fijación de sesión. |
| **Logout revoca el token en la API** | `POST /api/auth/logout`: el token deja de servir aunque alguien lo hubiera copiado. |
| **Todo dato se imprime con `e()`** | `htmlspecialchars` para prevenir XSS. |
| **Ocultar menú por rol = solo UX** | El frontend oculta lo que el rol no puede usar y responde 403, pero **la autorización real la hace la API**. |
| **Estados con etiqueta y color centralizados** (`status_meta`) | Un solo lugar para cambiar textos/colores; usa los mismos estados que `GET /api/catalogs` y el Figma (incluye `assigned` y `cancelled`). |
| **Transiciones de estado desde la API** | El detalle muestra los botones según `GET /api/catalogs` → `statuses[].actions`, no reglas duplicadas en el front. |

## Qué es real y qué es de ejemplo (día 1)

Cada pantalla indica su origen con un aviso visible.

| Pantalla | Datos | Endpoint |
|---|---|---|
| Login / logout | **Reales** | `POST /api/auth/login`, `GET /api/auth/me`, `POST /api/auth/logout` |
| Usuarios (con filtros Rol/Estado/Área) | **Reales** | `GET /api/users` |
| Categorías (área, activas, estado) y SLA | **Reales** | `GET /api/categories`, `GET /api/catalogs` |
| Nueva incidencia (selects) | **Reales** | `GET /api/catalogs`, `GET /api/users/agents` (envío: día 2) |
| Detalle (botones de acción: "Iniciar atención", "Resolver", "Reabrir"…) | **Reales** | `GET /api/catalogs` → `statuses[].actions` |
| Dashboard, Incidencias, Detalle, Asignadas, Mis incidencias, Auditoría | Ejemplo | `/api/incidents`, `/api/dashboard/summary`, `/api/audit` (días 2–3) |

Los datos de ejemplo viven solo en `src/SampleData.php` y **se eliminan** a medida que el backend publica cada endpoint.

## Pendiente

- **Día 2:** crear, listar, ver y actualizar incidencias contra la API; comentarios; cambio de estado; asignación; manejo de 403 al intentar modificar una incidencia ajena.
- **Día 3:** filtros combinados, paginación y ordenamiento desde la API; dashboard y auditoría reales; pruebas.

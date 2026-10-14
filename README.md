# Kodex Incidencias — Equipo 1

Plataforma de gestión de incidencias (Reto Kodex, 3 equipos · 4 días).
Diseño: Figma "Kodex Incidencias - Equipo 1".

| Carpeta | Qué es | Stack |
|---|---|---|
| [`backend/`](backend/README.md) | API REST + base de datos | PHP 8.3, MySQL 8, JWT |
| [`frontend/`](frontend/README.md) | Aplicación web | PHP 8.3 + HTML + Bootstrap 5 + JS |

Documentos clave:
- **Arquitectura y guía para el control:** [`docs/arquitectura.md`](docs/arquitectura.md)
- **Diagrama de datos:** [`backend/docs/modelo-datos.md`](backend/docs/modelo-datos.md)
- **Contrato de API (endpoints acordados):** [`backend/docs/openapi.yaml`](backend/docs/openapi.yaml) · Swagger en `http://localhost:8080/docs.html`

## Levantar la aplicación completa

Requisitos: PHP 8.3 (con `pdo_mysql` y `curl`), Composer y MySQL 8 / MariaDB 10.6+ (XAMPP o Laragon sirven).

**1. Base de datos** (una sola vez; en XAMPP desde phpMyAdmin → SQL):

```sql
CREATE DATABASE kodex CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE kodex_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kodex'@'localhost' IDENTIFIED BY 'kodex';
GRANT ALL ON kodex.* TO 'kodex'@'localhost';
GRANT ALL ON kodex_test.* TO 'kodex'@'localhost';
```

**2. Backend** — terminal 1:

```bash
cd backend
composer install
cp .env.example .env      # poner un JWT_SECRET largo: php -r "echo bin2hex(random_bytes(32));"
php bin/migrate.php       # crea las tablas
php bin/seed.php          # usuarios de demostración
composer serve            # API en http://localhost:8080  (prueba: /api/health)
```

**3. Frontend** — terminal 2:

```bash
cd frontend
cp .env.example .env      # API_URL=http://localhost:8080/api
php -S localhost:8000 -t public public/index.php
```

Abrir **http://localhost:8000** e iniciar sesión:

| Rol | Nombre | Correo | Contraseña |
|---|---|---|---|
| Administrador | Lucía Ramos | admin@kodex.pe | Admin123! |
| Agente | Jorge Paredes | agente@kodex.pe | Agente123! |
| Solicitante | Carlos Medina | usuario@kodex.pe | Usuario123! |

Pruebas del backend: `cd backend && composer test`.

## Avance por día

| Día | Frontend | Backend | Estado |
|---|---|---|---|
| 1 · Diseño y base técnica | Estructura, layout con sidebar por rol, navegación, login real, primeras pantallas | Modelo de datos, servidor, contrato de API, autenticación JWT | ✅ |
| 2 · Funcionalidades principales | Login, listado, formularios, detalle, validaciones de interfaz | CRUD, roles, permisos, validaciones de negocio | ⏳ |
| 3 · Integración y robustez | Integración completa, filtros, paginación, carga/error, dashboard | Auditoría, filtros, paginación, pruebas de reglas y permisos | ⏳ |
| 4 · Entrega y evaluación | Corrección de errores, Docker, README, demo login → cierre de incidencia | | ⏳ |

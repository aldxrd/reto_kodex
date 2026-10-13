-- =====================================================================
-- Kodex Incidencias - Esquema de base de datos
-- Motor: MySQL 8.0+ (compatible con MariaDB 10.6+)
-- Charset: utf8mb4
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS incident_comments;
DROP TABLE IF EXISTS incidents;
DROP TABLE IF EXISTS sla_policies;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS revoked_tokens;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Usuarios del sistema. Un usuario tiene exactamente un rol.
--   admin     : gestiona usuarios, categorías y cualquier incidencia
--   agent     : atiende incidencias asignadas (o las toma), cambia estado
--   requester : crea incidencias y consulta solo las suyas
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name             VARCHAR(120)  NOT NULL,
    email            VARCHAR(160)  NOT NULL,
    password_hash    VARCHAR(255)  NOT NULL,
    role             VARCHAR(20)   NOT NULL,
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,
    failed_logins    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until     DATETIME      NULL,
    last_login_at    DATETIME      NULL,
    created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    CONSTRAINT chk_users_role CHECK (role IN ('admin', 'agent', 'requester'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Tokens JWT revocados (logout). Se guarda el jti hasta que expira.
-- ---------------------------------------------------------------------
CREATE TABLE revoked_tokens (
    jti         CHAR(32)     NOT NULL,
    user_id     INT UNSIGNED NOT NULL,
    expires_at  DATETIME     NOT NULL,
    revoked_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (jti),
    KEY idx_revoked_expires (expires_at),
    CONSTRAINT fk_revoked_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Categorías de incidencia (Hardware, Software, Red, Accesos, ...)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(80)  NOT NULL,
    description  VARCHAR(255) NULL,
    is_active    TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Política de SLA por prioridad. Define cuándo vence una incidencia.
-- ---------------------------------------------------------------------
CREATE TABLE sla_policies (
    priority          VARCHAR(10)       NOT NULL,
    response_hours    SMALLINT UNSIGNED NOT NULL,
    resolution_hours  SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (priority),
    CONSTRAINT chk_sla_priority CHECK (priority IN ('low', 'medium', 'high', 'critical'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Incidencias
--   due_at            : fecha límite de resolución (created_at + SLA)
--   first_response_at : primera vez que pasa a in_progress (tiempo de respuesta)
--   resolved_at       : cuándo pasó a resolved (tiempo de atención)
-- Una incidencia está "vencida" si no está resuelta/cerrada/cancelada y now() > due_at,
-- o si se resolvió después de due_at.
-- ---------------------------------------------------------------------
CREATE TABLE incidents (
    id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code               VARCHAR(20)  NULL,
    title              VARCHAR(150) NOT NULL,
    description        TEXT         NOT NULL,
    category_id        INT UNSIGNED NOT NULL,
    priority           VARCHAR(10)  NOT NULL DEFAULT 'medium',
    status             VARCHAR(20)  NOT NULL DEFAULT 'open',
    requester_id       INT UNSIGNED NOT NULL,
    assignee_id        INT UNSIGNED NULL,
    due_at             DATETIME     NULL,
    first_response_at  DATETIME     NULL,
    resolved_at        DATETIME     NULL,
    closed_at          DATETIME     NULL,
    created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_incidents_code (code),
    KEY idx_incidents_status (status),
    KEY idx_incidents_priority (priority),
    KEY idx_incidents_category (category_id),
    KEY idx_incidents_requester (requester_id),
    KEY idx_incidents_assignee (assignee_id),
    KEY idx_incidents_created (created_at),
    KEY idx_incidents_due (due_at),
    CONSTRAINT fk_incidents_category  FOREIGN KEY (category_id)  REFERENCES categories (id),
    CONSTRAINT fk_incidents_requester FOREIGN KEY (requester_id) REFERENCES users (id),
    CONSTRAINT fk_incidents_assignee  FOREIGN KEY (assignee_id)  REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT chk_incidents_priority CHECK (priority IN ('low', 'medium', 'high', 'critical')),
    CONSTRAINT chk_incidents_status   CHECK (status IN ('open', 'in_progress', 'on_hold', 'resolved', 'closed', 'cancelled'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Comentarios / seguimiento de una incidencia
-- ---------------------------------------------------------------------
CREATE TABLE incident_comments (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    incident_id  INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NOT NULL,
    body         TEXT         NOT NULL,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_comments_incident (incident_id),
    CONSTRAINT fk_comments_incident FOREIGN KEY (incident_id) REFERENCES incidents (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_user     FOREIGN KEY (user_id)     REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Auditoría: quién cambió qué y cuándo.
--   changes guarda {"campo": {"old": x, "new": y}, ...}
-- Solo se inserta; nunca se actualiza ni borra desde la API.
-- ---------------------------------------------------------------------
CREATE TABLE audit_logs (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id      INT UNSIGNED    NULL,
    entity_type  VARCHAR(40)     NOT NULL,
    entity_id    INT UNSIGNED    NULL,
    action       VARCHAR(40)     NOT NULL,
    changes      JSON            NULL,
    ip_address   VARCHAR(45)     NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_user (user_id),
    KEY idx_audit_created (created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Datos de catálogo (no son datos de prueba: el sistema los necesita)
-- ---------------------------------------------------------------------
INSERT INTO sla_policies (priority, response_hours, resolution_hours) VALUES
    ('critical', 1, 4),
    ('high',     4, 24),
    ('medium',   8, 72),
    ('low',     24, 120);

INSERT INTO categories (name, description) VALUES
    ('Hardware', 'Equipos, periféricos e impresoras'),
    ('Software', 'Aplicaciones, licencias y errores de sistema'),
    ('Red',      'Conectividad, VPN, Wi-Fi'),
    ('Accesos',  'Cuentas, contraseñas y permisos'),
    ('Otros',    'Solicitudes que no encajan en otra categoría');

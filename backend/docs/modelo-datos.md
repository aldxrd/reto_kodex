# Modelo de datos

Esquema completo en [`database/schema.sql`](../database/schema.sql) (MySQL 8 / MariaDB 10.6+, utf8mb4, fechas en UTC).

```mermaid
erDiagram
    users ||--o{ incidents : "solicita (requester_id)"
    users |o--o{ incidents : "atiende (assignee_id)"
    categories ||--o{ incidents : clasifica
    sla_policies ||..o{ incidents : "define due_at (por prioridad)"
    incidents ||--o{ incident_comments : tiene
    users ||--o{ incident_comments : escribe
    users |o--o{ audit_logs : "realiza"
    users ||--o{ revoked_tokens : "cierra sesión"

    users {
        int id PK
        varchar name
        varchar email UK
        varchar password_hash "bcrypt"
        varchar role "admin | agent | requester"
        bool is_active
        smallint failed_logins
        datetime locked_until
        datetime last_login_at
    }
    categories {
        int id PK
        varchar name UK
        varchar description
        bool is_active
    }
    sla_policies {
        varchar priority PK "low | medium | high | critical"
        smallint response_hours
        smallint resolution_hours
    }
    incidents {
        int id PK
        varchar code UK "INC-000001"
        varchar title
        text description
        int category_id FK
        varchar priority
        varchar status "open | in_progress | on_hold | resolved | closed | cancelled"
        int requester_id FK
        int assignee_id FK "nullable"
        datetime due_at "created_at + SLA"
        datetime first_response_at
        datetime resolved_at
        datetime closed_at
    }
    incident_comments {
        int id PK
        int incident_id FK
        int user_id FK
        text body
    }
    audit_logs {
        bigint id PK
        int user_id FK "nullable"
        varchar entity_type "incident | user | auth"
        int entity_id
        varchar action "created | updated | status_changed | assigned | login | ..."
        json changes "{campo: {old, new}}"
        varchar ip_address
        datetime created_at
    }
    revoked_tokens {
        char jti PK
        int user_id FK
        datetime expires_at
    }
```

## Decisiones

- **Rol como columna con CHECK** (no tabla de roles): son 3 roles fijos definidos por el reto; un enum en PHP (`Kodex\Domain\Role`) y un `CHECK` en BD garantizan que no aparezcan valores inválidos.
- **Estados y transiciones en el dominio** (`Kodex\Domain\IncidentStatus`): la máquina de estados vive en un solo lugar del backend y se expone al front vía `GET /api/catalogs`, así el front no duplica reglas.

  ```
  open ──► in_progress ──► resolved ──► closed
   │          │   ▲            │
   │          ▼   │            └──► in_progress (reapertura)
   │        on_hold
   └──────────┴────────► cancelled
  ```
- **SLA por prioridad** (`sla_policies`): permite calcular `due_at` al crear y, con eso, las **vencidas** del dashboard. Los tiempos de atención salen de `first_response_at` y `resolved_at`.
- **Auditoría genérica** (`audit_logs`): una sola tabla para incidencias, usuarios y eventos de login; `changes` guarda el valor anterior y el nuevo de cada campo. Solo se inserta, nunca se edita.
- **Logout real**: el JWT lleva un `jti`; al cerrar sesión se guarda en `revoked_tokens` y deja de aceptarse aunque no haya expirado.
- **Bloqueo por fuerza bruta**: `failed_logins` y `locked_until` en `users`.
- **Índices** en las columnas por las que se filtra y ordena (estado, prioridad, categoría, responsable, solicitante, fechas) para que la búsqueda paginada del día 3 sea eficiente.

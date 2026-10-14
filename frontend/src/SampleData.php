<?php

declare(strict_types=1);

/**
 * DATOS DE EJEMPLO — solo para pantallas cuyo endpoint aún no existe
 * (/api/incidents, /api/dashboard, /api/audit se implementan el día 2–3).
 *
 * Las vistas que los usan muestran un aviso visible "Datos de ejemplo".
 * Cuando el endpoint exista, se reemplaza la llamada a esta clase por $api->get(...)
 * y este archivo se elimina. Nunca se presentan como datos reales.
 */
final class SampleData
{
    /** @return list<array<string, mixed>> */
    public static function incidents(): array
    {
        return [
            ['id' => 128, 'code' => 'INC-0128', 'title' => 'Caída del servidor de facturación', 'category' => 'Infraestructura', 'priority' => 'critical', 'status' => 'in_progress', 'requester' => 'Carlos Medina', 'assignee' => 'Jorge Paredes', 'created_at' => '2026-10-13 19:55:00', 'due_at' => '2026-10-13 21:55:00', 'overdue' => true],
            ['id' => 127, 'code' => 'INC-0127', 'title' => 'No se puede acceder al correo corporativo', 'category' => 'Accesos', 'priority' => 'high', 'status' => 'assigned', 'requester' => 'Ana Torres', 'assignee' => 'María Vega', 'created_at' => '2026-10-14 13:10:00', 'due_at' => '2026-10-14 19:10:00', 'overdue' => false],
            ['id' => 126, 'code' => 'INC-0126', 'title' => 'Impresora del área contable sin conexión', 'category' => 'Hardware', 'priority' => 'medium', 'status' => 'open', 'requester' => 'Carlos Medina', 'assignee' => null, 'created_at' => '2026-10-14 12:30:00', 'due_at' => '2026-10-15 00:30:00', 'overdue' => false],
            ['id' => 125, 'code' => 'INC-0125', 'title' => 'Solicitud de acceso a la VPN', 'category' => 'Accesos', 'priority' => 'low', 'status' => 'assigned', 'requester' => 'Ana Torres', 'assignee' => 'Jorge Paredes', 'created_at' => '2026-10-14 09:00:00', 'due_at' => '2026-10-15 09:00:00', 'overdue' => false],
            ['id' => 124, 'code' => 'INC-0124', 'title' => 'Error al generar reporte mensual de ventas', 'category' => 'Software', 'priority' => 'high', 'status' => 'in_progress', 'requester' => 'Ana Torres', 'assignee' => 'María Vega', 'created_at' => '2026-10-12 15:00:00', 'due_at' => '2026-10-12 21:00:00', 'overdue' => true],
            ['id' => 123, 'code' => 'INC-0123', 'title' => 'Monitor parpadea en recepción', 'category' => 'Hardware', 'priority' => 'low', 'status' => 'resolved', 'requester' => 'Carlos Medina', 'assignee' => 'Jorge Paredes', 'created_at' => '2026-10-11 14:00:00', 'due_at' => '2026-10-12 14:00:00', 'overdue' => false],
            ['id' => 122, 'code' => 'INC-0122', 'title' => 'Actualizar licencias de antivirus', 'category' => 'Seguridad', 'priority' => 'medium', 'status' => 'closed', 'requester' => 'Ana Torres', 'assignee' => 'María Vega', 'created_at' => '2026-10-09 16:00:00', 'due_at' => '2026-10-10 04:00:00', 'overdue' => false],
            ['id' => 121, 'code' => 'INC-0121', 'title' => 'Teclado duplicado por error', 'category' => 'Hardware', 'priority' => 'low', 'status' => 'cancelled', 'requester' => 'Carlos Medina', 'assignee' => null, 'created_at' => '2026-10-08 10:00:00', 'due_at' => '2026-10-09 10:00:00', 'overdue' => false],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function incident(int $id): ?array
    {
        foreach (self::incidents() as $incident) {
            if ($incident['id'] === $id) {
                return $incident + [
                    'description' => 'Desde las 14:40 el sistema no responde. Al emitir comprobantes aparece "Error 503 Service Unavailable". Afecta a todas las cajas de la sede central.',
                    'comments' => [
                        ['author' => $incident['requester'], 'role' => 'requester', 'role_label' => 'Solicitante', 'body' => 'Adjunto la captura del error. ¿Hay un tiempo estimado?', 'created_at' => '2026-10-13 20:02:00'],
                        ['author' => 'Lucía Ramos', 'role' => 'admin', 'role_label' => 'Administrador', 'body' => 'Asignada a soporte por ser de prioridad alta.', 'created_at' => '2026-10-13 20:10:00'],
                    ],
                    'history' => [
                        ['who' => $incident['assignee'] ?? 'Lucía Ramos', 'what' => 'cambió el estado', 'from' => 'assigned', 'to' => $incident['status'], 'at' => '2026-10-13 20:42:00'],
                        ['who' => 'Lucía Ramos', 'what' => 'asignó a ' . ($incident['assignee'] ?? '—'), 'from' => null, 'to' => null, 'at' => '2026-10-13 20:10:00'],
                        ['who' => $incident['requester'], 'what' => 'creó la incidencia', 'from' => null, 'to' => null, 'at' => $incident['created_at']],
                    ],
                ];
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public static function dashboard(): array
    {
        return [
            'open' => 46, 'resolved' => 82, 'overdue' => 14, 'avg_resolution' => '6 h 20 min',
            'by_status' => ['open' => 31, 'assigned' => 15, 'in_progress' => 19, 'resolved' => 82, 'closed' => 64],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function audit(): array
    {
        return [
            ['at' => '2026-10-13 20:42:00', 'user' => 'Jorge Paredes', 'action' => 'Cambio de estado', 'record' => 'INC-0128', 'field' => 'Estado', 'from' => 'Asignada', 'to' => 'En progreso'],
            ['at' => '2026-10-13 20:10:00', 'user' => 'Lucía Ramos', 'action' => 'Asignación', 'record' => 'INC-0128', 'field' => 'Responsable', 'from' => 'Sin asignar', 'to' => 'Jorge Paredes'],
            ['at' => '2026-10-13 19:55:00', 'user' => 'Carlos Medina', 'action' => 'Creación', 'record' => 'INC-0128', 'field' => 'Incidencia creada', 'from' => null, 'to' => 'Crítica · Software'],
            ['at' => '2026-10-13 17:31:00', 'user' => 'María Vega', 'action' => 'Edición', 'record' => 'INC-0127', 'field' => 'Prioridad', 'from' => 'Media', 'to' => 'Alta'],
            ['at' => '2026-10-13 16:02:00', 'user' => 'Jorge Paredes', 'action' => 'Cambio de estado', 'record' => 'INC-0123', 'field' => 'Estado', 'from' => 'En progreso', 'to' => 'Resuelta'],
        ];
    }
}

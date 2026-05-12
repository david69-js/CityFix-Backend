# Guía de Integración Frontend - Módulo 3: Trabajadores

Esta guía detalla los endpoints para la "Bandeja de Tareas" y la gestión de estados de reportes.  
Verificada contra el backend corriendo en `http://localhost:8888/api`.

**Auth:** JWT (Bearer Token). El trabajador obtiene el token via `POST /api/auth/login`.

---

## 1. Bandeja de Tareas (Mi Bandeja)

- **URL:** `GET /api/my-assignments`
- **Auth:** Bearer Token
- **Descripción:** Retorna las asignaciones del trabajador autenticado, ordenadas por `created_at` descendente.

### Ejemplo de Respuesta Real (2026-05-11):
```json
[
  {
    "id": 5,
    "issue_id": 28,
    "worker_id": 14,
    "status_id": 1,
    "notes": "Revisar bache urgente",
    "assigned_at": "2026-05-11 10:00:00",
    "created_at": "2026-05-12T01:30:33.000000Z",
    "updated_at": "2026-05-12T01:30:33.000000Z",
    "issue": {
      "id": 28,
      "title": "Bache de prueba HTTP",
      "description": "Reporte ciudadano",
      "location": "Av. Prueba 123",
      "latitude": 19.43,
      "longitude": -99.13,
      "status_id": 2,
      "is_hidden": false,
      "created_at": "2026-05-12T01:13:09.000000Z",
      "updated_at": "2026-05-12T01:13:42.000000Z",
      "category": {
        "id": 1,
        "name": "Infraestructura",
        "icon": null,
        "parent_id": null
      },
      "status": {
        "id": 2,
        "name": "En proceso",
        "color": "#3b82f6",
        "sort_order": 2
      }
    },
    "status": {
      "id": 1,
      "name": "Asignado"
    }
  }
]
```

### Tips para el Frontend:
- `issue.title` e `issue.location` para la tarjeta principal.
- `issue.status.name` = estado **del reporte** (Pendiente / En proceso / Resuelto).
- `status.name` = estado **de la asignación** (Asignado / En Progreso / Completado).
- `assigned_at` = fecha ISO en que se asignó la tarea.

---

## 2. Actualización de Estado del Reporte

- **URL:** `PATCH /api/issues/{issue_id}/status`
- **Auth:** Bearer Token
- **Cuerpo:**
```json
{
  "status_id": 2
}
```
- Genera automáticamente un registro en `issue_history` con el usuario autenticado como `changed_by`.

### Respuesta Exitosa (200 OK):
```json
{
  "id": 28,
  "status_id": 2,
  "status": {
    "id": 2,
    "name": "En proceso",
    "color": "#3b82f6",
    "sort_order": 2
  },
  "history": [
    {
      "id": 22,
      "issue_id": 28,
      "status_id": 2,
      "changed_by": 19,
      "changed_at": "2026-05-12T01:13:24.000000Z"
    }
  ]
}
```

---

## 3. Estados Disponibles (Referencia)

### Issue Status (estados del reporte)
Tabla: `issue_status` — vía `GET /api/issue-statuses`

| ID | Nombre | Color | Descripción |
|----|--------|-------|-------------|
| 1 | **Pendiente** | `#f59e0b` | Reportado por ciudadano, sin acción |
| 2 | **En proceso** | `#3b82f6` | Trabajador asignado y trabajando |
| 3 | **Resuelto** | `#10b981` | Problema solucionado |

### Assignment Status (estados de la asignación)
Tabla: `assignment_status`

| ID | Nombre |
|----|--------|
| 1 | Asignado |
| 2 | En Progreso |
| 3 | Completado |

> [!NOTE]
> Son dos tablas distintas. `issue_status` gestiona el ciclo de vida del reporte.  
> `assignment_status` gestiona el ciclo de vida de la asignación al trabajador.

---

## 4. Historial de Cambios (History Logs)

- **URL:** `GET /api/issues/{issue_id}/history-logs`
- **Auth:** Bearer Token
- Muestra la línea de tiempo de cambios de estado, con duración entre cada cambio.

---

## 5. Flujo Sugerido

1. **Login** → `POST /api/auth/login` → obtén el JWT.
2. **Dashboard** → `GET /api/my-assignments` → lista de tareas.
3. **Seleccionar tarea** → mostrar dropdown con [1=Pendiente, 2=En proceso, 3=Resuelto].
4. **Cambiar estado** → `PATCH /api/issues/{id}/status` con `{"status_id": N}`.
5. **Refresh** → actualizar la UI con la respuesta del servidor.

---

## 6. Headers Requeridos

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token_jwt}
```

---

## Historial de Cambios (Docs)

| Fecha | Cambio |
|-------|--------|
| 2026-05-11 | Corregidos nombres de status (eran: Abierto, En Progreso, Cerrado → ahora: Pendiente, En proceso) |
| 2026-05-11 | Corregido: 4 statuses → 3 statuses reales |
| 2026-05-11 | Agregada tabla `assignment_status` (estados de asignación) |
| 2026-05-11 | Reemplazado Sanctum por JWT |
| 2026-05-11 | Ejemplo de respuesta actualizado con estructura real |
| 2026-05-11 | Agregada sección de History Logs |

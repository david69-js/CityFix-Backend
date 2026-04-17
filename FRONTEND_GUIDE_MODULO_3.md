# Guía de Integración Frontend - Módulo 3: Trabajadores

Esta guía detalla los nuevos endpoints disponibles en el backend para la implementación de la "Bandeja de Tareas" y la gestión de estados de reportes.

---

## 1. Bandeja de Tareas (Mi Bandeja)

Permite al trabajador logueado ver todas las tareas que tiene asignadas.

- **URL:** `/api/my-assignments`
- **Método:** `GET`
- **Autenticación:** Requerido (Bearer Token)
- **Descripción:** Retorna un array de asignaciones con los datos del reporte relacionados.

### Ejemplo de Respuesta:
```json
[
  {
    "id": 5,
    "issue_id": 10,
    "worker_id": 3,
    "status_id": 1,
    "notes": "Revisar bache en calle principal",
    "created_at": "2024-04-17T12:00:00.000000Z",
    "issue": {
      "id": 10,
      "title": "Bache Profundo",
      "description": "Hay un bache que daña neumáticos...",
      "location": "Calle 50 con 25",
      "status_id": 1,
      "category": {
        "id": 2,
        "name": "Infraestructura"
      },
      "status": {
        "id": 1,
        "name": "Abierto"
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
- Usa `issue.title` y `issue.location` para la lista principal.
- Muestra el estado del reporte (`issue.status.name`) y el de la asignación (`status.name`).

---

## 2. Actualización de Estado del Reporte

Permite cambiar el estado de un reporte. Este cambio genera automáticamente un registro en el historial.

- **URL:** `/api/issues/{issue_id}/status`
- **Método:** `PATCH`
- **Autenticación:** Requerido (Bearer Token)
- **Cuerpo de la Petición:**
```json
{
  "status_id": 2
}
```

### Estados Disponibles (Referencia):
| ID | Nombre | Descripción |
|---|---|---|
| 1 | Abierto | Reportado por ciudadano |
| 2 | En Progreso | Trabajador asignado y trabajando |
| 3 | Resuelto | Problema solucionado |
| 4 | Cerrado | Verificado y finalizado |

### Respuesta Exitosa (200 OK):
Retorna el objeto `issue` actualizado con su relación de historial cargada.

```json
{
  "id": 10,
  "status_id": 2,
  "status": {
    "id": 2,
    "name": "En Progreso"
  },
  "history": [
    {
      "id": 1,
      "issue_id": 10,
      "status_id": 2,
      "changed_by": 3,
      "changed_at": "2024-04-17T15:30:00.000000Z"
    }
  ]
}
```

---

## 3. Flujo Sugerido

1. **Login**: El trabajador inicia sesión y obtiene su token Sanctum.
2. **Dashboard**: Al entrar al módulo de "Mis Tareas", llamar a `GET /api/my-assignments`.
3. **Acción**: Al hacer clic en una tarea, mostrar un selector (dropdown) con los estados posibles.
4. **Guardar**: Al cambiar el estado, enviar el `PATCH`. Refrescar la UI con la respuesta del servidor.

---

> [!IMPORTANT]
> Asegúrate de enviar los headers `Accept: application/json` y `Content-Type: application/json` en todas las peticiones.

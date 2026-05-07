# Guía de Integración Frontend - Módulo 5: Auditoría, Historial y Datos de Prueba

Este módulo introduce el sistema de seguimiento de cambios (Auditoría), un visor de historial avanzado con cálculos de tiempo y una base de datos poblada para pruebas masivas.

---

## 1. Visor de Historial Avanzado

Permite visualizar la evolución de un reporte, quién hizo el cambio y cuánto tiempo tomó cada etapa. Especialmente útil para métricas de eficiencia.

- **URL:** `/api/issues/{issue_id}/history-logs`
- **Método:** `GET`
- **Respuesta Exitosa:**
```json
{
  "issue_id": 1,
  "created_at": "2026-04-18 10:00:00",
  "total_resolution_time": "2 days 5 hours 30 minutes",
  "history": [
    {
      "status": "En proceso",
      "changed_by": "Juan Trabajador",
      "changed_at": "2026-04-19 09:00:00",
      "time_since_last_change": "23 hours"
    },
    {
      "status": "Resuelto",
      "changed_by": "Admin Sistema",
      "changed_at": "2026-04-20 15:30:00",
      "time_since_last_change": "1 day 6 hours"
    }
  ]
}
```
> [!TIP]
> `total_resolution_time` solo aparecerá si el reporte ha llegado al estado **"Resuelto"**. Si no, será `null`.

---

## 2. Comentarios y Social Feed

Se ha optimizado la forma de obtener comentarios para que sea más específica y contenga toda la información necesaria para el feed.

### Obtener comentarios de un reporte
- **URL:** `/api/issues/{issue_id}/comments`
- **Método:** `GET`
- **Cambio:** Ahora devuelve los comentarios filtrados por reporte e incluye el objeto `user` con: `id`, `first_name`, `last_name`, `email` y `avatar`.

### Contador de comentarios
- Al consultar reportes (`/api/issues` o `/api/issues/{id}`), ahora se incluye automáticamente el campo `comments_count`. Puedes usarlo para mostrar el ícono de "Globo de texto" con el número en los listados.

---

## 3. Categorías con Iconos

Las categorías base ahora tienen un slug de icono (basado en FontAwesome o similar) para ser renderizados en el front.

- **Entidad:** `Category`
- **Campo:** `icon` (ejemplo: "road", "tint", "lightbulb", "trash").

---

## 4. Datos de Prueba (Seeders)

Para facilitar el desarrollo, se pueden generar datos masivos de prueba. El backend ahora cuenta con:
- **Usuarios**: 10 ciudadanos y 5 trabajadores.
- **Reportes**: 25 incidentes con GPS (zona CDMX), fotos reales de Unsplash y votos aleatorios.
- **Comentarios**: Feed social lleno con 2-8 comentarios por reporte.

> [!NOTE]
> Puedes pedirle al equipo de backend que reinicie la base de datos con `php artisan migrate:fresh --seed` para ver estos cambios.

---

## 5. Auditoría (Transparente)

Todo cambio realizado en los modelos de **Issue**, **Comment** y **Assignment** está siendo auditado en el servidor. No requiere integración extra por parte del frontend, pero es bueno saber que existe una trazabilidad completa de quién borró o editó qué cosa.

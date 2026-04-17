# Guía de Integración Frontend - Módulo 4: Notificaciones

Este módulo implementa el sistema de notificaciones automáticas y campañas masivas.

---

## 1. Registro de Token Push (FCM)

Para que el usuario reciba notificaciones push, el frontend debe enviar el token de Firebase al backend una vez que el usuario inicie sesión.

- **URL:** `/api/users/fcm-token`
- **Método:** `POST`
- **Cuerpo:**
```json
{
  "fcm_token": "TOKEN_DE_FIREBASE_AQUI"
}
```

---

## 2. Listado de Notificaciones

Obtener las notificaciones del usuario autenticado (ordenadas de la más reciente a la más antigua).

- **URL:** `/api/notifications`
- **Método:** `GET`
- **Respuesta:**
```json
[
  {
    "id": 1,
    "type": "issue_created",
    "title": "Reporte Nuevo",
    "message": "Se ha creado un reporte: Bache en la calle",
    "related_id": 10,
    "is_read": false,
    "created_at": "..."
  }
]
```

---

## 3. Marcar como Leída

- **URL:** `/api/notifications/{id}/read`
- **Método:** `PATCH`

---

## 4. Campañas Generales (Solo Admin)

Permite enviar una notificación a todos los usuarios del sistema.

- **URL:** `/api/notifications/campaign`
- **Método:** `POST`
- **Cuerpo:**
```json
{
  "title": "Aviso Importante",
  "message": "Habrá mantenimiento en la zona centro mañana."
}
```

---

## 5. Eventos Automáticos (Backend)

El backend disparará notificaciones automáticamente en los siguientes casos:
1. **Nuevo Reporte**: Se notifica a todos los administradores.
2. **Cambio de Estado**: Se notifica al ciudadano que creó el reporte.
3. **Nuevo Comentario**: Se notifica al dueño del reporte y a todos los que hayan comentado previamente en él.

---

> [!NOTE]
> Las notificaciones se guardan en la base de datos y se pueden consultar vía API. La integración con Firebase requiere configurar las credenciales en el archivo `.env` del servidor.

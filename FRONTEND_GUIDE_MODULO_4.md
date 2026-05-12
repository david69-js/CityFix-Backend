# Guía de Integración Frontend - Módulo 4: Notificaciones

Este módulo implementa el sistema de notificaciones automáticas y campañas masivas.  
Verificada contra backend en `http://localhost:8888/api` (2026-05-11).

**Auth:** JWT (Bearer Token) para endpoints protegidos.

---

## 1. Registro de Token Push (FCM)

Para recibir notificaciones push, enviar el token de Firebase al backend post-login.

- **URL:** `POST /api/users/fcm-token`
- **Auth:** Bearer Token
- **Cuerpo:**
```json
{
  "fcm_token": "TOKEN_DE_FIREBASE_AQUI"
}
```
- Si Firebase no está configurado, el endpoint funciona igual (la Push se omite silenciosamente).

---

## 2. Listado de Notificaciones

- **URL:** `GET /api/notifications`
- **Auth:** Bearer Token
- **Respuesta:**
```json
[
  {
    "id": 1,
    "user_id": 1,
    "type": "issue_created",
    "title": "Reporte Nuevo",
    "message": "Se ha creado un reporte",
    "related_id": 10,
    "is_read": false,
    "created_at": "2026-05-12T00:52:26.000000Z",
    "updated_at": "2026-05-12T00:52:26.000000Z"
  }
]
```

---

## 3. Marcar como Leída

- **URL:** `PATCH /api/notifications/{id}/read`
- **Auth:** Bearer Token
- **Body:** No requiere

---

## 4. Campañas Generales (Solo Admin)

- **URL:** `POST /api/admin/notifications/campaign`
- **Auth:** Bearer Token + rol **Admin**
- **Cuerpo:**
```json
{
  "title": "Aviso Importante",
  "message": "Habrá mantenimiento en la zona centro mañana."
}
```
- Envía notificación en DB + push FCM a **todos** los usuarios del sistema.

---

## 5. Eventos Automáticos (Backend)

El backend dispara notificaciones automáticas en estos casos:
1. **Nuevo Reporte**: Se notifica a todos los administradores.
2. **Cambio de Estado**: Se notifica al ciudadano que creó el reporte.
3. **Nuevo Comentario**: Se notifica al dueño del reporte y a todos los comentaristas previos.

> Las notificaciones se guardan en DB (tabla `notifications`) y se consultan vía API.  
> Firebase Push requiere credenciales en el `.env` del servidor; si no están, solo aplica el guardado en DB.

---

## Historial de Cambios (Docs)

| Fecha | Cambio |
|-------|--------|
| 2026-05-11 | Corregida ruta de campaña: `/api/notifications/campaign` → `/api/admin/notifications/campaign` |
| 2026-05-11 | Agregado auth requerido (Bearer + Admin) en campaña y listado |
| 2026-05-11 | Agregada nota sobre FCM silencioso si no hay credenciales |

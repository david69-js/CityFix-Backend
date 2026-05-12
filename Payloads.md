# Documentación de Payloads (CityFix Backend)

Guía actualizada de los payloads JSON y `multipart/form-data` que debes enviar en las peticiones. Basada en el código real de los controladores (`laravel-app/app/Http/Controllers/` y `routes/api.php`).

Para actualizar registros (`PUT /api/X/{id}`) se usa el mismo formato, aunque todos los campos suelen ser opcionales.

La API corre en `http://localhost:8888/api` (vía Docker, nginx → php-fpm).

---

## Autenticación (JWT)

La API usa JWT (tymon/jwt-auth). El token se obtiene al login/register y se envía como `Authorization: Bearer {token}`.

### Registro de Usuario
- **Ruta:** `POST /api/auth/register`
- **Auth:** No requiere
- **Payload:**
```json
{
  "first_name": "Juan",
  "last_name": "Pérez",
  "email": "juan@example.com",
  "password": "password123",
  "invitation_code": "WORKER-1234"
}
```
- `invitation_code`: opcional. Si se envía un código válido, el usuario se registra con el rol asociado al código. Si no se envía, se asigna rol "Citizen".
- `password`: mínimo 6 caracteres.

### Inicio de Sesión
- **Ruta:** `POST /api/auth/login`
- **Auth:** No requiere
- **Payload:**
```json
{
  "email": "juan@example.com",
  "password": "password123"
}
```
- **Respuesta:**
```json
{
  "access_token": "eyJ...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### Login con Google
- **Ruta:** `POST /api/auth/google`
- **Auth:** No requiere
- **Payload:**
```json
{
  "id_token": "google_id_token_aqui"
}
```

### Obtener usuario autenticado
- **Ruta:** `GET /api/auth/me`
- **Auth:** `Bearer {token}`

### Cerrar sesión
- **Ruta:** `POST /api/auth/logout`
- **Auth:** `Bearer {token}`

---

## Recuperación de Contraseña

### 1. Solicitar enlace
- **Ruta:** `POST /api/auth/forgot-password`
- **Auth:** No requiere
- **Payload:**
```json
{
  "email": "juan@example.com"
}
```
- Envía un correo con un enlace que contiene un `token` único (válido 30 min).

### 2. Restablecer contraseña
- **Ruta:** `POST /api/auth/reset-password`
- **Auth:** No requiere
- **Payload:**
```json
{
  "email": "juan@example.com",
  "token": "token_7w5gET6FccSiCmZ0pIx2w...",
  "password": "nuevaPassword123",
  "password_confirmation": "nuevaPassword123"
}
```
- `password_confirmation`: **requerido** (usando regla `confirmed`).

---

## Reportes (Issues)

### Crear un reporte nuevo
- **Ruta:** `POST /api/issues`
- **Auth:** `Bearer {token}`
- **Content-Type:** `multipart/form-data`
- **Campos:**
| Campo | Tipo | Requerido |
|-------|------|-----------|
| `category_id` | integer | sí |
| `title` | string | sí (max 255) |
| `description` | string | sí |
| `location` | string | sí (max 255) |
| `latitude` | numeric | sí |
| `longitude` | numeric | sí |
| `image` | file | no (jpeg,png,jpg,gif, max 20MB) |

- `user_id` y `status_id = 1` (Pendiente) se auto-inyectan en el servidor.

### Listar reportes (feed público)
- **Ruta:** `GET /api/issues/feed`
- **Auth:** No requiere
- **Query params:** `?per_page=15`
- Solo devuelve issues con `is_hidden = false`.

### Ver detalle de un reporte
- **Ruta:** `GET /api/issues/{id}`
- **Auth:** No requiere

### Actualizar un reporte
- **Ruta:** `PUT /api/issues/{id}`
- **Auth:** `Bearer {token}`
- **Payload:** campos opcionales

### Eliminar un reporte
- **Ruta:** `DELETE /api/issues/{id}`
- **Auth:** `Bearer {token}`
- Respuesta: `204 No Content`

### Actualizar estado de un reporte (con historial)
- **Ruta:** `PATCH /api/issues/{id}/status`
- **Auth:** `Bearer {token}`
- **Payload:**
```json
{
  "status_id": 2
}
```
- Crea automáticamente un registro en `issue_history`. `changed_by` se toma del token.

### Historial de cambios de un reporte
- **Ruta:** `GET /api/issues/{id}/history-logs`
- **Auth:** `Bearer {token}`
- Incluye tiempo transcurrido entre cambios y tiempo total de resolución.

### Categorías
- **Ruta:** `POST /api/categories`
- **Auth:** No requiere
- **Payload:**
```json
{
  "name": "Baches",
  "icon": "fa-solid fa-road",
  "parent_id": null
}
```
- `parent_id`: opcional. Para subcategorías.

### Estados de reportes (Issue Status)
- **Ruta:** `POST /api/issue-statuses`
- **Auth:** No requiere
- **Payload:** Sin validaciones definidas (enviar según columnas: `name`, `color`, `sort_order`).

---

## Interacción Ciudadana

### Comentar en un reporte
- **Ruta:** `POST /api/issues/{issue_id}/comments`
- **Auth:** `Bearer {token}`
- **Payload:**
```json
{
  "comment": "Tengo el mismo problema en mi calle."
}
```
- `comment`: requerido, máximo 1000 caracteres.
- `user_id` se detecta automáticamente del token.

### CRUD alternativo de comentarios
- **Rutas:** `GET/POST /api/comments`, `GET/PUT/DELETE /api/comments/{id}`
- **Auth:** No requiere para lectura
- **Payload POST:**
```json
{
  "issue_id": 15,
  "comment": "¡Gracias por reparar este bache!"
}
```

### Votar / apoyar un reporte (toggle upvote)
- **Ruta:** `POST /api/issues/{issue_id}/toggle-upvote`
- **Auth:** `Bearer {token}`
- **Body:** No requiere. Si ya votaste, quita el voto; si no, lo agrega.

---

## Usuarios y Roles

### Listar usuarios (público)
- **Ruta:** `GET /api/users`
- **Auth:** No requiere

### CRUD de usuarios (Admin)
- **Rutas:** `/api/admin/users` (GET, POST), `/api/admin/users/{id}` (GET, PUT, DELETE)
- **Auth:** `Bearer {token}` + rol Admin
- **Payload POST:**
```json
{
  "first_name": "Ana",
  "last_name": "García",
  "email": "ana@example.com",
  "password": "password123",
  "phone": "+521234567890",
  "role_id": 2,
  "avatar": "(file image, max 2MB)"
}
```
- **Payload PUT:** mismos campos, todos opcionales

### Actualizar perfil propio
- **Ruta:** `POST /api/user/profile`
- **Auth:** `Bearer {token}`
- **Payload:** `first_name`, `last_name`, `phone`, `avatar` (todos opcionales)

### Actualizar FCM Token
- **Ruta:** `POST /api/users/fcm-token`
- **Auth:** `Bearer {token}`
- **Payload:**
```json
{
  "fcm_token": "token_firebase_aqui"
}
```

### Roles
- **Ruta:** `POST /api/roles`
- **Auth:** No requiere
- **Payload recomendado:**
```json
{
  "name": "Trabajador",
  "description": "Encargado de arreglar reportes"
}
```
- Nota: el controlador no tiene validaciones definidas.

### Permisos
- **Ruta:** `POST /api/permissions`
- **Auth:** No requiere
- **Payload recomendado:**
```json
{
  "name": "edit_issue",
  "description": "Puede modificar reportes"
}
```
- Nota: el controlador no tiene validaciones definidas.

---

## Códigos de Invitación

CRUD completo en `/api/invitation-codes` (apiResource). Incluye relación `role`.

### Listar códigos
- **Ruta:** `GET /api/invitation-codes`

### Crear código de invitación
- **Ruta:** `POST /api/invitation-codes`
- **Auth:** No requiere
- **Payload:**
```json
{
  "code": "WORKER-2024-PRO",
  "role_id": 2,
  "is_active": true,
  "expires_at": "2026-12-31 23:59:59",
  "max_uses": 10
}
```
- `code`: opcional. Si no se envía, se genera uno aleatorio de 8 caracteres.
- `role_id`: requerido.
- `is_active`: booleano, opcional.

### Ver/Actualizar/Eliminar código
- **Rutas:** `GET/PUT/DELETE /api/invitation-codes/{id}`
- **PUT payload:** `is_active`, `expires_at`, `max_uses` (todos opcionales)

---

## Asignaciones Internas

### Crear asignación
- **Ruta:** `POST /api/assignments`
- **Auth:** No requiere
- **Payload:**
```json
{
  "issue_id": 15,
  "worker_id": 8,
  "status_id": 1,
  "notes": "Llevar material rápido.",
  "assigned_at": "2026-04-08 09:00:00"
}
```

### Mi bandeja de tareas (Trabajador)
- **Ruta:** `GET /api/my-assignments`
- **Auth:** `Bearer {token}`
- Devuelve asignaciones donde `worker_id === usuario autenticado`.
- Incluye `issue.category`, `issue.status` y `status`.

### Estados de asignación
- **Ruta:** `POST /api/assignment-statuses`
- **Auth:** No requiere
- Nota: el controlador no tiene validaciones definidas.

---

## Historial de Reportes (Issue History)

### Crear registro de historial
- **Ruta:** `POST /api/issue-histories`
- **Auth:** No requiere
- Nota: el controlador no tiene validaciones definidas.

### Ver historial de un reporte (con tiempos)
- **Ruta:** `GET /api/issues/{issue}/history-logs`
- **Auth:** `Bearer {token}`

---

## Notificaciones

### Notificaciones del usuario autenticado
- **Ruta:** `GET /api/notifications`
- **Auth:** No requiere (pero filtra por `user_id = auth()->id()` si hay sesión)

### Marcar como leída
- **Ruta:** `PATCH /api/notifications/{id}/read`
- **Auth:** `Bearer {token}`
- **Body:** No requiere

### Marcar como leída (CRUD)
- **Ruta:** `PUT /api/notifications/{id}`
- **Payload:**
```json
{
  "is_read": true
}
```

### Imágenes de reportes (Issue Images)
- **Ruta:** `GET /api/issue-images` (lista todas)
- Las imágenes se asocian automáticamente al crear un Issue con `multipart/form-data`.

### Campaña de notificaciones (Admin)
- **Ruta:** `POST /api/admin/notifications/campaign`
- **Auth:** `Bearer {token}` + rol Admin
- **Payload:**
```json
{
  "title": "Mantenimiento Programado",
  "message": "El sistema estará en mantenimiento esta noche."
}
```
- Envía a TODOS los usuarios del sistema (guarda en DB + push FCM si tienen token).

---

## Google Maps Proxy

Todas requieren `Bearer {token}`.

| Ruta | Params |
|------|--------|
| `GET /api/maps/geocode` | `?address=Av+Principal` |
| `GET /api/maps/reverse-geocode` | `?lat=19.43&lng=-99.13` |
| `GET /api/maps/places/autocomplete` | `?input=Av+Principal&country=mx` |
| `GET /api/maps/places/details` | `?place_id=ChIJ...&fields=formatted_address` |

---

## Admin

### Listar todos los issues (incluyendo ocultos)
- **Ruta:** `GET /api/admin/issues`
- **Auth:** `Bearer {token}` + rol Admin
- **Query params:** `?per_page=20&is_hidden=false&status_id=1&category_id=3&search=bache`

### Editar cualquier issue (Admin)
- **Ruta:** `PUT /api/admin/issues/{id}`
- **Auth:** `Bearer {token}` + rol Admin
- **Payload:** `title`, `description`, `category_id`, `location`, `latitude`, `longitude`, `status_id` (todos opcionales)

### Ocultar/Mostrar issue (Admin)
- **Ruta:** `PATCH /api/admin/issues/{id}/toggle-hidden`
- **Auth:** `Bearer {token}` + rol Admin
- **Payload:**
```json
{
  "reason": "Reporte duplicado"
}
```
- `reason`: opcional. Solo aplica al ocultar.

---

## Seed de Base de Datos

- **Ruta:** `POST /api/seed`
- **Auth:** No requiere
- Ejecuta `php artisan db:seed` para poblar la base con datos iniciales.

---

## Historial de Cambios (Docs)

| Fecha | Cambio |
|-------|--------|
| 2026-05-11 | Revisión general de payloads contra código real de controladores |
| 2026-05-11 | Corregido `register`: password min 6 (no 8) |
| 2026-05-11 | Corregido `reset-password`: agregado `password_confirmation` |
| 2026-05-11 | Corregido `POST /api/issues`: description/location/lat/lng son **requeridos** (no opcionales) |
| 2026-05-11 | Corregido `image` en issues: es **nullable** (no requerido) |
| 2026-05-11 | Agregadas rutas Google Maps, Admin CRUD, toggle-hidden, campaigns |
| 2026-05-11 | Agregada ruta `PATCH /api/notifications/{id}/read` |
| 2026-05-11 | Agregada ruta `POST /api/user/profile` |
| 2026-05-11 | Movido CRUD de users a `/api/admin/users` (requiere Admin) |
| 2026-05-11 | Agregado `apiResource('invitation-codes')` en routes/api.php |
| 2026-05-11 | Fix: `GeneralNotification` ya no usa `via(['database'])` para evitar crash con columna `data` faltante |

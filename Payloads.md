# Documentación de Payloads (CityFix Backend)

Esta guía detalla la estructura JSON (payloads) que debes enviar en el cuerpo (body) de las peticiones para crear o actualizar registros. La información listada está basada en las modificaciones recientes de la API para soportar autenticación automatizada, GPS e imágenes de manera optimizada.

Para actualizar registros (ej. `PUT /api/X/{id}`), se usa el mismo formato de JSON, aunque todos los campos suelen ser opcionales para solo actualizar lo que necesites.

---

## 🔐 Autenticación e Ingreso

### Registro de Usuario
- **Ruta:** `POST /api/auth/register`
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
*(Nota: El campo `invitation_code` es opcional. Si lo envías y es válido, asignará el rol correspondiente, como Trabajador o Admin. Si no lo envías, serás un Ciudadano base).*

### Inicio de Sesión (Login)
- **Ruta:** `POST /api/auth/login`
- **Payload:**
```json
{
  "email": "juan@example.com",
  "password": "password123"
}
```

---

## ⚠️ Reportes Ciudadanos (Issues)

### Crear un Reporte Nuevo
- **Rutas CRUD:** `POST /api/issues`
- **Tipo de Contenido:** `multipart/form-data` *(Importante: Ya NO es JSON puro porque soporta archivos).*
- **Payload (Campos del Formulario):**
  - `category_id`: 3
  - `title`: "Bache en Avenida Principal"
  - `description`: "Hay un bache gigante que daña los coches y provoca accidentes."
  - `location`: "Av. Principal esq. Calle 2"
  - `latitude`: 19.432608
  - `longitude`: -99.133209
  - `image`: *(Archivo opcional de tipo jpeg, png, jpg, gif que no exceda 5MB)*

*(El `user_id` y el `status_id = 1` se auto-inyectan en el servidor).*

### Obtener el Feed Interactivo
- **Ruta:** `GET /api/issues/feed`
- **Query Params (Opcionales):** `?per_page=15`
- **Retorno:** Devuelve el array paginado listo para consumir en tu app. Incluye a los `images`, el responsable `user`, la `category` y los conteos pre-calculados de `upvotes_count` y `comments_count`.

### Categorías de Reportes
- **Ruta:** `POST /api/categories`
- **Payload:**
```json
{
  "name": "Baches",
  "icon": "fa-solid fa-road",
  "parent_id": null
}
```

### Estados de los Reportes (Status)
- **Ruta:** `POST /api/issue-statuses`
- **Payload:**
```json
{
  "name": "Pendiente",
  "color": "#FFC107",
  "sort_order": 1
}
```

---

## 💬 Interacción Ciudadana

### Comentar en un Reporte
- **Rutas Optimizadas:** `POST /api/issues/{issue_id}/comments`
- **Payload:**
```json
{
  "comment": "Tengo el mismo problema en mi calle de al lado."
}
```
*(Nota: El `user_id` ya se detecta de forma automática vía Token).*

- **Ruta Alternativa (CRUD Base):** `POST /api/comments`
- **Payload:**
```json
{
  "issue_id": 15,
  "comment": "¡Gracias por reparar este bache!"
}
```

### Votar / Apoyar un Reporte (Upvotes Toggle)
- **Ruta principal:** `POST /api/issues/{issue_id}/toggle-upvote`
- **Payload:** No requiere nada en el Body. Solamente se requiere el Token de autenticación en la cabecera. Si detecta que no habías votado, agregará el voto. Si ya habías votado, lo quitará previniendo el spam.

---

## 👥 Usuarios y Permisos Administrativos

### Crear un Usuario Directamente (Admin)
- **Ruta:** `POST /api/users`
- **Payload:**
```json
{
  "first_name": "Ana",
  "last_name": "García",
  "email": "ana@example.com",
  "password": "password123",
  "phone": "+521234567890",
  "avatar": "url_de_imagen_o_base64",
  "role_id": 2
}
```

### Roles
- **Ruta:** `POST /api/roles`
- **Payload:**
```json
{
  "name": "Trabajador",
  "description": "Encargado de arreglar reportes"
}
```

### Permisos
- **Ruta:** `POST /api/permissions`
- **Payload:**
```json
{
  "name": "edit_issue",
  "description": "Puede modificar reportes"
}
```

### Códigos de Invitación (Para Trabajadores / Sistema)
- **Ruta:** `POST /api/invitation-codes`
- **Payload:**
```json
{
  "code": "WORKER-1234",
  "role_id": 3,
  "is_active": true,
  "expires_at": "2026-12-31 23:59:59",
  "max_uses": 10,
  "used_count": 0
}
```

---

## 👷 Asignaciones y Gestión Interna

### Asignar Tarea a Trabajador
- **Ruta:** `POST /api/assignments`
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

### Estados de la Asignación
- **Ruta:** `POST /api/assignment-statuses`
- **Payload:**
```json
{
  "name": "En Camino"
}
```

### Historial de Cambios (Logs)
- **Ruta:** `POST /api/issue-histories`
- **Payload:**
```json
{
  "issue_id": 15,
  "status_id": 2,
  "changed_by": 5,
  "changed_at": "2026-04-08 10:30:00"
}
```

---

## 🔔 Notificaciones

### Crear Notificación del Sistema
- **Ruta:** `POST /api/notifications`
- **Payload:**
```json
{
  "user_id": 2,
  "type": "update",
  "title": "Reporte en Progreso",
  "message": "Los trabajadores ya están en camino.",
  "related_id": 15,
  "is_read": false
}
```

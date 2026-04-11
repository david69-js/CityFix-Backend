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
*(Nota: El campo `invitation_code` es opcional. Si se proporciona un código válido de "Trabajador", el usuario será registrado automáticamente con ese rol. Si no se envía, el usuario será un "Ciudadano" por defecto).*

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

## 🔑 Recuperación de Contraseña
``` Ejemplo
Link Completo=http://localhost:3000/reset-password?token=7w5gET6FccSiCmZ0pIx2wRACDoHaw9zEDUtuQlr6J7QMkRQZZPwLoenXfyQT2DWw&email=david%40studio31.io

token: 7w5gET6FccSiCmZ0pIx2wRACDoHaw9zEDUtuQlr6J7QMkRQZZPwLoenXfyQT2DWw

```

### 1. Solicitar enlace de recuperación
- **Ruta:** `POST /api/auth/forgot-password`
- **Payload:**
```json
{
  "email": "juan@example.com"
}
```
*(Nota: Esto enviará un correo electrónico con un enlace que contiene un token único con validez de 30 minutos).*

### 2. Restablecer la contraseña (desde el enlace)
- **Ruta:** `POST /api/auth/reset-password`
- **Payload:**
```json
{
  "email": "juan@example.com",
  "token": "token_recibido_en_el_correo",
  "password": "nuevaPassword123",
  "password_confirmation": "nuevaPassword123"
}
```

---

## ⚠️ Reportes Ciudadanos (Issues)

### Crear un Reporte Nuevo
- **Rutas CRUD:** `POST /api/issues`
- **Tipo de Contenido:** `multipart/form-data` *(Importante: Ya NO es JSON puro porque soporta archivos).*
- **Payload (Campos del Formulario):**
```json
{
  "category_id": 3,
  "title": "Bache en Avenida Principal",
  "description": "Hay un bache gigante que daña los coches y provoca accidentes.",
  "location": "Av. Principal esq. Calle 2",
  "latitude": 19.432608,
  "longitude": -99.133209,
  "image": "File"
}
```

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
  "issue_id": issue_id,
  "comment": "Tengo el mismo problema en mi calle de al lado."
}
```
*(Nota: El `issue_id` es el id del reporte que se quiere comentar.)*
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

### Actualizar Usuario (Cambio de Rol / Datos)
- **Ruta:** `PUT /api/users/{id}`
- **Payload:**
```json
{
  "role_id": 2,
  "first_name": "Ana Actualizada",
  "phone": "5551234567"
}
```
*(Nota: Un administrador puede usar esta ruta para cambiar el rol de un Ciudadano a Trabajador enviando el `role_id` correspondiente).*

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

### Códigos de Invitación (Generación - Admin)
- **Ruta:** `POST /api/invitation-codes`
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
*(Nota: El campo `code` es opcional; si no se envía, el sistema generará uno aleatorio de 8 caracteres. El `role_id` determina qué rol obtendrá quien use el código).*

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

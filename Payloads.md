# Documentación de Payloads (CityFix Backend)

Esta guía detalla la estructura JSON (payloads) que debes enviar en el cuerpo (body) de las peticiones para crear o actualizar registros. La información listada está basada estrictamente en la base de datos (migraciones) y las propiedades que permite guardar el backend de Laravel.

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
  "password": "password123"
}
```

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

### Crear / Administrar un Reporte
- **Rutas CRUD:** `POST /api/issues` | `PUT /api/issues/{id}`
- **Payload:**
```json
{
  "user_id": 1,
  "category_id": 3,
  "title": "Bache en Avenida Principal",
  "description": "Hay un bache gigante que daña los coches y provoca accidentes.",
  "location": "Av. Principal esq. Calle 2",
  "latitude": 19.432608,
  "longitude": -99.133209,
  "status_id": 1
}
```

### Añadir Imagen a un Reporte
- **Ruta:** `POST /api/issue-images`
- **Payload:**
```json
{
  "issue_id": 15,
  "image_url": "https://storage.midominio.com/imagen_del_bache.jpg"
}
```

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
- **Rutas:** `POST /api/issues/{issue}/comments` o `POST /api/comments`
- **Payload:**
```json
{
  "issue_id": 15,
  "user_id": 2,
  "comment": "Tengo el mismo problema en mi calle de al lado."
}
```

### Votar / Apoyar un Reporte (Upvotes)
- **Ruta principal:** `POST /api/issues/{issue}/toggle-upvote` *(No requiere JSON body ya que toma el Auth Token)*
- **Ruta manual (Admin):** `POST /api/upvotes`
- **Payload manual:**
```json
{
  "issue_id": 15,
  "user_id": 3
}
```

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

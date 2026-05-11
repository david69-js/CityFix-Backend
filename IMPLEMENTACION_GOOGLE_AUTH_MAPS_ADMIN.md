# 🚀 Implementación: Google Auth, Google Maps API & Admin Gestión de Issues

**Fecha:** 11 de Mayo, 2026  
**Rama:** `feature/google-auth-maps-admin`  

---

## 📋 Tabla de Contenidos

1. [Autenticación con Google](#1--autenticación-con-google)
2. [API de Google Maps (Proxy)](#2-️-api-de-google-maps-proxy)
3. [Admin: Ocultar y Editar Issues](#3-️-admin-ocultar-y-editar-issues)
4. [Bug Fixes Incluidos](#4--bug-fixes-incluidos)
5. [Archivos Modificados / Creados](#5--archivos-modificados--creados)
6. [Migraciones de Base de Datos](#6--migraciones-de-base-de-datos)
7. [Configuración Requerida](#7-️-configuración-requerida)
8. [Dependencias Agregadas](#8--dependencias-agregadas)

---

## 1. 🔐 Autenticación con Google

### Descripción

Permite a los usuarios iniciar sesión o registrarse usando su cuenta de Google. El flujo es:

1. El frontend (React Native / Web) usa el SDK de Google Sign-In para obtener un `id_token`
2. El frontend envía el `id_token` al endpoint `POST /api/auth/google`
3. El backend verifica el token con la librería oficial de Google
4. Si el usuario ya existe (por `google_id` o `email`) → se autentica y recibe JWT
5. Si no existe → se crea automáticamente con rol **Citizen** y recibe JWT

### Endpoint

```
POST /api/auth/google
Content-Type: application/json
```

**Request Body:**
```json
{
  "id_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
}
```

**Response (200 OK):**
```json
{
  "message": "Login con Google exitoso.",
  "user": {
    "id": 1,
    "first_name": "Hugo",
    "last_name": "Da Silva",
    "email": "hugo@gmail.com",
    "avatar": "https://lh3.googleusercontent.com/...",
    "role": {
      "id": 1,
      "name": "Citizen"
    }
  },
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "is_new_user": false
}
```

**Errores:**
| Código | Descripción |
|--------|-------------|
| 401 | Token de Google inválido |
| 422 | Falta el campo `id_token` |

### Comportamiento especial

- Si un usuario ya se registró con email/password y luego inicia sesión con Google (mismo email), se vincula automáticamente su `google_id`
- Si el usuario no tiene avatar local pero Google provee uno, se actualiza automáticamente
- Los usuarios de Google no tienen contraseña (campo `password` ahora es nullable)

---

## 2. 🗺️ API de Google Maps (Proxy)

### Descripción

El backend actúa como **proxy** para las APIs de Google Maps, protegiendo la API key del lado del servidor. El frontend nunca necesita la key directamente.

> ⚠️ **Todos los endpoints de Maps requieren autenticación JWT** (header `Authorization: Bearer <token>`)

### Endpoints

#### 2.1 Geocodificación (Dirección → Coordenadas)

```
GET /api/maps/geocode?address=Av. América, Cochabamba, Bolivia
Authorization: Bearer <token>
```

**Response:** Respuesta directa de Google Geocoding API.

---

#### 2.2 Geocodificación Inversa (Coordenadas → Dirección)

```
GET /api/maps/reverse-geocode?lat=-17.3895&lng=-66.1568
Authorization: Bearer <token>
```

**Response:** Respuesta directa de Google Geocoding API.

---

#### 2.3 Autocompletado de Lugares

```
GET /api/maps/places/autocomplete?input=Plaza 14&country=bo
Authorization: Bearer <token>
```

| Parámetro | Requerido | Descripción |
|-----------|-----------|-------------|
| `input` | ✅ | Texto de búsqueda |
| `country` | ❌ | Código ISO del país (ej: `bo` para Bolivia) |

**Response:** Respuesta directa de Google Places Autocomplete API.

---

#### 2.4 Detalles de un Lugar

```
GET /api/maps/places/details?place_id=ChIJN1t_tDeuEmsRUsoyG83frY4&fields=formatted_address,geometry,name
Authorization: Bearer <token>
```

| Parámetro | Requerido | Descripción |
|-----------|-----------|-------------|
| `place_id` | ✅ | ID del lugar de Google |
| `fields` | ❌ | Campos a retornar (default: `formatted_address,geometry,name`) |

---

## 3. 🛡️ Admin: Ocultar y Editar Issues

### Descripción

Los administradores pueden:
- **Ver todos los issues**, incluyendo los ocultos (el feed público los filtra)
- **Editar cualquier issue** (título, descripción, categoría, ubicación, estado)
- **Ocultar/mostrar issues** del feed público con una razón opcional

> ⚠️ **Todos los endpoints de Admin requieren autenticación JWT + Rol Admin**

### Endpoints

#### 3.1 Listar TODOS los Issues (Admin)

```
GET /api/admin/issues
Authorization: Bearer <admin_token>
```

**Filtros opcionales:**

| Parámetro | Ejemplo | Descripción |
|-----------|---------|-------------|
| `is_hidden` | `?is_hidden=true` | Solo ocultos / solo visibles |
| `status_id` | `?status_id=2` | Filtrar por estado |
| `category_id` | `?category_id=3` | Filtrar por categoría |
| `search` | `?search=bache` | Buscar en título, descripción y ubicación |
| `per_page` | `?per_page=10` | Paginación (default: 20) |

**Response (200 OK):** Paginación con todos los issues incluyendo `is_hidden` y `hidden_reason`.

---

#### 3.2 Editar un Issue (Admin)

```
PUT /api/admin/issues/{id}
Authorization: Bearer <admin_token>
Content-Type: application/json
```

**Request Body (todos los campos son opcionales):**
```json
{
  "title": "Bache en Av. América",
  "description": "Bache grande que afecta el tráfico",
  "category_id": 2,
  "location": "Av. América esq. Ayacucho",
  "latitude": -17.3895,
  "longitude": -66.1568,
  "status_id": 3
}
```

**Response (200 OK):**
```json
{
  "message": "Issue actualizado correctamente.",
  "issue": {
    "id": 5,
    "title": "Bache en Av. América",
    "description": "Bache grande que afecta el tráfico",
    "category": { "id": 2, "name": "Infraestructura" },
    "status": { "id": 3, "name": "En Progreso" },
    "images": [],
    "user": { "id": 1, "first_name": "Hugo", "last_name": "Da Silva" }
  }
}
```

> **Nota:** Si se cambia el `status_id`, se crea automáticamente un registro en `issue_history`.

---

#### 3.3 Ocultar / Mostrar un Issue (Toggle)

```
PATCH /api/admin/issues/{id}/toggle-hidden
Authorization: Bearer <admin_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "reason": "Contenido inapropiado"
}
```
> El campo `reason` es opcional. Si no se envía, se usa "Ocultado por administrador".

**Response al OCULTAR (200 OK):**
```json
{
  "message": "Issue ocultado del feed público.",
  "issue": { ... },
  "is_hidden": true
}
```

**Response al MOSTRAR (200 OK):**
```json
{
  "message": "Issue visible nuevamente.",
  "issue": { ... },
  "is_hidden": false
}
```

### Impacto en endpoints existentes

Los issues ocultos (`is_hidden = true`) **NO aparecen** en:
- `GET /api/issues` (index público)
- `GET /api/issues/feed` (feed principal)

Los issues ocultos **SÍ aparecen** en:
- `GET /api/admin/issues` (panel admin)
- `GET /api/issues/{id}` (show individual, si se conoce el ID)

---

## 4. 🔧 Bug Fixes Incluidos

| Bug | Corrección |
|-----|-----------|
| Rutas de Admin usaban `auth:sanctum` pero el proyecto usa JWT (`auth:api`) | Cambiado a `auth:api` en el grupo de rutas admin |
| `POST /user/profile` estaba fuera del grupo autenticado | Movido dentro del grupo `auth:api` |

---

## 5. 📁 Archivos Modificados / Creados

### Archivos Nuevos

| Archivo | Descripción |
|---------|-------------|
| `database/migrations/2026_05_11_000001_add_google_id_to_users_table.php` | Agrega `google_id` a users, hace password nullable |
| `database/migrations/2026_05_11_000002_add_is_hidden_to_issues_table.php` | Agrega `is_hidden` y `hidden_reason` a issues |
| `app/Http/Controllers/GoogleMapsController.php` | Proxy controller para Google Maps API |

### Archivos Modificados

| Archivo | Cambios |
|---------|---------|
| `app/Models/User.php` | `google_id` en `$fillable` y `$hidden` |
| `app/Models/Issue.php` | `is_hidden`, `hidden_reason` en `$fillable`; `is_hidden` cast a boolean |
| `app/Http/Controllers/AuthController.php` | Nuevo método `loginWithGoogle()` |
| `app/Http/Controllers/IssueController.php` | Métodos `adminIndex()`, `adminUpdate()`, `toggleHidden()`; filtro `is_hidden` en `feed()` e `index()` |
| `routes/api.php` | Rutas de Google Auth, Google Maps, Admin issues; fix middleware |
| `config/services.php` | Configuración de `google.client_id` y `google_maps.api_key` |
| `.env` | Variables `GOOGLE_CLIENT_ID` y `GOOGLE_MAPS_API_KEY` |
| `composer.json` / `composer.lock` | Dependencia `google/apiclient` |

---

## 6. 🗄️ Migraciones de Base de Datos

### Migración 1: `add_google_id_to_users_table`

```sql
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email;
ALTER TABLE users MODIFY password VARCHAR(255) NULL;
```

### Migración 2: `add_is_hidden_to_issues_table`

```sql
ALTER TABLE issues ADD COLUMN is_hidden BOOLEAN DEFAULT FALSE AFTER status_id;
ALTER TABLE issues ADD COLUMN hidden_reason VARCHAR(255) NULL AFTER is_hidden;
CREATE INDEX issues_is_hidden_index ON issues (is_hidden);
```

---

## 7. ⚙️ Configuración Requerida

Agregar las siguientes variables al archivo `.env` del servidor de producción:

```env
# Google OAuth - Obtener desde Google Cloud Console > Credentials > OAuth 2.0 Client IDs
GOOGLE_CLIENT_ID=tu-client-id-aqui.apps.googleusercontent.com

# Google Maps - Obtener desde Google Cloud Console > Credentials > API Keys
# Requiere habilitar: Geocoding API, Places API
GOOGLE_MAPS_API_KEY=AIzaSy...
```

### Pasos para obtener las credenciales:

1. Ir a [Google Cloud Console](https://console.cloud.google.com/)
2. Crear un proyecto o seleccionar uno existente
3. **Para Google Auth:**
   - Ir a APIs & Services > Credentials
   - Crear un OAuth 2.0 Client ID
   - Copiar el Client ID al `.env`
4. **Para Google Maps:**
   - Ir a APIs & Services > Library
   - Habilitar "Geocoding API" y "Places API"
   - Ir a Credentials > Create API Key
   - Copiar la API Key al `.env`

---

## 8. 📦 Dependencias Agregadas

| Paquete | Versión | Propósito |
|---------|---------|-----------|
| `google/apiclient` | ^2.19 | Verificación de Google ID Tokens para autenticación |

---

## 📊 Resumen de Endpoints Nuevos

| Método | Ruta | Auth | Rol | Descripción |
|--------|------|------|-----|-------------|
| POST | `/api/auth/google` | ❌ | — | Login/registro con Google |
| GET | `/api/maps/geocode` | ✅ JWT | — | Geocodificación |
| GET | `/api/maps/reverse-geocode` | ✅ JWT | — | Geocodificación inversa |
| GET | `/api/maps/places/autocomplete` | ✅ JWT | — | Autocompletado de lugares |
| GET | `/api/maps/places/details` | ✅ JWT | — | Detalles de lugar |
| GET | `/api/admin/issues` | ✅ JWT | Admin | Lista todos los issues |
| PUT | `/api/admin/issues/{id}` | ✅ JWT | Admin | Editar issue |
| PATCH | `/api/admin/issues/{id}/toggle-hidden` | ✅ JWT | Admin | Ocultar/mostrar issue |

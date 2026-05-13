# Plan de Implementación — CityFix Backend (Laravel)

## 📋 Resumen de Cambios Requeridos

| Prioridad | Feature | Estado Actual |
|-----------|---------|---------------|
| 🔴 Alta | Bug: Admin user tiene role_id incorrecto | ❌ Bloqueante |
| 🔴 Alta | Feed público necesita filtros (search, user_id, status_id, category_id) | ❌ No soporta |
| 🔴 Alta | Soft-delete / activar usuarios (is_active) | ❌ No existe |
| 🔴 Alta | Endpoint toggle-active para usuarios | ❌ No existe |
| 🟡 Media | adminIndex no filtra por user_id | ❌ No existe |
| 🟡 Media | Verificación real de invitation codes | ❌ Es mock |
| 🟢 Baja | Seguridad: rutas públicas sin auth | ⚠️ Parcial |

---

## 🔧 Fase 0 — Bugfixes Prioritarios

### 0.1 Corregir role_id del admin user

**Ejecutar en base de datos**:

```sql
UPDATE users SET role_id = 1 WHERE email = 'admin@cityfix.com';
```

**Verificar**:

```sql
SELECT u.id, u.first_name, u.email, u.role_id, r.name AS role_name
FROM users u
JOIN roles r ON r.id = u.role_id
WHERE u.email = 'admin@cityfix.com';
```

Si no existe un rol con `id = 1` y `name = 'Admin'`, verificar con:
```sql
SELECT * FROM roles;
```

### 0.2 Proteger rutas públicas

**Archivo**: `routes/api.php`

**Problema**: Las rutas `apiResource('issues', IssueController::class)` están fuera de cualquier middleware de autenticación. Cualquiera puede crear/modificar/eliminar issues.

**Además**: `GET /api/users` (línea 185) es público y expone todos los usuarios.

**Solución** — Modificar `routes/api.php`:

```php
// =============================
// API RESOURCES (protegidas)
// =============================
Route::middleware('auth:api')->group(function () {
    Route::apiResource('issues', IssueController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('comments', CommentController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('upvotes', UpvoteController::class)->only(['store', 'destroy']);
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show', 'update']);
});

// Recursos de solo lectura (públicos)
Route::apiResource('issues', IssueController::class)->only(['index', 'show']);
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);
Route::apiResource('issue-statuses', IssueStatusController::class)->only(['index', 'show']);
Route::apiResource('assignment-statuses', AssignmentStatusController::class)->only(['index', 'show']);
Route::apiResource('roles', RoleController::class)->only(['index', 'show']);
```

**O también** (más simple): Mover todo `apiResource` dentro de un grupo `auth:api` y agregar rutas públicas explícitamente.

> **Importante**: `POST /api/issues` necesita estar dentro de `auth:api` porque usa `auth('api')->id()` en `IssueController@store`.

### 0.3 Corregir validaciones vacías

**Problema**: Múltiples controllers tienen `$request->validate([])` (array vacío), lo que permite cualquier dato.

**Archivos a corregir**:

| Archivo | Método | Línea |
|---------|--------|-------|
| `app/Http/Controllers/IssueController.php` | `update()` | ~122 |
| `app/Http/Controllers/CommentController.php` | `store()` | ~varies |
| `app/Http/Controllers/UpvoteController.php` | `store()` | ~varies |
| `app/Http/Controllers/IssueHistoryController.php` | `store()`, `update()` | ~varies |
| `app/Http/Controllers/IssueImageController.php` | `store()`, `update()` | ~varies |
| `app/Http/Controllers/RoleController.php` | `store()`, `update()` | ~varies |
| `app/Http/Controllers/PermissionController.php` | `store()`, `update()` | ~varies |
| `app/Http/Controllers/IssueStatusController.php` | `store()`, `update()` | ~varies |
| `app/Http/Controllers/AssignmentStatusController.php` | `store()`, `update()` | ~varies |

**Ejemplo de corrección** para `IssueController@update`:

```php
public function update(Request $request, Issue $issue)
{
    $validated = $request->validate([
        'title'       => 'sometimes|string|max:255',
        'description' => 'sometimes|string',
        'category_id' => 'sometimes|exists:categories,id',
        'location'    => 'sometimes|string|max:255',
        'latitude'    => 'sometimes|numeric',
        'longitude'   => 'sometimes|numeric',
        'status_id'   => 'sometimes|exists:issue_status,id',
    ]);
    $issue->update($validated);
    return response()->json($issue);
}
```

---

## 🔍 Fase 1 — Buscador de Reportes (Feed Público)

### 1.1 Agregar filtros al método `feed()`

**Archivo**: `app/Http/Controllers/IssueController.php` — método `feed()` (~línea 84)

**Cambio**: Agregar parámetros opcionales de búsqueda y filtrado.

```php
public function feed(Request $request)
{
    $perPage = $request->query('per_page', 15);

    $query = Issue::with([
        'user:id,first_name,last_name,avatar',
        'category',
        'status',
        'images',
        'comments' => function($query) {
            $query->with('user:id,first_name,last_name,avatar')
                  ->latest()
                  ->limit(3);
        }
    ])
    ->where('is_hidden', false)
    ->withCount(['upvotes', 'comments']);

    // Filtro por búsqueda de texto (título, descripción, ubicación)
    if ($request->has('search')) {
        $search = $request->query('search');
        $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }

    // Filtro por usuario
    if ($request->has('user_id')) {
        $query->where('user_id', $request->query('user_id'));
    }

    // Filtro por estado
    if ($request->has('status_id')) {
        $query->where('status_id', $request->query('status_id'));
    }

    // Filtro por categoría
    if ($request->has('category_id')) {
        $query->where('category_id', $request->query('category_id'));
    }

    $issues = $query->orderBy('created_at', 'desc')->paginate($perPage);

    return response()->json($issues);
}
```

**Endpoint resultante**: `GET /api/issues/feed?per_page=15&search=bache&user_id=7&status_id=1&category_id=3`

### 1.2 Agregar filtro `user_id` a `adminIndex()`

**Archivo**: `app/Http/Controllers/IssueController.php` — método `adminIndex()` (~línea 164)

**Cambio**: Agregar filtro por `user_id` (además de los filtros existentes).

```php
// Después del filtro de categoría (~línea 188):

// Optional filter by user_id
if ($request->has('user_id')) {
    $query->where('user_id', $request->query('user_id'));
}
```

---

## 📦 Fase 2 — Archivar Reportes (ya existe en backend)

### 2.1 Endpoint existente

El endpoint `PATCH /api/admin/issues/{issue}/toggle-hidden` ya está implementado en:

**Archivo**: `app/Http/Controllers/IssueController.php` — método `toggleHidden()` (~línea 242)

**Funcionamiento**:
- Toggle de `is_hidden` (true ↔ false)
- Acepta `reason` (string opcional, máx 500 chars)
- Cuando se oculta, guarda `hidden_reason`
- Cuando se muestra, limpia `hidden_reason`

**No requiere cambios en backend.** Solo implementación en frontend.

---

## 👥 Fase 3 — Gestión de Usuarios para Admin

### 3.1 Agregar migración: `is_active` en users

**Crear archivo**: `database/migrations/YYYY_MM_DD_HHMMSS_add_is_active_to_users_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('fcm_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
```

**Ejecutar**: `php artisan migrate`

### 3.2 Poblar `is_active` para registros existentes

```bash
php artisan tinker
```

```php
DB::table('users')->whereNull('is_active')->update(['is_active' => true]);
```

### 3.3 Actualizar el modelo User

**Archivo**: `app/Models/User.php`

**Cambio**: Agregar `is_active` a `$fillable`:

```php
protected $fillable = [
    'first_name',
    'last_name',
    'email',
    'password',
    'phone',
    'avatar',
    'role_id',
    'fcm_token',
    'google_id',
    'is_active', // ← NUEVO
];
```

### 3.4 Crear endpoint: toggle-active para usuarios

**Archivo**: `app/Http/Controllers/UserController.php`

**Cambio**: Agregar nuevo método al final de la clase (antes de la llave de cierre).

```php
/**
 * Toggle user active/archived status.
 * PATCH /api/admin/users/{user}/toggle-active
 */
public function toggleActive(Request $request, User $user)
{
    // Prevenir desactivar a otro administrador
    if ($user->hasRole('Admin') && $user->id !== auth('api')->id()) {
        return response()->json([
            'message' => 'No puedes desactivar a otro administrador.'
        ], 403);
    }

    $newState = !$user->is_active;
    $user->update(['is_active' => $newState]);

    return response()->json([
        'message' => $newState ? 'Usuario activado correctamente.' : 'Usuario archivado correctamente.',
        'user'    => $user->load('role'),
        'is_active' => $newState,
    ]);
}
```

### 3.5 Registrar ruta

**Archivo**: `routes/api.php`

**Cambio**: Dentro del grupo `admin`, agregar la nueva ruta:

```php
Route::middleware(['auth:api', 'role:Admin'])->prefix('admin')->group(function () {
    Route::apiResource('users', UserController::class);
    Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive']); // ← NUEVO

    Route::get('/issues', [IssueController::class, 'adminIndex']);
    Route::put('/issues/{issue}', [IssueController::class, 'adminUpdate']);
    Route::patch('/issues/{issue}/toggle-hidden', [IssueController::class, 'toggleHidden']);
    Route::post('/notifications/campaign', [NotificationController::class, 'storeCampaign']);
});
```

### 3.6 Filtrar usuarios inactivos en feed público (opcional)

Si se quiere que los usuarios archivados no aparezcan en el feed público, se puede modificar la relación `user` en `Issue` o filtrar en el controlador.

En `IssueController@feed`, después de `where('is_hidden', false)`:

```php
->whereHas('user', function ($q) {
    $q->where('is_active', true);
})
```

O agregarlo en las relaciones eager-loaded.

---

## 🔘 Fase 4 — Verificación Real de Códigos de Invitación

### 4.1 Crear endpoint público para verificar código

**Archivo**: `app/Http/Controllers/InvitationCodeController.php`

**Cambio**: Agregar método `verify()`.

```php
/**
 * Verify an invitation code.
 * POST /api/invitation-codes/verify
 */
public function verify(Request $request)
{
    $validated = $request->validate([
        'code' => 'required|string|max:20',
    ]);

    $code = InvitationCode::where('code', $validated['code'])
        ->where('is_active', true)
        ->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        })
        ->first();

    if (!$code) {
        return response()->json([
            'valid' => false,
            'message' => 'Código de invitación inválido o expirado.'
        ], 422);
    }

    if ($code->max_uses && $code->used_count >= $code->max_uses) {
        return response()->json([
            'valid' => false,
            'message' => 'Este código de invitación ha alcanzado su límite de usos.'
        ], 422);
    }

    return response()->json([
        'valid'   => true,
        'message' => 'Código válido.',
        'role_id' => $code->role_id,
        'role'    => $code->role->name ?? null,
    ]);
}
```

### 4.2 Registrar ruta

**Archivo**: `routes/api.php`

**Cambio**: Agregar ruta dentro o fuera de auth (dependiendo de si queremos que solo usuarios autenticados puedan verificar):

```php
// Público (cualquiera puede verificar un código antes de registrarse)
Route::post('/invitation-codes/verify', [InvitationCodeController::class, 'verify']);
```

### 4.3 Actualizar el frontend

El frontend (`worker-registration.tsx`) debe cambiar su verificación mock para llamar a este endpoint en lugar de solo verificar `invitationCode.trim().length > 3`.

---

## 🛡️ Fase 5 — Seguridad Adicional

### 5.1 Endpoint `GET /api/users` protegido

**Archivo**: `routes/api.php` — línea 185

**Cambio**: Agregar middleware de autenticación.

```php
Route::middleware('auth:api')->group(function () {
    Route::get('users', [UserController::class, 'index']);
});
```

O, si se quiere que solo admins vean todos los usuarios:

```php
Route::middleware(['auth:api', 'role:Admin'])->group(function () {
    Route::get('users', [UserController::class, 'index']);
});
```

### 5.2 Endpoint `GET /api/notifications` protegido

Actualmente `apiResource('notifications', ...)` está fuera de auth. Debe protegerse.

**Archivo**: `routes/api.php`

```php
Route::middleware('auth:api')->group(function () {
    Route::apiResource('notifications', NotificationController::class)->only(['index', 'show', 'update']);
});
```

---

## 📁 Resumen de Archivos a Modificar/Crear (Backend)

### Archivos a modificar:
| Archivo | Cambios |
|---------|---------|
| `app/Http/Controllers/IssueController.php` | Agregar filtros a `feed()` y `adminIndex()` |
| `app/Http/Controllers/InvitationCodeController.php` | Agregar método `verify()` |
| `app/Http/Controllers/UserController.php` | Agregar método `toggleActive()` |
| `app/Models/User.php` | Agregar `is_active` a `$fillable` |
| `routes/api.php` | Agregar rutas, proteger rutas públicas, arreglar apiResources |

### Archivos a crear:
| Archivo | Propósito |
|---------|-----------|
| `database/migrations/YYYY_MM_DD_HHMMSS_add_is_active_to_users_table.php` | Agregar columna `is_active` a users |

---

## 🔗 Resumen de Nuevos Endpoints

| Método | Endpoint | Auth | Propósito |
|--------|----------|------|-----------|
| `GET` | `/api/issues/feed?search=&user_id=&status_id=&category_id=` | Público | Feed con filtros |
| `POST` | `/api/invitation-codes/verify` | Público | Verificar código de invitación |
| `PATCH` | `/api/admin/users/{user}/toggle-active` | Admin | Archivar/activar usuario |
| `PATCH` | `/api/admin/issues/{issue}/toggle-hidden` | Admin | Archivar/mostrar issue (ya existe) |

---

## 🧪 Pruebas Recomendadas

Después de implementar, probar:

```bash
# 1. Buscar issues por título
curl "https://cityfix-backend-production.up.railway.app/api/issues/feed?search=bache"

# 2. Buscar por usuario
curl "https://cityfix-backend-production.up.railway.app/api/issues/feed?user_id=7"

# 3. Buscar por estado
curl "https://cityfix-backend-production.up.railway.app/api/issues/feed?status_id=1"

# 4. Combinado
curl "https://cityfix-backend-production.up.railway.app/api/issues/feed?search=bache&status_id=1"

# 5. Verificar código de invitación
curl -X POST "https://cityfix-backend-production.up.railway.app/api/invitation-codes/verify" \
  -H "Content-Type: application/json" \
  -d '{"code":"CF-ABC123"}'

# 6. Archivar usuario (admin)
curl -X PATCH "https://cityfix-backend-production.up.railway.app/api/admin/users/7/toggle-active" \
  -H "Authorization: Bearer {token_admin}"

# 7. Verificar que rutas públicas no permitan crear issues sin auth
curl -X POST "https://cityfix-backend-production.up.railway.app/api/issues" \
  -H "Content-Type: application/json" \
  -d '{"title":"test"}'  # Debe devolver 401
```

---

## 📝 Notas Importantes

1. **IDs de estados**: El sistema actual asume:
   - `status_id = 1` → Pendiente/Reportado
   - `status_id = 2` → En Proceso
   - `status_id = 3` → Resuelto

   Si estos IDs cambian, actualizar el frontend y los seeders.

2. **Role names vs role_id**: El `RoleMiddleware` compara por nombre (string), pero el frontend compara por `role_id` (int). Si se crean nuevos roles, mantener consistencia.

3. **Imágenes**: Las URLs de imágenes se generan con `Storage::disk('public')->url()`. El frontend tiene `fixImageUrl()` que reemplaza `localhost` por la URL real del servidor.

4. **Rate limiting**: Considerar agregar rate limiting en los endpoints de búsqueda para evitar abuso.

5. **Índices**: Si se esperan muchos issues, agregar índices compuestos en la tabla `issues` para las columnas frecuentemente filtradas:
   ```sql
   ALTER TABLE issues ADD INDEX idx_feed_filters (is_hidden, status_id, category_id, user_id, created_at);
   ```

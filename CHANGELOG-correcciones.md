# Correcciones y Mejoras - Rama `correcciones-1`

Este documento detalla los cambios realizados en el sistema para solucionar problemas relacionados con la asignación de roles, validaciones faltantes y la protección de rutas.

## 1. Controlador de Asignaciones (`AssignmentController.php`)
- **Problema:** Las funciones `store` y `update` estaban vacías, lo que causaba que Laravel ignorara los campos y produjera errores SQL de campos faltantes.
- **Solución:** Se implementó la validación estricta de datos requeridos (`issue_id`, `worker_id`, `status_id`, `assigned_at`, `notes`) en ambas funciones antes de realizar la creación o actualización.

## 2. Inicialización de Roles (`RoleSeeder.php`)
- **Problema:** El rol de "Citizen" (ciudadano) era creado dinámicamente por `UserSeeder`, pero no existía en el archivo base donde se definen los roles (`RoleSeeder`).
- **Solución:** Se añadió explícitamente el rol `Citizen` al `RoleSeeder.php` para que la base de datos lo tenga disponible por defecto desde la inicialización.

## 3. Registro de Usuarios y Roles por Defecto (`AuthController.php`)
- **Problema:** Al registrarse un usuario común en `AuthController@register`, se le estaba asignando estáticamente el ID de rol "1". Si los seeders corrían correctamente, el rol con ID 1 correspondía al **Admin**, otorgando permisos administrativos a usuarios normales.
- **Solución:** Se actualizó la función `register` para que consulte dinámicamente en la base de datos cuál es el ID del rol "Citizen" y se lo asigne a los nuevos registros, evitando así vulnerabilidades de seguridad.

## 4. Subida de Fotos de Perfil y Teléfonos (`AuthController.php` y `UserController.php`)
- **Problema:** Al crear perfiles (ya sea desde el registro o desde el panel de administración), la API ignoraba el archivo de imagen enviado como `avatar` y el campo de teléfono, ya que no existían reglas de validación para ellos.
- **Solución:**
    - Se agregó validación para los campos `phone` y `avatar` (máximo 2MB, tipo imagen).
    - Se incluyó la lógica para capturar el archivo `avatar`, almacenarlo en la carpeta pública de Storage (`storage/app/public/avatars`) y guardar la ruta correcta en la base de datos.
    - Esta corrección se aplicó a `AuthController@register`, `UserController@store` y `UserController@update`.
    - En el `UserController`, se implementó la lógica completa de los métodos `store` y `update` (que también estaban vacíos o incompletos) e incluyeron validación y hashing de contraseñas si aplica.

## 5. Protección de Usuarios e Interfaz Administrativa (`UserController.php` y `routes/api.php`)
- **Problema:** El CRUD de usuarios (`UserController`) devolvía datos básicos y sus rutas estaban completamente desprotegidas en la API.
- **Solución:**
    - Se modificó la función `index` del `UserController` para que siempre devuelva a los usuarios con la información de su rol adjunta (`User::with('role')->get()`). De esta manera el frontend (el administrador) podrá identificar visualmente quién es `Citizen` y quién es `Worker`.
    - En `routes/api.php`, el recurso `apiResource('users')` fue movido y encapsulado dentro del grupo con **middleware para administradores** (`auth:sanctum` y `role:Admin`). Esto restringe listar, crear o modificar usuarios únicamente a los administradores del sistema.

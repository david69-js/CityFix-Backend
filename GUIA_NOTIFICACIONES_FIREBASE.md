# Guía de Configuración: Notificaciones Push (Firebase)

Esta guía detalla los pasos necesarios para habilitar y verificar las notificaciones push utilizando Firebase Cloud Messaging (FCM).

---

## 1. Configuración del Backend (Laravel)

El backend ya cuenta con el servicio `FcmService.php`, pero requiere configuración:

### A. Obtener credenciales de Firebase
1. Ve a la [Consola de Firebase](https://console.firebase.google.com/).
2. Selecciona tu proyecto -> **Configuración del proyecto** -> **Cuentas de servicio**.
3. Haz clic en **Generar nueva clave privada**. Esto descargará un archivo `.json`.

### B. Instalar credenciales
1. Guarda el archivo descargado en la carpeta `laravel-app/`.
2. Renómbralo a: `firebase-credentials.json`.
3. **IMPORTANTE:** Este archivo contiene llaves secretas. No lo subas a GitHub.

### C. Configurar Variables de Entorno
Edita tu archivo `laravel-app/.env` y añade:
```env
FIREBASE_CREDENTIALS=firebase-credentials.json
```

---

## 2. Configuración del Frontend

Debes registrar un Service Worker en la carpeta pública del frontend.

### A. Crear el Service Worker
Crea el archivo `firebase-messaging-sw.js` en `public/`:

```javascript
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: "TU_API_KEY",
  authDomain: "TU_PROYECTO.firebaseapp.com",
  projectId: "TU_PROYECTO_ID",
  storageBucket: "TU_PROYECTO.appspot.com",
  messagingSenderId: "TU_SENDER_ID",
  appId: "TU_APP_ID"
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((payload) => {
  console.log('[firebase-messaging-sw.js] Mensaje recibido: ', payload);
  const notificationTitle = payload.notification.title;
  const notificationOptions = {
    body: payload.notification.body,
    icon: '/favicon.ico'
  };

  self.registration.showNotification(notificationTitle, notificationOptions);
});
```

### B. Enviar el Token al Backend
Envía el token generado por el navegador al endpoint:
- **Endpoint:** `POST /api/users/fcm-token`
- **Body:** `{ "fcm_token": "TOKEN_AQUÍ" }`

---

## 3. Verificación (Testing)

### Opción A: Vía API (Postman)
Como usuario Admin, envía una notificación general:

- **URL:** `POST /api/notifications/campaign`
- **Body:**
```json
{
  "title": "Prueba de CityFix",
  "message": "¡Funciona!"
}
```

### Opción B: Prueba rápida (Tinker)
Ejecuta esto para probar un token específico:
```bash
php artisan tinker --execute="App\Services\FcmService::sendPush('TOKEN_DEL_USUARIO', 'Título', 'Cuerpo');"
```

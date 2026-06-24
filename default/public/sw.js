// Nombre de la caché (Cambiá la versión cuando hagas cambios grandes en el diseño)
const CACHE_NAME = 'pulso-uno-v1';

// Importar los scripts necesarios de Firebase SDK (Versión modular clásica)
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js');

// Configuración de tu proyecto Firebase (La sacás de la consola de Firebase)
const firebaseConfig = {
    apiKey: "AIzaSy...",
    authDomain: "pulsounoapp.firebaseapp.com",
    projectId: "pulsounoapp",
    storageBucket: "pulsounoapp.appspot.com",
    messagingSenderId: "1234567890",
    appId: "1:1234567890:web:abcdef..."
};

// Inicializar Firebase en segundo plano
firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Manejar las notificaciones en segundo plano (Background)
messaging.onBackgroundMessage((payload) => {
    console.log('[sw.js] Notificación en segundo plano recibida: ', payload);

    const notificationTitle = payload.notification.title || 'PulsoUno';
    const notificationOptions = {
        body: payload.notification.body,
        icon: '/img/icon-192x192.png',
        badge: '/img/badge-72x72.png',
        data: {
            url: payload.data?.url || '/dashboard'
        }
    };

    self.registration.showNotification(notificationTitle, notificationOptions);
});

// Los archivos indispensables que forman el "App Shell" (Estructura de la aplicación)
// ⚠️ IMPORTANTE: Aseguramos que incluyan las rutas relativas correctas
const ASSETS_TO_CACHE = [
  './',
  './login',                      // <-- Guardamos la ruta del login en caché de forma explícita
  './index',                      // <-- Guardamos el index por defecto
  './css/bootstrap.min.css',
  './css/styles.css',             // Tu CSS personalizado
  './javascript/bootstrap.bundle.min.js',
  './javascript/app.js',
  './manifest.json',
  './img/UNO-logo-web.png', 
  './img/favicon.ico', 
  './img/logo.jpg'
];

// =========================================================================
// 1. EVENTO INSTALL: Se ejecuta cuando el navegador detecta la PWA por primera vez
// =========================================================================
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Guardando el App Shell en la caché');
        // Usamos un mapeo para evitar que si un archivo opcional falla, no rompa toda la instalación
        return Promise.all(
            ASSETS_TO_CACHE.map(url => {
                return cache.add(url).catch(err => console.warn(`No se pudo cachear el recurso: ${url}`, err));
            })
        );
      })
      .then(() => self.skipWaiting())
  );
});

// =========================================================================
// 2. EVENTO ACTIVATE: Limpia versiones viejas de caché para que no ocupen espacio
// =========================================================================
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('[Service Worker] Borrando caché antigua:', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// =========================================================================
// 3. EVENTO FETCH: Intercepta todas las peticiones que hace la App al servidor
// =========================================================================
self.addEventListener('fetch', (event) => {
  // Omitir peticiones de Firebase u otros dominios externos
  if (!event.request.url.startsWith(self.location.origin)) return;
  
  // Omitir peticiones que no sean GET (como los POST de login o geolocalización)
  if (event.request.method !== 'GET') return;

  const requestUrl = new URL(event.request.url);

  // CASO A: Peticiones de API o Navegaciones de páginas dinámicas (Ej: /login, /comunidad, /api/...)
  if (requestUrl.pathname.includes('/api/') || event.request.mode === 'navigate') {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          // Si el servidor (XAMPP) responde bien, clonamos y actualizamos la caché
          if (response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // 🚨 ¡CONTINGENCIA OFFLINE! Si XAMPP está apagado, buscamos en la caché.
          console.log('[Service Worker] Servidor inaccesible. Buscando contingencia en caché...');
          return caches.match(event.request).then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            // Si el usuario intentaba navegar a cualquier lado y falló el servidor, le damos el Login offline
            if (event.request.mode === 'navigate') {
              return caches.match('./login');
            }
          });
        })
    );
  } else {
    // CASO B: ARCHIVOS ESTÁTICOS PUROS (Cache-First): Bootstrap, imágenes, JS estático
    event.respondWith(
      caches.match(event.request)
        .then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }
          // Si no está en caché, va a la red y lo guarda dinámicamente
          return fetch(event.request).then((response) => {
            if (response.status === 200) {
              const responseClone = response.clone();
              caches.open(CACHE_NAME).then((cache) => {
                cache.put(event.request, responseClone);
              });
            }
            return response;
          });
        }).catch(() => {
            // Si falla por completo (ej: imágenes no cacheadas sin red), evitamos el crash
            return new Response('Recurso no disponible offline', { status: 503, statusText: 'Service Unavailable' });
        })
    );
  }
});

// =========================================================================
// 4. RASTREO EN SEGUNDO PLANO Y MENSAJERÍA
// =========================================================================
let bgIntervalId = null;

self.addEventListener('message', (event) => {
    if (event.data && event.data.action === 'START_BACKGROUND_TRACKING') {
        iniciarRastreoIninterrumpido();
    } else if (event.data && event.data.action === 'STOP_BACKGROUND_TRACKING') {
        if (bgIntervalId) {
            clearInterval(bgIntervalId);
            bgIntervalId = null;
        }
    }
});

function iniciarRastreoIninterrumpido() {
    if (bgIntervalId) clearInterval(bgIntervalId);

    bgIntervalId = setInterval(() => {
        if ('geolocation' in self.navigator) {
            self.navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Si no hay red, ni intentamos hacer el fetch para ahorrar recursos
                if (!self.navigator.onLine) return;

                const formData = new URLSearchParams();
                formData.append('lat', lat);
                formData.append('lng', lng);

                fetch('/pulsounoapp/pool/actualizar_posicion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                }).catch(err => console.log("Fallo envío en 2do plano:", err));
            }, null, { enableHighAccuracy: true });
        }
    }, 20000); 
}

// Escuchar el evento 'push' enviado desde el servidor
self.addEventListener('push', function(event) {
    let data = { title: 'PulsoUno', body: 'Tenés una nueva actualización académica.' };

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: '/img/logo.jpg',
        badge: '/img/logo.jpg',
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/index'
        }
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Registrar la acción de clic en la notificación
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});
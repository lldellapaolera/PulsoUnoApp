// Nombre de la caché (Podés cambiar la versión cuando hagas cambios grandes en el diseño)
const CACHE_NAME = 'pulso-uno-v1';

// Los archivos indispensables que forman el "App Shell" (Estructura de la aplicación)
const ASSETS_TO_CACHE = [
  './',
  './index.html', // O la ruta raíz que use tu cliente
  '/PulsoUnoApp/asistencia', 
  './css/bootstrap.min.css',
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
        return cache.addAll(ASSETS_TO_CACHE);
      })
      .then(() => self.skipWaiting()) // Fuerza a que se vuelva el SW activo de inmediato
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
    }).then(() => self.clients.claim()) // Toma el control de la app inmediatamente
  );
});

// =========================================================================
// 3. EVENTO FETCH: Intercepta todas las peticiones que hace la App al servidor
// =========================================================================
self.addEventListener('fetch', (event) => {
  const requestUrl = new URL(event.request.url);

  // ESTRATEGIA PARA LA API (Network-First): Si la petición va dirigida al controlador de KumbiaPHP
  if (requestUrl.pathname.includes('/api/')) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          // Si el servidor responde bien, guardamos/actualizamos una copia en caché
          if (response.status === 200) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then((cache) => {
              cache.put(event.request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // ¡MAGIA OFFLINE! Si no hay internet, busca el último JSON guardado de esa consulta de API
          console.log('[Service Worker] Sin conexión. Recuperando datos de la API desde la caché');
          return caches.match(event.request);
        })
    );
  } else {
    // ESTRATEGIA PARA ARCHIVOS ESTÁTICOS (Cache-First): Bootstrap, imágenes, html, js
    event.respondWith(
      caches.match(event.request)
        .then((cachedResponse) => {
          // Si ya está en la caché del celu, lo devuelve al toque sin gastar datos
          if (cachedResponse) {
            return cachedResponse;
          }
          // Si no estaba (ej. un PDF de apuntes nuevo), lo va a buscar a la red
          return fetch(event.request);
        })
    );
  }
});

// 🔄 código interno dentro de sw.js

let bgIntervalId = null;

// Escuchamos las órdenes de la app principal
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

    // El Service Worker va a intentar correr esta rutina cada 20 segundos de fondo
    bgIntervalId = setInterval(() => {
        // Usamos la API de geolocalización desde el entorno global de self si está disponible
        // O ejecutamos un auto-despertar sincrónico
        if ('geolocation' in self.navigator) {
            self.navigator.geolocation.getCurrentPosition((position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                // Armamos el envío directo al backend usando rutas absolutas
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
# 🛰️ PulsoUno - Sistema de Gestión e Infraestructura Universitaria (PWA)

¡Bienvenido al repositorio de **PulsoUno**! Este proyecto ha sido transformado en una **Progressive Web App (PWA)** funcional, adaptada para cumplir con los estándares académicos más exigentes y resolver problemas críticos de conectividad intermitente o nula (Modo Offline-First).

Este documento sirve como guía técnica para todo el equipo de cara a la presentación y defensa del proyecto.

---

## 🚀 Funcionalidades Clave Añadidas

1. **Arquitectura Offline-First (Resiliencia de Red):**
   * **Login Protegido:** El sistema detecta mediante JavaScript nativo (`navigator.onLine`) si el dispositivo tiene señal. Si no hay red, congela el formulario de ingreso y muestra un banner de advertencia dinámico de Bootstrap para evitar peticiones colgadas (`ERR_FAILED`) o errores de servidor.
   * **Banner de Estado Global:** Dentro de la sesión activa, si el usuario cruza una zona sin señal, se activa un banner superior estético que avisa: *"Estás navegando en Modo Offline (Sin conexión). Viendo datos locales mínimos"*.

2. **Rastreo y Sincronización en Segundo Plano:**
   * Implementación de un sistema de tracking ininterrumpido a través de mensajería del Service Worker que permite capturar datos de geolocalización y preparar los envíos al backend de forma eficiente.

3. **Notificaciones Push e Integración con Firebase (FCM):**
   * Vinculación con el SDK de **Firebase Cloud Messaging** para la recepción de alertas académicas y notificaciones en segundo plano, generando un token único sincronizado directamente con nuestro backend en KumbiaPHP.

4. **Instalación Nativa (PWA):**
   * La app cumple con los requisitos del App Shell, permitiendo a los usuarios "Instalar" PulsoUno directamente en la pantalla de inicio de sus dispositivos Android, iOS o Escritorio sin intermediarios de tiendas (Play Store/App Store).

---

## 🛠️ Tecnologías y Herramientas Utilizadas

* **Backend / Servidor:** KumbiaPHP Framework (MVC), PHP, XAMPP (Apache/MySQL).
* **Frontend / Interfaz:** HTML5, CSS3 personalizado, Bootstrap 5.3.3 (Mobile-First).
* **Core PWA:** JavaScript Moderno (Vanilla JS), Service Worker API, Cache Storage API, Web App Manifest.
* **Mensajería Push:** Firebase SDK v10.8 (Compat), Firebase Cloud Messaging (FCM).

---

## 🧠 Técnicas de Ingeniería Web Implementadas

### 1. Gestión del Ciclo de Vida del Service Worker (`sw.js`)
* **Estrategia Cache-First para Estáticos:** Recursos como Bootstrap, íconos y fuentes se sirven instantáneamente desde la caché local del dispositivo, optimizando el rendimiento y ahorrando datos.
* **Estrategia Network-First con Fallback para Navegación:** Para las vistas dinámicas y controladores de KumbiaPHP (como `/login` o `/api/`), el Service Worker intenta primero buscar datos actualizados en la red. Si el servidor (XAMPP) está apagado o inaccesible, el bloque `.catch()` intercepta el fallo y sirve la última versión almacenada en caché.
* **Evasión de Falsos Positivos en Android (Cold Start):** Se programó un retraso inicial (`setTimeout`) y una prueba de fuego (`fetch` real al manifest) en el inicio de la app para evitar que los retrasos del chip de red en dispositivos móviles bloqueen el login por error al abrir la app desde cero.

### 2. Optimización del Almacenamiento (App Shell)
* Uso de un mapeo controlado en la instalación del Service Worker mediante `Promise.all` para asegurar que fallos menores en recursos opcionales no rompan el proceso de instalación y activación de la PWA.

---

## 🎯 Preguntas Clave para la Defensa de Mañana (Machete Técnico)

* **¿Por qué la app no se rompe si apagamos XAMPP?** Porque el `sw.js` intercepta la petición del navegador antes de que salga a la red. Al fallar el fetch físico al puerto local, el Service Worker recurre al método `caches.match()` y sirve el HTML/CSS/JS que guardó previamente durante la instalación.
* **¿Qué es la mejora progresiva en nuestro proyecto?** Significa que si un usuario entra desde un navegador viejo sin soporte para Service Workers, PulsoUno sigue funcionando perfectamente como un sitio web tradicional. Pero si entra desde un navegador moderno, el sistema "mejora" ofreciendo instalación y soporte offline.
* **¿Por qué usamos HTTPS / Contexto Seguro?** Porque los Service Workers manejan información sensible (interceptan todo el tráfico de la app). Por seguridad del protocolo, los navegadores bloquean estas APIs a menos que se corra bajo `https://` o en entornos de desarrollo sobre `localhost`.
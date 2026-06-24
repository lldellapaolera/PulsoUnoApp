# 🛰️ PulsoUno - Sistema de Gestión e Infraestructura Universitaria (PWA)

¡Bienvenido al repositorio de **PulsoUno**! Este proyecto ha sido transformado en una **Progressive Web App (PWA)** funcional, adaptada para cumplir con los estándares académicos más exigentes y resolver problemas críticos de conectividad intermitente o nula (Modo Offline-First).

Este documento sirve como guía técnica para todo el equipo de cara a la presentación y defensa del proyecto.

## 🎯 Origen del Proyecto: Motivación e Impacto Institucional

### 1. ¿Qué motivó la idea?
La comunidad académica de la **Universidad Nacional del Oeste (UNO)** se enfrenta diariamente a desafíos de conectividad dentro y fuera de los campus. La idea de **PulsoUno** nace de la necesidad de democratizar el acceso a la información universitaria. Al movernos en entornos de conectividad intermitente (zonas de baja señal, trayectos en transporte público o saturación de las redes Wi-Fi institucionales en horarios pico), las plataformas tradicionales web suelen quedar inaccesibles. PulsoUno resuelve esto transformando el acceso en una experiencia continua, liviana y disponible en el bolsillo de cada estudiante y docente, sin importar el estado de su red.

### 2. Limitaciones del Campus Actual (SIU-Guaraní) y Oportunidades de Mejora
Si bien el SIU-Guaraní es la herramienta estándar para la gestión académica, presenta ciertas falencias que este proyecto busca subsanar desde una perspectiva UX/UI y Mobile-First:
* **Falta de Adaptabilidad Dinámica (Offline):** Si el servidor central experimenta alta demanda (como en fechas de inscripción a materias) o el alumno pierde conexión por un segundo, la navegación se interrumpe por completo perdiendo los datos en pantalla.
* **Ausencia de Notificaciones Nativas:** El SIU-Guaraní depende del correo electrónico tradicional para alertas importantes. PulsoUno introduce alertas push instantáneas (gracias a Firebase) para cambios de aula, suspensiones por fuerza mayor o publicación de notas.
* **Interfaz Compleja para el Día a Día:** Consultar un horario, un aula o el estado de una asistencia requiere múltiples clics en sistemas tradicionales. PulsoUno funciona como un "App Shell" simplificado para el uso cotidiano en el pasillo de la facultad.

### 3. Beneficios Directos para Alumnos y Profesores
* **Para Estudiantes:** Acceso inmediato a sus datos de cursada y herramientas de asistencia sin consumir datos móviles innecesarios, gracias a que los recursos estáticos se quedan viviendo en el almacenamiento local del teléfono.
* **Para Profesores:** Una vía de comunicación directa y ágil con sus comisiones. Capacidad de emitir alertas tempranas que impactan de inmediato en los dispositivos de los alumnos inscritos.

---

## 🔒 Cuestiones de Seguridad y Buenas Prácticas

El desarrollo bajo el estándar PWA y el framework KumbiaPHP nos obligó a estructurar el proyecto bajo estrictas normas de seguridad digital:
* **Contexto Seguro Obligatorio:** El uso de Service Workers exige entornos de producción bajo protocolo **HTTPS**. Esto garantiza que los datos interceptados y cacheados (como tokens de sesión o geolocalización) viajen y se almacenen de forma cifrada, previniendo ataques de intermediario (*Man-in-the-Middle*).
* **Control de Sesiones Híbrido:** El App Shell valida la identidad mediante `Auth::is_valid()` en el servidor. Si el usuario pierde la conexión, el entorno visual se mantiene operativo pero los formularios sensibles quedan congelados en el cliente hasta que se reestablezca un canal seguro con la base de datos MySQL.
* **Aislamiento de Recursos:** La API de caché almacena estrictamente archivos estáticos de la interfaz y respuestas JSON analizadas, evitando persistir datos confidenciales de forma vulnerable en el navegador.

---

## 🔮 Hoja de Ruta: Próximas Características y Demandas Futuras

Atendiendo a los requerimientos de la Universidad y a las sugerencias de la comunidad estudiantil, se contemplan las siguientes extensiones para futuras versiones del sistema:

* **Sincronización en Segundo Plano Completa (Background Sync API):** Permitir que un alumno pueda registrar una solicitud de trámite o marcar un presentismo estando offline, y que el navegador envíe la petición automáticamente al servidor de KumbiaPHP apenas detecte el retorno de la señal, sin que el usuario deba reintentarlo manualmente.
* **Integración de Mapas Offline para los Campus:** Integrar mapas vectoriales base de las sedes de la universidad dentro del script del Service Worker, permitiendo buscar aulas o dependencias internas sin necesidad de descargar cartografía de internet en el momento.
* **Modo de Ahorro de Datos Extremo:** Una opción en la configuración de la app para que los estudiantes con planes de datos limitados puedan deshabilitar la descarga de imágenes o recursos pesados, priorizando únicamente texto plano institucional y alertas de texto.
* **Panel de Autenticación Biométrica (WebAuthn):** Permitir el acceso al login mediante la huella dactilar o reconocimiento facial nativo del celular del estudiante, elevando la seguridad y eliminando la fricción de recordar contraseñas complejas en entornos de movilidad.

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


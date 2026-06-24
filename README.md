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
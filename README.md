
# PulsoUno 🚀
> **Sistema Institucional del Oeste — Gestión Académica, Presentismo Offline y Movilidad Comunitaria**

PulsoUno es una aplicación web progresiva (**PWA**) diseñada específicamente para modernizar y agilizar la vida universitaria en la **Universidad Nacional del Oeste (UNO)**. La plataforma unifica la gestión académica diaria de docentes y alumnos con un sistema solidario de movilidad comunitaria, permitiendo un funcionamiento fluido incluso dentro de las aulas sin conectividad.

---

## 🗺️ Arquitectura del Sistema

El proyecto implementa una arquitectura desacoplada basada en el patrón **MVC (Modelo-Vista-Controlador)**:

* **Backend (API Restful):** Desarrollado sobre **KumbiaPHP v1.0 beta** bajo **PHP 8.0.30**, configurado en un entorno de servidor robusto administrado mediante **Virtualmin/Webmin**. Utiliza el ORM nativo `ActiveRecord` para la interacción óptima con **MySQL 8.0**.
* **Frontend (App Shell PWA):** Un cliente estático ultraliviano construido con **Bootstrap v5.3.3** y JavaScript vanilla. Está diseñado bajo el enfoque *Mobile-First* para optimizar la usabilidad en dispositivos móviles mediante navegación ergonómica en la zona inferior (`fixed-bottom`).

---

## 🛠️ Características Principales

### 📊 Módulo Académico y Asistencia (Profesores / Alumnos)
* **Toma de Asistencia Eficiente:** Los docentes registran el presentismo de su comisión con interfaces rápidas basadas en componentes táctiles nativos (`form-switch`).
* **Persistencia y Sincronización Offline:** El *Service Worker* captura y retiene las asistencias mediante almacenamiento local (`IndexedDB` / `localStorage`) si no hay señal en el aula, sincronizándolas automáticamente por lotes en segundo plano al recuperar la conexión.
* **Panel Estadístico del Alumno:** Cálculo automatizado en tiempo real del porcentaje de presentismo por materia con alertas preventivas al descender del límite de regularidad (75%).
* **Simulador de Cursada y Correlatividades:** Renderizado condicional del árbol de correlatividades según actas de examen, permitiendo al alumno saber exactamente qué asignaturas está habilitado para cursar o rendir.

### 🚗 Módulo de Movilidad Comunitaria ("UNO-Pool")
* **Red de Acompañantes y Conductores:** Configuración de perfiles de viaje para alumnos y profesores de la comunidad ("Ofrece lugar" o "Busca viaje"), detallando rutas de tránsito y horarios de cursada.
* **Algoritmo de Coincidencias Geográficas:** Cruce inteligente de datos de trayectos interurbanos en la zona oeste (ej. Merlo, Padua, Ituzaingó) priorizando compatibilidad horaria y cercanía de rutas.
* **Enlace de Contacto Seguro:** Integración con APIs de mensajería instantánea para la coordinación rápida entre compañeros de forma privada.

### 📂 Repositorio de Documentación Estática
* **Acceso Remoto sin Señal:** Caché selectiva basada en estrategias *Cache-First* para almacenar programas de cátedra y apuntes en formato PDF directamente en el dispositivo móvil.

---

## 📂 Estructura del Proyecto


kumbiaphp/
├── default/
│   └── app/
│       ├── controllers/
│       │   └── api_controller.php      # Endpoints JSON principales (Asistencia, Pool, Notas)
│       └── models/
│           ├── materia.php             # Lógica del árbol de correlatividades
│           ├── asistencia.php          # Lógica del presentismo docente
│           └── viaje.php               # Motor de coincidencias de UNO-Pool
└── public/                             # Raíz del servidor web (App Shell PWA)
    ├── css/
    │   └── bootstrap.min.css           # Framework UI Bootstrap v5.3.3
    ├── js/
    │   ├── bootstrap.bundle.min.js
    │   └── app.js                      # Lógica cliente y registro del Service Worker
    ├── manifest.json                   # Configuración de instalación PWA
    ├── sw.js                           # Service Worker y estrategias de caché offline
    └── index.html                      # Punto de entrada de la aplicación
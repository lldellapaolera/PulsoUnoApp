/**
 * PulsoUno - Motor de Lógica del Cliente PWA
 * Ubicación: public/javascript/app.js
 */

// 1. REGISTRO DEL SERVICE WORKER
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    // Apuntamos al sw.js que está en la raíz de public
    navigator.serviceWorker.register('../sw.js')
      .then(reg => console.log('[PWA] Service Worker registrado con éxito', reg))
      .catch(err => console.error('[PWA] Error al registrar el Service Worker', err));
  });
}



// 2. CONFIGURACIÓN GENERAL DE LA API
// Ajustamos la ruta para que le pegue correctamente a la API desde cualquier vista de KumbiaPHP
const API_URL = window.location.origin + '/PulsoUnoApp/api'; 
// (Nota: Ajustá '/pulso-uno' si el nombre de tu carpeta en el servidor local es distinto)
//const API_URL = '../api'; // Ruta relativa hacia tu ApiController de KumbiaPHP

// =========================================================================
// 3. CAPA DE PERSISTENCIA Y SINCRONIZACIÓN OFFLINE (Módulo de Asistencia)
// =========================================================================

/**
 * Captura la asistencia tomada por el profesor y evalúa el estado de la red
 */
function procesarAsistenciaDocente(comisionId, fecha, listaAsistencias) {
  const payload = {
    comision_id: comisionId,
    fecha: fecha,
    asistencias_lista: listaAsistencias // Array de objetos { alumno_id: X, presente: 1/0 }
  };

  // Verificamos si el navegador tiene acceso a internet en este momento
  if (navigator.onLine) {
    console.log('[API] Conexión detectada. Enviando asistencia a KumbiaPHP...');
    enviarAlServidor(payload);
  } else {
    console.warn('[PWA] Sin señal en el aula. Guardando en almacenamiento local...');
    guardarEnBufferLocal(payload);
    mostrarAlertaUI('Asistencia guardada localmente. Se sincronizará sola al recuperar señal.', 'warning');
  }
}

/**
 * Realiza la petición Fetch real hacia nuestro controlador en KumbiaPHP 1.2.0
 */
function enviarAlServidor(data) {
  fetch(`${API_URL}/guardar_asistencia`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
  })
  .then(response => response.json())
  .then(res => {
    if (res.status === 'success') {
      console.log('[API] Sincronización exitosa:', res.message);
      mostrarAlertaUI('Asistencia guardada en el servidor correctamente.', 'success');
    } else {
      console.error('[API] Error parcial en el backend:', res.message);
    }
  })
  .catch(err => {
    console.error('[API] Fallo de red en el Fetch, re-enviando al búfer local:', err);
    guardarEnBufferLocal(data);
  });
}

/**
 * Guarda el lote de asistencias en el LocalStorage del teléfono si no hay internet
 */
function guardarEnBufferLocal(data) {
  // Recuperamos lo que ya esté en el búfer o creamos un array vacío
  let buffer = JSON.parse(localStorage.getItem('pulso_asistencias_buffer')) || [];
  
  // Añadimos el nuevo lote de faltas tomadas offline
  buffer.push(data);
  
  localStorage.setItem('pulso_asistencias_buffer', JSON.stringify(buffer));
}

/**
 * Sincroniza por lotes todo lo acumulado en el almacenamiento local al recuperar internet
 */
function vaciarBufferAServidor() {
  let buffer = JSON.parse(localStorage.getItem('pulso_asistencias_buffer')) || [];
  
  if (buffer.length === 0) return;

  console.log(`[PWA] Conexión recuperada. Sincronizando ${buffer.length} lote(s) pendientes...`);
  
  // Enviamos cada lote retenido uno por uno de forma asíncrona
  buffer.forEach((lote, index) => {
    fetch(`${API_URL}/guardar_asistencia`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(lote)
    })
    .then(response => response.json())
    .then(res => {
      if (res.status === 'success') {
        // Removemos este lote específico del búfer local
        buffer.splice(index, 1);
        localStorage.setItem('pulso_asistencias_buffer', JSON.stringify(buffer));
        console.log('[PWA] Lote sincronizado y limpiado del almacenamiento local.');
      }
    })
    .catch(err => console.error('[PWA] Error reintentando enviar lote diferido:', err));
  });
}

// =========================================================================
// 4. ESCUCHADORES DE EVENTOS DE RED (Detectores de Estado del Celular)
// =========================================================================

// Escucha cuando el dispositivo vuelve a tener señal (Wi-Fi o datos)
window.addEventListener('online', () => {
  console.log('[Red] Dispositivo Online.');
  mostrarAlertaUI('Conexión restablecida. Sincronizando datos pendientes...', 'info');
  vaciarBufferAServidor();
});

// Escucha si el dispositivo se queda sin red
window.addEventListener('offline', () => {
  console.warn('[Red] Dispositivo Offline. Cambiando a modo local protegido.');
  mostrarAlertaUI('Modo fuera de línea activado. Tus cambios se guardarán de forma segura en el dispositivo.', 'danger');
});

// Helper simple para inyectar alertas visuales de Bootstrap 5 en la pantalla
function mostrarAlertaUI(mensaje, tipo) {
  // Esto asume que tenés un contenedor con id="alert-container" en tu index.html
  const container = document.getElementById('alert-container');
  if (!container) return;

  const alerta = document.createElement('div');
  alerta.className = `alert alert-${tipo} alert-dismissible fade show shadow-sm mt-2`;
  alerta.role = 'alert';
  alerta.innerHTML = `
    <strong>PulsoUno:</strong> ${mensaje}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  container.appendChild(alerta);

  // Auto-remover la alerta a los 4 segundos para no entorpecer la pantalla
  setTimeout(() => {
    alerta.classList.remove('show');
    alerta.remove();
  }, 4000);
}

// =========================================================================
// 5. CONTROL DE ESTADO DE CONEXIÓN EN LA HOME
// =========================================================================
document.addEventListener('DOMContentLoaded', () => {
    const statusCard = document.getElementById('pwa-status-card');
    const statusIcon = document.getElementById('pwa-status-icon');
    const statusTitle = document.getElementById('pwa-status-title');
    const statusDesc = document.getElementById('pwa-status-desc');

    if (!statusCard) return; // Si no estamos en la Home, frena la ejecución

    function actualizarIndicadorRedHome() {
        if (navigator.onLine) {
            statusIcon.style.setProperty('background-color', '#28a74515', 'important');
            statusIcon.innerHTML = '📱';
            statusTitle.textContent = 'PulsoUno está lista';
            statusTitle.className = 'fw-bold mb-0 text-dark';
            statusDesc.textContent = 'La app ya se descargó en tu dispositivo y funciona sin internet.';
        } else {
            statusIcon.style.setProperty('background-color', '#dc354515', 'important');
            statusIcon.innerHTML = '⚠️';
            statusTitle.textContent = 'Modo Fuera de Línea';
            statusTitle.className = 'fw-bold mb-0 text-danger';
            statusDesc.textContent = 'Navegando sin conexión. Podés seguir usando tus datos guardados.';
        }
    }

    // Escuchamos los cambios de red globales
    window.addEventListener('online', actualizarIndicadorRedHome);
    window.addEventListener('offline', actualizarIndicadorRedHome);
    
    // Ejecutamos una comprobación inicial al cargar la pantalla
    actualizarIndicadorRedHome();
});
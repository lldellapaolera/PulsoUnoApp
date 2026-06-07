<?php

class PoolController extends AppController {

    public $usuario_actual_id;
    public $solicitudes_recibidas;
    public $mis_notificaciones;
    public $solicitudes_enviadas;
    public $mis_viajes_publicados;
    public $mis_viajes_coordinados;

    protected function before_filter() {
        if (!Auth::is_valid()) {
            if (Input::isAjax()) {
                View::select(null, 'json');
                $this->data = ['error' => 'Sesión expirada.'];
                return false;
            }
            Redirect::to('login');
            return false;
        }
    }

    public function index() {
        $sesion = Auth::get_active_identity();
        $this->usuario_actual_id = (int)$sesion['id'];

        // 1. Solicitudes pendientes que le hicieron a este conductor (Lo que ya tenías)
        $solicitudModel = new UnoPoolSolicitud();
        $this->solicitudes_recibidas = $solicitudModel->find_all_by_sql("
            SELECT s.*, u.nombre as pasajero_nombre, p.origen, p.destino, p.hora
            FROM uno_pool_solicitud s
            INNER JOIN uno_pool p ON s.viaje_id = p.id
            INNER JOIN usuarios u ON s.pasajero_id = u.id
            WHERE p.usuario_id = {$this->usuario_actual_id} AND s.estado = 'pendiente'
            ORDER BY s.created_at DESC
        ");

        // 2. NUEVO: Mis Viajes Publicados (Por si quiero eliminarlos)
        $poolModel = new UnoPool();
        $this->mis_viajes_publicados = $poolModel->find("conditions: usuario_id = {$this->usuario_actual_id}", "order: hora ASC");

        // 3. NUEVO: Mis Viajes Coordinados (Viajes de otros donde a mí me aceptaron)
        $this->mis_viajes_coordinados = $solicitudModel->find_all_by_sql("
            SELECT s.id as solicitud_id, p.origen, p.destino, p.hora, p.dias, u.nombre as conductor_nombre, u.telefono as conductor_telefono
            FROM uno_pool_solicitud s
            INNER JOIN uno_pool p ON s.viaje_id = p.id
            INNER JOIN usuarios u ON p.usuario_id = u.id
            WHERE s.pasajero_id = {$this->usuario_actual_id} AND s.estado = 'aceptado'
            ORDER BY p.hora ASC
        ");

        // 4. Notificaciones sin leer (Lo que armamos en el paso anterior)
        $notifModel = new UnoNotificaciones();
        $this->mis_notificaciones = $notifModel->find("conditions: usuario_id = {$this->usuario_actual_id} AND leido = 0", "order: id DESC");
    }

    // public function index() {
    //     $sesion = Auth::get_active_identity();
    //     $this->usuario_actual_id = (int)$sesion['id'];

    //     // Traemos las solicitudes pendientes que le hicieron a los viajes de este conductor
    //     $solicitudModel = new UnoPoolSolicitud();
    //     $this->solicitudes_recibidas = $solicitudModel->find_all_by_sql("
    //         SELECT s.*, u.nombre as pasajero_nombre, p.origen, p.destino, p.hora
    //         FROM uno_pool_solicitud s
    //         INNER JOIN uno_pool p ON s.viaje_id = p.id
    //         INNER JOIN usuarios u ON s.pasajero_id = u.id
    //         WHERE p.usuario_id = {$this->usuario_actual_id} AND s.estado = 'pendiente'
    //         ORDER BY s.created_at DESC
    //     ");

    //     // Adentro de PoolController::index()...
    //     $sesion = Auth::get_active_identity();
    //     $this->usuario_actual_id = (int)$sesion['id'];

    //     // Buscamos las notificaciones sin leer de este usuario
    //     $notifModel = new UnoNotificaciones();
    //     $this->mis_notificaciones = $notifModel->find("conditions: usuario_id = {$this->usuario_actual_id} AND leido = 0", "order: id DESC");
    // }

    /**
     * API: Buscar viajes e incluir el estado de la solicitud de seguridad
     */
    public function buscar() {
        View::select(null, 'json');
        
        $tipo = Input::get('tipo') ?: 'acompañante';
        $localidad = filter_var(Input::get('localidad')?:'', FILTER_SANITIZE_STRING);
        $sesion = Auth::get_active_identity();
        $usuario_actual_id = (int)$sesion['id'];

        $poolModel = new UnoPool();
        $buscar_tipo = ($tipo === 'acompañante') ? 'conductor' : 'acompañante';

        // Consulta avanzada: Trae los viajes y hace un LEFT JOIN con las solicitudes del usuario actual
        $resultados = $poolModel->find_all_by_sql("
            SELECT p.*, u.nombre as usuario_nombre, u.telefono as usuario_telefono,
                   s.estado as solicitud_estado
            FROM uno_pool p
            INNER JOIN usuarios u ON p.usuario_id = u.id
            LEFT JOIN uno_pool_solicitud s ON p.id = s.viaje_id AND s.pasajero_id = {$usuario_actual_id}
            WHERE p.tipo = '{$buscar_tipo}' 
              AND p.localidad LIKE '%{$localidad}%'
              AND p.usuario_id != {$usuario_actual_id}
            ORDER BY p.hora ASC
        ");

        $viajes = [];
        foreach ($resultados as $r) {
            $viajes[] = [
                'id' => $r->id,
                'nombre' => $r->usuario_nombre,
                // OJO DE SEGURIDAD: Solo enviamos el teléfono si está aceptado
                'telefono' => ($r->solicitud_estado === 'aceptado') ? $r->usuario_telefono : '',
                'tipo' => $r->tipo,
                'origen' => $r->origen,
                'destino' => $r->destino,
                'hora' => date("H:i", strtotime($r->hora)) . ' hs',
                'dias' => $r->dias,
                'detalles' => $r->detalles ?: 'Sin detalles adicionales.',
                // Si el estado es aceptado, enviamos las coordenadas; si no, van en nulo por seguridad
                'latitud' => ($r->solicitud_estado === 'aceptado') ? $r->latitud : null,
                'longitud' => ($r->solicitud_estado === 'aceptado') ? $r->longitud : null,
                'solicitud_estado' => $r->solicitud_estado ?: 'ninguna'
            ];
        }

        $this->data = $viajes;
    }

    public function solicitar_viaje() {
        View::select(null, 'json');
        $sesion = Auth::get_active_identity();
        $pasajero_id = (int)$sesion['id'];
        $pasajero_nombre = $sesion['nombre']; // Asumiendo que el nombre está en la sesión

        if (Input::hasPost('viaje_id')) {
            $viaje_id = (int)Input::post('viaje_id');
            
            $solicitud = new UnoPoolSolicitud();
            $solicitud->viaje_id = $viaje_id;
            $solicitud->pasajero_id = $pasajero_id;
            $solicitud->estado = 'pendiente';

            if ($solicitud->save()) {
                // 🔍 Buscamos quién es el dueño del viaje para notificarlo
                $viaje = (new UnoPool())->find_first($viaje_id);
                if ($viaje) {
                    $notificacion = new UnoNotificaciones();
                    $notificacion->usuario_id = $viaje->usuario_id; // ID del Conductor
                    $notificacion->mensaje = "🚗 " . $pasajero_nombre . " te envió una solicitud para unirse a tu viaje de las " . date('H:i', strtotime($viaje->hora)) . " hs.";
                    $notificacion->leido = 0;
                    $notificacion->save();
                }

                $this->data = ['status' => 'success', 'message' => 'Solicitud enviada al conductor.'];
            } else {
                $this->data = ['status' => 'error', 'message' => 'Ya enviaste una solicitud para este viaje.'];
            }
            return;
        }
        $this->data = ['status' => 'error', 'message' => 'ID inválido.'];
    }

    /**
     * ACCIÓN POST: El conductor acepta o rechaza (Notifica al Acompañante)
     */
    public function responder_solicitud() {
        View::select(null, null);
        $sesion = Auth::get_active_identity();

        if (Input::hasPost('solicitud_id') && Input::hasPost('accion')) {
            $solicitud_id = (int)Input::post('solicitud_id');
            $accion = Input::post('accion'); // 'aceptar' o 'rechazar'

            $solicitudModel = new UnoPoolSolicitud();
            $soli = $solicitudModel->find_first($solicitud_id);

            if ($soli) {
                // Validación de seguridad: el viaje debe ser del conductor logueado
                $viaje = (new UnoPool())->find_first($soli->viaje_id);
                if ($viaje && (int)$viaje->usuario_id === (int)$sesion['id']) {
                    
                    $nuevo_estado = ($accion === 'aceptar') ? 'aceptado' : 'rechazado';
                    $soli->estado = $nuevo_estado;
                    
                    if ($soli->update()) {
                        // 🔔 NOTIFICACIÓN AL PASAJERO: Avisamos el resultado del veredicto
                        $notificacion = new UnoNotificaciones();
                        $notificacion->usuario_id = $soli->pasajero_id; // ID del Acompañante
                        
                        if ($nuevo_estado === 'aceptado') {
                            $notificacion->mensaje = "✅ ¡Buenas noticias! " . $sesion['nombre'] . " aceptó tu solicitud de viaje desde " . $viaje->origen . ". Ya podés ver su GPS y coordinar.";
                        } else {
                            $notificacion->mensaje = "❌ Tu solicitud de viaje con " . $sesion['nombre'] . " para las " . date('H:i', strtotime($viaje->hora)) . " hs fue rechazada.";
                        }
                        
                        $notificacion->leido = 0;
                        $notificacion->save();

                        Flash::valid("Solicitud procesada con éxito.");
                    } else {
                        Flash::error("No se pudo actualizar la solicitud.");
                    }
                } else {
                    Flash::error("Acceso no autorizado.");
                }
            }
        }
        Redirect::to('pool');
    }

    /**
     * Acción POST para guardar una nueva oferta/búsqueda de viaje
     */
    public function publicar() {
        View::select(null, null);
        $sesion = Auth::get_active_identity();

        if (Input::hasPost('tipo') && Input::hasPost('localidad') && Input::hasPost('origen') && Input::hasPost('destino') && Input::hasPost('hora')) {
            $pool = new UnoPool();
            $pool->usuario_id = $sesion['id'];
            $pool->tipo = Input::post('tipo');
            $pool->localidad = filter_var(Input::post('localidad'), FILTER_SANITIZE_STRING);
            $pool->origen = filter_var(Input::post('origen'), FILTER_SANITIZE_STRING);
            $pool->destino = filter_var(Input::post('destino'), FILTER_SANITIZE_STRING);
            $pool->hora = Input::post('hora');
            
            // Unimos los días elegidos del checkbox en un solo string
            $dias_array = Input::post('dias') ?: [];
            $pool->dias = count($dias_array) > 0 ? implode(', ', $dias_array) : 'A coordinar';
            
            $pool->detalles = filter_var(Input::post('detalles'), FILTER_SANITIZE_STRING);

            if ($pool->save()) {
                Flash::valid('¡Viaje publicado con éxito!');
                Redirect::to('pool'); // O 'pool/index' según tu enrutador
            } else {
                Flash::error('No se pudo publicar el viaje.');
                Redirect::to('pool');
            }
            
        }
        Redirect::to('pool');
    }

    /**
     * API endpoint para actualizar la ubicación actual vía POST (AJAX)
     */
    public function actualizar_posicion() {
        View::select(null, 'json');
        $sesion = Auth::get_active_identity();
        $usuario_id = (int)$sesion['id'];

        if (Input::hasPost('lat') && Input::hasPost('lng')) {
            $lat = (float) Input::post('lat');
            $lng = (float) Input::post('lng');

            $pool = new UnoPool();
            
            // Buscamos la publicación activa más reciente de este usuario para actualizarla
            $viajeActivo = $pool->find_first("conditions: usuario_id = {$usuario_id}", "order: id DESC");

            if ($viajeActivo) {
                $viajeActivo->latitud = $lat;
                $viajeActivo->longitud = $lng;
                $viajeActivo->updated_at = date('Y-m-d H:i:s');
                
                if ($viajeActivo->update()) {
                    $this->data = ['status' => 'success', 'message' => 'Ubicación actualizada.'];
                    return;
                }
            }
            $this->data = ['status' => 'error', 'message' => 'No se encontró un viaje activo para este usuario.'];
            return;
        }
        $this->data = ['status' => 'error', 'message' => 'Parámetros insuficientes.'];
    }

    public function marcar_notificacion_leida($id) {
    View::select(null, 'json');
    $notif = (new UnoNotificaciones())->find_first((int)$id);
    if ($notif) {
        $notif->leido = 1;
        $notif->update();
    }
    $this->data = ['status' => 'ok'];
}

/**
     * API: Trae las notificaciones activas sin leer del usuario en formato JSON
     */
    public function obtener_notificaciones_ajax() {
        View::select(null, 'json');
        $sesion = Auth::get_active_identity();
        $usuario_id = (int)$sesion['id'];

        $notifModel = new UnoNotificaciones();
        $notificaciones = $notifModel->find("conditions: usuario_id = {$usuario_id} AND leido = 0", "order: id DESC");

        $data = [];
        foreach ($notificaciones as $n) {
            $data[] = [
                'id' => $n->id,
                'mensaje' => $n->mensaje
            ];
        }
        $this->data = $data;
    }

    /**
     * ACCIÓN POST: El creador elimina su viaje publicado (Avisa a los pasajeros aceptados)
     */
    public function cancelar_viaje_publicado() {
        View::select(null, null);
        $sesion = Auth::get_active_identity();
        $usuario_id = (int)$sesion['id'];

        if (Input::hasPost('viaje_id')) {
            $viaje_id = (int)Input::post('viaje_id');

            $poolModel = new UnoPool();
            $viaje = $poolModel->find_first($viaje_id);

            // Verificamos que el viaje exista y pertenezca al usuario logueado
            if ($viaje && (int)$viaje->usuario_id === $usuario_id) {
                
                // 🔔 NOTIFICACIÓN ANTES DE BORRAR: Avisar a todos los pasajeros que estaban aceptados
                $solicitudModel = new UnoPoolSolicitud();
                $pasajerosAceptados = $solicitudModel->find("conditions: viaje_id = {$viaje_id} AND estado = 'aceptado'");
                
                foreach ($pasajerosAceptados as $sol) {
                    $notificacion = new UnoNotificaciones();
                    $notificacion->usuario_id = $sol->pasajero_id;
                    $notificacion->mensaje = "⚠️ El viaje de las " . date('H:i', strtotime($viaje->hora)) . " hs desde " . $viaje->origen . " al que te habías sumado fue CANCELADO por el conductor.";
                    $notificacion->leido = 0;
                    $notificacion->save();
                }

                // Borramos el viaje (por CASCADE en la BD, se borran sus solicitudes en uno_pool_solicitudes)
                if ($viaje->delete()) {
                    Flash::valid("Viaje eliminado y pasajeros notificados correctamente.");
                } else {
                    Flash::error("No se pudo eliminar el viaje.");
                }
            } else {
                Flash::error("Acceso no autorizado o viaje inexistente.");
            }
        }
        Redirect::to('pool');
    }

    /**
     * ACCIÓN POST: El pasajero desiste de un viaje que ya le habían aceptado
     */
    public function desistir_viaje_coordinado() {
        View::select(null, null);
        $sesion = Auth::get_active_identity();
        $usuario_id = (int)$sesion['id'];

        if (Input::hasPost('solicitud_id')) {
            $solicitud_id = (int)Input::post('solicitud_id');

            $solicitudModel = new UnoPoolSolicitud();
            $soli = $solicitudModel->find_first($solicitud_id);

            // Verificamos que la solicitud exista y sea de este pasajero
            if ($soli && (int)$soli->pasajero_id === $usuario_id) {
                
                $viaje = (new UnoPool())->find_first($soli->viaje_id);
                
                if ($viaje) {
                    // 🔔 NOTIFICACIÓN AL CHOFER: Le avisamos que el alumno se bajó del auto
                    $notificacion = new UnoNotificaciones();
                    $notificacion->usuario_id = $viaje->usuario_id; // ID del Conductor
                    $notificacion->mensaje = "🚶 El alumno " . $sesion['nombre'] . " se bajó de tu viaje de las " . date('H:i', strtotime($viaje->hora)) . " hs.";
                    $notificacion->leido = 0;
                    $notificacion->save();
                }

                // Eliminamos la solicitud para liberar el lugar
                if ($soli->delete()) {
                    Flash::valid("Te bajaste del viaje correctamente. El conductor fue avisado.");
                } else {
                    Flash::error("No se pudo procesar la cancelación.");
                }
            } else {
                Flash::error("Solicitud no válida.");
            }
        }
        Redirect::to('pool');
    }

    /**
     * API JSON: Retorna usuarios activos con GPS, calculando la distancia real
     */
    public function obtener_usuarios_cercanos() {
        View::select(null, 'json');
        $sesion = Auth::get_active_identity();
        $usuario_id = (int)$sesion['id'];

        // Capturamos la ubicación actual del usuario que está mirando la pantalla
        // Si todavía no la envió, usamos unas coordenadas base por defecto (ej: Centro de Merlo)
        $mi_lat = Input::get('lat') ?: -34.6656;
        $mi_lng = Input::get('lng') ?: -58.7281;

        $poolModel = new UnoPool();
        
        // FÓRMULA DE HAVERSINE EN SQL: 
        // 6371 es el radio de la Tierra en km. Calcula la distancia geométrica exacta.
        // Filtramos usuarios que se hayan actualizado en los últimos 20 minutos (1200 segundos)
        $sql = "
            SELECT p.*, u.nombre as usuario_nombre, u.telefono as usuario_telefono,
                (6371 * ACOS(
                    COS(RADIANS({$mi_lat})) * COS(RADIANS(p.latitud)) * COS(RADIANS(p.longitud) - RADIANS({$mi_lng})) + 
                    SIN(RADIANS({$mi_lat})) * SIN(RADIANS(p.latitud))
                )) AS distancia
            FROM uno_pool p
            INNER JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.usuario_id != {$usuario_id}
              AND p.latitud IS NOT NULL AND p.latitud != '0'
              AND p.longitud IS NOT NULL AND p.longitud != '0'
              AND TIMESTAMPDIFF(SECOND, p.updated_at, NOW()) <= 1200
            ORDER BY distancia ASC
            LIMIT 20
        ";

        $resultados = $poolModel->find_all_by_sql($sql);

        $usuarios_cercanos = [];
        foreach ($resultados as $r) {
            $usuarios_cercanos[] = [
                'id' => $r->id,
                'usuario_id' => $r->usuario_id,
                'nombre' => $r->usuario_nombre,
                'tipo' => $r->tipo,
                'origen' => $r->origen,
                'destino' => $r->destino,
                'latitud' => $r->latitud,
                'longitud' => $r->longitud,
                'distancia' => round($r->distancia, 2) // Redondeamos a 2 decimales (ej: 0.45 km = 450 metros)
            ];
        }

        $this->data = $usuarios_cercanos;
    }





}
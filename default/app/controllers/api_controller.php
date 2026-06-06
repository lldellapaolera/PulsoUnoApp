<?php
class ApiController extends AppController {

    /**
     * Filtro que se ejecuta antes de cualquier acción.
     * Desactiva el layout visual de KumbiaPHP para responder únicamente JSON.
     */
    protected function before_filter() {
        // Configuramos para responder en formato JSON puro
        View::select(null, 'json');
        
        // Cabeceras CORS por si necesitas probar desde distintos entornos locales
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        // Responder rápido a peticiones de tipo OPTIONS (Preflight)
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit(0);
        }
    }

    /**
     * Auxiliar para capturar el cuerpo de las peticiones POST en JSON
     */
    private function getJsonInput() {
        return json_decode(file_get_contents('php://input'), true);
    }

    /* =========================================================================
       1. ENDPOINTS DEL MÓDULO ACADÉMICO Y ASISTENCIA
       ========================================================================= */

    /**
     * POST: /api/guardar_asistencia
     * Registra o sincroniza las asistencias tomadas por el docente (soporta ráfagas offline)
     */
    public function guardar_asistencia() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->data = ['status' => 'error', 'message' => 'Método no permitido'];
            return;
        }

        $input = $this->getJsonInput();
        
        // Validamos que vengan los datos mínimos obligatorios
        if (!isset($input['comision_id']) || !isset($input['asistencias_lista']) || !isset($input['fecha'])) {
            $this->data = ['status' => 'error', 'message' => 'Datos incompletos'];
            return;
        }

        $asistenciaModel = new Asistencias();
        $errores = 0;

        // Procesamos la lista de alumnos enviada (soporta el guardado por lotes masivos)
        foreach ($input['asistencias_lista'] as $alumno) {
            $exito = $asistenciaModel->guardarAsistenciaSafe(
                $input['comision_id'],
                $alumno['alumno_id'],
                $input['fecha'],
                $alumno['presente']
            );
            if (!$exito) $errores++;
        }

        if ($errores === 0) {
            $this->data = ['status' => 'success', 'message' => 'Asistencia sincronizada correctamente'];
        } else {
            $this->data = ['status' => 'warning', 'message' => "Se procesó con $errores errores parciales"];
        }
    }

    /**
     * GET: /api/presentismo_alumno/?alumno_id=X&comision_id=Y
     * Devuelve el porcentaje de asistencia de un alumno para renderizar el gráfico en Bootstrap
     */
    public function presentismo_alumno() {
        $alumno_id = Input::get('alumno_id');
        $comision_id = Input::get('comision_id');

        if (!$alumno_id || !comision_id) {
            $this->data = ['status' => 'error', 'message' => 'Faltan parámetros requeridos'];
            return;
        }

        $asistenciaModel = new Asistencias();
        $porcentaje = $asistenciaModel->calcularPorcentajeAsistencia($alumno_id, $comision_id);

        $this->data = [
            'status' => 'success',
            'alumno_id' => (int)$alumno_id,
            'comision_id' => (int)$comision_id,
            'porcentaje_asistencia' => $porcentaje,
            'condicion' => ($porcentaje >= 75) ? 'Regular/Promocionable' : 'Alerta: Libre por Inasistencias'
        ];
    }

    /**
     * GET: /api/simular_correlativa/?alumno_id=X&materia_id=Y
     * Informa a la PWA si el alumno está legalmente habilitado para rendir/cursar según el árbol de la BD
     */
    public function simular_correlativa() {
        $alumno_id = Input::get('alumno_id');
        $materia_id = Input::get('materia_id');

        if (!$alumno_id || !$materia_id) {
            $this->data = ['status' => 'error', 'message' => 'Faltan parámetros requeridos'];
            return;
        }

        $materiaModel = new Materias();
        $habilitado = $materiaModel->tieneCorrelativasAprobadas($alumno_id, $materia_id);

        $this->data = [
            'status' => 'success',
            'materia_id' => (int)$materia_id,
            'habilitado' => $habilitado,
            'message' => $habilitado ? 'Cumple con los requisitos correlativos previos.' : 'Bloqueado: Requiere finales previos aprobados.'
        ];
    }

    /* =========================================================================
       2. ENDPOINTS DEL MÓDULO DE MOVILIDAD (UNO-Pool)
       ========================================================================= */

    /**
     * GET: /api/buscar_viajes/?usuario_id=X&localidad=Y&rol=Z
     * Retorna las coincidencias de transporte seguro disponibles en el Oeste
     */
    public function buscar_viajes() {
        $usuario_id = Input::get('usuario_id');
        $localidad = Input::get('localidad');
        $rol_actual = Input::get('rol'); // 'conductor' o 'acompañante'

        if (!$usuario_id || !$localidad || !$rol_actual) {
            $this->data = ['status' => 'error', 'message' => 'Faltan criterios de búsqueda'];
            return;
        }

        $viajesModel = new Viajes();
        $coincidencias = $viajesModel->buscarCoincidencias($usuario_id, $localidad, $rol_actual);

        // Transformamos el set de datos para mandarlo limpio por JSON
        $resultado = [];
        foreach ($coincidencias as $viaje) {
            $resultado[] = [
                'viaje_id' => (int)$viaje->id,
                'nombre_completo' => $viaje->nombre . ' ' . $viaje->apellido,
                'origen' => $viaje->origen,
                'destino' => $viaje->destino,
                'dias' => $viaje->dias_viaje,
                'hora' => $viaje->hora_salida,
                'asientos' => (int)$viaje->asientos_disponibles,
                'detalles' => $viaje->detalles,
                // Texto preconfigurado para disparar la API de WhatsApp desde JS de forma segura
                'url_whatsapp' => "https://api.whatsapp.com/send?phone=" . preg_replace('/[^0-9]/', '', $viaje->telefono) . "&text=" . urlencode("Hola " . $viaje->nombre . ", vi tu viaje en PulsoUno desde " . $viaje->localidad . ". ¿Coordinamos para ir a la facu?")
            ];
        }

        $this->data = [
            'status' => 'success',
            'total_coincidencias' => count($resultado),
            'viajes' => $resultado
        ];
    }
}
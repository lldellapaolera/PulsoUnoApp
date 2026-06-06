<?php

class AsistenciaController extends AppController {
    
    public $mis_comisiones;
    public $comision_seleccionada;
    public $alumnos;
    public $fechas_cargadas = []; // Almacenará los días que ya tienen asistencia

    protected function before_filter() {
        if (!Auth::is_valid()) {
            Redirect::to('login');
            return false;
        }
    }

    public function index($comision_id = null) {
        $sesionUsuario = Auth::get_active_identity();
        $usuario_id = (int) $sesionUsuario['id'];
        
        $comisionesModel = new Comisiones();
        
        // 1. Buscamos todas las comisiones que este profesor dicta
        $this->mis_comisiones = $comisionesModel->find_all_by_sql("
            SELECT c.*, m.nombre as materia_nombre, m.codigo as materia_codigo
            FROM comisiones c
            INNER JOIN materias m ON c.materia_id = m.id
            WHERE c.profesor_id = $usuario_id
        ");

        $this->comision_seleccionada = null;
        $this->alumnos = [];

        // 2. Si el profesor seleccionó una comisión específica por la URL
        if (!is_null($comision_id) && $comision_id !== '') {
            $comision_id = (int)$comision_id;
            
            // Verificamos que la comisión realmente le pertenezca
            $this->comision_seleccionada = $comisionesModel->find_by_sql("
                SELECT c.*, m.nombre as materia_nombre, m.codigo as materia_codigo
                FROM comisiones c
                INNER JOIN materias m ON c.materia_id = m.id
                WHERE c.id = $comision_id AND c.profesor_id = $usuario_id
            ");

            if ($this->comision_seleccionada) {
                // Buscamos los alumnos reales inscriptos a esa comisión en particular
                $inscripcionesModel = new Inscripciones();
                $this->alumnos = $inscripcionesModel->find_all_by_sql("
                    SELECT u.id, u.nombre, u.apellido, u.legajo
                    FROM inscripciones i
                    INNER JOIN usuarios u ON i.alumno_id = u.id
                    WHERE i.comision_id = $comision_id
                    ORDER BY u.apellido ASC, u.nombre ASC
                ");

                // 🚨 NUEVO: Buscamos qué fechas ya tienen registros de asistencia para esta comisión
                $asistenciasModel = new Asistencias();
                $fechasRegistradas = $asistenciasModel->find_all_by_sql("
                    SELECT DISTINCT fecha 
                    FROM asistencias 
                    WHERE comision_id = $comision_id 
                    ORDER BY fecha DESC
                ");
                
                foreach ($fechasRegistradas as $f) {
                    $this->fechas_cargadas[] = $f->fecha;
                }
            }
        }
    }

    /**
     * 🚨 NUEVO AJAX ENDPOINT: Obtiene los estados de asistencia para una fecha determinada
     * URL: /asistencia/obtener_asistencia_fecha?comision_id=X&fecha=YYYY-MM-DD
     */
    public function obtener_asistencia_fecha() {
        View::select(null, null); // Apagamos la renderización visual

        $comision_id = (int) Input::get('comision_id');
        $fecha = Input::get('fecha');

        if (!$comision_id || !$fecha) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Parámetros inválidos.']);
            exit;
        }

        $asistenciasModel = new Asistencias();
        // Buscamos los registros de ese día
        $registros = $asistenciasModel->find_all_by_sql("
            SELECT alumno_id, presente 
            FROM asistencias 
            WHERE comision_id = $comision_id AND fecha = '$fecha'
        ");

        $mapeoAsistencia = [];
        foreach ($registros as $reg) {
            $mapeoAsistencia[(int)$reg->alumno_id] = (int)$reg->presente;
        }

        header('Content-Type: application/json');
        // Devuelve si existe registro previo (true/false) y el mapa de alumnos presentes
        echo json_encode([
            'status' => 'success',
            'existe_registro' => ( is_array($registros) && count($registros) > 0),
            'asistencias' => $mapeoAsistencia
        ]);
        exit;
    }

    /**
     * AJAX ENDPOINT: Guarda el lote de asistencia enviado por el JS
     */
    public function guardar() {
        View::select(null, null);

        if (!Input::hasPost('comision_id') || !Input::hasPost('fecha')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Faltan parámetros obligatorios.']);
            exit;
        }

        $comision_id = (int) Input::post('comision_id');
        $fecha = Input::post('fecha');
        $presentes_ids = Input::post('presentes') ? Input::post('presentes') : [];
        
        $inscripcionesModel = new Inscripciones();
        $alumnos_inscritos = $inscripcionesModel->find_all_by_sql("SELECT alumno_id FROM inscripciones WHERE comision_id = $comision_id");

        $asistenciasModel = new Asistencias();
        
        try {
            foreach ($alumnos_inscritos as $alumno) {
                $alumno_id = (int) $alumno->alumno_id;
                $estado_presente = in_array($alumno_id, $presentes_ids) ? 1 : 0;

                $existe = $asistenciasModel->find_first("conditions: comision_id = $comision_id AND alumno_id = $alumno_id AND fecha = '$fecha'");
                
                if ($existe) {
                    $existe->presente = $estado_presente;
                    $existe->update();
                } else {
                    $nuevaAsistencia = new Asistencias();
                    $nuevaAsistencia->comision_id = $comision_id;
                    $nuevaAsistencia->alumno_id = $alumno_id;
                    $nuevaAsistencia->fecha = $fecha;
                    $nuevaAsistencia->presente = $estado_presente;
                    $nuevaAsistencia->create();
                }
            }

            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => '¡Asistencia guardada correctamente en el sistema!']);
            exit;

        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos al guardar: ' . $e->getMessage()]);
            exit;
        }
    }
}
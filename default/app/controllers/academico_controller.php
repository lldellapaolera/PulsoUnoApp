<?php

class AcademicoController extends AppController {
    public $presentismo;
    public $materias_plan;
    public $template = 'academico';    


    protected function before_filter() {
        if (!Auth::is_valid()) {
            Redirect::to('login');
            return false;
        }
    }

    public function index() {
        $sesionUsuario = Auth::get_active_identity();
        $alumno_id = $sesionUsuario['id'];

        // Instanciamos el modelo de inscripciones real
        $inscripcionesModel = new Inscripciones();
        $this->presentismo = $inscripcionesModel->getPresentismoAlumno($alumno_id);

        // Cargamos las materias para el select del simulador
        $materiasModel = new Materias();
        $this->materias_plan = $materiasModel->getMateriasPlan();
    }

    /**
     * AJAX Endpoint: Simular correlativas pasando el ID por parámetro de URL
     * URL destino: /academica/simular_correlativa/[ID]
     */
    public function simular_correlativa($materia_id = null) {
        // Desactivamos renderizado de vistas de Kumbia
        View::select(null, null);
        
        // Si no viene el parámetro en la URL, devolvemos error
        if (is_null($materia_id) || $materia_id === '') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'rechazado', 'message' => 'No se especificó ninguna materia válida.']);
            exit;
        }

        $materia_id = (int) $materia_id;
        $sesionUsuario = Auth::get_active_identity();
        $alumno_id = (int) $sesionUsuario['id'];

        $materiasModel = new Materias();
        $materiaTarget = $materiasModel->find_first($materia_id);

        if (!$materiaTarget) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'rechazado', 'message' => 'La materia seleccionada no existe en el plan.']);
            exit;
        }

        // 1. Buscamos las correlativas usando SQL directo
        $correlativasModel = new Correlatividades();
        $requisitos = $correlativasModel->find_all_by_sql("
            SELECT correlativa_id 
            FROM correlatividades 
            WHERE materia_id = $materia_id
        ");

        if (is_array($requisitos) && count($requisitos) > 0) {
            foreach ($requisitos as $req) {
                // 2. Verificamos si el alumno tiene la materia en estado 'regular' o 'promocionada'
                $inscripcionesModel = new Inscripciones();
                $aprobada = $inscripcionesModel->find_first_by_sql("
                    SELECT i.estado_materia 
                    FROM inscripciones i
                    INNER JOIN comisiones c ON i.comision_id = c.id
                    WHERE i.alumno_id = $alumno_id 
                      AND c.materia_id = " . (int)$req->correlativa_id . " 
                      AND i.estado_materia IN ('regular', 'promocionada')
                ");

                // Si no se encontró registro aprobado
                if (!$aprobada) {
                    $materiaFaltante = $materiasModel->find_first((int)$req->correlativa_id);
                    $nombreFaltante = $materiaFaltante ? $materiaFaltante->nombre : "Materia Requisito";
                    $codigoFaltante = $materiaFaltante ? $materiaFaltante->codigo : "INF";

                    header('Content-Type: application/json');
                    echo json_encode([
                        'status' => 'rechazado',
                        'message' => "Requisito insuficiente para cursar {$materiaTarget->nombre}. Es necesario tener regularizada o aprobada: {$nombreFaltante} ({$codigoFaltante})."
                    ]);
                    exit;
                }
            }
        }

        // Si superó todas las validaciones
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'aprobado',
            'message' => "¡Habilitado! Cumplís con los requisitos de correlatividades necesarios para cursar {$materiaTarget->nombre}."
        ]);
        exit;
    }
}
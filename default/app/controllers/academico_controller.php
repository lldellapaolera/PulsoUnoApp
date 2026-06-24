<?php

class AcademicoController extends AppController {
    public $presentismo;
    public $materias_plan;
    public $materias_aprobadas;
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

        $sesionUsuario = Auth::get_active_identity();
        $alumno_id = (int)$sesionUsuario['id'];

        
        

        // 3. Traemos TODAS las inscripciones históricas del alumno que estén aprobadas o regulares
        $historico = $inscripcionesModel->find_all_by_sql("
            SELECT DISTINCT m.id, m.codigo, m.nombre, i.estado_materia, i.nota_final,c.sede_aula
            FROM inscripciones i
            INNER JOIN comisiones c ON i.comision_id = c.id
            INNER JOIN materias m ON c.materia_id = m.id
            WHERE i.alumno_id = $alumno_id 
              AND i.estado_materia IN ('regular', 'promocionada')
        ");

        if (!$historico) {
            $historico = [];
        }

        // Mapeamos las aprobadas indexadas por su ID de materia para filtrar rápido
        $aprobadasIds = [];
        $this->materias_aprobadas = [];
        foreach ($historico as $h) {
            $this->materias_aprobadas[$h->id] = $h;
            $aprobadasIds[] = (int)$h->id;
        }

        // 4. Identificamos cuáles le faltan cruzando el plan completo contra los IDs obtenidos
        foreach ($this->materias_plan as $mat) {
            if (!in_array((int)$mat->id, $aprobadasIds)) {
                $this->materias_faltantes[] = $mat;
            }
        }
    }

    /**
     * AJAX Endpoint: Simular correlativas pasando el ID por parámetro de URL
     * URL destino: /academica/simular_correlativa/[ID]
     */
    public function simular_correlativa($materia_id = null) {
    // Desactivamos renderizado de vistas de Kumbia
    View::select(null, null);
    header('Content-Type: application/json');
    
    // Si no viene el parámetro en la URL, devolvemos error
    if (is_null($materia_id) || $materia_id === '') {
        echo json_encode(['status' => 'rechazado', 'message' => 'No se especificó ninguna materia válida.']);
        exit;
    }

    $materia_id = (int) $materia_id;
    $sesionUsuario = Auth::get_active_identity();
    $alumno_id = (int) $sesionUsuario['id'];

    $materiasModel = new Materias();
    $materiaTarget = $materiasModel->find($materia_id); // Cambiado a find() clásico que es más seguro para ID primario

    if (!$materiaTarget) {
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

    // SOLUCIÓN: Si find_all_by_sql no encuentra nada, puede devolver false. Lo forzamos a array.
    if (!$requisitos) {
        $requisitos = [];
    }

    if (count($requisitos) > 0) {
        foreach ($requisitos as $req) {
            // 2. Verificamos si el alumno tiene la materia en estado 'regular' o 'promocionada'
            $inscripcionesModel = new Inscripciones();
            
            // Corregido find_first_by_sql que a veces se comporta impredecible si no devuelve registros
            $aprobada = $inscripcionesModel->find_all_by_sql("
                SELECT i.estado_materia 
                FROM inscripciones i
                INNER JOIN comisiones c ON i.comision_id = c.id
                WHERE i.alumno_id = $alumno_id 
                  AND c.materia_id = " . (int)$req->correlativa_id . " 
                  AND i.estado_materia IN ('regular', 'promocionada')
                LIMIT 1
            ");

            // Si no se encontró ningún registro que cumpla la condición
            if (empty($aprobada)) {
                $materiaFaltante = $materiasModel->find((int)$req->correlativa_id);
                $nombreFaltante = $materiaFaltante ? $materiaFaltante->nombre : "Materia Requisito";
                $codigoFaltante = $materiaFaltante ? $materiaFaltante->codigo : "INF";

                echo json_encode([
                    'status' => 'rechazado',
                    'message' => "Requisito insuficiente para cursar {$materiaTarget->nombre}. Es necesario tener regularizada o aprobada: {$nombreFaltante} ({$codigoFaltante})."
                ]);
                exit;
            }
        }
    }

    // Si no tiene correlativas (count == 0) o si superó todas las aprobaciones correctamente
    echo json_encode([
        'status' => 'aprobado',
        'message' => count($requisitos) === 0 
            ? "¡Habilitado! Esta materia no posee correlativas previas en el plan." 
            : "¡Habilitado! Cumplís con los requisitos de correlatividades necesarios para cursar {$materiaTarget->nombre}."
    ]);
    exit;
}
}
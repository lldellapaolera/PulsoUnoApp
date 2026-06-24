<?php

class AdminMateriasController extends AppController {

    /**
     * Filtro de seguridad obligatorio
     */
    protected function before_filter() {
        if (!Auth::is_valid() || Auth::get('rol') !== 'administrador') {
            Flash::error("No tenés permisos para acceder a esta sección.");
            Redirect::to('dashboard');
            return false;
        }
    }

    /**
     * Listado principal de materias
     */
    public function index() {
        $m = new Materias(); // Asumo que tu modelo se llama Materias
        $this->listMaterias = $m->find("order: nombre ASC");
    }

    /**
     * Crear o editar una materia
     */
    // Reemplazá temporalmente tu acción guardar por esta para auditar el error:
public function guardar($id = null) {
    $this->materia = new Materias();
    
    if (Input::hasPost('materia')) {
        $data = Input::post('materia');
        
        // Si el ID no está vacío, es una edición: cargamos la materia existente
        if (!empty($data['id'])) {
            $this->materia = $this->materia->find($data['id']);
        }
        
        // ASIGNACIÓN MANUAL EXPLÍCITA (Evita fallos de dump_result)
        $this->materia->codigo = $data['codigo'];
        $this->materia->nombre = $data['nombre'];
        $this->materia->promocionable = isset($data['promocionable']) ? 1 : 0;
        
        // Intentamos guardar el registro
        if ($this->materia->save()) {
            Flash::valid("¡Materia guardada con éxito!");
            Redirect::to('admin_materias');
        } else {
            Flash::error("Ocurrió un error al intentar guardar la materia.");
        }
    } else if ($id) {
        $this->materia = $this->materia->find($id);
    }
}

    /**
     * Eliminar una materia
     */
    public function eliminar($id) {
        $m = new Materias();
        $materia = $m->find($id);
        
        if ($materia && $materia->delete()) {
            Flash::valid("Materia eliminada correctamente.");
        } else {
            Flash::error("No se pudo eliminar la materia.");
        }
        Redirect::to('admin_materias');
    }

    /**
     * Administrar las correlativas de una materia específica
     */
    public function correlatividades($materia_id) {
        $m = new Materias();
        $this->materia = $m->find($materia_id);
        
        if (!$this->materia) {
            Flash::error("La materia no existe.");
            Redirect::to('admin_materias');
            return;
        }

        // Si viene por POST, agregamos una nueva correlativa
        if (Input::hasPost('correlativa_id')) {
            $correlativa_id = Input::post('correlativa_id');
            
            // Validación básica: No puede ser correlativa de sí misma
            if ($materia_id == $correlativa_id) {
                Flash::error("Una materia no puede ser correlativa de sí misma.");
            } else {
                // Instanciamos el modelo de la tabla intermedia
                // Cambiá 'Correlatividades' por el nombre exacto de tu modelo
                $c = new Correlatividades();
                
                // Validar que no exista ya esa relación para no duplicar filas
                $existe = $c->find_first("conditions: materia_id = $materia_id AND correlativa_id = $correlativa_id");
                
                if ($existe) {
                    Flash::warning("Esa materia ya está asignada como correlativa.");
                } else {
                    $c->materia_id = $materia_id;
                    $c->correlativa_id = $correlativa_id;
                    
                    if ($c->save()) {
                        Flash::valid("Correlatividad agregada con éxito.");
                    } else {
                        Flash::error("No se pudo guardar la correlatividad.");
                    }
                }
            }
            Redirect::to('admin_materias/correlatividades/' . $materia_id);
            return;
        }

        // Obtener las correlativas actuales de esta materia con un JOIN limpio
        $c = new Correlatividades();
        $this->listCorrelativas = $c->find_all_by_sql("
            SELECT c.id, m.codigo, m.nombre 
            FROM correlatividades c
            INNER JOIN materias m ON c.correlativa_id = m.id
            WHERE c.materia_id = $materia_id
            ORDER BY m.nombre ASC
        ");

        // Obtener el listado de TODAS las materias para el desplegable (excepto ella misma)
        $this->todasLasMaterias = $m->find("conditions: id != $materia_id", "order: nombre ASC");
    }

    /**
     * Quitar una correlatividad
     */
    public function eliminar_correlativa($id, $materia_id) {
        $c = new Correlatividades();
        $correlatividad = $c->find($id);
        
        if ($correlatividad && $correlatividad->delete()) {
            Flash::valid("Correlatividad quitada correctamente.");
        } else {
            Flash::error("No se pudo quitar la correlatividad.");
        }
        Redirect::to('admin_materias/correlatividades/' . $materia_id);
    }
}
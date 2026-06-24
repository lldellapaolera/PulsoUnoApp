<?php

class AdminComisionesController extends AppController {

public $listComisiones;
public $comision;
public $listMaterias;
public $listProfesores;
public $listInscritos;
public $listAlumnosDisponibles;
public $inscripcion;

    protected function before_filter() {
        if (!Auth::is_valid() || Auth::get('rol') !== 'administrador') {
            Flash::error("No tenés permisos para acceder a esta sección.");
            Redirect::to('index');
            return false;
        }
    }

    /**
     * Listado principal de Comisiones
     */
    public function index() {
        $c = new Comisiones();
        // Levantamos las comisiones haciendo JOINs limpios para mostrar nombres de materias y profesores
        $this->listComisiones = $c->find_all_by_sql("
            SELECT c.*, m.codigo AS materia_codigo, m.nombre AS materia_nombre, u.nombre AS prof_nombre, u.apellido AS prof_apellido
            FROM comisiones c
            INNER JOIN materias m ON c.materia_id = m.id
            INNER JOIN usuarios u ON c.profesor_id = u.id
            ORDER BY c.anio DESC, c.cuatrimestre DESC, m.nombre ASC
        ");
    }
/**
     * Crear o Editar una Comisión básica
     */
    public function guardar($id = null) {
        $this->comision = new Comisiones();
        
        if (Input::hasPost('comision')) {
            $data = Input::post('comision');
            
            if (!empty($data['id'])) {
                $this->comision = $this->comision->find($data['id']);
            }
            
            // Asignación manual directa
            $this->comision->materia_id    = $data['materia_id'];
            $this->comision->profesor_id   = $data['profesor_id'];
            $this->comision->cuatrimestre  = $data['cuatrimestre'];
            $this->comision->anio          = $data['anio'];
            $this->comision->dias_horarios = $data['dias_horarios'];
            $this->comision->sede_aula = $data['sede_aula'];
            
            if ($this->comision->save()) {
                Flash::valid("Comisión guardada con éxito.");
                Redirect::to('admin_comisiones');
            } else {
                Flash::error("Ocurrió un error al intentar guardar la comisión.");
            }
        } else {
            if ($id) {
                // Para el find clásico de edición no hay problema
                $this->comision = $this->comision->find($id); 
            }
            // Listados necesarios para los desplegables de la vista
            $this->listMaterias = (new Materias())->find("order: nombre ASC");
            
            // Esto permite listar todos los profesores. Se pueden asignar a las materias que quieras libres
            $this->listProfesores = (new Usuarios())->find("conditions: rol = 'profesor'", "order: apellido, nombre ASC");
        }
    }

    /**
     * Pantalla para gestionar Alumnos Inscriptos en la Comisión
     */
    public function alumnos($comision_id) {
        $c = new Comisiones();
        
        // CORRECCIÓN AQUÍ: Usamos find_by_sql para consultas personalizadas con JOIN
        $this->comision = $c->find_by_sql("
            SELECT c.*, m.nombre AS materia_nombre, u.apellido AS prof_apellido
            FROM comisiones c
            INNER JOIN materias m ON c.materia_id = m.id
            INNER JOIN usuarios u ON c.profesor_id = u.id
            WHERE c.id = $comision_id
            LIMIT 1
        ");

        if (!$this->comision) {
            Flash::error("La comisión no existe.");
            Redirect::to('admin_comisiones');
            return;
        }

        // Si viene por POST, inscribimos un nuevo alumno
        if (Input::hasPost('alumno_id')) {
            $alumno_id = Input::post('alumno_id');
            
            $ins = new Inscripciones();
            $existe = $ins->find_first("conditions: comision_id = $comision_id AND alumno_id = $alumno_id");
            
            if ($existe) {
                Flash::warning("El alumno ya se encuentra inscrito en esta comisión.");
            } else {
                $ins->comision_id = $comision_id;
                $ins->alumno_id = $alumno_id;
                $ins->estado_materia = 'cursando'; 
                
                if ($ins->save()) {
                    Flash::valid("Alumno inscrito correctamente.");
                } else {
                    Flash::error("No se pudo inscribir al alumno.");
                }
            }
            Redirect::to('admin_comisiones/alumnos/' . $comision_id);
            return;
        }

        // Listar alumnos actualmente inscritos en esta comisión
        $ins = new Inscripciones();
        $this->listInscritos = $ins->find_all_by_sql("
            SELECT i.id, u.legajo, u.nombre, u.apellido, u.email, i.estado_materia
            FROM inscripciones i
            INNER JOIN usuarios u ON i.alumno_id = u.id
            WHERE i.comision_id = $comision_id
            ORDER BY u.apellido, u.nombre ASC
        ");

        // Listar todos los alumnos de la universidad para el desplegable
        $this->listAlumnosDisponibles = (new Usuarios())->find("conditions: rol = 'alumno'", "order: apellido, nombre ASC");
    }

    /**
     * Dar de baja la inscripción de un alumno
     */
    public function desinscribir($id, $comision_id) {
        $ins = new Inscripciones();
        $inscripcion = $ins->find($id);
        
        if ($inscripcion && $inscripcion->delete()) {
            Flash::valid("Inscripción dada de baja correctamente.");
        } else {
            Flash::error("No se pudo remover la inscripción.");
        }
        Redirect::to('admin_comisiones/alumnos/' . $comision_id);
    }

    /**
     * Eliminar Comisión completa
     */
    public function eliminar($id) {
        $c = new Comisiones();
        $comision = $c->find($id);
        if ($comision && $comision->delete()) {
            Flash::valid("Comisión eliminada correctamente.");
        } else {
            Flash::error("No se pudo eliminar la comisión.");
        }
        Redirect::to('admin_comisiones');
    }

    /**
     * Formulario para modificar notas y estado de un alumno inscrito
     */
    public function calificar($inscripcion_id) {
        $ins = new Inscripciones();
        
        // Buscamos la inscripción con un join para tener los datos del alumno y la materia en la vista
        $this->inscripcion = $ins->find_by_sql("
            SELECT i.*, u.nombre AS alu_nombre, u.apellido AS alu_apellido, u.legajo AS alu_legajo, m.nombre AS materia_nombre
            FROM inscripciones i
            INNER JOIN usuarios u ON i.alumno_id = u.id
            INNER JOIN comisiones c ON i.comision_id = c.id
            INNER JOIN materias m ON c.materia_id = m.id
            WHERE i.id = $inscripcion_id
            LIMIT 1
        ");

        if (!$this->inscripcion) {
            Flash::error("La inscripción no existe.");
            Redirect::to('admin_comisiones');
            return;
        }

        // Si viene por POST, guardamos las notas y el estado
        if (Input::hasPost('calificacion')) {
            $data = Input::post('calificacion');
            
            // Instanciamos el objeto real para persistir
            $registro = $ins->find($inscripcion_id);
            
            // ASIGNACIÓN MANUAL EXPLÍCITA CON FILTRO PARA PERMITIR NULLS
            $registro->nota_parcial1 = ($data['nota_parcial1'] !== '') ? $data['nota_parcial1'] : null;
            $registro->nota_parcial2 = ($data['nota_parcial2'] !== '') ? $data['nota_parcial2'] : null;
            $registro->nota_tps      = ($data['nota_tps'] !== '') ? $data['nota_tps'] : null;
            $registro->nota_final    = ($data['nota_final'] !== '') ? $data['nota_final'] : null;
            $registro->estado_materia = $data['estado_materia']; // 'cursando','regular','promocionada','libre'
            
            
            if ($registro->save()) {
                Flash::valid("Actas de cursada actualizadas para el alumno.");
                // Redireccionamos de vuelta a la lista de alumnos de esa misma comisión
                Redirect::to('admin_comisiones/alumnos/' . $registro->comision_id);
            } else {
                Flash::error("No se pudieron guardar las calificaciones.");
                Redirect::to('admin_comisiones/alumnos/' . $registro->comision_id);
            }
            return;
        }
    }
}
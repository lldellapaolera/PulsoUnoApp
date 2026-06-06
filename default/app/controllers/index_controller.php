<?php



/**
 * Controller por defecto si no se usa el routes
 *
 */
class IndexController extends AppController
{

    
    public $presentismo;
    public $materias_plan;
    public $es_profesor;
    public function index()
    {
        $this->es_profesor = false;
        $sesionUsuario = Auth::get_active_identity();
        $usuario_id = (int) $sesionUsuario['id'];
        $rolUsuario = $sesionUsuario['rol'];

        // 1. Lógica existente para alumnos (Presentismo)
        $inscripcionesModel = new Inscripciones();
        $this->presentismo = $inscripcionesModel->getPresentismoAlumno($usuario_id);

        // 2. Lógica existente para el Simulador
        $materiasModel = new Materias();
        $this->materias_plan = $materiasModel->getMateriasPlan();    
        // 3. NUEVA VALIDACIÓN: ¿Es profesor activo de alguna comisión?
    

        // Si el rol en su perfil es de profesor o administrador, verificamos en comisiones
        if ($rolUsuario === 'profesor' || $rolUsuario === 'administrador') {
            // Instanciamos el modelo de comisiones (asegurate de tener comisiones.php en models)
            $comisionesModel = new Comisiones();
            
            // Buscamos si existe al menos una comisión asignada a su ID
            $esDocente = $comisionesModel->find_first("conditions: profesor_id = $usuario_id");
            
            if ($esDocente) {
                $this->es_profesor = true;
            }
        }
        
    }
}

<?php

class DocumentosController extends AppController {

    public $mis_comisiones;
    public $comision_seleccionada;
    public $elementos;
    public $ruta_relativa;
    public $rolUsuario;
    public $usuario_id;

    protected function before_filter() {
        if (!Auth::is_valid()) {
            Redirect::to('login');
            return false;
        }
    }

    public function index($comision_id = null, $subcarpeta = '') {
        $sesionUsuario = Auth::get_active_identity();
        $this->usuario_id = (int) $sesionUsuario['id'];
        $this->rolUsuario = $sesionUsuario['rol'];

        $comisionesModel = new Comisiones();

        // 1. DETERMINAR MATERIAS SEGÚN EL ROL
        if ($this->rolUsuario === 'profesor' || $this->rolUsuario === 'administrador') {
            $this->materias_validas = $comisionesModel->find_all_by_sql("
                SELECT c.id as comision_id, m.nombre as materia_nombre
                FROM comisiones c
                INNER JOIN materias m ON c.materia_id = m.id
                WHERE c.profesor_id = {$this->usuario_id}
            ");
        } else {
            $inscripcionesModel = new Inscripciones();
            $this->materias_validas = $inscripcionesModel->find_all_by_sql("
                SELECT c.id as comision_id, m.nombre as materia_nombre
                FROM inscripciones i
                INNER JOIN comisiones c ON i.comision_id = c.id
                INNER JOIN materias m ON c.materia_id = m.id
                WHERE i.alumno_id = {$this->usuario_id}
            ");
        }

        $this->comision_seleccionada = null;
        $this->elementos = [];
        $this->ruta_relativa = str_replace(array('../', '..\\', './', '.\\'), '', urldecode($subcarpeta));

        // 2. EXPLORACIÓN DE LA CARPETA SI SE ELIGIÓ MATERIA
        if (!is_null($comision_id) && $comision_id !== '') {
            $comision_id = (int)$comision_id;

            $esValida = false;
            foreach ($this->materias_validas as $mv) {
                if ((int)$mv->comision_id === $comision_id) {
                    $esValida = true;
                    $this->comision_seleccionada = $mv;
                    break;
                }
            }

            if ($esValida) {
                // 🚨 RUTA MULTIPLATAFORMA ABSOLUTA Y LIMPIA
                $base_dir = APP_PATH . '../public/uploads/apuntes/comision_' . $comision_id . '/';
                if (!empty($this->ruta_relativa)) {
                    $base_dir .= $this->ruta_relativa . '/';
                }
                
                // Normalizamos las barras para Windows y Linux de forma unificada
                $base_dir = str_replace(array('\\', '//'), '/', $base_dir);

                // Si no existe el directorio base de la materia en public, lo creamos
                if (!is_dir($base_dir)) {
                    mkdir($base_dir, 0755, true);
                }

                // Escaneamos el directorio real en el disco para el listado de la vista
                if (is_dir($base_dir)) {
                    $archivos = scandir($base_dir);
                    foreach ($archivos as $archivo) {
                        if ($archivo === '.' || $archivo === '..') continue;

                        $es_dir = is_dir($base_dir . $archivo);
                        $this->elementos[] = [
                            'nombre' => $archivo,
                            'es_carpeta' => $es_dir,
                            'peso' => $es_dir ? '-' : round(filesize($base_dir . $archivo) / 1024 / 1024, 2) . ' MB',
                            'fecha' => date("d/m/Y H:i", filemtime($base_dir . $archivo))
                        ];
                    }
                }
            }
        }
    }

    /**
     * ACCIÓN: Crear una nueva subcarpeta (Solo Profesores/Admin)
     */
    public function crear_carpeta() {
        View::select(null, null);
        $sesion = Auth::get_active_identity();
        
        if ($sesion['rol'] !== 'profesor' && $sesion['rol'] !== 'administrador') {
            Flash::error("No tenés permisos para realizar esta acción.");
            Redirect::to('documentos');
            return;
        }

        if (Input::hasPost('comision_id', 'nombre_carpeta')) {
            $comision_id = (int) Input::post('comision_id');
            $nombre_carpeta = preg_replace("/[^a-zA-Z0-9\._-]/", "_", Input::post('nombre_carpeta'));
            $ruta_actual = str_replace(array('../', '..\\'), '', Input::post('ruta_actual', ''));

            $dir = APP_PATH . '../public/uploads/apuntes/comision_' . $comision_id . '/' . $ruta_actual . '/' . $nombre_carpeta;
            //$dir = str_replace(array('\\', '//'), '/', $dir);
            $dir = str_replace(['\\', '//'], DIRECTORY_SEPARATOR, $dir);

            
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                Flash::valid("Carpeta '{$nombre_carpeta}' creada con éxito.");
            } else {
                Flash::warning("La carpeta ya existe.");
            }
            Redirect::to("documentos/index/{$comision_id}/" . urlencode($ruta_actual));
        }
    }

    
    /**
     * ACCIÓN: Subir un archivo físico (Solo Profesores/Admin)
     */
    public function subir_archivo() {
        View::select(null, null);
        $sesion = Auth::get_active_identity();

        if ($sesion['rol'] !== 'profesor' && $sesion['rol'] !== 'administrador') {
            Flash::error("Acceso denegado.");
            Redirect::to('documentos');
            return;
        }

        if (Input::hasPost('comision_id')) {
            $comision_id = (int) Input::post('comision_id');
            $ruta_actual = str_replace(array('../', '..\\'), '', Input::post('ruta_actual', ''));

            // 1. Validamos que el archivo haya llegado al Request global nativo de PHP
            if (!isset($_FILES['documento']) || $_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
                Flash::error("No se recibió ningún archivo o excede el límite permitido por el servidor.");
                Redirect::to("documentos/index/{$comision_id}/" . urlencode($ruta_actual));
                return;
            }

            // 2. Sanitizamos el nombre original del archivo
            $nombre_original = basename($_FILES['documento']['name']);
            $nombre_sanitizado = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $nombre_original);

            // 3. Validamos la extensión de forma manual y segura
            $extension = strtolower(pathinfo($nombre_sanitizado, PATHINFO_EXTENSION));
            $extensiones_permitidas = array('pdf', 'docx', 'doc', 'xlsx', 'pptx', 'zip', 'rar', 'txt', 'png', 'jpg');

            if (!in_array($extension, $extensiones_permitidas)) {
                Flash::error("Formato de archivo no permitido (.{$extension}).");
                Redirect::to("documentos/index/{$comision_id}/" . urlencode($ruta_actual));
                return;
            }

            // 4. Definimos el destino en la raíz pública absoluta basándonos en APP_PATH
            $destino_dir = APP_PATH . '../public/uploads/apuntes/comision_' . $comision_id . '/' . $ruta_actual;
            $destino_dir = str_replace(array('\\', '//'), '/', $destino_dir);
            $destino_dir = rtrim($destino_dir, '/') . '/';

            // 5. Si la carpeta física no existe en Windows/Linux, la creamos con permisos
            if (!is_dir($destino_dir)) {
                if (!mkdir($destino_dir, 0755, true)) {
                    Flash::error("Error del sistema operativo: No se pudo crear el directorio de destino.");
                    Redirect::to("documentos/index/{$comision_id}/" . urlencode($ruta_actual));
                    return;
                }
            }

            $ruta_completa_archivo = $destino_dir . $nombre_sanitizado;

            // 6. Al NO usar Upload::factory, tmp_name es 100% válido y PHP escribe el archivo real en el disco
            if (move_uploaded_file($_FILES['documento']['tmp_name'], $ruta_completa_archivo)) {
                Flash::valid("¡El archivo '{$nombre_sanitizado}' se subió y guardó físicamente con éxito!");
            } else {
                Flash::error("Error crítico: El archivo se transmitió pero no se pudo escribir en el disco.");
            }

            Redirect::to("documentos/index/{$comision_id}/" . urlencode($ruta_actual));
        }
    }

    /**
     * ACCIÓN: Eliminar un archivo o carpeta (Solo Profesores/Admin)
     */
    public function eliminar() {
        View::select(null, null);
        $sesion = Auth::get_active_identity();

        if ($sesion['rol'] !== 'profesor' && $sesion['rol'] !== 'administrador') {
            Flash::error("No tenés permisos.");
            Redirect::to('documentos');
            return;
        }

        $comision_id = (int) Input::get('comision_id');
        $nombre = Input::get('nombre');
        $ruta_actual = str_replace(array('../', '..\\'), '', Input::get('ruta_actual', ''));

        $target = APP_PATH . '../public/uploads/apuntes/comision_' . $comision_id . '/' . $ruta_actual . '/' . $nombre;
        $target = str_replace(array('\\', '//'), '/', $target);

        if (file_exists($target)) {
            if (is_dir($target)) {
                rmdir($target);
            } else {
                unlink($target);
            }
            Flash::valid("Eliminado correctamente.");
        } else {
            Flash::error("El elemento no existe.");
        }

        Redirect::to("documentos/index/{$comision_id}/" . urlencode($ruta_actual));
    }
}
<?php

class EstudioController extends AppController {
    public $neuro_perfil;
    public $neuro_ranking;
    public $template = 'academico'; 

    protected function before_filter() {
        if (!Auth::is_valid()) {
            Redirect::to('login');
            return false;
        }
    }

    public function index() {
        $sesionUsuario = Auth::get_active_identity();
        $alumno_id = (int)$sesionUsuario['id'];

        // Instanciamos el modelo nativo de KumbiaPHP
        $perfilModel = new NeuroPerfiles();

        // Buscamos el primer registro que coincida usando el ORM
        $perfil = $perfilModel->find_first("usuario_id = $alumno_id");

        if (!$perfil) {
            // Si no existe, creamos e insertamos usando las propiedades del objeto
            $perfil = new NeuroPerfiles();
            $perfil->usuario_id = $alumno_id;
            $perfil->liga_id = 1;
            $perfil->xp_total = 0;
            $perfil->xp_semanal = 0;
            $perfil->racha_actual = 0;
            $perfil->racha_maxima = 0;
            $perfil->escudos_disponibles = 0;
            $perfil->ultima_actividad_fecha = '0000-00-00';
            
            if(!$perfil->create()){
                throw new Exception("No se pudo crear el perfil de neuro");
            } // Método nativo de KumbiaPHP para INSERT
        }
        
        $this->neuro_perfil = $perfil;
        $id_liga = $perfil ? (int)$perfil->liga_id : 1;

        // Para el ranking sí usamos find_all con condiciones limpias del framework
        $this->neuro_ranking = $perfilModel->find("liga_id = $id_liga", "order: xp_semanal DESC", "limit: 30");

        if (!$this->neuro_ranking) { 
            $this->neuro_ranking = []; 
        }
    }

    /**
     * Endpoint AJAX - Registrar Escape (Por GET unificado)
     */
    public function registrar_escape($tipo = 'distracion') {
        // Cancelamos vistas de forma absoluta
        View::select(null);
        View::template(null);

        header('Content-Type: application/json; charset=utf-8');

        // Traducimos el código de la URL al texto que va a la base de datos
        $motivos = [
            'distracion' => 'Redes Sociales o Movil',
            'externo'    => 'Obligaciones Externas',
            'cansancio'  => 'Fatiga o Cansancio'
        ];
        $motivo_final = isset($motivos[$tipo]) ? $motivos[$tipo] : 'Distraccion';

        $sesionUsuario = Auth::get_active_identity();
        $alumno_id = (int)$sesionUsuario['id'];

        $perfilModel = new NeuroPerfiles();
        $perfil = $perfilModel->find_first("usuario_id = $alumno_id");
        
        if ($perfil) {
            $escape = new NeuroRegistroEscapes();
            $escape->perfil_id = (int)$perfil->id;
            $escape->motivo_escape = $motivo_final;
            $escape->tregua_aceptada = 1;
            $escape->tregua_cumplida = 1;
            
            if ($escape->create()) {
                echo json_encode(['status' => 'ok', 'message' => 'Intervalo de tregua registrado con éxito.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al guardar la pausa en el modelo.']);
            }
         } else {
             echo json_encode(['status' => 'error', 'message' => 'No se encontró perfil asociado.']);
         }
        exit;
    }

    /**
     * Endpoint AJAX - Registrar Sesión de Estudio
     */
    public function registrar_estudio($minutos = 0) {
        View::select(null, null);
        header('Content-Type: application/json');

        $xp=0;
        $minutos = (int)$minutos;
        if ($minutos <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Cantidad de minutos inválida.']);
            exit;
        } else 
        if ($minutos == 15) {
            $xp=10;
        }
        if ($minutos == 30) {
            $xp=20;
        } else if ($minutos == 60) {
            $xp=40;
        }

        

        $sesionUsuario = Auth::get_active_identity();
        $alumno_id = (int)$sesionUsuario['id'];
        $hoy = date('Y-m-d');

        

        $perfilModel = new NeuroPerfiles();
        $perfil = $perfilModel->find_first("usuario_id = $alumno_id");

        
        
        if ($perfil) {
            $seguimientoModel = new NeuroSeguimientoDiario();
            
            // Buscamos si ya existe el registro del día usando find_first nativo
            $seguimiento = $seguimientoModel->find_first("perfil_id = {$perfil->id} AND fecha = '$hoy'");

            

            if ($seguimiento) {
                // Si existe, actualizamos las propiedades del objeto directamente
                $nuevos_minutos = (int)$seguimiento->minutos_estudio_total + $minutos;
                $seguimiento->minutos_estudio_total = $nuevos_minutos;
                $seguimiento->meta_diaria_cumplida = ($nuevos_minutos >= 30) ? 1 : 0;
                $seguimiento->update(); // Método nativo de KumbiaPHP para UPDATE
            } else {
                // Si no existe, creamos una nueva instancia del modelo de seguimiento
                $nuevoSeguimiento = new NeuroSeguimientoDiario();
                $nuevoSeguimiento->perfil_id = $perfil->id;
                $nuevoSeguimiento->fecha = $hoy;
                $nuevoSeguimiento->minutos_estudio_total = $minutos;
                $nuevoSeguimiento->meta_diaria_cumplida = ($minutos >= 30) ? 1 : 0;
                $nuevoSeguimiento->escudo_utilizado=0;
                $nuevoSeguimiento->create();
            }
            

            // Volvemos a verificar el estado de la meta usando el ORM
            $checkDiario = $seguimientoModel->find_first("perfil_id = {$perfil->id} AND fecha = '$hoy'");
            
            if ($checkDiario && (int)$checkDiario->meta_diaria_cumplida === 1 && $perfil->ultima_actividad_fecha !== $hoy) {
                $nueva_racha = (int)$perfil->racha_actual + 1;
                $max_racha = ($nueva_racha > (int)$perfil->racha_maxima) ? $nueva_racha : (int)$perfil->racha_maxima;

                $perfil->xp_total += 100;
                $perfil->xp_semanal += 100;
                $perfil->racha_actual = $nueva_racha;
                $perfil->racha_maxima = $max_racha;
                $perfil->ultima_actividad_fecha = $hoy;
                $perfil->update();

                echo json_encode(['status' => 'racha_up', 'message' => '¡Excelente! Meta diaria alcanzada: +100 XP y racha actualizada.']);
            } else {
                $perfil->xp_total += $xp;
                $perfil->xp_semanal += $xp;
                $perfil->update();

                echo json_encode(['status' => 'xp_up', 'message' => 'Bloque de estudio cargado correctamente.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se pudo procesar el perfil del usuario.']);
        }
        exit;
    }

    /**
     * Endpoint AJAX - Registrar Escape alternativo sin guiones
     * URL: /estudio/tregua
     */
    /**
     * Endpoint AJAX - Registrar Escape
     * URL: /estudio/tregua
     */
    public function tregua() {
        // Cancelamos vistas de forma absoluta e inmediata
        View::select(null,null);
        header('Content-Type: application/json');

        // Capturamos el POST procesado de forma limpia
        $motivo = Input::post('motivo');
        $motivo_limpio = $motivo ? strip_tags(trim($motivo)) : 'Distraccion';

        $sesionUsuario = Auth::get_active_identity();
        $alumno_id = (int)$sesionUsuario['id'];

        $perfilModel = new NeuroPerfiles();
        $perfil = $perfilModel->find_first("usuario_id = $alumno_id");
        
        if ($perfil) {
            $escape = new NeuroRegistroEscapes();
            $escape->perfil_id = (int)$perfil->id;
            $escape->motivo_escape = $motivo_limpio;
            $escape->tregua_aceptada = 1;
            
            if ($escape->create()) {
                echo json_encode(['status' => 'ok', 'message' => 'Intervalo de tregua registrado con éxito.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Error al guardar la pausa en el modelo.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No se encontró perfil asociado.']);
        }
        
        // Rompemos el flujo para evitar fugas de HTML de KumbiaPHP
        exit;
    }
}
<?php
class ComunidadController extends AppController {

    public $usuario_actual_id;
    public $mis_amigos;
    public $sugerencias_companeros;

    /**
     * Pantalla Principal de la Red de Cuidado Mutuo
     */
    public function index() {
        $sesion = Auth::get_active_identity();
        $this->usuario_actual_id = (int)$sesion['id'];

        // 🚀 APLICANDO TU SUGERENCIA: Usamos el modelo Usuarios para tirar el SQL crudo
        $uModel = new Usuarios();

        $sql_amigos = "
            SELECT c.id as relacion_id, u.id as amigo_id, u.nombre, u.telefono, p.latitud, p.longitud, p.updated_at, CONCAT(u.apellido,' ',u.nombre,' - ',u.legajo) as nombre_para_mostrar
            FROM uno_comunidad c
            INNER JOIN usuarios u ON c.amigo_id = u.id
            LEFT JOIN uno_pool p ON u.id = p.usuario_id
            WHERE c.usuario_id = {$this->usuario_actual_id}
        ";
        $this->mis_amigos = $uModel->find_all_by_sql($sql_amigos);

        $sql_sugerencias = "
            SELECT id, CONCAT(apellido,' ',nombre,' - ',legajo) AS nombre FROM usuarios 
            WHERE id != {$this->usuario_actual_id} 
              AND id NOT IN (SELECT amigo_id FROM uno_comunidad WHERE usuario_id = {$this->usuario_actual_id})
            LIMIT 10
        ";
        $this->sugerencias_companeros = $uModel->find_all_by_sql($sql_sugerencias);
    }

    /**
     * ACCIÓN POST: Agregar un compañero al círculo de confianza
     */
    public function agregar_companero() {
        // Desactivamos vistas de KumbiaPHP de raíz para responder JSON puro
        View::select(null);
        View::template(null);
        header('Content-Type: application/json; charset=utf-8');

        $sesion = Auth::get_active_identity();

        if (Input::hasPost('amigo_id')) {
            $amigo_id = (int)Input::post('amigo_id');
            $usuario_id = (int)$sesion['id'];
            
            $nuevo_companero = new UnoComunidad();
            $nuevo_companero->usuario_id=$usuario_id;
            $nuevo_companero->amigo_id=$amigo_id;
            $nuevo_companero->estado='aceptado';
            
            // Usamos el modelo para ejecutar la query de inserción
            //$sql = "INSERT IGNORE INTO uno_comunidad (usuario_id, amigo_id, estado) VALUES ({$usuario_id}, {$amigo_id}, 'aceptado')";

            //$uModel->sql($sql);
            $nuevo_companero->create();

            $msg = "" . $sesion['nombre'] . " te sumó a su círculo de confianza para cuidarse mutuamente.";
            $notificacion = new UnoNotificaciones();
            $notificacion->usuario_id=$amigo_id;
            $notificacion->mensaje=$msg;
            $notificacion->leido='0';
            //$sql_notif = "INSERT INTO uno_notificaciones (usuario_id, mensaje, leido) VALUES ({$amigo_id}, '{$msg}', 0)";
            //$uModel->sql($sql_notif);
            $notificacion->create();

            // Respondemos éxito en formato JSON en vez de usar Flash
            echo json_encode([
                'status' => 'ok',
                'message' => 'Compañero añadido a tu red de cuidado con éxito.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Petición inválida, falta el identificador del compañero.'
            ]);
        }
        exit; // Cortamos el flujo
    }

    /**
     * ACCIÓN POST: Quitar o dejar de seguir a un compañero
     */
    public function eliminar_relacion() {
        View::select(null, null);
        $sesion = Auth::get_active_identity();

        if (Input::hasPost('relacion_id')) {
            $relacion_id = (int)Input::post('relacion_id');
            $usuario_id = (int)$sesion['id'];

            $uModel = new Usuarios();
            $sql = "DELETE FROM uno_comunidad WHERE id = {$relacion_id} AND usuario_id = {$usuario_id}";
            $uModel->sql($sql);

            Flash::valid("Compañero removido de tu círculo.");
        }
        Redirect::to('comunidad');
    }

    /**
     * API AJAX: Obtener historial de chat e insertar mensajes nuevos en vivo
     */
    public function chat_box($amigo_id) {
        View::select(null, 'json');
        $sesion = Auth::get_active_identity();
        $mi_id = (int)$sesion['id'];
        $amigo_id = (int)$amigo_id;

        $uno_comunidad_chat = new UnoComunidadChats();
        $uModel = new Usuarios();

        if (Input::hasPost('mensaje')) {
            // Sanitizamos el texto de forma segura para evitar roturas
            // Usamos trim + htmlspecialchars para limpiar HTML/JS y addslashes
            // para escapar comillas antes de insertar en SQL crudo.
            $raw_mensaje = Input::post('mensaje');
            $texto_limpio = addslashes(trim(htmlspecialchars($raw_mensaje, ENT_QUOTES, 'UTF-8')));
            // $sql_insert = "INSERT INTO uno_comunidad_chats (emisor_id, receptor_id, mensaje) VALUES ({$mi_id}, {$amigo_id}, '{$texto_limpio}')";
            // $uModel->sql($sql_insert);

            $uno_comunidad_chat->emisor_id=$mi_id;
            $uno_comunidad_chat->receptor_id=$amigo_id;
            $uno_comunidad_chat->mensaje=$texto_limpio;
            $uno_comunidad_chat->create();

            $this->data = ['status' => 'enviado'];
            return;
        }

        $sql_select = "
            SELECT emisor_id, mensaje, date_format(created_at, '%H:%i') as hora, DATE_FORMAT(created_at, '%Y-%m-%d') as fecha_completa 
            FROM uno_comunidad_chats 
            WHERE (emisor_id = {$mi_id} AND receptor_id = {$amigo_id})
               OR (emisor_id = {$amigo_id} AND receptor_id = {$mi_id})
            ORDER BY id ASC
            LIMIT 50
        ";
        
        $this->data = $uModel->find_all_by_sql($sql_select);
    }
}
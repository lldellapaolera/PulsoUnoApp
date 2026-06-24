<?php


require_once VENDOR_PATH.'autoload.php';
use Google_Client;

class NotificacionesController extends AppController {

    protected function before_filter() {
        View::select(null, null);
        if (!Auth::is_valid()) {
            header('HTTP/1.1 401 Unauthorized');
            exit;
        }
    }

    /**
     * Guarda el token FCM del dispositivo
     */
    public function guardar_token_fcm() {

    
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['token'])) {
            echo json_encode(['status' => 'error', 'message' => 'Token no recibido']);
            exit;
        }

        $usuario_id = (int) Auth::get('id');
        $token = $data['token'];

        $fcmModel = new NotificacionesFcm();
        $existe = $fcmModel->find_first("conditions: usuario_id = $usuario_id AND fcm_token = '$token'");

        if (!$existe) {
            $fcmModel->usuario_id = $usuario_id;
            $fcmModel->fcm_token  = $token;
            $fcmModel->save();
        }

        echo json_encode(['status' => 'ok', 'message' => 'Token registrado en Firebase con éxito']);
        exit;
    }

    /**
     * Disparar push usando la API HTTP v1 de Firebase
     * URL de prueba: /notificaciones/enviar_push_fcm/[usuario_id]
     */
    public function enviar_push_fcm($usuario_id) {
        $usuario_id = (int) $usuario_id;
        
        $fcmModel = new NotificacionesFcm();
        $dispositivos = $fcmModel->find("conditions: usuario_id = $usuario_id");

        if (!$dispositivos) {
            echo "El usuario no tiene dispositivos vinculados a Firebase.";
            exit;
        }

        // 1. Autenticación con Google usando el JSON de la cuenta de servicio
        $rutaJson = APP_PATH . 'config/firebase_credentials.json';
        
        
        $client = new Google\Client();
        $client->setAuthConfig($rutaJson);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        
        // Obtener el Access Token de OAuth2 válido
        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];
        
        // Leer el Project ID directamente desde el JSON de Google
        $credenciales = json_decode(file_get_contents($rutaJson), true);
        $projectId = $credenciales['project_id'];

        $urlEndpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // 2. Enviar a cada dispositivo registrado del usuario
        foreach ($dispositivos as $dis) {
            $payload = [
                'message' => [
                    'token' => $dis->fcm_token,
                    'notification' => [
                        'title' => '¡Nueva Nota Cargada! 📝',
                        'body' => 'Se acaban de actualizar tus actas en la pestaña Cursada.'
                    ],
                    'data' => [
                        'url' => '/academico'
                    ]
                ]
            ];

            // Curl estándar para golpear a Google con el token OAuth2
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $urlEndpoint);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            
            $respuesta = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode == 200) {
                echo "[FCM OK] Notificación enviada al dispositivo ID: {$dis->id}<br>";
            } else {
                echo "[FCM FALLO] Código http: {$httpCode}. Respuesta: {$respuesta}<br>";
                // Si el token ya no es válido (por ejemplo, desinstalaron la app), lo limpiamos
                if ($httpCode == 404 || $httpCode == 410) {
                    $dis->delete();
                }
            }
        }
    }
}
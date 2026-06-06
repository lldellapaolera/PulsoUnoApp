<?php
/**
 * Controlador para la autenticación de usuarios
 * Mapea a app/views/login/
 */
class LoginController extends AppController {

    /**
     * Pantalla de inicio de sesión
     */

    protected function before_filter() {
        // Forzamos de forma segura el layout exclusivo para el login
        View::template('default_login'); 
    }

    public function index() {
        // Si ya tiene sesión activa real en el servidor, al index directo
        if (Session::has('usuario_id')) {
            Redirect::to('index');
            return;
        }

        if(Input::hasPost('usuario')) {
            $user=new Usuarios(Input::post('usuario'));
            
            //throw new Exception("Legajo: $usuario->legajo, Password: $usuario->password"); // Debug temporal, eliminar luego
            //$usuarioModel = new Usuarios();

            $usuario=Load::model('usuarios')->find_first("conditions: legajo = '$user->legajo'");
            
            $password_md5 = md5($user->password);
            
            if(isset($usuario)){
                $auth = new Auth("model", "class: usuarios", "legajo: $user->legajo", "password: $password_md5");
                if ($auth->authenticate()) {
                    
                    
                    Redirect::to('index');
                    return;
                } else {
                    Flash::error("Legajo o contraseña incorrectos.");
                    return;
                }
            }   

        }
    }

    /**
     * Procesa el envío del formulario de login
     */
    public function autenticar() {
       
    }

    /**
     * Cierra la sesión
     */
    public function salir() {
        
        if(Auth::is_valid()){

            $sesionUsuario=Auth::get_active_identity();
			Auth::destroy_identity();
			Redirect::to('login');
		}
    }
}
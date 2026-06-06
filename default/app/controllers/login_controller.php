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
							
             


            // $auth = new Auth("model", "class: usuarios", "legajo: $user->legajo", "password: $user->password");
			// if ($auth->authenticate()) {
            //     $usuario=Load::model('usuarios')->find_first("conditions: legajo = '$user->legajo'")->login($user->legajo, $user->password);
			//     Flash::valid("Bienvenido, {$usuario->nombre}!");
            //     Redirect::to('index');
            //     return;
								
			// } else {
			//     Flash::error("Legajo o contraseña incorrectos.");
            //     return;
				
			// }



            //$usuario = $usuarioModel->login($user->legajo, $user->password);

            // if ($usuario) {
            //     // Credenciales válidas, iniciar sesión 
            //     Session::set('usuario_id', $usuario->id);
            //     Session::set('usuario_nombre', $usuario->nombre);
            //     Session::set('usuario_apellido', $usuario->apellido);
            //     Session::set('usuario_legajo', $usuario->legajo);
            //     Session::set('usuario_rol', $usuario->rol);

            //     Flash::valid("Bienvenido, {$usuario->nombre}!");
            //     Redirect::to('index');
            //     return;
            // } else {
            //     // Credenciales inválidas
            //     Flash::error("Legajo o contraseña incorrectos.");
            // }

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
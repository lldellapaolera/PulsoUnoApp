<?php

class AdminUsuariosController extends AppController {

    protected function before_filter() {
        if (!Auth::is_valid() || Auth::get('rol') !== 'administrador') {
            Flash::error("No tenés permisos para acceder a esta sección.");
            Redirect::to('index');
            return false;
        }
    }

    public function index() {
        $u = new Usuarios();
        $this->listUsuarios = $u->find("order: apellido, nombre ASC");
    }

    public function guardar($id = null) {
        $this->usuario = new Usuarios();
        
        if (Input::hasPost('usuario')) {
            $data = Input::post('usuario');
            
            if (!empty($data['id'])) {
                $this->usuario = $this->usuario->find($data['id']);
            }
            
            // ASIGNACIÓN DE TODOS LOS CAMPOS DE TU TABLA REAL
            $this->usuario->legajo    = $data['legajo'];
            $this->usuario->nombre    = $data['nombre'];
            $this->usuario->apellido  = $data['apellido'];
            $this->usuario->email     = $data['email'];
            $this->usuario->rol       = $data['rol'];
            $this->usuario->telefono  = !empty($data['telefono']) ? $data['telefono'] : null;
            $this->usuario->localidad = !empty($data['localidad']) ? $data['localidad'] : null;
            
            // Manejo de contraseña (MD5)
            if (!empty($data['id'])) {
                if (!empty($data['password'])) {
                    $this->usuario->password = md5($data['password']);
                }
            } else {
                $this->usuario->password = md5($data['password']);
            }
            
            if ($this->usuario->save()) {
                Flash::valid("Usuario guardado con éxito.");
                Redirect::to('admin_usuarios');
            } else {
                Flash::error("Ocurrió un error al intentar guardar el usuario.");
            }
        } else if ($id) {
            $this->usuario = $this->usuario->find($id);
        }
    }

    public function eliminar($id) {
        $u = new Usuarios();
        $usuario = $u->find($id);
        
        if ($usuario && $usuario->delete()) {
            Flash::valid("Usuario eliminado correctamente.");
        } else {
            Flash::error("No se pudo eliminar el usuario.");
        }
        Redirect::to('admin_usuarios');
    }
}
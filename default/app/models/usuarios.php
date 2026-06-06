<?php
class Usuarios extends ActiveRecord {
    
    /**
     * Valida las credenciales de acceso de alumnos o profesores
     */
    public function login($legajo, $password) {
        $usuario = $this->find_first("conditions: legajo = '$legajo'");
        
        if ($usuario && password_verify($password, $usuario->password)) {
            return $usuario;
        }
        return false;
    }

    /**
     * Obtiene los alumnos inscritos en una comisión específica
     */
    public function getAlumnosPorComision($comision_id) {
        return $this->find_all_by_sql("
            SELECT u.id, u.legajo, u.nombre, u.apellido 
            FROM usuarios u
            INNER JOIN inscripciones i ON u.id = i.alumno_id
            WHERE i.comision_id = $comision_id
            ORDER BY u.apellido, u.nombre
        ");
    }
}
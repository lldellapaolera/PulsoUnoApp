
<?php
class Viajes extends ActiveRecord {

    /**
     * Busca coincidencias de viajes compartidos basándose en la localidad y el rol opuesto
     */
    public function buscarCoincidencias($usuario_id, $localidad, $rol_actual) {
        // Si el usuario es conductor, buscamos acompañantes que vayan para el mismo lado, y viceversa
        $buscarRol = ($rol_actual == 'conductor') ? 'acompañante' : 'conductor';
        
        // Excluimos los viajes propios y filtramos por viajes activos
        return $this->find_all_by_sql("
            SELECT v.*, u.nombre, u.apellido, u.telefono, u.localidad
            FROM viajes v
            INNER JOIN usuarios u ON v.usuario_id = u.id
            WHERE v.activo = 1 
              AND v.usuario_id != $usuario_id
              AND v.tipo_usuario = '$buscarRol'
              AND (u.localidad LIKE '%$localidad%' OR v.origen LIKE '%$localidad%')
            ORDER BY v.hora_salida ASC
        ");
    }
}
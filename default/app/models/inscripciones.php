<?php

class Inscripciones extends ActiveRecord {

    protected function initialize() {
        $this->belongs_to('comisiones');
        $this->belongs_to('usuarios', 'fk: alumno_id');
    }

    /**
     * Trae las materias que está cursando el alumno y calcula su presentismo real
     */
    public function getPresentismoAlumno($alumno_id) {
        // Sanitizamos el parámetro por seguridad
        $alumno_id = (int) $alumno_id;

        // Consulta SQL pura optimizada para agrupar asistencias
        return $this->find_all_by_sql("
            SELECT 
                i.id as inscripcion_id,
                i.estado_materia,
                m.nombre as materia_nombre,
                m.codigo as materia_codigo,
                -- Cuenta cuántas clases totales se registraron para esta comisión hasta la fecha
                (SELECT COUNT(*) FROM asistencias a WHERE a.comision_id = i.comision_id AND a.alumno_id = i.alumno_id) as clases_totales,
                -- Cuenta cuántas de esas clases el alumno tuvo presente = 1
                (SELECT COUNT(*) FROM asistencias a WHERE a.comision_id = i.comision_id AND a.alumno_id = i.alumno_id AND a.presente = 1) as clases_asistidas
            FROM inscripciones i
            INNER JOIN comisiones c ON i.comision_id = c.id
            INNER JOIN materias m ON c.materia_id = m.id
            WHERE i.alumno_id = $alumno_id AND i.estado_materia = 'cursando'
        ");
    }
}

<?php
class Asistencias extends ActiveRecord {

    /**
     * Registra de forma segura la asistencia tomada por el profesor (soporta lotes offline)
     */
    public function guardarAsistenciaSafe($comision_id, $alumno_id, $fecha, $presente) {
        // Buscamos si ya se había sincronizado previamente para evitar duplicados por reintentos de red
        $existe = $this->find_first("conditions: comision_id = $comision_id AND alumno_id = $alumno_id AND fecha = '$fecha'");
        
        if ($existe) {
            $existe->presente = $presente;
            return $existe->update();
        }

        $nueva = new Asistencias();
        $nueva->comision_id = $comision_id;
        $nueva->alumno_id = $alumno_id;
        $nueva->fecha = $fecha;
        $nueva->presente = $presente;
        return $nueva->save();
    }

    /**
     * Calcula el porcentaje de presentismo de un alumno en una comisión
     */
    public function calcularPorcentajeAsistencia($alumno_id, $comision_id) {
        $totalClases = $this->count("conditions: comision_id = $comision_id");
        if ($totalClases == 0) return 100; // Si no se tomó lista nunca, inicia al 100%

        $asistencias = $this->count("conditions: comision_id = $comision_id AND alumno_id = $alumno_id AND presente = 1");
        
        $porcentaje = ($asistencias / $totalClases) * 100;
        return round($porcentaje, 2);
    }
}
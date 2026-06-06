<?php

class Comisiones extends ActiveRecord {
    
    /**
     * Inicializa las relaciones de la tabla comisiones
     */
    protected function initialize() {
        // Una comisión pertenece a una materia específica
        $this->belongs_to('materias');
        
        // Una comisión pertenece a un profesor (que es un registro en la tabla usuarios)
        $this->belongs_to('usuarios', 'fk: profesor_id');
    }
}
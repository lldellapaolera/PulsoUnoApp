<?php

class Correlatividades extends ActiveRecord {
    
    protected function initialize() {
        // Apuntan ambas a la tabla materias
        $this->belongs_to('materias', 'fk: materia_id');
        $this->belongs_to('materias', 'fk: correlativa_id');
    }
}
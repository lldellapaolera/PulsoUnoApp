
<?php
class Materias extends ActiveRecord {  
    protected function initialize() {
        $this->has_many('comisiones');
        $this->has_many('correlatividades');
    }

    public function getMateriasPlan() {
        return $this->find("order: nombre ASC");
    }
}
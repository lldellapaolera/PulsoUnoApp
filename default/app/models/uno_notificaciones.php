<?php
class UnoNotificaciones extends ActiveRecord {
    protected function initialize() {
        $this->belongs_to('usuarios');
    }
}
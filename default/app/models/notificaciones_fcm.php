<?php
class NotificacionesFcm extends ActiveRecord {
    protected function initialize() {
        $this->belongs_to('usuarios');
    }
}
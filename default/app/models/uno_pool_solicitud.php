<?php
class UnoPoolSolicitud extends ActiveRecord {
    protected function initialize() {
        $this->belongs_to('uno_pool', 'fk: viaje_id');
        $this->belongs_to('usuarios', 'fk: pasajero_id');
    }
}
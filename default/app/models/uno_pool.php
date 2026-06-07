<?php

class UnoPool extends ActiveRecord {
    
    protected function initialize() {
        $this->belongs_to('usuarios');
    }
}
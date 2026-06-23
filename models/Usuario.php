<?php

class Usuario {
    public $usuario_id;
    public $email;
    public $senha;
    public $tipo; // 1 = ADMIN, 2 = CLIENTE, 3 = FORNECEDOR

    public function __construct($email = null, $senha = null, $tipo = null, $usuario_id = null) {
        $this->email      = $email;
        $this->senha      = $senha;
        $this->tipo       = $tipo;
        $this->usuario_id = $usuario_id;
    }
}

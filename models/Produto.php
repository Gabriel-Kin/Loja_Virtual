<?php

class Produto {
    public $produto_id;
    public $fornecedor_id;
    public $nome;
    public $descricao;

    public function __construct($nome = null, $descricao = null, $fornecedor_id = null, $produto_id = null) {
        $this->nome          = $nome;
        $this->descricao     = $descricao;
        $this->fornecedor_id = $fornecedor_id;
        $this->produto_id    = $produto_id;
    }
}

<?php

class Estoque {
    public $estoque_id;
    public $produto_id;
    public $quantidade;
    public $preco;

    public function __construct($produto_id = null, $quantidade = null, $preco = null, $estoque_id = null) {
        $this->produto_id = $produto_id;
        $this->quantidade = $quantidade;
        $this->preco      = $preco;
        $this->estoque_id = $estoque_id;
    }
}

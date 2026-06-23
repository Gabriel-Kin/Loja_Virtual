<?php

class Endereco {
    public $endereco_id;
    public $cidade;
    public $estado;
    public $rua;
    public $numero;
    public $complemento;
    public $bairro;
    public $cep;

    public function __construct($dados = []) {
        $this->endereco_id = $dados['endereco_id'] ?? null;
        $this->cidade      = $dados['cidade']      ?? null;
        $this->estado      = $dados['estado']      ?? null;
        $this->rua         = $dados['rua']         ?? null;
        $this->numero      = $dados['numero']      ?? null;
        $this->complemento = $dados['complemento'] ?? null;
        $this->bairro      = $dados['bairro']      ?? null;
        $this->cep         = $dados['cep']         ?? null;
    }
}

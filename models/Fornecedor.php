<?php

class Fornecedor {
    public $fornecedor_id;
    public $usuario_id;
    public $endereco_id;
    public $nome;
    public $descricao;
    public $telefone;

    public function __construct($dados = []) {
        $this->fornecedor_id = $dados['fornecedor_id'] ?? null;
        $this->usuario_id    = $dados['usuario_id']    ?? null;
        $this->endereco_id   = $dados['endereco_id']   ?? null;
        $this->nome          = $dados['nome']          ?? null;
        $this->descricao     = $dados['descricao']     ?? null;
        $this->telefone      = $dados['telefone']      ?? null;
    }
}

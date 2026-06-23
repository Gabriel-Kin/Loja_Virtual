<?php

class Cliente {
    public $cliente_id;
    public $usuario_id;
    public $endereco_id;
    public $nome;
    public $telefone;
    public $cartao_credito;

    public function __construct($dados = []) {
        $this->cliente_id     = $dados['cliente_id']     ?? null;
        $this->usuario_id     = $dados['usuario_id']     ?? null;
        $this->endereco_id    = $dados['endereco_id']    ?? null;
        $this->nome           = $dados['nome']           ?? null;
        $this->telefone       = $dados['telefone']       ?? null;
        $this->cartao_credito = $dados['cartao_credito'] ?? null;
    }
}

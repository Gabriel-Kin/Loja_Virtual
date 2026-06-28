<?php

class ProdutoImagem {
    public ?int $produto_imagem_id;
    public int $produto_id;
    public string $caminho;
    public ?string $data_cadastro;

    /**
     * Construtor da classe ProdutoImagem
     * * @param int $produto_id ID do produto ao qual a imagem pertence
     * @param string $caminho Caminho relativo do arquivo no servidor
     * @param int|null $produto_imagem_id ID autoincremento do banco (opcional no cadastro)
     * @param string|null $data_cadastro Data de inserção gerada pelo banco (opcional)
     */
    public function __construct(int $produto_id, string $caminho, ?int $produto_imagem_id = null, ?string $data_cadastro = null) {
        $this->produto_id = $produto_id;
        $this->caminho = $caminho;
        $this->produto_imagem_id = $produto_imagem_id;
        $this->data_cadastro = $data_cadastro;
    }
}
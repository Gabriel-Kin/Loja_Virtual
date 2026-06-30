<?php

/**
 * Carrinho de compras (US04).
 *
 * Mantém os itens em $_SESSION['carrinho'] para que o carrinho funcione
 * mesmo sem o cliente estar logado (o login só é exigido na finalização — US05).
 *
 * Cada item é guardado no formato:
 * produto_id => [
 * 'produto_id'     => int,
 * 'nome'           => string,
 * 'fornecedor'     => string,
 * 'preco'          => float,
 * 'quantidade'     => int,
 * 'imagem_caminho' => string // <-- ADICIONADO
 * ]
 *
 * Esta classe NÃO acessa o banco: a validação de estoque é feita no
 * endpoint (carrinho_ajax.php), que consulta o ProdutoDAO. Assim a regra
 * de negócio de persistência fica separada da manipulação da sessão.
 */
class Carrinho {

    /** Garante que a estrutura do carrinho exista na sessão. */
    private static function init() {
        if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
            $_SESSION['carrinho'] = [];
        }
    }

    /**
     * Adiciona uma quantidade ao item. Se o produto já estiver no carrinho,
     * a quantidade é somada à existente.
     * * AJUSTADO: Incluído o parâmetro opcional $imagem_caminho
     */
    public static function adicionar($produto_id, $nome, $preco, $quantidade, $fornecedor = "", $imagem_caminho = "") {
        self::init();
        $produto_id = (int) $produto_id;
        $quantidade = (int) $quantidade;

        if (isset($_SESSION['carrinho'][$produto_id])) {
            $_SESSION['carrinho'][$produto_id]['quantidade'] += $quantidade;
            // Opcional: Atualiza o caminho da imagem caso tenha entrado vazio antes
            if (!empty($imagem_caminho)) {
                $_SESSION['carrinho'][$produto_id]['imagem_caminho'] = $imagem_caminho;
            }
        } else {
            $_SESSION['carrinho'][$produto_id] = [
                'produto_id'     => $produto_id,
                'nome'           => $nome,
                'fornecedor'     => $fornecedor,
                'preco'          => (float) $preco,
                'quantidade'     => $quantidade,
                'imagem_caminho' => $imagem_caminho, // <-- SALVA O CAMINHO NO ITEM
            ];
        }
    }

    /** Define a quantidade absoluta de um item (usado ao alterar no carrinho). */
    public static function atualizar($produto_id, $quantidade) {
        self::init();
        $produto_id = (int) $produto_id;
        $quantidade = (int) $quantidade;

        if (!isset($_SESSION['carrinho'][$produto_id])) {
            return;
        }
        if ($quantidade <= 0) {
            self::remover($produto_id);
            return;
        }
        $_SESSION['carrinho'][$produto_id]['quantidade'] = $quantidade;
    }

    /** Remove um item do carrinho. */
    public static function remover($produto_id) {
        self::init();
        $produto_id = (int) $produto_id;
        unset($_SESSION['carrinho'][$produto_id]);
    }

    /** Esvazia o carrinho (útil após finalizar o pedido — US05). */
    public static function limpar() {
        $_SESSION['carrinho'] = [];
    }

    /** Quantidade atualmente no carrinho para um produto específico. */
    public static function quantidadeDoProduto($produto_id) {
        self::init();
        $produto_id = (int) $produto_id;
        return $_SESSION['carrinho'][$produto_id]['quantidade'] ?? 0;
    }

    /** Lista os itens com o valor total de cada linha calculado. */
    public static function itens() {
        self::init();
        $itens = [];
        foreach ($_SESSION['carrinho'] as $item) {
            $item['subtotal'] = $item['preco'] * $item['quantidade'];
            $itens[] = $item;
        }
        return $itens;
    }

    /** Valor total do carrinho. */
    public static function total() {
        self::init();
        $total = 0.0;
        foreach ($_SESSION['carrinho'] as $item) {
            $total += $item['preco'] * $item['quantidade'];
        }
        return $total;
    }

    /** Soma das quantidades (para um eventual contador no header). */
    public static function quantidadeTotal() {
        self::init();
        $qtd = 0;
        foreach ($_SESSION['carrinho'] as $item) {
            $qtd += $item['quantidade'];
        }
        return $qtd;
    }
}
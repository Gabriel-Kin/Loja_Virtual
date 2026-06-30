<?php
/**
 * Página do Carrinho de Compras (US04).
 *
 * Layout inspirado no Mercado Livre: lista de itens à esquerda (card) e
 * um resumo da compra à direita. Todas as operações (alterar quantidade e
 * remover) são feitas via AJAX (fetch) contra carrinho_ajax.php, atualizando
 * o total e o contador do header sem recarregar a página.
 *
 * Os produtos são adicionados pela vitrine (public/index.php), que chama o
 * mesmo endpoint AJAX.
 */

session_start();
require_once __DIR__ . "/../../config/bootstrap.php";

// Aviso de status vindo da finalização do pedido (US05).
$bannerTexto = "";
$bannerClasse = "";
switch ($_GET['status'] ?? '') {
    case 'sucesso':
        $num = htmlspecialchars($_GET['pedido'] ?? '');
        $bannerTexto  = "Pedido nº $num criado com sucesso! O estoque foi atualizado.";
        $bannerClasse = "cart-msg-ok";
        break;
    case 'erro':
        $bannerTexto  = "Não foi possível concluir: " . htmlspecialchars($_GET['msg'] ?? 'erro desconhecido.');
        $bannerClasse = "cart-msg-erro";
        break;
    case 'vazio':
        $bannerTexto  = "Seu carrinho está vazio.";
        $bannerClasse = "cart-msg-erro";
        break;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Carrinho - TechStore</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(ROOT_PATH . '/css/style.css') ?>">
</head>
<body>

    <?php include ROOT_PATH . "/views/layouts/header.php"; ?>

    <div class="container">
        <h2 class="carrinho-titulo">Carrinho</h2>

        <?php if ($bannerTexto): ?>
            <div class="cart-msg <?= $bannerClasse ?>"><?= $bannerTexto ?></div>
        <?php endif; ?>

        <div id="cart-msg" class="cart-msg" style="display:none;"></div>

        <div class="carrinho-layout">

            <div class="carrinho-itens">
                <div class="carrinho-card">
                    <div id="cart-body"></div>
                    <div id="cart-vazio" class="carrinho-vazio" style="display:none;">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <p>Seu carrinho está vazio.</p>
                        <a href="<?= BASE_URL ?>/public/index.php" class="btn">Ver produtos</a>
                    </div>
                </div>
            </div>

            <aside class="carrinho-resumo">
                <div class="carrinho-card">
                    <h3>Resumo da compra</h3>
                    <div class="resumo-linha">
                        <span>Produtos (<span id="resumo-qtd">0</span>)</span>
                        <span id="resumo-subtotal">R$ 0,00</span>
                    </div>
                    <div class="resumo-total">
                        <span>Total</span>
                        <span id="cart-total">R$ 0,00</span>
                    </div>
                    <button class="btn btn-continuar" id="btn-continuar" onclick="encerrarPedido()">
                        Encerrar pedido
                    </button>
                </div>
            </aside>

        </div>
    </div>

    <script>
        const AJAX_URL = "<?= BASE_URL ?>/views/carrinho/carrinho_ajax.php";
        const BASE_URL_SISTEMA = "<?= BASE_URL ?>"; // Injeta a constante para uso nas imagens do JavaScript

        function formatarBRL(valor) {
            return "R$ " + Number(valor).toLocaleString("pt-BR", {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });
        }

        function mostrarMensagem(texto, sucesso) {
            const box = document.getElementById("cart-msg");
            if (!texto) { box.style.display = "none"; return; }
            box.textContent = texto;
            box.style.display = "block";
            box.classList.toggle("cart-msg-ok", !!sucesso);
            box.classList.toggle("cart-msg-erro", !sucesso);
        }

        // Atualiza o contador de itens no ícone do carrinho (header).
        function atualizarBadgeHeader(quantidade) {
            const badge = document.getElementById("cart-badge");
            if (!badge) return;
            badge.textContent = quantidade;
            badge.style.display = Navigaquantidade = quantidade > 0 ? "" : "none";
        }

        async function enviarAcao(params) {
            try {
                const resposta = await fetch(AJAX_URL, {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: new URLSearchParams(params).toString()
                });
                const dados = await resposta.json();
                renderizarCarrinho(dados.carrinho);
                mostrarMensagem(dados.mensagem, dados.ok);
                return dados;
            } catch (e) {
                mostrarMensagem("Falha de comunicação com o servidor.", false);
            }
        }

        // Desenha a lista de itens e o resumo a partir do estado do servidor.
        function renderizarCarrinho(carrinho) {
            const corpo = document.getElementById("cart-body");
            const vazio = document.getElementById("cart-vazio");
            corpo.innerHTML = "";

            const itens = carrinho.itens || [];

            if (itens.length === 0) {
                vazio.style.display = "block";
            } else {
                vazio.style.display = "none";
                itens.forEach(function (item) {
                    const linha = document.createElement("div");
                    linha.className = "carrinho-item";

                    // ADJUSTED: Monta a imagem dinamicamente ou renderiza o ícone caso o item retorne vazio do banco
                    let htmlFoto = '<div class="item-foto" style="width:60px; height:60px; display:flex; align-items:center; justify-content:center; background:#f9f9f9; border:1px solid #eee; border-radius:4px;"><i class="fa-solid fa-image" style="color:#ccc; font-size:20px;"></i></div>';
                    
                    // Verifica se a chave imagem_caminho ou caminho existe no objeto de sessão
                    let caminhoImagem = item.imagem_caminho || item.caminho || '';
                    if (caminhoImagem) {
                        htmlFoto = '<div class="item-foto" style="width:60px; height:60px; border-radius:4px; overflow:hidden; border:1px solid #eee;">' +
                                   '<img src="' + BASE_URL_SISTEMA + caminhoImagem + '" style="width:100%; height:100%; object-fit:cover; display:block;">' +
                                   '</div>';
                    }

                    linha.innerHTML =
                        htmlFoto +
                        '<div class="item-info">' +
                            '<span class="item-nome">' + item.nome + '</span>' +
                            (item.fornecedor ? '<span class="item-fornecedor">Vendido por ' + item.fornecedor + '</span>' : '') +
                            '<span class="item-preco-unit">' + formatarBRL(item.preco) + ' / un.</span>' +
                            '<button class="item-remover" onclick="removerItem(' + item.produto_id + ')">' +
                                '<i class="fa-regular fa-trash-can"></i> Excluir</button>' +
                        '</div>' +
                        '<div class="item-stepper">' +
                            '<button class="stepper-btn" onclick="alterarQtd(' + item.produto_id + ', ' + (item.quantidade - 1) + ')">&minus;</button>' +
                            '<input type="number" class="stepper-input" min="1" value="' + item.quantidade + '" ' +
                                'onchange="alterarQtd(' + item.produto_id + ', this.value)">' +
                            '<button class="stepper-btn" onclick="alterarQtd(' + item.produto_id + ', ' + (item.quantidade + 1) + ')">+</button>' +
                        '</div>' +
                        '<div class="item-subtotal">' + formatarBRL(item.subtotal) + '</div>';
                    corpo.appendChild(linha);
                });
            }

            document.getElementById("resumo-qtd").textContent = carrinho.quantidade_total;
            document.getElementById("resumo-subtotal").textContent = carrinho.total_formatado;
            document.getElementById("cart-total").textContent = carrinho.total_formatado;
            document.getElementById("btn-continuar").disabled = itens.length === 0;
            atualizarBadgeHeader(carrinho.quantidade_total);
        }

        function alterarQtd(produtoId, quantidade) {
            enviarAcao({ acao: "atualizar", produto_id: produtoId, quantidade: quantity });
        }

        function removerItem(produtoId) {
            enviarAcao({ acao: "remover", produto_id: produtoId });
        }

        function encerrarPedido() {
            if (!confirm("Deseja encerrar o pedido? Os itens do carrinho serão registrados como uma compra.")) {
                return;
            }
            window.location.href = "<?= BASE_URL ?>/views/carrinho/finalizar.php";
        }

        // Carrega o estado inicial do carrinho ao abrir a página.
        enviarAcao({ acao: "listar" });
    </script>

</body>
</html>
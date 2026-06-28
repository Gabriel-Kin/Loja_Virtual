<?php
session_start();
require_once __DIR__ . "/../../config/bootstrap.php";

if (!isset($_SESSION['usuario_id'])) { header("Location: " . BASE_URL . "/public/index.php"); exit; }


$db = getDB();
$produtoDAO   = new ProdutoDAO($db);
$estoqueDAO   = new EstoqueDAO($db);
$fornecedorDAO = new FornecedorDAO($db);

// Processar Cadastro (produto + estoque numa única transação)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bt_cadastrar'])) {
    try {
        $db->beginTransaction();

        $produto = new Produto($_POST['nome'], $_POST['descricao'], $_POST['fornecedor_id']);
        $produtoId = $produtoDAO->inserir($produto);

        $estoqueDAO->inserir(new Estoque($produtoId, $_POST['qtd'], $_POST['preco']));

        $db->commit();
        echo "<script>
            alert('Produto e estoque criados com sucesso!');
            window.location.href='" . BASE_URL . "/views/produtos/produtos.php';
        </script>";
    } catch (Exception $e) {
        $db->rollBack();
        die("ERRO AO CRIAR PRODUTO: " . $e->getMessage());
    }
}

// Busca fornecedores para o Select (Combo Box)
$fornecedores = $fornecedorDAO->listarParaSelect();

// Busca de Produtos
$busca = $_GET['search'] ?? "";
$lista = $produtoDAO->consultar($busca);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(ROOT_PATH . '/css/style.css') ?>">
    <title>Gestão de Produtos</title>
</head>
<body>
    <?php include ROOT_PATH . "/views/layouts/header.php"; ?>
    
    <div class="container">

        <div class="lista-toolbar">
            <h2>Produtos</h2>
            <button type="button" class="btn" onclick="abrirModal()">
                <i class="fa-solid fa-plus"></i> Novo Produto
            </button>
        </div>

        <form method="GET" class="lista-busca">
            <div class="busca-campo">
                <i class="fa-solid fa-magnifying-glass busca-icone"></i>
                <input type="text" name="search" placeholder="Buscar por nome ou código..." value="<?= htmlspecialchars($busca) ?>">
            </div>
            <button type="submit" class="btn">Buscar</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Cód</th>
                    <th>Nome</th>
                    <th>Fornecedor</th>
                    <th>Estoque</th>
                    <th>Preço</th>
                    <th style="width:60px; text-align:center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($lista as $p): ?>
                <tr>
                    <td><?= $p['produto_id'] ?></td>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['fornecedor_nome']) ?></td>
                    <td><?= $p['quantidade'] ?></td>
                    <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                    <td style="text-align:center;">
                        <div class="kebab-wrap">
                            <button type="button" class="kebab-btn" onclick="alternarKebab(this)" aria-label="Ações" aria-haspopup="true">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <div class="kebab-menu">
                                <a href="<?= BASE_URL ?>/views/produtos/editar_produto.php?id=<?= $p['produto_id'] ?>">
                                    <i class="fa-solid fa-pen"></i> Alterar
                                </a>
                                <a href="<?= BASE_URL ?>/views/produtos/excluir_produto.php?id=<?= $p['produto_id'] ?>"
                                   class="kebab-item-perigo"
                                   onclick="return confirm('Excluir este produto?')">
                                    <i class="fa-solid fa-trash"></i> Remover
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (count($lista) === 0): ?>
                <tr><td colspan="6" style="text-align:center;">Nenhum produto encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal: novo produto -->
    <div id="modal-novo-produto" class="modal-overlay" onclick="if (event.target === this) fecharModal()">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Novo Produto</h3>
                <button type="button" class="modal-close" onclick="fecharModal()" aria-label="Fechar">&times;</button>
            </div>
            <form method="POST">
                <input type="text" name="nome" placeholder="Nome do Produto" required>
                <textarea name="descricao" placeholder="Descrição"></textarea>

                <label>Fornecedor:</label>
                <select name="fornecedor_id" required>
                    <option value="">Selecione um fornecedor</option>
                    <?php foreach($fornecedores as $f): ?>
                        <option value="<?= $f['fornecedor_id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                    <?php endforeach; ?>
                </select>

                <input type="number" name="qtd" placeholder="Quantidade inicial" required>
                <input type="number" step="0.01" name="preco" placeholder="Preço (ex: 99.90)" required>

                <button type="submit" name="bt_cadastrar" class="btn" style="width:100%; margin-top:8px;">Salvar Produto</button>
            </form>
        </div>
    </div>

    <script>
        // Modal de novo produto
        function abrirModal() { document.getElementById("modal-novo-produto").classList.add("aberto"); }
        function fecharModal() { document.getElementById("modal-novo-produto").classList.remove("aberto"); }

        // Menu de ações (três pontinhos)
        function alternarKebab(btn) {
            const menu = btn.nextElementSibling;
            const estavaAberto = menu.classList.contains("aberto");
            fecharTodosKebabs();
            if (!estavaAberto) menu.classList.add("aberto");
        }
        function fecharTodosKebabs() {
            document.querySelectorAll(".kebab-menu.aberto").forEach(function (m) {
                m.classList.remove("aberto");
            });
        }
        document.addEventListener("click", function (e) {
            if (!e.target.closest(".kebab-wrap")) fecharTodosKebabs();
        });
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") { fecharModal(); fecharTodosKebabs(); }
        });
    </script>
</body>
</html>

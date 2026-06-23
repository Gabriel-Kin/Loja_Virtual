<?php
session_start();
if (!isset($_SESSION['usuario_id'])) { header("Location: index.php"); exit; }

require_once "config/bootstrap.php";
$db = getDB();
$produtoDAO = new ProdutoDAO($db);
$estoqueDAO = new EstoqueDAO($db);
$mensagem = "";

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // Busca produto e estoque juntos (via DAO)
    $prod = $produtoDAO->buscarComEstoquePorId($id);
    if (!$prod) die("Produto não encontrado.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();

        // Update Produto
        $produtoDAO->atualizar(new Produto($_POST['nome'], $_POST['descricao'], null, $id));

        // Update Estoque (Manutenção)
        $estoqueDAO->atualizarPorProduto($id, $_POST['qtd'], $_POST['preco']);

        $db->commit();
        header("Location: produtos.php?msg=sucesso");
    } catch (Exception $e) {
        $db->rollBack();
        $mensagem = "Erro: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/style.css">
    <title>Manutenção de Estoque</title>
</head>
<body>
    <div class="container">
        <h2>Alterar Produto / Estoque</h2>
        <form method="POST">
            <label>Nome:</label>
            <input type="text" name="nome" value="<?= $prod['nome'] ?>" required>
            
            <label>Descrição:</label>
            <textarea name="descricao"><?= $prod['descricao'] ?></textarea>
            
            <label>Quantidade em Estoque:</label>
            <input type="number" name="qtd" value="<?= $prod['quantidade'] ?>" required>
            
            <label>Preço Unitário (R$):</label>
            <input type="number" step="0.01" name="preco" value="<?= $prod['preco'] ?>" required>
            
            <button type="submit" class="btn-edit" style="width:100%; padding:15px;">Salvar Alterações</button>
            <br><br>
            <a href="produtos.php" style="display:block; text-align:center;">Voltar</a>
        </form>
    </div>
</body>
</html>
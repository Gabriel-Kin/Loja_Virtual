<?php
session_start();
require_once "config/bootstrap.php";
if (isset($_GET['id'])) {
    $db = getDB();
    $produtoDAO = new ProdutoDAO($db);
    $estoqueDAO = new EstoqueDAO($db);
    try {
        $db->beginTransaction();
        // Remove estoque primeiro
        $estoqueDAO->excluirPorProduto($_GET['id']);
        // Remove produto
        $produtoDAO->excluir($_GET['id']);
        $db->commit();
    } catch (Exception $e) { $db->rollBack(); }
}
header("Location: produtos.php");
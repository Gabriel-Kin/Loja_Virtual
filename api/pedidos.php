<?php

require_once __DIR__ . "/../config/bootstrap.php";

header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "erro" => true,
        "mensagem" => "Método não permitido. Use GET."
    ]);

    exit;
}

try {
    $db = getDB();
    $pedidoDAO = new PedidoDAO($db);

    $numero = null;
    $cliente = null;

    if (isset($_GET["numero"]) && $_GET["numero"] !== "") {
        $numero = (int) $_GET["numero"];
    }

    if (isset($_GET["cliente"]) && $_GET["cliente"] !== "") {
        $cliente = trim($_GET["cliente"]);
    }

    $pedidos = $pedidoDAO->consultarPedidos($numero, $cliente);

    foreach ($pedidos as &$pedido) {
        $pedido["itens"] = $pedidoDAO->consultarItensPedido(
            (int) $pedido["pedido_id"]
        );
    }

    unset($pedido);

    echo json_encode([
        "erro" => false,
        "quantidade" => count($pedidos),
        "pedidos" => $pedidos
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);

    echo json_encode([
        "erro" => true,
        "mensagem" => "Erro ao consultar pedidos.",
        "detalhe" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
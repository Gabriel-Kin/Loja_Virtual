<?php
session_start();
require_once __DIR__ . "/../../config/bootstrap.php";

if (!isset($_SESSION['usuario_id'])) { header("Location: " . BASE_URL . "/public/index.php"); exit; }


$db = getDB();
$enderecoDAO   = new EnderecoDAO($db);
$usuarioDAO    = new UsuarioDAO($db);
$fornecedorDAO = new FornecedorDAO($db);
$mensagem = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();

        // 1. Endereço
        $enderecoId = $enderecoDAO->inserir(new Endereco($_POST));

        // 2. Usuário (Tipo 3 = Fornecedor) — senha protegida com hash
        $senhaHash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $usuarioId = $usuarioDAO->inserir(new Usuario($_POST['email'], $senhaHash, 3));

        // 3. Fornecedor vinculado ao usuário e endereço
        $fornecedor = new Fornecedor([
            'usuario_id'  => $usuarioId,
            'endereco_id' => $enderecoId,
            'nome'        => $_POST['nome'],
            'descricao'   => $_POST['descricao'],
            'telefone'    => $_POST['telefone']
        ]);
        $fornecedorDAO->inserir($fornecedor);

        $db->commit();
        $mensagem = "Fornecedor cadastrado com sucesso!";
    } catch (Exception $e) {
        $db->rollBack();
        $mensagem = "Erro ao cadastrar fornecedor: " . $e->getMessage();
    }
}

// Consulta (por código ou nome)
$busca = $_GET['search'] ?? "";
$lista = $fornecedorDAO->consultar($busca);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(ROOT_PATH . '/css/style.css') ?>">
    <title>Gestão de Fornecedores</title>
</head>
<body>
    <?php include ROOT_PATH . "/views/layouts/header.php"; ?>
    
    <div class="container">

        <div class="lista-toolbar">
            <h2>Fornecedores</h2>
            <button type="button" class="btn" onclick="abrirModal()">
                <i class="fa-solid fa-plus"></i> Novo Fornecedor
            </button>
        </div>

        <?php if ($mensagem): ?>
            <div class="lista-msg"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <form method="GET" class="lista-busca">
            <div class="busca-campo">
                <i class="fa-solid fa-magnifying-glass busca-icone"></i>
                <input type="text" name="search" placeholder="Buscar por nome ou código..." value="<?= htmlspecialchars($busca) ?>">
            </div>
            <button type="submit" class="btn">Buscar</button>
            <?php if ($busca !== ""): ?>
                <a href="<?= BASE_URL ?>/views/fornecedores/fornecedores.php" class="btn btn-secundario" style="text-decoration:none;">Limpar</a>
            <?php endif; ?>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Cód</th>
                    <th>Nome</th>
                    <th>Cidade</th>
                    <th>E-mail</th>
                    <th style="width:60px; text-align:center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($lista as $f): ?>
                <tr>
                    <td><?= $f['fornecedor_id'] ?></td>
                    <td><?= htmlspecialchars($f['nome']) ?></td>
                    <td><?= htmlspecialchars($f['cidade']) ?></td>
                    <td><?= htmlspecialchars($f['email']) ?></td>
                    <td style="text-align:center;">
                        <div class="kebab-wrap">
                            <button type="button" class="kebab-btn" onclick="alternarKebab(this)" aria-label="Ações" aria-haspopup="true">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <div class="kebab-menu">
                                <a href="<?= BASE_URL ?>/views/fornecedores/editar_fornecedor.php?id=<?= $f['fornecedor_id'] ?>">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </a>
                                <a href="<?= BASE_URL ?>/views/fornecedores/excluir_fornecedor.php?id=<?= $f['fornecedor_id'] ?>"
                                   class="kebab-item-perigo"
                                   onclick="return confirm('Deseja realmente remover este fornecedor?')">
                                    <i class="fa-solid fa-trash"></i> Remover
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (count($lista) === 0): ?>
                <tr><td colspan="5" style="text-align:center;">Nenhum fornecedor encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal: novo fornecedor -->
    <div id="modal-novo-fornecedor" class="modal-overlay" onclick="if (event.target === this) fecharModal()">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Novo Fornecedor</h3>
                <button type="button" class="modal-close" onclick="fecharModal()" aria-label="Fechar">&times;</button>
            </div>
            <form method="POST">
                <h4 style="margin:6px 0;">Dados Gerais</h4>
                <input type="text" name="nome" placeholder="Nome da Empresa" required>
                <input type="email" name="email" placeholder="E-mail (Login)" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <input type="text" name="telefone" placeholder="Telefone">
                <textarea name="descricao" placeholder="Descrição dos serviços"></textarea>

                <h4 style="margin:6px 0;">Endereço</h4>
                <input type="text" name="rua" placeholder="Rua" required>
                <input type="text" name="numero" placeholder="Número" style="width: 20%;">
                <input type="text" name="bairro" placeholder="Bairro" style="width: 78%;">
                <input type="text" name="cidade" placeholder="Cidade" required>
                <input type="text" name="estado" placeholder="Estado (UF)" maxlength="2">
                <input type="text" name="cep" placeholder="CEP">

                <button type="submit" class="btn" style="width:100%; margin-top:8px;">Salvar Fornecedor</button>
            </form>
        </div>
    </div>

    <script>
        // Modal de novo fornecedor
        function abrirModal() { document.getElementById("modal-novo-fornecedor").classList.add("aberto"); }
        function fecharModal() { document.getElementById("modal-novo-fornecedor").classList.remove("aberto"); }

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

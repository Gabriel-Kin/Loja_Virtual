<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../../config/bootstrap.php";

// Segurança: Apenas ADMIN (Tipo 1) acessa esta página
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 1) {
    header("Location: " . BASE_URL . "/public/index.php");
    exit;
}


$usuarioDAO = new UsuarioDAO(getDB());
$mensagem = "";

// Lógica de Cadastro (Inclusão)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bt_cadastrar'])) {
    try {
        $senhaHash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $usuario = new Usuario($_POST['email'], $senhaHash, $_POST['tipo']);
        if ($usuarioDAO->inserir($usuario)) {
            $mensagem = "Usuário cadastrado com sucesso!";
        }
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
    }
}

// Lógica de Consulta (Por Nome/Email ou Código)
$busca = $_GET['search'] ?? "";
$listaUsuarios = $usuarioDAO->listar($busca);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= filemtime(ROOT_PATH . '/css/style.css') ?>">
    <title>Gestão de Usuários</title>
</head>
<body>
    <?php include ROOT_PATH . "/views/layouts/header.php"; ?>
    
    <div class="container">

        <div class="lista-toolbar">
            <h2>Usuários</h2>
            <button type="button" class="btn" onclick="abrirModal()">
                <i class="fa-solid fa-plus"></i> Novo Usuário
            </button>
        </div>

        <form method="GET" class="lista-busca">
            <div class="busca-campo">
                <i class="fa-solid fa-magnifying-glass busca-icone"></i>
                <input type="text" name="search" placeholder="Buscar por e-mail ou ID..." value="<?= htmlspecialchars($busca) ?>">
            </div>
            <button type="submit" class="btn">Buscar</button>
        </form>

        <?php if ($mensagem): ?>
            <div class="lista-msg"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>E-mail</th>
                    <th>Tipo</th>
                    <th style="width:60px; text-align:center;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($listaUsuarios as $user): ?>
                <tr>
                    <td><?= $user['usuario_id'] ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <?php
                        if ($user['tipo'] == 1) echo "Admin";
                        elseif ($user['tipo'] == 2) echo "Cliente";
                        else echo "Fornecedor";
                        ?>
                    </td>
                    <td style="text-align:center;">
                        <div class="kebab-wrap">
                            <button type="button" class="kebab-btn" onclick="alternarKebab(this)" aria-label="Ações" aria-haspopup="true">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <div class="kebab-menu">
                                <a href="<?= BASE_URL ?>/views/usuarios/editar_usuario.php?id=<?= $user['usuario_id'] ?>">
                                    <i class="fa-solid fa-pen"></i> Editar
                                </a>
                                <a href="<?= BASE_URL ?>/views/usuarios/excluir_usuario.php?id=<?= $user['usuario_id'] ?>"
                                   class="kebab-item-perigo"
                                   onclick="return confirm('Deseja realmente excluir este usuário?')">
                                    <i class="fa-solid fa-trash"></i> Excluir
                                </a>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>

                <?php if (count($listaUsuarios) === 0): ?>
                <tr><td colspan="4" style="text-align:center;">Nenhum usuário encontrado.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal: novo usuário -->
    <div id="modal-novo-usuario" class="modal-overlay" onclick="if (event.target === this) fecharModal()">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Novo Usuário</h3>
                <button type="button" class="modal-close" onclick="fecharModal()" aria-label="Fechar">&times;</button>
            </div>
            <form method="POST">
                <input type="email" name="email" placeholder="E-mail" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <select name="tipo">
                    <option value="1">1 - Administrador</option>
                    <option value="2">2 - Cliente</option>
                    <option value="3">3 - Fornecedor</option>
                </select>
                <button type="submit" name="bt_cadastrar" class="btn" style="width:100%; margin-top:8px;">
                    Cadastrar Usuário
                </button>
            </form>
        </div>
    </div>

    <script>
        // Modal de novo usuário
        function abrirModal() {
            document.getElementById("modal-novo-usuario").classList.add("aberto");
        }
        function fecharModal() {
            document.getElementById("modal-novo-usuario").classList.remove("aberto");
        }

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
        // Fecha o menu ao clicar fora dele
        document.addEventListener("click", function (e) {
            if (!e.target.closest(".kebab-wrap")) fecharTodosKebabs();
        });
        // Fecha modal/menus com a tecla Esc
        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") { fecharModal(); fecharTodosKebabs(); }
        });
    </script>
</body>
</html>

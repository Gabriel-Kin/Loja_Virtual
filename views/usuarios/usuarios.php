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

$db = getDB();
$usuarioDAO = new UsuarioDAO($db);
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

// Lógica de Consulta com Paginação (8 itens por página)
$busca = trim($_GET['search'] ?? "");
$limite = 8;
$paginaAtual = (isset($_GET['pagina']) && ctype_digit($_GET['pagina'])) ? (int) $_GET['pagina'] : 1;
if ($paginaAtual < 1) {
    $paginaAtual = 1;
}

// Obtém os totais e calcula as páginas necessárias
$totalRegistros = $usuarioDAO->contarTotal($busca);
$totalPaginas = $totalRegistros > 0 ? (int) ceil($totalRegistros / $limite) : 1;

if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}

// Busca apenas os 8 registros da página ativa
$listaUsuarios = $usuarioDAO->listarPaginado($busca, $paginaAtual, $limite);

/** Função auxiliar para construir os links mantendo o termo buscado */
function urlPaginacao(int $numPagina, string $busca): string
{
    $params = ['pagina' => $numPagina];
    if ($busca !== "") {
        $params['search'] = $busca;
    }
    return "?" . http_build_query($params);
}
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
                <?php foreach ($listaUsuarios as $user): ?>
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
                    <tr>
                        <td colspan="4" style="text-align:center;">Nenhum usuário encontrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($totalPaginas > 1): ?>
            <div class="paginacao" style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px;">

                <?php if ($paginaAtual > 1): ?>
                    <a class="btn btn-secundario" href="<?= urlPaginacao($paginaAtual - 1, $busca) ?>">Anterior</a>
                <?php else: ?>
                    <button class="btn btn-secundario" disabled>Anterior</button>
                <?php endif; ?>

                <div class="paginas-numeros" style="display: flex; gap: 5px;">
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <?php if ($i === $paginaAtual): ?>
                            <button class="btn btn-ativo" disabled style="padding: 5px 10px; font-weight: bold; background-color: #007bff; color: #fff; border: 1px solid #007bff; cursor: not-allowed;">
                                <?= $i ?>
                            </button>
                        <?php else: ?>
                            <a class="btn btn-secundario" href="<?= urlPaginacao($i, $busca) ?>" style="padding: 5px 10px; text-decoration: none;">
                                <?= $i ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>

                <?php if ($paginaAtual < $totalPaginas): ?>
                    <a class="btn btn-secundario" href="<?= urlPaginacao($paginaAtual + 1, $busca) ?>">Próxima</a>
                <?php else: ?>
                    <button class="btn btn-secundario" disabled>Próxima</button>
                <?php endif; ?>

            </div>
            <div style="text-align: center; color: #777; font-size: 13px; margin-top: 8px;">
                Mostrando página <?= $paginaAtual ?> de <?= $totalPaginas ?> (Total de <?= $totalRegistros ?> registros)
            </div>
        <?php endif; ?>
    </div>

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
        function abrirModal() {
            document.getElementById("modal-novo-usuario").classList.add("aberto");
        }

        function fecharModal() {
            document.getElementById("modal-novo-usuario").classList.remove("aberto");
        }

        function alternarKebab(btn) {
            const menu = btn.nextElementSibling;
            const estavaAberto = menu.classList.contains("aberto");
            fecharTodosKebabs();
            if (!estavaAberto) menu.classList.add("aberto");
        }

        function fecharTodosKebabs() {
            document.querySelectorAll(".kebab-menu.aberto").forEach(function(m) {
                m.classList.remove("aberto");
            });
        }
        document.addEventListener("click", function(e) {
            if (!e.target.closest(".kebab-wrap")) fecharTodosKebabs();
        });
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                fecharModal();
                fecharTodosKebabs();
            }
        });
    </script>
</body>

</html>
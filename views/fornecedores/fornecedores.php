<?php
session_start();
require_once __DIR__ . "/../../config/bootstrap.php";

// Segurança: apenas usuários internos (ADMIN tipo 1) gerenciam fornecedores
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] != 1) {
    header("Location: " . BASE_URL . "/public/index.php");
    exit;
}

$db = getDB();
$enderecoDAO   = new EnderecoDAO($db);
$usuarioDAO    = new UsuarioDAO($db);
$fornecedorDAO = new FornecedorDAO($db);
$mensagem = "";

// Inclusão de fornecedor (endereço + usuário tipo 3 + fornecedor)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bt_cadastrar'])) {
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

// Lógica de Consulta com Paginação (8 itens por página - US02)
$busca = trim($_GET['search'] ?? "");
$limite = 8;
$paginaAtual = (isset($_GET['pagina']) && ctype_digit($_GET['pagina'])) ? (int) $_GET['pagina'] : 1;
if ($paginaAtual < 1) { $paginaAtual = 1; }

// Obtém totais e calcula as páginas necessárias
$totalRegistros = $fornecedorDAO->contarTotal($busca);
$totalPaginas = $totalRegistros > 0 ? (int) ceil($totalRegistros / $limite) : 1;

if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}

// Busca apenas o lote de 8 fornecedores da página ativa
$lista = $fornecedorDAO->consultarPaginado($busca, $paginaAtual, $limite);

/** Função auxiliar para construir os links mantendo o termo buscado */
function urlPaginacao(int $numPagina, string $busca): string {
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
        function abrirModal() { document.getElementById("modal-novo-fornecedor").classList.add("aberto"); }
        function fecharModal() { document.getElementById("modal-novo-fornecedor").classList.remove("aberto"); }

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
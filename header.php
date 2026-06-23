<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<header>
    <div class="header-container">
        <a href="index.php" class="logo"><i class="fa-solid fa-store"></i> TechStore</a>
        <nav>
            <?php if (isset($_SESSION['usuario_id'])): ?>
                <a href="dashboard.php"><i class="fa-solid fa-table-columns"></i> Painel</a>
                <span class="user-greeting">Olá, <?= $_SESSION['usuario_email'] ?></span>
                <a href="logout.php" class="btn-sair"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
            <?php else: ?>
                <a href="login.php" class="btn-login"><i class="fa-solid fa-right-to-bracket"></i> Entrar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
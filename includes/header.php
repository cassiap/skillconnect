<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!function_exists('get_flash')) {
    require_once __DIR__ . '/../config/helpers.php';
}

// Base URL dinâmica (funciona da raiz e de subpastas)
$_base = '/' . basename(dirname(__DIR__));

$isLogado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;
$nomeUsuario = $isLogado ? $_SESSION['nome'] : '';
$perfil = $isLogado ? $_SESSION['perfil'] : '';

// Coleta flash messages
$_flash_success = get_flash('success');
$_flash_error   = get_flash('error');
$_flash_info    = get_flash('info');
?>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- FontAwesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">

<!-- Meta para garantir a codificação correta -->
<meta charset="utf-8">

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand font-weight-bold text-primary" href="<?= $_base ?>/index.php">SkillConnect</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="<?= $_base ?>/index.php">Início</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $_base ?>/user/cursos.php">Cursos</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $_base ?>/user/vagas.php">Vagas</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= $_base ?>/user/contato.php">Contato</a></li>
            </ul>

            <ul class="navbar-nav">
                <?php if (!$isLogado): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $_base ?>/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $_base ?>/auth/register.php"><i class="fas fa-user-plus"></i> Criar Conta</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="perfilDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($nomeUsuario); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="perfilDropdown">
                            <a class="dropdown-item" href="<?= $_base ?>/user/meus-dados.php"><i class="fas fa-id-card"></i> Meus Dados</a>
                            <?php if ($perfil === 'admin'): ?>
                                <a class="dropdown-item" href="<?= $_base ?>/admin/admin.php"><i class="fas fa-cogs"></i> Painel Admin</a>
                                <a class="dropdown-item" href="<?= $_base ?>/admin/listarclientes.php"><i class="fas fa-users"></i> Listar Usuários</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="<?= $_base ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash Messages -->
<?php if ($_flash_success): ?>
    <div class="alert alert-success alert-dismissible fade show mx-3" role="alert">
        <?php echo htmlspecialchars($_flash_success); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>
<?php if ($_flash_error): ?>
    <div class="alert alert-danger alert-dismissible fade show mx-3" role="alert">
        <?php echo htmlspecialchars($_flash_error); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>
<?php if ($_flash_info): ?>
    <div class="alert alert-info alert-dismissible fade show mx-3" role="alert">
        <?php echo htmlspecialchars($_flash_info); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<!-- jQuery (obrigatório para DataTables e Bootstrap) -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

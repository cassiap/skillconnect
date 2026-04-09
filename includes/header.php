<?php
/**
 * Arquivo de cabeçalho da aplicação SkillConnect
 * 
 * Este arquivo contém a estrutura HTML do cabeçalho da aplicação, incluindo
 * navegação, autenticação de usuário, exibição de mensagens flash e estilos CSS.
 * Responsável por exibir a barra de navegação e verificar o status de login do usuário.
 * 
 * @author SkillConnect
 * @version 1.0
 */

if (!function_exists('get_flash')) {
    require_once __DIR__ . '/../config/helpers.php';
}

$_base = app_base_path();
$isLogado = isset($_SESSION['logado']) && $_SESSION['logado'] === true;
$nomeUsuario = $isLogado ? ($_SESSION['nome'] ?? '') : '';
$perfil = $isLogado ? ($_SESSION['perfil'] ?? '') : '';
$currentPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

$isHome = (strpos($currentPath, '/index.php') !== false);
$isCursos = (strpos($currentPath, '/user/cursos.php') !== false
    || strpos($currentPath, '/user/curso.php') !== false
    || strpos($currentPath, '/user/meus-cursos.php') !== false
    || strpos($currentPath, '/user/meu-curso.php') !== false
    || strpos($currentPath, '/user/inscrever.php') !== false);
$isVagas = (strpos($currentPath, '/user/vagas.php') !== false
    || strpos($currentPath, '/user/vaga.php') !== false
    || strpos($currentPath, '/user/candidatar.php') !== false
    || strpos($currentPath, '/user/minhas-candidaturas.php') !== false);
$isAssistente = (strpos($currentPath, '/user/assistente.php') !== false);
$isContato = (strpos($currentPath, '/user/contato.php') !== false);

$_flash_success = get_flash('success');
$_flash_error = get_flash('error');
$_flash_info = get_flash('info');
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
<meta charset="utf-8">
<style>
    .sc-header {
        background: #ffffff;
        border-bottom: 1px solid #e5e7eb;
    }
    .sc-brand {
        font-weight: 800;
        color: #0f172a !important;
        letter-spacing: .2px;
    }
    .sc-brand-mark {
        width: 12px;
        height: 12px;
        border-radius: 999px;
        display: inline-block;
        margin-right: 8px;
        background: linear-gradient(135deg, #1d4ed8 0%, #0f766e 100%);
        box-shadow: 0 0 0 4px rgba(29, 78, 216, .12);
    }
    .sc-nav-link {
        color: #334155 !important;
        font-weight: 500;
        border-radius: 10px;
        padding: 7px 11px !important;
        margin-right: 3px;
    }
    .sc-nav-link:hover {
        background: #f1f5f9;
        color: #0f172a !important;
    }
    .sc-nav-link.active {
        background: #eff6ff;
        color: #1d4ed8 !important;
    }
    .sc-auth-btn {
        border-radius: 999px;
        font-weight: 600;
        padding: 7px 14px !important;
    }
    .sc-auth-btn-login {
        border: 1px solid #e2e8f0;
        color: #0f172a !important;
        margin-right: 8px;
    }
    .sc-auth-btn-register {
        border: 1px solid #bfdbfe;
        background: #eff6ff;
        color: #1e3a8a !important;
    }
    .sc-flash-wrap {
        max-width: 1140px;
        margin: 14px auto 0;
        padding: 0 15px;
    }
</style>

<nav class="navbar navbar-expand-lg sc-header sticky-top">
    <div class="container">
        <a class="navbar-brand sc-brand" href="<?= $_base ?>/index.php">
            <span class="sc-brand-mark"></span>SkillConnect
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link sc-nav-link <?php echo $isHome ? 'active' : ''; ?>" href="<?= $_base ?>/index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link sc-nav-link <?php echo $isCursos ? 'active' : ''; ?>" href="<?= $_base ?>/user/cursos.php">Cursos</a></li>
                <li class="nav-item"><a class="nav-link sc-nav-link <?php echo $isVagas ? 'active' : ''; ?>" href="<?= $_base ?>/user/vagas.php">Vagas</a></li>
                <li class="nav-item"><a class="nav-link sc-nav-link <?php echo $isAssistente ? 'active' : ''; ?>" href="<?= $_base ?>/user/assistente.php">Assistente IA</a></li>
                <li class="nav-item"><a class="nav-link sc-nav-link <?php echo $isContato ? 'active' : ''; ?>" href="<?= $_base ?>/user/contato.php">Contato</a></li>
            </ul>

            <ul class="navbar-nav align-items-lg-center mt-3 mt-lg-0">
                <?php if (!$isLogado): ?>
                    <li class="nav-item">
                        <a class="nav-link sc-auth-btn sc-auth-btn-login" href="<?= $_base ?>/auth/login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link sc-auth-btn sc-auth-btn-register" href="<?= $_base ?>/auth/register.php"><i class="fas fa-user-plus"></i> Criar conta</a>
                    </li>
                <?php else: ?>
                    <?php if ($perfil === 'admin'): ?>
                        <li class="nav-item mr-2">
                            <a class="nav-link sc-auth-btn sc-auth-btn-register" href="<?= $_base ?>/admin/admin.php"><i class="fas fa-cogs"></i> Admin</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle sc-nav-link" href="#" id="perfilDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($nomeUsuario); ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="perfilDropdown">
                            <a class="dropdown-item" href="<?= $_base ?>/user/meus-dados.php"><i class="fas fa-id-card"></i> Meus dados</a>
                            <a class="dropdown-item" href="<?= $_base ?>/user/meus-cursos.php"><i class="fas fa-book-open"></i> Meus cursos</a>
                            <a class="dropdown-item" href="<?= $_base ?>/user/minhas-candidaturas.php"><i class="fas fa-briefcase"></i> Minhas vagas</a>
                            <a class="dropdown-item" href="<?= $_base ?>/user/meu-curriculo.php"><i class="fas fa-file-pdf"></i> Meu currículo</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="<?= $_base ?>/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                        </div>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="sc-flash-wrap">
    <?php if ($_flash_success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_flash_success); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>
    <?php if ($_flash_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_flash_error); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>
    <?php if ($_flash_info): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_flash_info); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
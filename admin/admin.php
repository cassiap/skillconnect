<?php
require_once __DIR__ . '/../config/helpers.php';
admin_check();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Administrador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-5">
    <h2 class="mb-4 text-primary">Painel do Administrador</h2>
    <p class="text-muted">Bem-vindo(a), <strong><?php echo htmlspecialchars($_SESSION['nome'] ?? ''); ?></strong>!</p>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-plus-circle fa-2x text-success mb-3"></i>
                    <h5 class="card-title">Cadastrar Vaga</h5>
                    <a href="cadastravaga.php" class="btn btn-outline-success btn-sm mt-2">Acessar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-2x text-primary mb-3"></i>
                    <h5 class="card-title">Ver Candidaturas</h5>
                    <a href="candidaturas.php" class="btn btn-outline-primary btn-sm mt-2">Acessar</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-body text-center">
                    <i class="fas fa-book fa-2x text-warning mb-3"></i>
                    <h5 class="card-title">Cadastrar Curso</h5>
                    <a href="cadastracurso.php" class="btn btn-outline-warning btn-sm mt-2">Acessar</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

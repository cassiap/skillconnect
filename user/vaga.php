<?php
/**
 * Página de visualização detalhada de uma vaga específica
 * 
 * Este arquivo exibe os detalhes completos de uma vaga de emprego,
 * incluindo título, empresa, descrição, requisitos e opção de candidatura.
 * 
 * @author Sistema SkillConnect
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
$isAdmin = isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin';

// Valida o ID da vaga
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Vaga não especificada.");
}

$id = intval($_GET['id']);
$sql = $isAdmin
    ? "SELECT * FROM vagas WHERE id = ?"
    : "SELECT * FROM vagas WHERE id = ? AND ativo = 1";
$stmt = $cx->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    flash('error', 'Vaga não encontrada.');
    redirect('vagas.php');
}

$vaga = $resultado->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($vaga['titulo']); ?> - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-5">
    <a href="vagas.php" class="btn btn-secondary mb-4">&larr; Voltar para lista de vagas</a>

    <div class="card shadow">
        <div class="card-body">
            <h2 class="text-primary"><?php echo htmlspecialchars($vaga['titulo']); ?></h2>

            <?php if ($vaga['empresa']): ?>
                <p class="text-muted mb-1"><strong>Empresa:</strong> <?php echo htmlspecialchars($vaga['empresa']); ?></p>
            <?php endif; ?>

            <p class="text-muted mb-1">
                <strong>Tipo:</strong> <?php echo htmlspecialchars($vaga['tipo']); ?> |
                <strong>Modalidade:</strong> <?php echo htmlspecialchars($vaga['modalidade']); ?>
            </p>

            <?php if ($vaga['salario']): ?>
                <p class="text-muted mb-1"><strong>Salario:</strong> <?php echo htmlspecialchars($vaga['salario']); ?></p>
            <?php endif; ?>

            <?php if ($vaga['cidade'] || $vaga['estado']): ?>
                <p class="text-muted mb-1">
                    <strong>Localização:</strong>
                    <?php echo htmlspecialchars(trim($vaga['cidade'] . ' / ' . $vaga['estado'], ' /')); ?>
                </p>
            <?php endif; ?>

            <h5 class="mt-4">Descrição da vaga</h5>
            <p><?php echo nl2br(htmlspecialchars($vaga['descricao'])); ?></p>

            <?php if ($vaga['requisitos']): ?>
                <h5 class="mt-3">Requisitos</h5>
                <p><?php echo nl2br(htmlspecialchars($vaga['requisitos'])); ?></p>
            <?php endif; ?>

            <?php if (!$isAdmin): ?>
                <a href="candidatar.php?vaga_id=<?php echo $vaga['id']; ?>" class="btn btn-success mt-3">
                    Quero me candidatar
                </a>
            <?php else: ?>
                <div class="alert alert-info mt-3 mb-0">
                    Visualizacao administrativa. Administradores nao se candidatam a vagas.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

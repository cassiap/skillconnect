<?php
require_once __DIR__ . '/../config/db.php';

// Verifica se o ID foi passado via URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Curso não especificado.");
}

$id = intval($_GET['id']);
$stmt = $cx->prepare("SELECT * FROM cursos WHERE id = ? AND ativo = 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    flash('error', 'Curso não encontrado.');
    redirect('cursos.php');
}

$curso = $resultado->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($curso['titulo']); ?> - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container mt-5">
    <a href="cursos.php" class="btn btn-secondary mb-4">&larr; Voltar para lista de cursos</a>

    <div class="card shadow">
        <div class="card-body">
            <h2 class="text-primary"><?php echo htmlspecialchars($curso['titulo']); ?></h2>

            <div class="row mt-3 mb-3">
                <div class="col-md-3">
                    <strong>Modalidade:</strong><br>
                    <?php echo htmlspecialchars($curso['modalidade']); ?>
                </div>
                <div class="col-md-3">
                    <strong>Nivel:</strong><br>
                    <?php echo htmlspecialchars($curso['nivel']); ?>
                </div>
                <div class="col-md-3">
                    <strong>Carga Horaria:</strong><br>
                    <?php echo $curso['carga_horaria'] ? $curso['carga_horaria'] . ' horas' : 'Não informada'; ?>
                </div>
                <div class="col-md-3">
                    <strong>Vagas:</strong><br>
                    <?php echo $curso['vagas'] > 0 ? $curso['vagas'] : 'Ilimitadas'; ?>
                </div>
            </div>

            <?php if ($curso['preco'] > 0): ?>
                <p><strong>Preco:</strong> R$ <?php echo number_format($curso['preco'], 2, ',', '.'); ?></p>
            <?php else: ?>
                <p><strong>Preco:</strong> <span class="text-success font-weight-bold">Gratuito</span></p>
            <?php endif; ?>

            <h5 class="mt-3">Descricao</h5>
            <p><?php echo nl2br(htmlspecialchars($curso['descricao'])); ?></p>

            <a href="inscrever.php?curso_id=<?php echo $curso['id']; ?>" class="btn btn-success mt-3">Quero me inscrever</a>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Busca cursos ativos
$stmt = $cx->prepare("SELECT * FROM cursos WHERE ativo = 1 ORDER BY id DESC");
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Cursos Disponíveis - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary">Cursos Profissionalizantes</h2>
        <?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin'): ?>
            <a href="../admin/cadastracurso.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Adicionar Curso
            </a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered" id="tabelaCursos" width="100%" cellspacing="0">
            <thead class="thead-light">
                <tr>
                    <th>Curso</th>
                    <th>Modalidade</th>
                    <th>Nivel</th>
                    <th>Carga Horaria</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while ($curso = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($curso['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($curso['modalidade']); ?></td>
                            <td><?php echo htmlspecialchars($curso['nivel']); ?></td>
                            <td><?php echo $curso['carga_horaria'] ? $curso['carga_horaria'] . 'h' : '—'; ?></td>
                            <td>
                                <a href="curso.php?id=<?php echo $curso['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye"></i> Ver Detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">Nenhum curso cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function() {
        if (!$.fn.DataTable.isDataTable('#tabelaCursos')) {
            $('#tabelaCursos').DataTable({
                "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json" }
            });
        }
    });
</script>

</body>
</html>

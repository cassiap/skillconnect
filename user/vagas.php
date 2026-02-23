<?php
require_once __DIR__ . '/../config/db.php';

// Busca vagas ativas
$stmt = $cx->prepare("SELECT * FROM vagas WHERE ativo = 1 ORDER BY id DESC");
$stmt->execute();
$resultado = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Vagas de Emprego - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary">Vagas de Emprego</h2>

        <?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin'): ?>
            <a href="../admin/cadastravaga.php" class="btn btn-success">Adicionar Vaga</a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered" id="tabelaVagas" width="100%" cellspacing="0">
            <thead class="thead-light">
                <tr>
                    <th>Titulo</th>
                    <th>Empresa</th>
                    <th>Tipo</th>
                    <th>Modalidade</th>
                    <th>Salario</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado->num_rows > 0): ?>
                    <?php while ($vaga = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vaga['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($vaga['empresa'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($vaga['tipo']); ?></td>
                            <td><?php echo htmlspecialchars($vaga['modalidade']); ?></td>
                            <td><?php echo htmlspecialchars($vaga['salario'] ?? ''); ?></td>
                            <td>
                                <a href="vaga.php?id=<?php echo $vaga['id']; ?>" class="btn btn-outline-primary btn-sm">
                                    Ver Detalhes
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Nenhuma vaga cadastrada no momento.</td>
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
        $('#tabelaVagas').DataTable({
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json"
            }
        });
    });
</script>

</body>
</html>

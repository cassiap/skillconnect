<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Permite acesso apenas para administradores
if (!isset($_SESSION['logado']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Busca candidaturas com dados do usuario e da vaga
$stmt = $cx->prepare("SELECT c.id, u.nome, u.email, v.titulo, c.curriculo_path, c.status, c.criado_em
                       FROM candidaturas c
                       JOIN usuarios u ON c.usuario_id = u.id
                       JOIN vagas v ON c.vaga_id = v.id
                       ORDER BY c.criado_em DESC");
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Candidaturas - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-5">
    <h2 class="text-primary mb-4">Candidaturas para Vagas</h2>

    <?php if ($resultado->num_rows > 0): ?>
        <div class="table-responsive">
            <table id="tabelaCandidaturas" class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Vaga</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Currículo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $resultado->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                            <td>
                                <?php
                                $badges = [
                                    'enviada'    => 'badge-info',
                                    'em_analise' => 'badge-warning',
                                    'aprovado'   => 'badge-success',
                                    'reprovado'  => 'badge-danger',
                                ];
                                $cls = $badges[$row['status']] ?? 'badge-secondary';
                                echo '<span class="badge ' . $cls . '">' . htmlspecialchars($row['status']) . '</span>';
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['criado_em'])); ?></td>
                            <td>
                                <?php if ($row['curriculo_path']): ?>
                                    <a href="../uploads/<?php echo htmlspecialchars($row['curriculo_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file-pdf"></i> Ver PDF
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">Nenhuma candidatura registrada ainda.</p>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
    $(document).ready(function () {
        $('#tabelaCandidaturas').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            }
        });
    });
</script>

</body>
</html>

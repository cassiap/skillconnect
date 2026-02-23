<?php
require_once __DIR__ . '/../config/db.php';

admin_check();

// Consulta todos os usuarios
$stmt = $cx->prepare("SELECT id, nome, cpf, telefone, email, perfil, ativo FROM usuarios ORDER BY id DESC");
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Listar Usuários - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-5">
    <h2 class="mb-4 text-primary">Usuários Cadastrados</h2>

    <div class="table-responsive">
        <table class="table table-bordered" id="tabelaUsuarios">
            <thead class="thead-dark">
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Telefone</th>
                    <th>Email</th>
                    <th>Perfil</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $resultado->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u['nome']); ?></td>
                        <td><?php echo htmlspecialchars($u['cpf'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($u['telefone'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $u['perfil'] === 'admin' ? 'badge-danger' : 'badge-primary'; ?>">
                                <?php echo htmlspecialchars($u['perfil']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="../auth/registeralterarcliente.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min.js"></script>
<script>
$(document).ready(function() {
    $('#tabelaUsuarios').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
        }
    });
});
</script>

</body>
</html>

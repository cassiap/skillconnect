<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Bloqueia acesso se não for admin
if (!isset($_SESSION['logado']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Verifica se o ID foi passado
if (!isset($_GET['id'])) {
    die("ID do usuário não informado.");
}

$id = intval($_GET['id']);
$stmt = $cx->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if (!$row = $result->fetch_assoc()) {
    die("Usuário não encontrado.");
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Usuário – SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-5">
    <h2 class="mb-4 text-primary">Editar Usuário</h2>
    <form method="POST" action="../admin/alterarclienteserver.php">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">

        <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" name="nome" id="nome" class="form-control"
                   value="<?php echo htmlspecialchars($row['nome']); ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="cpf">CPF</label>
                <input type="text" name="cpf" id="cpf" class="form-control"
                       value="<?php echo htmlspecialchars($row['cpf'] ?? ''); ?>">
            </div>
            <div class="form-group col-md-6">
                <label for="telefone">Telefone</label>
                <input type="text" name="telefone" id="telefone" class="form-control"
                       value="<?php echo htmlspecialchars($row['telefone'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="cep">CEP</label>
            <input type="text" name="cep" id="cep" class="form-control"
                   value="<?php echo htmlspecialchars($row['cep'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" name="email" id="email" class="form-control"
                   value="<?php echo htmlspecialchars($row['email']); ?>">
        </div>

        <button type="submit" class="btn btn-primary btn-block">Salvar Alterações</button>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>

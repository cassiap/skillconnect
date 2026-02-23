<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['logado']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        echo "<script>alert('Sessão expirada. Tente novamente.');</script>";
    } else {
    $titulo    = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $carga     = intval($_POST['carga_horaria'] ?? 0);
    $modalidade = $_POST['modalidade'] ?? 'online';
    $nivel     = $_POST['nivel'] ?? 'basico';
    $preco     = floatval($_POST['preco'] ?? 0);
    $vagas     = intval($_POST['vagas'] ?? 0);

    $stmt = $cx->prepare("INSERT INTO cursos (titulo, descricao, carga_horaria, modalidade, nivel, preco, vagas) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param("ssissdi", $titulo, $descricao, $carga, $modalidade, $nivel, $preco, $vagas);

    if ($stmt->execute()) {
        echo "<script>alert('Curso cadastrado com sucesso!'); window.location.href='../user/cursos.php';</script>";
    } else {
        echo "<script>alert('Erro ao cadastrar curso.');</script>";
    }
    $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Curso</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container mt-5">
    <h2 class="mb-4 text-primary">Cadastrar Novo Curso</h2>
    <form method="POST" action="">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label for="titulo">Título do Curso</label>
            <input type="text" class="form-control" name="titulo" id="titulo" required>
        </div>
        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea class="form-control" name="descricao" id="descricao" rows="4" required></textarea>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="carga_horaria">Carga Horária (horas)</label>
                <input type="number" class="form-control" name="carga_horaria" id="carga_horaria" min="0">
            </div>
            <div class="form-group col-md-4">
                <label for="modalidade">Modalidade</label>
                <select class="form-control" name="modalidade" id="modalidade">
                    <option value="online">Online</option>
                    <option value="presencial">Presencial</option>
                    <option value="hibrido">Híbrido</option>
                </select>
            </div>
            <div class="form-group col-md-4">
                <label for="nivel">Nível</label>
                <select class="form-control" name="nivel" id="nivel">
                    <option value="basico">Básico</option>
                    <option value="intermediario">Intermediário</option>
                    <option value="avancado">Avançado</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="preco">Preço (R$)</label>
                <input type="number" class="form-control" name="preco" id="preco" step="0.01" min="0" value="0.00">
            </div>
            <div class="form-group col-md-6">
                <label for="vagas">Vagas Disponíveis</label>
                <input type="number" class="form-control" name="vagas" id="vagas" min="0" value="0">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Salvar Curso</button>
        <a href="../user/cursos.php" class="btn btn-secondary ml-2">Voltar</a>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>

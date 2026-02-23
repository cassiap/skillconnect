<?php
require_once __DIR__ . '/../config/db.php';

admin_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['titulo'])) {
    if (!csrf_validate()) {
        flash('error', 'Sessão expirada. Tente novamente.');
        redirect('cadastravaga.php');
    }

    $titulo     = trim($_POST['titulo'] ?? '');
    $empresa    = trim($_POST['empresa'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');
    $requisitos = trim($_POST['requisitos'] ?? '');
    $tipo       = $_POST['tipo'] ?? 'CLT';
    $modalidade = $_POST['modalidade'] ?? 'presencial';
    $salario    = trim($_POST['salario'] ?? '');
    $cidade     = trim($_POST['cidade'] ?? '');
    $estado     = trim($_POST['estado'] ?? '');

    $stmt = $cx->prepare("INSERT INTO vagas (titulo, empresa, descricao, requisitos, tipo, modalidade, salario, cidade, estado) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssssss", $titulo, $empresa, $descricao, $requisitos, $tipo, $modalidade, $salario, $cidade, $estado);

    if ($stmt->execute()) {
        $stmt->close();
        flash('success', 'Vaga cadastrada com sucesso!');
        redirect('../user/vagas.php');
    } else {
        $stmt->close();
        flash('error', 'Erro ao cadastrar vaga.');
        redirect('cadastravaga.php');
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Vaga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-5">
    <h2 class="mb-4 text-primary">Cadastrar Nova Vaga</h2>
    <form method="POST" action="">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label for="titulo">Título da Vaga</label>
            <input type="text" class="form-control" name="titulo" id="titulo" required>
        </div>
        <div class="form-group">
            <label for="empresa">Empresa</label>
            <input type="text" class="form-control" name="empresa" id="empresa">
        </div>
        <div class="form-group">
            <label for="descricao">Descrição</label>
            <textarea class="form-control" name="descricao" id="descricao" rows="4" required></textarea>
        </div>
        <div class="form-group">
            <label for="requisitos">Requisitos</label>
            <textarea class="form-control" name="requisitos" id="requisitos" rows="3"></textarea>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="tipo">Tipo de Contratação</label>
                <select class="form-control" name="tipo" id="tipo">
                    <option value="CLT">CLT</option>
                    <option value="PJ">PJ</option>
                    <option value="Estágio">Estágio</option>
                    <option value="Freelance">Freelance</option>
                    <option value="Temporário">Temporário</option>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label for="modalidade">Modalidade</label>
                <select class="form-control" name="modalidade" id="modalidade">
                    <option value="presencial">Presencial</option>
                    <option value="remoto">Remoto</option>
                    <option value="hibrido">Híbrido</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label for="salario">Salário</label>
                <input type="text" class="form-control" name="salario" id="salario" placeholder="R$ 2.000 ou A combinar">
            </div>
            <div class="form-group col-md-4">
                <label for="cidade">Cidade</label>
                <input type="text" class="form-control" name="cidade" id="cidade">
            </div>
            <div class="form-group col-md-4">
                <label for="estado">Estado</label>
                <input type="text" class="form-control" name="estado" id="estado" maxlength="2" placeholder="SP">
            </div>
        </div>
        <button type="submit" class="btn btn-success">Salvar Vaga</button>
        <a href="../user/vagas.php" class="btn btn-secondary ml-2">Cancelar</a>
    </form>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>

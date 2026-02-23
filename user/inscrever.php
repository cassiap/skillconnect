<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Exige login
if (!isset($_SESSION['logado'])) {
    $_SESSION['url_destino'] = $_SERVER['REQUEST_URI'];
    header('Location: ../auth/login.php');
    exit();
}

$curso_id = intval($_GET['curso_id'] ?? 0);
$usuario_id = $_SESSION['user_id'];

// Busca o titulo do curso
$curso_nome = "Curso não encontrado";
if ($curso_id > 0) {
    $stmt = $cx->prepare("SELECT titulo FROM cursos WHERE id = ? AND ativo = 1");
    $stmt->bind_param("i", $curso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $curso_nome = $row['titulo'];
    }
    $stmt->close();
}

// Processa o formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        echo "<script>alert('Sessão expirada. Tente novamente.'); window.location.href=window.location.href;</script>";
        exit;
    }
    $curso_id_form = intval($_POST['curso_id'] ?? 0);

    // Verifica se ja existe inscricao
    $check = $cx->prepare("SELECT id FROM inscricoes_cursos WHERE usuario_id = ? AND curso_id = ?");
    $check->bind_param("ii", $usuario_id, $curso_id_form);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        echo "<script>alert('Você já está inscrito nesse curso.'); window.location.href='cursos.php';</script>";
        exit;
    }
    $check->close();

    // Insere inscricao
    $stmt = $cx->prepare("INSERT INTO inscricoes_cursos (usuario_id, curso_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $usuario_id, $curso_id_form);
    if ($stmt->execute()) {
        $stmt->close();
        echo "<script>alert('Inscrição realizada com sucesso!'); window.location.href='cursos.php';</script>";
        exit;
    } else {
        $stmt->close();
        echo "<script>alert('Erro ao salvar inscrição.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Inscrição no Curso - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container mt-5">
    <h2 class="text-primary mb-4">Inscrever-se em: <?php echo htmlspecialchars($curso_nome); ?></h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($_SESSION['nome'] ?? ''); ?></p>
            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>

            <form method="POST" action="">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                <button type="submit" class="btn btn-success">Confirmar Inscrição</button>
                <a href="cursos.php" class="btn btn-secondary ml-2">Voltar</a>
            </form>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

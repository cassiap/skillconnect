<?php
/**
 * Página de inscrição em cursos
 * 
 * Este arquivo gerencia o processo de inscrição de usuários em cursos,
 * incluindo validações, verificação de inscrições existentes e processamento
 * do formulário de confirmação de inscrição.
 * 
 * @author Sistema SkillConnect
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

$curso_id = (int) ($_GET['curso_id'] ?? $_POST['curso_id'] ?? 0);
$usuario_id = (int) ($_SESSION['user_id'] ?? 0);

if ($curso_id <= 0) {
    flash('error', 'Curso invalido.');
    redirect('cursos.php');
}

$cursoStmt = $cx->prepare("SELECT id, titulo FROM cursos WHERE id = ? AND ativo = 1 LIMIT 1");
$cursoStmt->bind_param("i", $curso_id);
$cursoStmt->execute();
$cursoRes = $cursoStmt->get_result();
$curso = $cursoRes->fetch_assoc();
$cursoStmt->close();

if (!$curso) {
    flash('error', 'Curso nao encontrado ou inativo.');
    redirect('cursos.php');
}

$checkStmt = $cx->prepare("SELECT id, status FROM inscricoes_cursos WHERE usuario_id = ? AND curso_id = ? LIMIT 1");
$checkStmt->bind_param("ii", $usuario_id, $curso_id);
$checkStmt->execute();
$inscricaoExistente = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

if ($inscricaoExistente) {
    flash('info', 'Voce ja esta inscrito neste curso.');
    redirect('meus-cursos.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        flash('error', 'Sessao expirada. Tente novamente.');
        redirect("inscrever.php?curso_id={$curso_id}");
    }

    try {
        $stmt = $cx->prepare("INSERT INTO inscricoes_cursos (usuario_id, curso_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $usuario_id, $curso_id);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Inscricao realizada com sucesso!');
        redirect('meus-cursos.php');
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() === 1062) {
            flash('info', 'Voce ja esta inscrito neste curso.');
            redirect('meus-cursos.php');
        }
        flash('error', 'Erro ao registrar inscricao.');
        redirect("inscrever.php?curso_id={$curso_id}");
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Inscricao no Curso - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container mt-5">
    <h2 class="text-primary mb-4">Inscrever-se em: <?php echo htmlspecialchars($curso['titulo']); ?></h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <p><strong>Nome:</strong> <?php echo htmlspecialchars($_SESSION['nome'] ?? ''); ?></p>
            <p><strong>E-mail:</strong> <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                <button type="submit" class="btn btn-success">Confirmar inscricao</button>
                <a href="curso.php?id=<?php echo $curso_id; ?>" class="btn btn-secondary ml-2">Voltar</a>
            </form>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>
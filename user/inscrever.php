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
$perfil = trim((string) ($_SESSION['perfil'] ?? ''));

if ($perfil === 'admin') {
    flash('info', 'Administradores nao realizam inscricao em cursos.');
    redirect('cursos.php');
}

if ($curso_id <= 0) {
    flash('error', 'Curso invalido.');
    redirect('cursos.php');
}

$cursoStmt = $cx->prepare("SELECT id, titulo, duracao_dias, inscricoes_ate FROM cursos WHERE id = ? AND ativo = 1 LIMIT 1");
$cursoStmt->bind_param("i", $curso_id);
$cursoStmt->execute();
$cursoRes = $cursoStmt->get_result();
$curso = $cursoRes->fetch_assoc();
$cursoStmt->close();

if (!$curso) {
    flash('error', 'Curso nao encontrado ou inativo.');
    redirect('cursos.php');
}

if (prazo_encerrado($curso['inscricoes_ate'] ?? null)) {
    flash('error', 'O prazo de inscricao deste curso encerrou em ' . date('d/m/Y', strtotime($curso['inscricoes_ate'])) . '.');
    redirect('cursos.php');
}

$checkStmt = $cx->prepare("SELECT id, status FROM inscricoes_cursos WHERE usuario_id = ? AND curso_id = ? LIMIT 1");
$checkStmt->bind_param("ii", $usuario_id, $curso_id);
$checkStmt->execute();
$inscricaoExistente = $checkStmt->get_result()->fetch_assoc();
$checkStmt->close();

// Inscrição cancelada pode ser reativada; qualquer outro status bloqueia nova inscrição.
$reativacao = $inscricaoExistente && $inscricaoExistente['status'] === STATUS_INSC_CANCELADO;

if ($inscricaoExistente && !$reativacao) {
    flash('info', 'Voce ja esta inscrito neste curso.');
    redirect('meus-cursos.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        flash('error', 'Sessao expirada. Tente novamente.');
        redirect("inscrever.php?curso_id={$curso_id}");
    }

    try {
        // Calcula data de expiração com base no prazo do curso (NULL = sem prazo)
        $duracaoDias = (int)($curso['duracao_dias'] ?? 0);
        $expiraEm = $duracaoDias > 0
            ? date('Y-m-d H:i:s', strtotime("+{$duracaoDias} days"))
            : null;

        if ($reativacao) {
            // Reativa a inscrição cancelada reiniciando o prazo de acesso.
            $inscricaoId = (int) $inscricaoExistente['id'];
            $stmt = $cx->prepare("UPDATE inscricoes_cursos SET status = 'pendente', acesso_expira_em = ?, criado_em = NOW() WHERE id = ?");
            $stmt->bind_param("si", $expiraEm, $inscricaoId);
            $stmt->execute();
            $stmt->close();

            flash('success', 'Inscricao reativada com sucesso!');
            redirect('meus-cursos.php');
        }

        $stmt = $cx->prepare("INSERT INTO inscricoes_cursos (usuario_id, curso_id, acesso_expira_em) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $usuario_id, $curso_id, $expiraEm);
        $stmt->execute();
        $stmt->close();

        flash('success', 'Inscricao realizada com sucesso!');
        redirect('meus-cursos.php');
    } catch (mysqli_sql_exception $e) {
        if ((int) $e->getCode() === 1062) {
            flash('info', 'Voce ja esta inscrito neste curso.');
            redirect('meus-cursos.php');
        }
        error_log('inscrever.php inscricao insert error: ' . $e->getMessage());
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

            <?php if (!empty($curso['inscricoes_ate'])): ?>
                <p class="text-muted small">
                    <i class="far fa-calendar-alt"></i>
                    Inscricoes abertas ate <?php echo date('d/m/Y', strtotime($curso['inscricoes_ate'])); ?>.
                </p>
            <?php endif; ?>

            <?php if ($reativacao): ?>
                <div class="alert alert-info">
                    Voce cancelou sua inscricao neste curso anteriormente. Ao confirmar, ela sera reativada
                    e seu progresso nas aulas sera mantido.
                </div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="curso_id" value="<?php echo $curso_id; ?>">
                <button type="submit" class="btn btn-success"><?php echo $reativacao ? 'Reativar inscricao' : 'Confirmar inscricao'; ?></button>
                <a href="curso.php?id=<?php echo $curso_id; ?>" class="btn btn-secondary ml-2">Voltar</a>
            </form>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

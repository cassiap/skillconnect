<?php
/**
 * Arquivo responsável por exibir os detalhes de um curso específico
 * 
 * Este arquivo busca as informações de um curso no banco de dados
 * baseado no ID fornecido via URL e exibe seus detalhes completos,
 * incluindo título, modalidade, nível, carga horária, vagas, preço e descrição.
 * 
 * @author Sistema SkillConnect
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';
$isAdmin = isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin';

// Verifica se o ID foi passado via URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Curso não especificado.");
}

$id = intval($_GET['id']);
$sql = $isAdmin
    ? "SELECT * FROM cursos WHERE id = ?"
    : "SELECT * FROM cursos WHERE id = ? AND ativo = 1";
$stmt = $cx->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    flash('error', 'Curso não encontrado.');
    redirect('cursos.php');
}

$curso = $resultado->fetch_assoc();
$stmt->close();

$inscricoesEncerradas = prazo_encerrado($curso['inscricoes_ate'] ?? null);

// Média de avaliações do curso (estrelas dadas pelos alunos)
$mediaCurso = null;
$avgStmt = $cx->prepare("SELECT ROUND(AVG(nota), 1) AS media, COUNT(*) AS total FROM avaliacoes_cursos WHERE curso_id = ?");
$avgStmt->bind_param("i", $id);
$avgStmt->execute();
$avgRow = $avgStmt->get_result()->fetch_assoc();
$avgStmt->close();
if ($avgRow && (int) $avgRow['total'] > 0) {
    $mediaCurso = ['media' => (float) $avgRow['media'], 'total' => (int) $avgRow['total']];
}

// Depoimentos: avaliações do curso que têm comentário
$depoimentos = [];
$depStmt = $cx->prepare(
    "SELECT av.nota, av.comentario, av.atualizado_em, u.nome
     FROM avaliacoes_cursos av
     INNER JOIN usuarios u ON u.id = av.usuario_id
     WHERE av.curso_id = ? AND av.comentario IS NOT NULL AND av.comentario <> ''
     ORDER BY av.atualizado_em DESC
     LIMIT 5"
);
$depStmt->bind_param("i", $id);
$depStmt->execute();
$depRes = $depStmt->get_result();
while ($depRow = $depRes->fetch_assoc()) {
    $depoimentos[] = $depRow;
}
$depStmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($curso['titulo']); ?> - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container mt-5">
    <a href="cursos.php" class="btn btn-secondary mb-4">&larr; Voltar para lista de cursos</a>

    <div class="card shadow">
        <div class="card-body">
            <h2 class="text-primary"><?php echo htmlspecialchars($curso['titulo']); ?></h2>

            <?php if ($mediaCurso): ?>
                <p class="mb-2">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="fas fa-star" style="color:<?php echo $s <= round($mediaCurso['media']) ? '#f59e0b' : '#d1d5db'; ?>;"></i>
                    <?php endfor; ?>
                    <strong><?php echo number_format($mediaCurso['media'], 1); ?></strong>
                    <span class="text-muted small">(<?php echo $mediaCurso['total']; ?> avaliação<?php echo $mediaCurso['total'] > 1 ? 'ões' : ''; ?> de alunos)</span>
                </p>
            <?php endif; ?>

            <div class="row mt-3 mb-3">
                <div class="col-md-3">
                    <strong>Modalidade:</strong><br>
                    <?php echo htmlspecialchars($curso['modalidade']); ?>
                </div>
                <div class="col-md-3">
                    <strong>Nivel:</strong><br>
                    <?php echo htmlspecialchars($curso['nivel']); ?>
                </div>
                <div class="col-md-3">
                    <strong>Carga Horaria:</strong><br>
                    <?php echo $curso['carga_horaria'] ? $curso['carga_horaria'] . ' horas' : 'Não informada'; ?>
                </div>
                <div class="col-md-3">
                    <strong>Vagas:</strong><br>
                    <?php echo $curso['vagas'] > 0 ? $curso['vagas'] : 'Ilimitadas'; ?>
                </div>
            </div>

            <?php if ($curso['preco'] > 0): ?>
                <p><strong>Preco:</strong> R$ <?php echo number_format($curso['preco'], 2, ',', '.'); ?></p>
            <?php else: ?>
                <p><strong>Preco:</strong> <span class="text-success font-weight-bold">Gratuito</span></p>
            <?php endif; ?>

            <?php if (!empty($curso['inscricoes_ate'])): ?>
                <?php if ($inscricoesEncerradas): ?>
                    <p><span class="badge badge-danger">Inscricoes encerradas em <?php echo date('d/m/Y', strtotime($curso['inscricoes_ate'])); ?></span></p>
                <?php else: ?>
                    <p><span class="badge badge-info">Inscricoes abertas ate <?php echo date('d/m/Y', strtotime($curso['inscricoes_ate'])); ?></span></p>
                <?php endif; ?>
            <?php endif; ?>

            <h5 class="mt-3">Descricao</h5>
            <p><?php echo nl2br(htmlspecialchars($curso['descricao'])); ?></p>

            <?php if (!$isAdmin): ?>
                <?php if ($inscricoesEncerradas): ?>
                    <button class="btn btn-secondary mt-3" disabled>Inscricoes encerradas</button>
                <?php else: ?>
                    <a href="inscrever.php?curso_id=<?php echo $curso['id']; ?>" class="btn btn-success mt-3">Quero me inscrever</a>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info mt-3 mb-0">
                    Visualizacao administrativa. A gestao de status deste curso esta em <a href="cursos.php" class="alert-link">Cursos</a>
                    e o conteudo (modulos e aulas) em
                    <a href="../admin/curso-conteudo.php?curso_id=<?php echo (int) $curso['id']; ?>" class="alert-link">Gerenciar conteudo</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (count($depoimentos) > 0): ?>
        <div class="card shadow mt-4">
            <div class="card-body">
                <h5 class="mb-3"><i class="far fa-comments text-primary"></i> O que os alunos dizem</h5>
                <?php foreach ($depoimentos as $dep): ?>
                    <div class="p-3 mb-2" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-1">
                            <strong class="small"><?php echo htmlspecialchars($dep['nome']); ?></strong>
                            <span class="small text-muted">
                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                    <i class="fas fa-star" style="font-size:11px; color:<?php echo $s <= (int) $dep['nota'] ? '#f59e0b' : '#d1d5db'; ?>;"></i>
                                <?php endfor; ?>
                                · <?php echo date('d/m/Y', strtotime((string) $dep['atualizado_em'])); ?>
                            </span>
                        </div>
                        <div class="small" style="color:#334155; line-height:1.6;">
                            <?php echo nl2br(htmlspecialchars((string) $dep['comentario'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

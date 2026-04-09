<?php
/**
 * Página de listagem de cursos do usuário
 * 
 * Este arquivo exibe todos os cursos em que o usuário está inscrito,
 * mostrando o status da inscrição, progresso nas aulas e informações
 * detalhadas de cada curso.
 * 
 * @author Sistema SkillConnect
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
$inscricoes = [];

$sql = "
    SELECT
        ic.id,
        ic.status,
        ic.criado_em,
        c.id AS curso_id,
        c.titulo,
        c.modalidade,
        c.nivel,
        c.carga_horaria,
        COALESCE(a.total_aulas, 0) AS total_aulas,
        COALESCE(p.total_concluidas, 0) AS total_concluidas
    FROM inscricoes_cursos ic
    INNER JOIN cursos c ON c.id = ic.curso_id
    LEFT JOIN (
        SELECT m.curso_id, COUNT(a.id) AS total_aulas
        FROM modulos m
        LEFT JOIN aulas a ON a.modulo_id = m.id AND a.ativo = 1
        WHERE m.ativo = 1
        GROUP BY m.curso_id
    ) a ON a.curso_id = c.id
    LEFT JOIN (
        SELECT m.curso_id, pa.usuario_id, COUNT(pa.id) AS total_concluidas
        FROM progresso_aulas pa
        INNER JOIN aulas al ON al.id = pa.aula_id
        INNER JOIN modulos m ON m.id = al.modulo_id
        GROUP BY m.curso_id, pa.usuario_id
    ) p ON p.curso_id = c.id AND p.usuario_id = ic.usuario_id
    WHERE ic.usuario_id = ?
    ORDER BY ic.criado_em DESC
";

$stmt = $cx->prepare($sql);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $inscricoes[] = $row;
}
$stmt->close();

$statusLabel = [
    'pendente' => 'Pendente',
    'confirmado' => 'Confirmado',
    'cancelado' => 'Cancelado',
    'concluido' => 'Concluido',
];
$statusClass = [
    'pendente' => 'badge-warning',
    'confirmado' => 'badge-success',
    'cancelado' => 'badge-secondary',
    'concluido' => 'badge-primary',
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meus Cursos - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        .hero {
            border-radius: 14px;
            background: linear-gradient(120deg, #1d4ed8 0%, #0f766e 100%);
            color: #fff;
            padding: 22px;
        }
        .progress {
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
        }
    </style>
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <div class="hero mb-4">
        <h1 class="h4 mb-1">Meus cursos</h1>
        <p class="mb-0">Acompanhe suas inscricoes e avance pelas aulas.</p>
    </div>

    <?php if (count($inscricoes) === 0): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <p class="mb-3">Voce ainda nao tem inscricoes em cursos.</p>
                <a href="cursos.php" class="btn btn-primary"><i class="fas fa-search"></i> Explorar cursos</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Curso</th>
                                <th>Status</th>
                                <th>Progresso</th>
                                <th>Inscrito em</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($inscricoes as $i): ?>
                            <?php
                                $totalAulas = (int) $i['total_aulas'];
                                $concluidas = (int) $i['total_concluidas'];
                                $percent = $totalAulas > 0 ? (int) floor(($concluidas * 100) / $totalAulas) : 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="font-weight-bold"><?php echo htmlspecialchars($i['titulo']); ?></div>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($i['modalidade']); ?> |
                                        <?php echo htmlspecialchars($i['nivel']); ?> |
                                        <?php echo $i['carga_horaria'] ? (int) $i['carga_horaria'] . 'h' : '-'; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php $st = $i['status']; ?>
                                    <span class="badge <?php echo $statusClass[$st] ?? 'badge-secondary'; ?>">
                                        <?php echo htmlspecialchars($statusLabel[$st] ?? $st); ?>
                                    </span>
                                </td>
                                <td style="min-width:220px;">
                                    <?php if ($totalAulas <= 0): ?>
                                        <small class="text-muted">Aulas em configuracao</small>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between small mb-1">
                                            <span><?php echo $concluidas; ?>/<?php echo $totalAulas; ?> aulas</span>
                                            <span><?php echo $percent; ?>%</span>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percent; ?>%;"></div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($i['criado_em'])); ?></td>
                                <td class="text-right">
                                    <a class="btn btn-sm btn-outline-primary" href="meu-curso.php?curso_id=<?php echo (int) $i['curso_id']; ?>">
                                        Continuar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>
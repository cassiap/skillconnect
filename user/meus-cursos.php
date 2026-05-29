<?php
/**
 * Listagem de cursos inscritos pelo aluno
 *
 * Exibe os cursos em formato de cards com progresso colorido, alertas de expiração,
 * estimativa de conclusão, badge de curso concluído e atalho para certificado.
 *
 * @package SkillConnect
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

if (($_SESSION['perfil'] ?? '') === 'admin') {
    flash('info', 'Area exclusiva para alunos.');
    redirect(app_url('admin/admin.php'));
}

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
$inscricoes = [];

$sql = "
    SELECT
        ic.id,
        ic.status,
        ic.criado_em,
        ic.acesso_expira_em,
        c.id AS curso_id,
        c.titulo,
        c.modalidade,
        c.nivel,
        c.carga_horaria,
        COALESCE(a.total_aulas, 0)      AS total_aulas,
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
    STATUS_INSC_PENDENTE   => 'Pendente',
    STATUS_INSC_CONFIRMADO => 'Em andamento',
    STATUS_INSC_CANCELADO  => 'Cancelado',
    STATUS_INSC_CONCLUIDO  => 'Concluído',
];
$statusClass = [
    STATUS_INSC_PENDENTE   => 'badge-warning',
    STATUS_INSC_CONFIRMADO => 'badge-primary',
    STATUS_INSC_CANCELADO  => 'badge-secondary',
    STATUS_INSC_CONCLUIDO  => 'badge-success',
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
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <div class="hero-meus-cursos mb-4">
        <h1 class="h4 mb-1">Meus cursos</h1>
        <p class="mb-0">Acompanhe suas inscrições, progresso e validade de acesso.</p>
    </div>

    <?php $flashInfo = get_flash('info'); if ($flashInfo): ?>
        <div class="alert alert-info alert-dismissible">
            <?= htmlspecialchars($flashInfo) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>
    <?php $flashSuccess = get_flash('success'); if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible">
            <?= htmlspecialchars($flashSuccess) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>

    <?php if (count($inscricoes) === 0): ?>
        <div class="card shadow-sm">
            <div class="card-body empty-state">
                <i class="fas fa-graduation-cap"></i>
                <p class="text-muted mb-3">Você ainda não tem inscrições em cursos.</p>
                <a href="cursos.php" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Explorar cursos</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($inscricoes as $i): ?>
                <?php
                    $totalAulas = (int) $i['total_aulas'];
                    $concluidas = (int) $i['total_concluidas'];
                    $percent = $totalAulas > 0 ? (int) floor($concluidas * 100 / $totalAulas) : 0;
                    $st = (string) ($i['status'] ?? 'pendente');

                    // Cor da barra de progresso por faixa
                    $barClass = 'bg-danger';
                    if ($percent >= 100)     $barClass = 'bg-success';
                    elseif ($percent >= 67)  $barClass = 'bg-primary';
                    elseif ($percent >= 34)  $barClass = 'bg-warning';

                    // Expiração
                    $expirado      = false;
                    $expirandoBreve = false;
                    $diasRestantes = null;
                    $expiraTexto   = null;
                    if (!empty($i['acesso_expira_em'])) {
                        $expiraTs = strtotime((string) $i['acesso_expira_em']);
                        $agora    = time();
                        $expirado = $agora > $expiraTs;
                        if (!$expirado) {
                            $diasRestantes  = (int) ceil(($expiraTs - $agora) / 86400);
                            $expirandoBreve = $diasRestantes <= 7;
                            $expiraTexto    = date('d/m/Y', $expiraTs);
                        }
                    }

                    // Estimativa de conclusão
                    $etaText = '';
                    if ($totalAulas > 0 && $concluidas > 0 && $concluidas < $totalAulas) {
                        $diasDesde = max(1, (int) ceil((time() - strtotime((string) $i['criado_em'])) / 86400));
                        $rate = $concluidas / $diasDesde;
                        if ($rate > 0) {
                            $etaDias = (int) ceil(($totalAulas - $concluidas) / $rate);
                            if ($etaDias > 0 && $etaDias <= 365) {
                                $etaText = 'Neste ritmo, termina em ~' . $etaDias . ' dia' . ($etaDias === 1 ? '' : 's');
                            }
                        }
                    }

                    $cardClass = '';
                    if ($expirado)       $cardClass = 'card-expirado';
                    elseif ($percent >= 100) $cardClass = 'card-concluido';
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="curso-card <?= $cardClass ?> d-flex flex-column p-3">
                        <?php if ($percent >= 100): ?>
                            <div class="ribbon">CONCLUÍDO</div>
                        <?php endif; ?>

                        <!-- Badges de nível e modalidade -->
                        <div class="mb-2">
                            <span class="badge-soft"><?= htmlspecialchars(ucfirst((string) ($i['modalidade'] ?: 'Geral'))) ?></span>
                            <span class="badge-soft"><?= htmlspecialchars(ucfirst((string) ($i['nivel'] ?: 'Livre'))) ?></span>
                            <span class="badge <?= $statusClass[$st] ?? 'badge-secondary' ?>" style="font-size:10px;">
                                <?= htmlspecialchars($statusLabel[$st] ?? $st) ?>
                            </span>
                        </div>

                        <!-- Título -->
                        <h5 class="font-weight-bold mb-1" style="font-size:15px; line-height:1.3; flex-grow:0;">
                            <?= htmlspecialchars((string) ($i['titulo'] ?? '')) ?>
                        </h5>
                        <div class="small text-muted mb-3">
                            <i class="far fa-clock"></i>
                            <?= $i['carga_horaria'] ? (int) $i['carga_horaria'] . 'h' : '—' ?>
                            &nbsp;·&nbsp; Inscrito em <?= date('d/m/Y', strtotime((string) $i['criado_em'])) ?>
                        </div>

                        <!-- Progresso -->
                        <?php if ($totalAulas > 0): ?>
                            <div class="d-flex justify-content-between small mb-1">
                                <span><?= $concluidas ?>/<?= $totalAulas ?> aulas</span>
                                <strong><?= $percent ?>%</strong>
                            </div>
                            <div class="progress mb-1">
                                <div class="progress-bar <?= $barClass ?>" style="width:<?= $percent ?>%;"></div>
                            </div>
                            <?php if ($etaText): ?>
                                <div class="eta-text mb-2"><?= htmlspecialchars($etaText) ?></div>
                            <?php else: ?>
                                <div class="mb-2"></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="small text-muted mb-3">Aulas em configuração</div>
                        <?php endif; ?>

                        <!-- Badge de validade -->
                        <?php if (!empty($i['acesso_expira_em'])): ?>
                            <?php if ($expirado): ?>
                                <span class="expiry-badge expiry-danger mb-3">
                                    <i class="fas fa-lock"></i> Acesso expirado
                                </span>
                            <?php elseif ($expirandoBreve): ?>
                                <span class="expiry-badge expiry-warn mb-3">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Expira em <?= $diasRestantes ?> dia<?= $diasRestantes === 1 ? '' : 's' ?> (<?= $expiraTexto ?>)
                                </span>
                            <?php else: ?>
                                <span class="expiry-badge expiry-ok mb-3">
                                    <i class="fas fa-calendar-check"></i> Válido até <?= $expiraTexto ?>
                                </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="mb-1"></div>
                        <?php endif; ?>

                        <!-- Botões de ação -->
                        <div class="mt-auto d-flex" style="gap:8px;">
                            <?php if (!$expirado): ?>
                                <a class="btn btn-sm btn-primary flex-grow-1"
                                   href="meu-curso.php?curso_id=<?= (int) $i['curso_id'] ?>">
                                    <?php if ($percent === 0): ?>
                                        <i class="fas fa-play mr-1"></i> Começar
                                    <?php elseif ($percent >= 100): ?>
                                        <i class="fas fa-redo mr-1"></i> Revisar
                                    <?php else: ?>
                                        <i class="fas fa-arrow-right mr-1"></i> Continuar
                                    <?php endif; ?>
                                </a>
                            <?php else: ?>
                                <span class="btn btn-sm btn-outline-secondary flex-grow-1 disabled">
                                    <i class="fas fa-lock mr-1"></i> Expirado
                                </span>
                            <?php endif; ?>

                            <?php if ($percent >= 100): ?>
                                <a class="btn btn-sm btn-outline-success"
                                   href="certificado.php?curso_id=<?= (int) $i['curso_id'] ?>"
                                   title="Ver certificado">
                                    <i class="fas fa-certificate"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

<?php
/**
 * Visualizador de aulas do curso inscrito
 *
 * Permite ao aluno navegar pelos módulos e aulas, marcar progresso,
 * avaliar aulas com 1–5 estrelas e acompanhar expiração de acesso.
 * Ao atingir 100%, a inscrição é marcada como concluída automaticamente.
 *
 * @package SkillConnect
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

if (($_SESSION['perfil'] ?? '') === 'admin') {
    flash('info', 'Area exclusiva para alunos.');
    redirect(app_url('admin/admin.php'));
}

$usuarioId        = (int) ($_SESSION['user_id'] ?? 0);
$cursoId          = (int) ($_GET['curso_id'] ?? $_POST['curso_id'] ?? 0);
$aulaSelecionadaId = (int) ($_GET['aula'] ?? $_POST['aula_atual'] ?? 0);

if ($cursoId <= 0) {
    flash('error', 'Curso invalido.');
    redirect('meus-cursos.php');
}

// Inscrição + data de expiração
$inscricaoStmt = $cx->prepare(
    "SELECT id, status, acesso_expira_em FROM inscricoes_cursos WHERE usuario_id = ? AND curso_id = ? LIMIT 1"
);
$inscricaoStmt->bind_param("ii", $usuarioId, $cursoId);
$inscricaoStmt->execute();
$inscricao = $inscricaoStmt->get_result()->fetch_assoc();
$inscricaoStmt->close();

if (!$inscricao) {
    flash('error', 'Voce nao esta inscrito neste curso.');
    redirect('meus-cursos.php');
}

if (($inscricao['status'] ?? '') === STATUS_INSC_CANCELADO) {
    flash('error', 'Sua inscricao neste curso esta cancelada.');
    redirect('meus-cursos.php');
}

// Calcula estado de expiração
$expirado       = false;
$expirandoBreve = false;
$diasRestantes  = null;

if (!empty($inscricao['acesso_expira_em'])) {
    $expiraTs = strtotime((string) $inscricao['acesso_expira_em']);
    $agora    = time();
    $expirado = $agora > $expiraTs;
    if (!$expirado) {
        $diasRestantes  = (int) ceil(($expiraTs - $agora) / 86400);
        $expirandoBreve = $diasRestantes <= 7;
    }
}

// ===== POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aulaAtual = (int) ($_POST['aula_atual'] ?? $aulaSelecionadaId);

    if (!csrf_validate()) {
        flash('error', 'Sessao expirada. Tente novamente.');
        redirect("meu-curso.php?curso_id={$cursoId}&aula={$aulaAtual}");
    }

    $acao   = trim((string) ($_POST['acao'] ?? ''));
    $aulaId = (int) ($_POST['aula_id'] ?? 0);

    // ----- Avaliação de aula -----
    if ($acao === 'avaliar') {
        $nota       = (int) ($_POST['nota'] ?? 0);
        $comentario = trim((string) ($_POST['comentario'] ?? ''));

        if ($aulaId <= 0 || $nota < 1 || $nota > 5) {
            flash('error', 'Avaliacao invalida.');
            redirect("meu-curso.php?curso_id={$cursoId}&aula={$aulaId}");
        }

        try {
            $avStmt = $cx->prepare(
                "INSERT INTO avaliacoes_aulas (usuario_id, aula_id, nota, comentario)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE nota = VALUES(nota), comentario = VALUES(comentario), atualizado_em = NOW()"
            );
            $avStmt->bind_param("iiis", $usuarioId, $aulaId, $nota, $comentario);
            $avStmt->execute();
            $avStmt->close();
            flash('success', 'Avaliacao salva!');
        } catch (mysqli_sql_exception $e) {
            error_log('meu-curso.php avaliacao insert error: ' . $e->getMessage());
            flash('error', 'Erro ao salvar avaliacao.');
        }

        redirect("meu-curso.php?curso_id={$cursoId}&aula={$aulaId}");
    }

    // ----- Avaliação do curso como um todo -----
    if ($acao === 'avaliar_curso') {
        $nota       = (int) ($_POST['nota'] ?? 0);
        $comentario = trim((string) ($_POST['comentario'] ?? ''));

        if ($nota < 1 || $nota > 5) {
            flash('error', 'Avaliacao invalida.');
            redirect("meu-curso.php?curso_id={$cursoId}&aula={$aulaAtual}");
        }

        try {
            $avcStmt = $cx->prepare(
                "INSERT INTO avaliacoes_cursos (usuario_id, curso_id, nota, comentario)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE nota = VALUES(nota), comentario = VALUES(comentario), atualizado_em = NOW()"
            );
            $avcStmt->bind_param("iiis", $usuarioId, $cursoId, $nota, $comentario);
            $avcStmt->execute();
            $avcStmt->close();
            flash('success', 'Avaliacao do curso salva. Obrigado pelo feedback!');
        } catch (mysqli_sql_exception $e) {
            error_log('meu-curso.php avaliacao curso insert error: ' . $e->getMessage());
            flash('error', 'Erro ao salvar a avaliacao do curso.');
        }

        redirect("meu-curso.php?curso_id={$cursoId}&aula={$aulaAtual}");
    }

    // ----- Progresso (concluir / desfazer) — bloqueado se expirado -----
    if ($expirado) {
        flash('error', 'Seu acesso a este curso expirou.');
        redirect("meu-curso.php?curso_id={$cursoId}&aula={$aulaAtual}");
    }

    if ($aulaId > 0) {
        // Valida se aula pertence ao curso
        $valStmt = $cx->prepare(
            "SELECT a.id
             FROM aulas a
             INNER JOIN modulos m ON m.id = a.modulo_id
             WHERE a.id = ? AND m.curso_id = ? AND a.ativo = 1 AND m.ativo = 1
             LIMIT 1"
        );
        $valStmt->bind_param("ii", $aulaId, $cursoId);
        $valStmt->execute();
        $ok = $valStmt->get_result()->fetch_assoc();
        $valStmt->close();

        if ($ok) {
            if ($acao === 'concluir') {
                try {
                    $ins = $cx->prepare("INSERT INTO progresso_aulas (usuario_id, aula_id) VALUES (?, ?)");
                    $ins->bind_param("ii", $usuarioId, $aulaId);
                    $ins->execute();
                    $ins->close();
                    flash('success', 'Aula marcada como concluida.');

                    // Verifica se o curso foi 100% completado
                    $checkStmt = $cx->prepare(
                        "SELECT COUNT(a.id) AS total,
                                SUM(CASE WHEN pa.id IS NOT NULL THEN 1 ELSE 0 END) AS concluidas
                         FROM aulas a
                         INNER JOIN modulos m ON m.id = a.modulo_id
                         LEFT JOIN progresso_aulas pa ON pa.aula_id = a.id AND pa.usuario_id = ?
                         WHERE m.curso_id = ? AND m.ativo = 1 AND a.ativo = 1"
                    );
                    $checkStmt->bind_param("ii", $usuarioId, $cursoId);
                    $checkStmt->execute();
                    $checkRow = $checkStmt->get_result()->fetch_assoc();
                    $checkStmt->close();

                    if ($checkRow && (int) $checkRow['total'] > 0
                        && (int) $checkRow['total'] === (int) $checkRow['concluidas']
                    ) {
                        $complStmt = $cx->prepare(
                            "UPDATE inscricoes_cursos SET status = '" . STATUS_INSC_CONCLUIDO . "'
                             WHERE usuario_id = ? AND curso_id = ? AND status != '" . STATUS_INSC_CONCLUIDO . "'"
                        );
                        $complStmt->bind_param("ii", $usuarioId, $cursoId);
                        $complStmt->execute();
                        $complStmt->close();
                        flash('success', 'Curso concluido! Seu certificado esta disponivel.');
                    }
                } catch (mysqli_sql_exception $e) {
                    if ((int) $e->getCode() === 1062) {
                        flash('info', 'Essa aula ja estava marcada como concluida.');
                    } else {
                        error_log('meu-curso.php progresso_aulas insert error: ' . $e->getMessage());
                        flash('error', 'Nao foi possivel atualizar o progresso.');
                    }
                }
            } elseif ($acao === 'desfazer') {
                $del = $cx->prepare("DELETE FROM progresso_aulas WHERE usuario_id = ? AND aula_id = ? LIMIT 1");
                $del->bind_param("ii", $usuarioId, $aulaId);
                $del->execute();
                $del->close();

                // Se o curso estava como concluído, reverte para em andamento
                $revStmt = $cx->prepare(
                    "UPDATE inscricoes_cursos SET status = '" . STATUS_INSC_CONFIRMADO . "'
                     WHERE usuario_id = ? AND curso_id = ? AND status = '" . STATUS_INSC_CONCLUIDO . "'"
                );
                $revStmt->bind_param("ii", $usuarioId, $cursoId);
                $revStmt->execute();
                $revStmt->close();

                flash('info', 'Aula desmarcada.');
            }
        }
    }

    redirect("meu-curso.php?curso_id={$cursoId}&aula={$aulaAtual}");
}

// Dados do curso
$cursoStmt = $cx->prepare(
    "SELECT id, titulo, descricao, carga_horaria, modalidade, nivel FROM cursos WHERE id = ? LIMIT 1"
);
$cursoStmt->bind_param("i", $cursoId);
$cursoStmt->execute();
$curso = $cursoStmt->get_result()->fetch_assoc();
$cursoStmt->close();

if (!$curso) {
    flash('error', 'Curso nao encontrado.');
    redirect('meus-cursos.php');
}

// Módulos, aulas e progresso
$itensStmt = $cx->prepare(
    "SELECT
        m.id AS modulo_id,
        m.titulo AS modulo_titulo,
        m.ordem AS modulo_ordem,
        a.id AS aula_id,
        a.titulo AS aula_titulo,
        a.conteudo AS aula_conteudo,
        a.video_url AS aula_video_url,
        a.material_url AS aula_material_url,
        a.duracao_min,
        a.ordem AS aula_ordem,
        pa.id AS progresso_id
     FROM modulos m
     LEFT JOIN aulas a ON a.modulo_id = m.id AND a.ativo = 1
     LEFT JOIN progresso_aulas pa ON pa.aula_id = a.id AND pa.usuario_id = ?
     WHERE m.curso_id = ? AND m.ativo = 1
     ORDER BY m.ordem ASC, a.ordem ASC"
);
$itensStmt->bind_param("ii", $usuarioId, $cursoId);
$itensStmt->execute();
$res = $itensStmt->get_result();

$modulos        = [];
$totAulas       = 0;
$totConcluidas  = 0;
$primeiraAula   = null;
$aulaSelecionada = null;
$aulaIds        = [];

while ($row = $res->fetch_assoc()) {
    $mid = (int) $row['modulo_id'];
    if (!isset($modulos[$mid])) {
        $modulos[$mid] = [
            'id'     => $mid,
            'titulo' => $row['modulo_titulo'],
            'ordem'  => (int) $row['modulo_ordem'],
            'aulas'  => [],
        ];
    }

    if (!empty($row['aula_id'])) {
        $concluida = !empty($row['progresso_id']);
        $aula = [
            'id'           => (int) $row['aula_id'],
            'titulo'       => $row['aula_titulo'],
            'conteudo'     => $row['aula_conteudo'] ?? '',
            'video_url'    => $row['aula_video_url'] ?? '',
            'material_url' => $row['aula_material_url'] ?? '',
            'modulo_titulo' => $row['modulo_titulo'] ?? '',
            'duracao_min'  => (int) ($row['duracao_min'] ?? 0),
            'ordem'        => (int) ($row['aula_ordem'] ?? 0),
            'concluida'    => $concluida,
        ];
        $modulos[$mid]['aulas'][] = $aula;
        $aulaIds[] = $aula['id'];

        if ($primeiraAula === null) {
            $primeiraAula = $aula;
        }
        if ($aulaSelecionadaId > 0 && $aula['id'] === $aulaSelecionadaId) {
            $aulaSelecionada = $aula;
        }

        $totAulas++;
        if ($concluida) {
            $totConcluidas++;
        }
    }
}
$itensStmt->close();

$percentual = $totAulas > 0 ? (int) floor(($totConcluidas * 100) / $totAulas) : 0;
if ($aulaSelecionada === null) {
    $aulaSelecionada = $primeiraAula;
}

// Médias de avaliação por aula
$mediaAvaliacoes = [];
if ($aulaIds !== []) {
    $placeholders = implode(',', array_fill(0, count($aulaIds), '?'));
    $types        = str_repeat('i', count($aulaIds));
    $avgStmt      = $cx->prepare(
        "SELECT aula_id, ROUND(AVG(nota), 1) AS media, COUNT(*) AS total
         FROM avaliacoes_aulas
         WHERE aula_id IN ({$placeholders})
         GROUP BY aula_id"
    );
    $avgStmt->bind_param($types, ...$aulaIds);
    $avgStmt->execute();
    $avgRes = $avgStmt->get_result();
    while ($avgRow = $avgRes->fetch_assoc()) {
        $mediaAvaliacoes[(int) $avgRow['aula_id']] = [
            'media' => (float) $avgRow['media'],
            'total' => (int) $avgRow['total'],
        ];
    }
    $avgStmt->close();
}

// Avaliação do curso: a do aluno e a média geral
$minhaAvaliacaoCurso = null;
$myCursoStmt = $cx->prepare(
    "SELECT nota, comentario FROM avaliacoes_cursos WHERE usuario_id = ? AND curso_id = ? LIMIT 1"
);
$myCursoStmt->bind_param("ii", $usuarioId, $cursoId);
$myCursoStmt->execute();
$minhaAvaliacaoCurso = $myCursoStmt->get_result()->fetch_assoc();
$myCursoStmt->close();

$mediaCurso = null;
$avgCursoStmt = $cx->prepare(
    "SELECT ROUND(AVG(nota), 1) AS media, COUNT(*) AS total FROM avaliacoes_cursos WHERE curso_id = ?"
);
$avgCursoStmt->bind_param("i", $cursoId);
$avgCursoStmt->execute();
$avgCursoRow = $avgCursoStmt->get_result()->fetch_assoc();
$avgCursoStmt->close();
if ($avgCursoRow && (int) $avgCursoRow['total'] > 0) {
    $mediaCurso = [
        'media' => (float) $avgCursoRow['media'],
        'total' => (int) $avgCursoRow['total'],
    ];
}

// Avaliação do aluno na aula selecionada
$minhaAvaliacao = null;
if ($aulaSelecionada) {
    $myRatingStmt = $cx->prepare(
        "SELECT nota, comentario FROM avaliacoes_aulas WHERE usuario_id = ? AND aula_id = ? LIMIT 1"
    );
    $aulaSelecionadaId = (int) $aulaSelecionada['id'];
    $myRatingStmt->bind_param("ii", $usuarioId, $aulaSelecionadaId);
    $myRatingStmt->execute();
    $minhaAvaliacao = $myRatingStmt->get_result()->fetch_assoc();
    $myRatingStmt->close();
}

// Comentários dos alunos na aula selecionada (avaliações que têm texto)
$comentariosAula = [];
if ($aulaSelecionada) {
    $comStmt = $cx->prepare(
        "SELECT av.nota, av.comentario, av.atualizado_em, u.nome
         FROM avaliacoes_aulas av
         INNER JOIN usuarios u ON u.id = av.usuario_id
         WHERE av.aula_id = ? AND av.comentario IS NOT NULL AND av.comentario <> ''
         ORDER BY av.atualizado_em DESC
         LIMIT 10"
    );
    $aulaSelecionadaIdAtual = (int) $aulaSelecionada['id'];
    $comStmt->bind_param("i", $aulaSelecionadaIdAtual);
    $comStmt->execute();
    $comRes = $comStmt->get_result();
    while ($comRow = $comRes->fetch_assoc()) {
        $comentariosAula[] = $comRow;
    }
    $comStmt->close();
}

/**
 * Valida e retorna uma URL HTTP/HTTPS segura.
 *
 * Rejeita URLs com protocolos não-HTTP (javascript:, data:, etc.)
 * para evitar vetores de XSS em src e href.
 *
 * @param mixed $url Valor bruto recebido do banco.
 * @return string URL validada ou string vazia se inválida.
 */
function safe_http_url($url): string {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }
    $parts  = parse_url($url);
    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    if ($scheme !== 'http' && $scheme !== 'https') {
        return '';
    }
    return $url;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meu Curso - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">

    <!-- Hero do curso -->
    <div class="hero-meu-curso mb-4">
        <h1 class="h4 mb-1"><?= htmlspecialchars($curso['titulo']) ?></h1>
        <p class="mb-2"><?= htmlspecialchars((string) ($curso['descricao'] ?? '')) ?></p>
        <div class="small">
            <?= htmlspecialchars((string) ($curso['modalidade'] ?? '-')) ?> |
            <?= htmlspecialchars((string) ($curso['nivel'] ?? '-')) ?> |
            <?= $curso['carga_horaria'] ? (int) $curso['carga_horaria'] . 'h' : '-' ?>
            <?php if ($mediaCurso): ?>
                |
                <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="fas fa-star" style="font-size:11px; color:<?= $s <= round($mediaCurso['media']) ? '#fbbf24' : 'rgba(255,255,255,.35)' ?>;"></i>
                <?php endfor; ?>
                <?= number_format($mediaCurso['media'], 1) ?> (<?= $mediaCurso['total'] ?> avaliação<?= $mediaCurso['total'] > 1 ? 'ões' : '' ?>)
            <?php endif; ?>
        </div>
    </div>

    <!-- Banner de expiração -->
    <?php if ($expirado): ?>
        <div class="expiry-alert-danger mb-4 d-flex align-items-center">
            <i class="fas fa-lock text-danger fa-lg mr-3"></i>
            <div>
                <strong>Acesso expirado.</strong>
                Seu prazo para este curso encerrou. O conteúdo está disponível para leitura, mas não é mais possível marcar aulas como concluídas.
            </div>
        </div>
    <?php elseif ($expirandoBreve): ?>
        <div class="expiry-alert-warn mb-4 d-flex align-items-center">
            <i class="fas fa-exclamation-triangle text-warning fa-lg mr-3"></i>
            <div>
                <strong>Atenção:</strong> seu acesso a este curso expira em
                <strong><?= $diasRestantes ?> dia<?= $diasRestantes === 1 ? '' : 's' ?></strong>.
                Aproveite para concluir as aulas restantes.
            </div>
        </div>
    <?php endif; ?>

    <!-- Banner de conclusão -->
    <?php if ($percentual >= 100): ?>
        <div class="celebration-banner mb-4 d-flex align-items-center justify-content-between flex-wrap">
            <div>
                <span class="confetti">🎉</span>
                <strong class="ml-2" style="font-size:16px;">Parabéns! Você concluiu este curso!</strong>
                <div class="small mt-1 opacity-75">Seu certificado de conclusão está disponível.</div>
            </div>
            <a href="certificado.php?curso_id=<?= $cursoId ?>"
               class="btn btn-light btn-sm mt-2 mt-md-0">
                <i class="fas fa-certificate mr-1"></i> Ver certificado
            </a>
        </div>
    <?php endif; ?>

    <!-- Flash messages -->
    <?php $fSuccess = get_flash('success'); if ($fSuccess): ?>
        <div class="alert alert-success alert-dismissible">
            <?= htmlspecialchars($fSuccess) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>
    <?php $fInfo = get_flash('info'); if ($fInfo): ?>
        <div class="alert alert-info alert-dismissible">
            <?= htmlspecialchars($fInfo) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>
    <?php $fError = get_flash('error'); if ($fError): ?>
        <div class="alert alert-danger alert-dismissible">
            <?= htmlspecialchars($fError) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>

    <!-- Barra de progresso -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Seu progresso</strong>
                <span><?= $totConcluidas ?>/<?= $totAulas ?> aulas (<?= $percentual ?>%)</span>
            </div>
            <div class="progress">
                <div class="progress-bar bg-success" style="width:<?= $percentual ?>%;"></div>
            </div>
        </div>
    </div>

    <!-- Área da aula selecionada -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                <div class="pr-3">
                    <p class="text-muted mb-1 small">Área da aula</p>
                    <?php if ($aulaSelecionada): ?>
                        <h2 class="h5 mb-1"><?= htmlspecialchars($aulaSelecionada['titulo']) ?></h2>
                        <p class="text-muted mb-0 small">Módulo: <?= htmlspecialchars($aulaSelecionada['modulo_titulo']) ?></p>
                    <?php else: ?>
                        <h2 class="h5 mb-1">Nenhuma aula selecionada</h2>
                    <?php endif; ?>
                </div>
                <?php if ($aulaSelecionada && (int) $aulaSelecionada['duracao_min'] > 0): ?>
                    <span class="badge badge-light">
                        <i class="far fa-clock"></i> <?= (int) $aulaSelecionada['duracao_min'] ?> min
                    </span>
                <?php endif; ?>
            </div>

            <?php
                // video_embed_url() valida a URL e converte links do YouTube para o formato embed
                $videoUrl    = $aulaSelecionada ? video_embed_url($aulaSelecionada['video_url']) : '';
                $materialUrl = $aulaSelecionada ? safe_http_url($aulaSelecionada['material_url']) : '';
            ?>

            <?php if ($videoUrl !== ''): ?>
                <div class="embed-responsive embed-responsive-16by9 border rounded">
                    <iframe class="embed-responsive-item"
                            src="<?= htmlspecialchars($videoUrl) ?>"
                            allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <div class="lesson-stage">
                    <div class="lesson-stage-note">
                        <i class="fas fa-play-circle fa-2x mb-2 text-primary"></i>
                        <h3 class="h6 mb-2">Espaço reservado para o vídeo da aula</h3>
                        <p class="mb-0">Quando o administrador cadastrar o link de vídeo, ele será exibido aqui.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($materialUrl !== ''): ?>
                <div class="mt-3">
                    <a class="btn btn-sm btn-outline-primary"
                       href="<?= htmlspecialchars($materialUrl) ?>"
                       target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-link"></i> Abrir material da aula
                    </a>
                </div>
            <?php endif; ?>

            <!-- Conteúdo da aula -->
            <?php if ($aulaSelecionada && trim($aulaSelecionada['conteudo']) !== ''): ?>
                <div class="mt-3 p-3" style="background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0;">
                    <h4 class="h6 text-primary mb-2"><i class="fas fa-align-left mr-1"></i> Conteúdo da aula</h4>
                    <div style="line-height:1.7; color:#334155;">
                        <?= nl2br(htmlspecialchars($aulaSelecionada['conteudo'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Widget de avaliação -->
            <?php if ($aulaSelecionada): ?>
                <div class="mt-4 p-3" style="border:1px solid #e2e8f0; border-radius:10px; background:#fff;">
                    <h4 class="h6 mb-3"><i class="fas fa-star text-warning mr-1"></i> Avaliar esta aula</h4>
                    <form method="POST" id="form-avaliacao">
                        <?= csrf_field() ?>
                        <input type="hidden" name="curso_id"   value="<?= $cursoId ?>">
                        <input type="hidden" name="aula_atual" value="<?= (int) $aulaSelecionada['id'] ?>">
                        <input type="hidden" name="aula_id"    value="<?= (int) $aulaSelecionada['id'] ?>">
                        <input type="hidden" name="acao"       value="avaliar">

                        <div class="stars-input mb-3" id="star-widget">
                            <?php
                            $notaAtual = (int) ($minhaAvaliacao['nota'] ?? 0);
                            for ($s = 5; $s >= 1; $s--): ?>
                                <input type="radio" id="star<?= $s ?>" name="nota" value="<?= $s ?>"
                                       <?= $notaAtual === $s ? 'checked' : '' ?>>
                                <label for="star<?= $s ?>" title="<?= $s ?> estrela<?= $s > 1 ? 's' : '' ?>">&#9733;</label>
                            <?php endfor; ?>
                        </div>

                        <div class="form-group mb-2">
                            <textarea name="comentario" class="form-control" rows="2"
                                      placeholder="Comentário opcional..."
                                      style="font-size:13px;"><?= htmlspecialchars((string) ($minhaAvaliacao['comentario'] ?? '')) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="fas fa-star mr-1"></i>
                            <?= $minhaAvaliacao ? 'Atualizar avaliação' : 'Enviar avaliação' ?>
                        </button>
                        <?php if ($minhaAvaliacao): ?>
                            <span class="small text-muted ml-2">
                                Sua nota atual: <?= $notaAtual ?>/5
                            </span>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Comentários dos alunos sobre esta aula -->
                <?php if (count($comentariosAula) > 0): ?>
                    <div class="mt-4">
                        <h4 class="h6 mb-3">
                            <i class="far fa-comments text-primary mr-1"></i>
                            O que os alunos acharam desta aula
                            <span class="text-muted small font-weight-normal">(<?= count($comentariosAula) ?>)</span>
                        </h4>
                        <?php foreach ($comentariosAula as $com): ?>
                            <div class="p-3 mb-2" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;">
                                <div class="d-flex justify-content-between align-items-center flex-wrap mb-1">
                                    <strong class="small"><?= htmlspecialchars($com['nome']) ?></strong>
                                    <span class="small text-muted">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <i class="fas fa-star <?= $s <= (int) $com['nota'] ? 'star-avg' : 'star-avg-empty' ?>" style="font-size:11px;"></i>
                                        <?php endfor; ?>
                                        · <?= date('d/m/Y', strtotime((string) $com['atualizado_em'])) ?>
                                    </span>
                                </div>
                                <div class="small" style="color:#334155; line-height:1.6;">
                                    <?= nl2br(htmlspecialchars((string) $com['comentario'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Lista de módulos e aulas -->
    <?php if (count($modulos) === 0): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <p class="mb-0">Este curso ainda não possui aulas cadastradas.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($modulos as $mod): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <strong>Módulo <?= (int) $mod['ordem'] ?>:</strong>
                    <?= htmlspecialchars($mod['titulo']) ?>
                </div>
                <div class="card-body">
                    <?php if (count($mod['aulas']) === 0): ?>
                        <p class="text-muted mb-0">Sem aulas neste módulo.</p>
                    <?php else: ?>
                        <?php foreach ($mod['aulas'] as $aula): ?>
                            <?php
                                $isSelected = $aulaSelecionada && (int) $aulaSelecionada['id'] === (int) $aula['id'];
                                $avgInfo    = $mediaAvaliacoes[(int) $aula['id']] ?? null;
                            ?>
                            <div class="lesson-card
                                <?= $aula['concluida'] ? 'lesson-done' : '' ?>
                                <?= $isSelected ? 'lesson-selected' : '' ?>
                                p-3">
                                <div class="d-flex justify-content-between align-items-start flex-wrap">
                                    <div class="pr-3">
                                        <div class="font-weight-bold">
                                            <?= htmlspecialchars($aula['titulo']) ?>
                                        </div>
                                        <div class="d-flex align-items-center flex-wrap mt-1" style="gap:10px;">
                                            <?php if ($aula['duracao_min'] > 0): ?>
                                                <small class="text-muted">
                                                    <i class="far fa-clock"></i> <?= $aula['duracao_min'] ?> min
                                                </small>
                                            <?php endif; ?>
                                            <?php if ($avgInfo): ?>
                                                <small class="text-muted" title="<?= $avgInfo['total'] ?> avaliação(ões)">
                                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                                        <i class="fas fa-star <?= $s <= round($avgInfo['media']) ? 'star-avg' : 'star-avg-empty' ?>" style="font-size:11px;"></i>
                                                    <?php endfor; ?>
                                                    <?= number_format($avgInfo['media'], 1) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="mt-2 mt-md-0 text-right">
                                        <a class="btn btn-sm btn-outline-primary mb-1 mr-md-2"
                                           href="meu-curso.php?curso_id=<?= $cursoId ?>&aula=<?= (int) $aula['id'] ?>">
                                            Abrir aula
                                        </a>
                                        <?php if (!$expirado): ?>
                                            <form method="POST" class="d-inline-block">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="curso_id"   value="<?= $cursoId ?>">
                                                <input type="hidden" name="aula_atual" value="<?= (int) $aula['id'] ?>">
                                                <input type="hidden" name="aula_id"    value="<?= (int) $aula['id'] ?>">
                                                <?php if ($aula['concluida']): ?>
                                                    <input type="hidden" name="acao" value="desfazer">
                                                    <button class="btn btn-sm btn-outline-secondary">Desmarcar</button>
                                                <?php else: ?>
                                                    <input type="hidden" name="acao" value="concluir">
                                                    <button class="btn btn-sm btn-success">Marcar concluída</button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Avaliação do curso como um todo -->
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h4 class="h6 mb-3"><i class="fas fa-award text-warning mr-1"></i> Avaliar este curso</h4>
            <p class="text-muted small mb-3">
                Sua opinião sobre o curso completo ajuda outros alunos a escolherem melhor.
            </p>
            <form method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="curso_id"   value="<?= $cursoId ?>">
                <input type="hidden" name="aula_atual" value="<?= $aulaSelecionada ? (int) $aulaSelecionada['id'] : 0 ?>">
                <input type="hidden" name="acao"       value="avaliar_curso">

                <div class="stars-input mb-3">
                    <?php
                    $notaCursoAtual = (int) ($minhaAvaliacaoCurso['nota'] ?? 0);
                    for ($s = 5; $s >= 1; $s--): ?>
                        <input type="radio" id="cstar<?= $s ?>" name="nota" value="<?= $s ?>"
                               <?= $notaCursoAtual === $s ? 'checked' : '' ?>>
                        <label for="cstar<?= $s ?>" title="<?= $s ?> estrela<?= $s > 1 ? 's' : '' ?>">&#9733;</label>
                    <?php endfor; ?>
                </div>

                <div class="form-group mb-2">
                    <textarea name="comentario" class="form-control" rows="2"
                              placeholder="O que você achou do curso? (opcional)"
                              style="font-size:13px;"><?= htmlspecialchars((string) ($minhaAvaliacaoCurso['comentario'] ?? '')) ?></textarea>
                </div>
                <button type="submit" class="btn btn-sm btn-warning">
                    <i class="fas fa-star mr-1"></i>
                    <?= $minhaAvaliacaoCurso ? 'Atualizar avaliação do curso' : 'Avaliar curso' ?>
                </button>
                <?php if ($minhaAvaliacaoCurso): ?>
                    <span class="small text-muted ml-2">Sua nota atual: <?= $notaCursoAtual ?>/5</span>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

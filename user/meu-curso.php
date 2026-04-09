<?php
/**
 * Página de visualização de curso inscrito pelo usuário
 *
 * Este arquivo gerencia a visualização de cursos em que o usuário está inscrito,
 * permitindo navegar entre aulas, marcar progresso e visualizar conteúdo das aulas.
 *
 * @package SkillConnect
 * @author Sistema SkillConnect
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
$cursoId = (int) ($_GET['curso_id'] ?? $_POST['curso_id'] ?? 0);
$aulaSelecionadaId = (int) ($_GET['aula'] ?? $_POST['aula_atual'] ?? 0);

if ($cursoId <= 0) {
    flash('error', 'Curso invalido.');
    redirect('meus-cursos.php');
}

// Garante que o aluno esta inscrito no curso.
$inscricaoStmt = $cx->prepare("SELECT id, status FROM inscricoes_cursos WHERE usuario_id = ? AND curso_id = ? LIMIT 1");
$inscricaoStmt->bind_param("ii", $usuarioId, $cursoId);
$inscricaoStmt->execute();
$inscricao = $inscricaoStmt->get_result()->fetch_assoc();
$inscricaoStmt->close();

if (!$inscricao) {
    flash('error', 'Voce nao esta inscrito neste curso.');
    redirect('meus-cursos.php');
}

if (($inscricao['status'] ?? '') === 'cancelado') {
    flash('error', 'Sua inscricao neste curso esta cancelada.');
    redirect('meus-cursos.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aulaAtual = (int) ($_POST['aula_atual'] ?? $aulaSelecionadaId);
    if (!csrf_validate()) {
        flash('error', 'Sessao expirada. Tente novamente.');
        redirect("meu-curso.php?curso_id={$cursoId}&aula={$aulaAtual}");
    }

    $aulaId = (int) ($_POST['aula_id'] ?? 0);
    $acao = trim((string) ($_POST['acao'] ?? ''));

    if ($aulaId > 0) {
        // Valida se aula pertence ao curso.
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
                } catch (mysqli_sql_exception $e) {
                    if ((int) $e->getCode() === 1062) {
                        flash('info', 'Essa aula ja estava marcada como concluida.');
                    } else {
                        flash('error', 'Nao foi possivel atualizar o progresso.');
                    }
                }
            } elseif ($acao === 'desfazer') {
                $del = $cx->prepare("DELETE FROM progresso_aulas WHERE usuario_id = ? AND aula_id = ? LIMIT 1");
                $del->bind_param("ii", $usuarioId, $aulaId);
                $del->execute();
                $del->close();
                flash('info', 'Aula desmarcada.');
            }
        }
    }

    redirect("meu-curso.php?curso_id={$cursoId}&aula={$aulaAtual}");
}

// Dados do curso
$cursoStmt = $cx->prepare("SELECT id, titulo, descricao, carga_horaria, modalidade, nivel FROM cursos WHERE id = ? LIMIT 1");
$cursoStmt->bind_param("i", $cursoId);
$cursoStmt->execute();
$curso = $cursoStmt->get_result()->fetch_assoc();
$cursoStmt->close();

if (!$curso) {
    flash('error', 'Curso nao encontrado.');
    redirect('meus-cursos.php');
}

// Carrega modulos e aulas com status de progresso.
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

$modulos = [];
$totAulas = 0;
$totConcluidas = 0;
$primeiraAula = null;
$aulaSelecionada = null;

while ($row = $res->fetch_assoc()) {
    $mid = (int) $row['modulo_id'];
    if (!isset($modulos[$mid])) {
        $modulos[$mid] = [
            'id' => $mid,
            'titulo' => $row['modulo_titulo'],
            'ordem' => (int) $row['modulo_ordem'],
            'aulas' => [],
        ];
    }

    if (!empty($row['aula_id'])) {
        $concluida = !empty($row['progresso_id']);
        $aula = [
            'id' => (int) $row['aula_id'],
            'titulo' => $row['aula_titulo'],
            'conteudo' => $row['aula_conteudo'] ?? '',
            'video_url' => $row['aula_video_url'] ?? '',
            'material_url' => $row['aula_material_url'] ?? '',
            'modulo_titulo' => $row['modulo_titulo'] ?? '',
            'duracao_min' => (int) ($row['duracao_min'] ?? 0),
            'ordem' => (int) ($row['aula_ordem'] ?? 0),
            'concluida' => $concluida,
        ];
        $modulos[$mid]['aulas'][] = $aula;

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

/**
 * Valida e retorna uma URL HTTP/HTTPS segura
 *
 * Verifica se a URL fornecida é válida e utiliza protocolo HTTP ou HTTPS.
 * Retorna string vazia se a URL for inválida ou não usar protocolo seguro.
 *
 * @param mixed $url A URL a ser validada
 * @return string A URL validada ou string vazia se inválida
 */
function safe_http_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return '';
    }

    $parts = parse_url($url);
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
    <style>
        .hero {
            border-radius: 14px;
            background: linear-gradient(120deg, #0f766e 0%, #1d4ed8 100%);
            color: #fff;
            padding: 22px;
        }
        .lesson-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
            margin-bottom: 10px;
        }
        .lesson-done {
            border-color: #86efac;
            background: #f0fdf4;
        }
        .lesson-selected {
            border-color: #93c5fd;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, .12);
        }
        .progress {
            height: 10px;
            border-radius: 999px;
            background: #dbeafe;
        }
        .lesson-stage {
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            min-height: 320px;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2f7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 22px;
        }
        .lesson-stage-note {
            text-align: center;
            color: #475569;
            max-width: 560px;
        }
    </style>
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <div class="hero mb-4">
        <h1 class="h4 mb-1"><?php echo htmlspecialchars($curso['titulo']); ?></h1>
        <p class="mb-2"><?php echo htmlspecialchars($curso['descricao'] ?? ''); ?></p>
        <div class="small">
            <?php echo htmlspecialchars($curso['modalidade'] ?? '-'); ?> |
            <?php echo htmlspecialchars($curso['nivel'] ?? '-'); ?> |
            <?php echo $curso['carga_horaria'] ? (int) $curso['carga_horaria'] . 'h' : '-'; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Seu progresso</strong>
                <span><?php echo $totConcluidas; ?>/<?php echo $totAulas; ?> aulas (<?php echo $percentual; ?>%)</span>
            </div>
            <div class="progress">
                <div class="progress-bar bg-success" style="width: <?php echo $percentual; ?>%;"></div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                <div class="pr-3">
                    <p class="text-muted mb-1 small">Area da aula</p>
                    <?php if ($aulaSelecionada): ?>
                        <h2 class="h5 mb-1"><?php echo htmlspecialchars($aulaSelecionada['titulo']); ?></h2>
                        <p class="text-muted mb-0 small">Modulo: <?php echo htmlspecialchars($aulaSelecionada['modulo_titulo']); ?></p>
                    <?php else: ?>
                        <h2 class="h5 mb-1">Nenhuma aula selecionada</h2>
                    <?php endif; ?>
                </div>
                <?php if ($aulaSelecionada && (int) $aulaSelecionada['duracao_min'] > 0): ?>
                    <span class="badge badge-light"><i class="far fa-clock"></i> <?php echo (int) $aulaSelecionada['duracao_min']; ?> min</span>
                <?php endif; ?>
            </div>

            <?php
                $videoUrl = $aulaSelecionada ? safe_http_url($aulaSelecionada['video_url']) : '';
                $materialUrl = $aulaSelecionada ? safe_http_url($aulaSelecionada['material_url']) : '';
            ?>

            <?php if ($videoUrl !== ''): ?>
                <div class="embed-responsive embed-responsive-16by9 border rounded">
                    <iframe class="embed-responsive-item" src="<?php echo htmlspecialchars($videoUrl); ?>" allowfullscreen></iframe>
                </div>
            <?php else: ?>
                <div class="lesson-stage">
                    <div class="lesson-stage-note">
                        <i class="fas fa-play-circle fa-2x mb-2 text-primary"></i>
                        <h3 class="h6 mb-2">Espaco reservado para o video da aula</h3>
                        <p class="mb-0">Quando o administrador cadastrar o link de video, ele sera exibido aqui.</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($materialUrl !== ''): ?>
                <div class="mt-3">
                    <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($materialUrl); ?>" target="_blank" rel="noopener noreferrer">
                        <i class="fas fa-link"></i> Abrir material da aula
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (count($modulos) === 0): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <p class="mb-0">Este curso ainda nao possui aulas cadastradas
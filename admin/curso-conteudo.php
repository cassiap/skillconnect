<?php
/**
 * Gestão de conteúdo do curso (módulos e aulas) pelo administrador
 *
 * Permite cadastrar módulos, cadastrar e editar aulas (título, conteúdo,
 * link de vídeo, material e duração) e ativar/desativar itens. O link de
 * vídeo aceita qualquer formato do YouTube (watch, youtu.be, shorts) —
 * a conversão para o formato embed é feita por video_embed_url() na
 * exibição ao aluno.
 *
 * @package SkillConnect
 */

require_once __DIR__ . '/../config/db.php';

admin_check();

$cursoId = (int) ($_GET['curso_id'] ?? $_POST['curso_id'] ?? 0);

if ($cursoId <= 0) {
    flash('error', 'Curso invalido.');
    redirect('../user/cursos.php');
}

$cursoStmt = $cx->prepare("SELECT id, titulo, ativo FROM cursos WHERE id = ? LIMIT 1");
$cursoStmt->bind_param("i", $cursoId);
$cursoStmt->execute();
$curso = $cursoStmt->get_result()->fetch_assoc();
$cursoStmt->close();

if (!$curso) {
    flash('error', 'Curso nao encontrado.');
    redirect('../user/cursos.php');
}

/**
 * Verifica se um módulo pertence ao curso em edição.
 *
 * @param mysqli $cx Conexão ativa
 * @param int $moduloId ID do módulo
 * @param int $cursoId ID do curso
 * @return bool True se o módulo existe e pertence ao curso
 */
function modulo_pertence_ao_curso(mysqli $cx, int $moduloId, int $cursoId): bool {
    $stmt = $cx->prepare("SELECT id FROM modulos WHERE id = ? AND curso_id = ? LIMIT 1");
    $stmt->bind_param("ii", $moduloId, $cursoId);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $ok;
}

// ===== POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        flash('error', 'Sessao expirada. Tente novamente.');
        redirect("curso-conteudo.php?curso_id={$cursoId}");
    }

    $acao = trim((string) ($_POST['acao'] ?? ''));

    // ----- Novo módulo -----
    if ($acao === 'add_modulo') {
        $titulo = trim((string) ($_POST['titulo'] ?? ''));

        if ($titulo === '') {
            flash('error', 'Informe o titulo do modulo.');
        } else {
            try {
                $ordStmt = $cx->prepare("SELECT COALESCE(MAX(ordem), 0) + 1 FROM modulos WHERE curso_id = ?");
                $ordStmt->bind_param("i", $cursoId);
                $ordStmt->execute();
                $ordem = (int) $ordStmt->get_result()->fetch_row()[0];
                $ordStmt->close();

                $ins = $cx->prepare("INSERT INTO modulos (curso_id, titulo, ordem) VALUES (?, ?, ?)");
                $ins->bind_param("isi", $cursoId, $titulo, $ordem);
                $ins->execute();
                $ins->close();
                flash('success', 'Modulo cadastrado.');
            } catch (mysqli_sql_exception $e) {
                error_log('curso-conteudo.php add_modulo error: ' . $e->getMessage());
                flash('error', 'Erro ao cadastrar modulo.');
            }
        }
        redirect("curso-conteudo.php?curso_id={$cursoId}");
    }

    // ----- Nova aula / edição de aula -----
    if ($acao === 'add_aula' || $acao === 'edit_aula') {
        $moduloId    = (int) ($_POST['modulo_id'] ?? 0);
        $aulaId      = (int) ($_POST['aula_id'] ?? 0);
        $titulo      = trim((string) ($_POST['titulo'] ?? ''));
        $conteudo    = trim((string) ($_POST['conteudo'] ?? ''));
        $videoUrl    = trim((string) ($_POST['video_url'] ?? ''));
        $materialUrl = trim((string) ($_POST['material_url'] ?? ''));
        $duracaoMin  = max(0, (int) ($_POST['duracao_min'] ?? 0));

        $erro = '';
        if ($titulo === '') {
            $erro = 'Informe o titulo da aula.';
        } elseif (!modulo_pertence_ao_curso($cx, $moduloId, $cursoId)) {
            $erro = 'Modulo invalido.';
        } elseif ($videoUrl !== '' && video_embed_url($videoUrl) === '') {
            $erro = 'Link de video invalido. Use um link http(s) valido (YouTube ou embed).';
        } elseif ($materialUrl !== '' && !filter_var($materialUrl, FILTER_VALIDATE_URL)) {
            $erro = 'Link de material invalido.';
        }

        if ($erro !== '') {
            flash('error', $erro);
            redirect("curso-conteudo.php?curso_id={$cursoId}");
        }

        $videoDb    = $videoUrl !== '' ? $videoUrl : null;
        $materialDb = $materialUrl !== '' ? $materialUrl : null;
        $duracaoDb  = $duracaoMin > 0 ? $duracaoMin : null;

        try {
            if ($acao === 'add_aula') {
                $ordStmt = $cx->prepare("SELECT COALESCE(MAX(ordem), 0) + 1 FROM aulas WHERE modulo_id = ?");
                $ordStmt->bind_param("i", $moduloId);
                $ordStmt->execute();
                $ordem = (int) $ordStmt->get_result()->fetch_row()[0];
                $ordStmt->close();

                $ins = $cx->prepare(
                    "INSERT INTO aulas (modulo_id, titulo, conteudo, video_url, material_url, duracao_min, ordem)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                $ins->bind_param("issssii", $moduloId, $titulo, $conteudo, $videoDb, $materialDb, $duracaoDb, $ordem);
                $ins->execute();
                $ins->close();
                flash('success', 'Aula cadastrada.');
            } else {
                // Garante que a aula em edição pertence a um módulo deste curso
                $chk = $cx->prepare(
                    "SELECT a.id FROM aulas a INNER JOIN modulos m ON m.id = a.modulo_id
                     WHERE a.id = ? AND m.curso_id = ? LIMIT 1"
                );
                $chk->bind_param("ii", $aulaId, $cursoId);
                $chk->execute();
                $existe = (bool) $chk->get_result()->fetch_assoc();
                $chk->close();

                if (!$existe) {
                    flash('error', 'Aula nao encontrada neste curso.');
                    redirect("curso-conteudo.php?curso_id={$cursoId}");
                }

                $upd = $cx->prepare(
                    "UPDATE aulas SET modulo_id = ?, titulo = ?, conteudo = ?, video_url = ?, material_url = ?, duracao_min = ?
                     WHERE id = ?"
                );
                $upd->bind_param("issssii", $moduloId, $titulo, $conteudo, $videoDb, $materialDb, $duracaoDb, $aulaId);
                $upd->execute();
                $upd->close();
                flash('success', 'Aula atualizada.');
            }
        } catch (mysqli_sql_exception $e) {
            error_log('curso-conteudo.php ' . $acao . ' error: ' . $e->getMessage());
            flash('error', 'Erro ao salvar aula.');
        }
        redirect("curso-conteudo.php?curso_id={$cursoId}");
    }

    // ----- Ativar/desativar módulo -----
    if ($acao === 'toggle_modulo') {
        $moduloId = (int) ($_POST['modulo_id'] ?? 0);
        if (modulo_pertence_ao_curso($cx, $moduloId, $cursoId)) {
            $upd = $cx->prepare("UPDATE modulos SET ativo = 1 - ativo WHERE id = ?");
            $upd->bind_param("i", $moduloId);
            $upd->execute();
            $upd->close();
            flash('success', 'Status do modulo atualizado.');
        } else {
            flash('error', 'Modulo invalido.');
        }
        redirect("curso-conteudo.php?curso_id={$cursoId}");
    }

    // ----- Ativar/desativar aula -----
    if ($acao === 'toggle_aula') {
        $aulaId = (int) ($_POST['aula_id'] ?? 0);
        $upd = $cx->prepare(
            "UPDATE aulas a INNER JOIN modulos m ON m.id = a.modulo_id
             SET a.ativo = 1 - a.ativo
             WHERE a.id = ? AND m.curso_id = ?"
        );
        $upd->bind_param("ii", $aulaId, $cursoId);
        $upd->execute();
        $alterou = $upd->affected_rows > 0;
        $upd->close();
        flash($alterou ? 'success' : 'error', $alterou ? 'Status da aula atualizado.' : 'Aula invalida.');
        redirect("curso-conteudo.php?curso_id={$cursoId}");
    }

    redirect("curso-conteudo.php?curso_id={$cursoId}");
}

// ===== GET: módulos + aulas (inclui inativos — visão administrativa) =====
$modulos = [];
$listStmt = $cx->prepare(
    "SELECT m.id AS modulo_id, m.titulo AS modulo_titulo, m.ordem AS modulo_ordem, m.ativo AS modulo_ativo,
            a.id AS aula_id, a.titulo AS aula_titulo, a.video_url, a.material_url, a.duracao_min,
            a.ordem AS aula_ordem, a.ativo AS aula_ativo
     FROM modulos m
     LEFT JOIN aulas a ON a.modulo_id = m.id
     WHERE m.curso_id = ?
     ORDER BY m.ordem ASC, a.ordem ASC"
);
$listStmt->bind_param("i", $cursoId);
$listStmt->execute();
$res = $listStmt->get_result();
while ($row = $res->fetch_assoc()) {
    $mid = (int) $row['modulo_id'];
    if (!isset($modulos[$mid])) {
        $modulos[$mid] = [
            'id'     => $mid,
            'titulo' => $row['modulo_titulo'],
            'ordem'  => (int) $row['modulo_ordem'],
            'ativo'  => (int) $row['modulo_ativo'],
            'aulas'  => [],
        ];
    }
    if (!empty($row['aula_id'])) {
        $modulos[$mid]['aulas'][] = [
            'id'           => (int) $row['aula_id'],
            'titulo'       => $row['aula_titulo'],
            'video_url'    => (string) ($row['video_url'] ?? ''),
            'material_url' => (string) ($row['material_url'] ?? ''),
            'duracao_min'  => (int) ($row['duracao_min'] ?? 0),
            'ordem'        => (int) ($row['aula_ordem'] ?? 0),
            'ativo'        => (int) ($row['aula_ativo'] ?? 0),
        ];
    }
}
$listStmt->close();

// Aula em edição (prefill do formulário)
$aulaEdicao = null;
$editarAulaId = (int) ($_GET['editar_aula'] ?? 0);
if ($editarAulaId > 0) {
    $edStmt = $cx->prepare(
        "SELECT a.id, a.modulo_id, a.titulo, a.conteudo, a.video_url, a.material_url, a.duracao_min
         FROM aulas a INNER JOIN modulos m ON m.id = a.modulo_id
         WHERE a.id = ? AND m.curso_id = ? LIMIT 1"
    );
    $edStmt->bind_param("ii", $editarAulaId, $cursoId);
    $edStmt->execute();
    $aulaEdicao = $edStmt->get_result()->fetch_assoc();
    $edStmt->close();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conteúdo do Curso - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <a href="../user/cursos.php" class="btn btn-secondary mb-3">&larr; Voltar para cursos</a>

    <h2 class="text-primary mb-1">Conteúdo: <?php echo htmlspecialchars($curso['titulo']); ?></h2>
    <p class="text-muted mb-4">
        Gerencie módulos e aulas deste curso.
        <?php if ((int) $curso['ativo'] !== 1): ?>
            <span class="badge badge-warning">Curso inativo</span>
        <?php endif; ?>
    </p>

    <?php $f = get_flash('success'); if ($f): ?>
        <div class="alert alert-success alert-dismissible">
            <?php echo htmlspecialchars($f); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>
    <?php $f = get_flash('error'); if ($f): ?>
        <div class="alert alert-danger alert-dismissible">
            <?php echo htmlspecialchars($f); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Coluna esquerda: módulos e aulas existentes -->
        <div class="col-lg-7 mb-4">
            <?php if (count($modulos) === 0): ?>
                <div class="alert alert-info">
                    Este curso ainda não tem módulos. Cadastre o primeiro módulo ao lado.
                </div>
            <?php else: ?>
                <?php foreach ($modulos as $mod): ?>
                    <div class="card shadow-sm mb-3 <?php echo $mod['ativo'] ? '' : 'bg-light'; ?>">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Módulo <?php echo $mod['ordem']; ?>:</strong>
                                <?php echo htmlspecialchars($mod['titulo']); ?>
                                <?php if (!$mod['ativo']): ?>
                                    <span class="badge badge-secondary ml-1">Inativo</span>
                                <?php endif; ?>
                            </div>
                            <form method="POST" class="mb-0">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="curso_id" value="<?php echo $cursoId; ?>">
                                <input type="hidden" name="modulo_id" value="<?php echo $mod['id']; ?>">
                                <input type="hidden" name="acao" value="toggle_modulo">
                                <button type="submit" class="btn btn-sm <?php echo $mod['ativo'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>">
                                    <?php echo $mod['ativo'] ? 'Desativar' : 'Reativar'; ?>
                                </button>
                            </form>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($mod['aulas']) === 0): ?>
                                <p class="text-muted small p-3 mb-0">Sem aulas neste módulo.</p>
                            <?php else: ?>
                                <table class="table table-sm mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Aula</th>
                                            <th>Vídeo</th>
                                            <th>Duração</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($mod['aulas'] as $aula): ?>
                                        <tr class="<?php echo $aula['ativo'] ? '' : 'text-muted'; ?>">
                                            <td><?php echo $aula['ordem']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($aula['titulo']); ?>
                                                <?php if (!$aula['ativo']): ?>
                                                    <span class="badge badge-secondary">Inativa</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($aula['video_url'] === ''): ?>
                                                    <span class="text-muted small">—</span>
                                                <?php elseif (video_embed_url($aula['video_url']) !== ''): ?>
                                                    <span class="text-success small"><i class="fas fa-check-circle"></i> OK</span>
                                                <?php else: ?>
                                                    <span class="text-danger small" title="Link inválido — não será exibido ao aluno">
                                                        <i class="fas fa-exclamation-triangle"></i> Inválido
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="small"><?php echo $aula['duracao_min'] > 0 ? $aula['duracao_min'] . ' min' : '—'; ?></td>
                                            <td class="text-right text-nowrap">
                                                <a class="btn btn-sm btn-outline-primary"
                                                   href="curso-conteudo.php?curso_id=<?php echo $cursoId; ?>&editar_aula=<?php echo $aula['id']; ?>#form-aula">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="curso_id" value="<?php echo $cursoId; ?>">
                                                    <input type="hidden" name="aula_id" value="<?php echo $aula['id']; ?>">
                                                    <input type="hidden" name="acao" value="toggle_aula">
                                                    <button type="submit" class="btn btn-sm <?php echo $aula['ativo'] ? 'btn-outline-danger' : 'btn-outline-success'; ?>"
                                                            title="<?php echo $aula['ativo'] ? 'Desativar aula' : 'Reativar aula'; ?>">
                                                        <i class="fas <?php echo $aula['ativo'] ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Coluna direita: formulários -->
        <div class="col-lg-5">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-folder-plus text-primary"></i> Novo módulo</h5>
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="curso_id" value="<?php echo $cursoId; ?>">
                        <input type="hidden" name="acao" value="add_modulo">
                        <div class="form-group">
                            <label for="modulo_titulo">Título do módulo</label>
                            <input type="text" class="form-control" name="titulo" id="modulo_titulo" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Cadastrar módulo</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm" id="form-aula">
                <div class="card-body">
                    <h5 class="mb-3">
                        <i class="fas <?php echo $aulaEdicao ? 'fa-pen' : 'fa-plus-circle'; ?> text-primary"></i>
                        <?php echo $aulaEdicao ? 'Editar aula' : 'Nova aula'; ?>
                    </h5>

                    <?php if (count($modulos) === 0): ?>
                        <p class="text-muted small mb-0">Cadastre um módulo antes de adicionar aulas.</p>
                    <?php else: ?>
                        <form method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="curso_id" value="<?php echo $cursoId; ?>">
                            <input type="hidden" name="acao" value="<?php echo $aulaEdicao ? 'edit_aula' : 'add_aula'; ?>">
                            <?php if ($aulaEdicao): ?>
                                <input type="hidden" name="aula_id" value="<?php echo (int) $aulaEdicao['id']; ?>">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="aula_modulo">Módulo</label>
                                <select class="form-control" name="modulo_id" id="aula_modulo" required>
                                    <?php foreach ($modulos as $mod): ?>
                                        <option value="<?php echo $mod['id']; ?>"
                                            <?php echo ($aulaEdicao && (int) $aulaEdicao['modulo_id'] === $mod['id']) ? 'selected' : ''; ?>>
                                            Módulo <?php echo $mod['ordem']; ?> — <?php echo htmlspecialchars($mod['titulo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="aula_titulo">Título da aula</label>
                                <input type="text" class="form-control" name="titulo" id="aula_titulo" required
                                       value="<?php echo htmlspecialchars((string) ($aulaEdicao['titulo'] ?? '')); ?>">
                            </div>
                            <div class="form-group">
                                <label for="aula_video">Link do vídeo</label>
                                <input type="url" class="form-control" name="video_url" id="aula_video"
                                       placeholder="https://www.youtube.com/watch?v=..."
                                       value="<?php echo htmlspecialchars((string) ($aulaEdicao['video_url'] ?? '')); ?>">
                                <small class="form-text text-muted">
                                    Cole qualquer link do YouTube (watch, youtu.be ou shorts) — a conversão para o
                                    player é automática. Em branco = aula sem vídeo.
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="aula_conteudo">Conteúdo / texto da aula</label>
                                <textarea class="form-control" name="conteudo" id="aula_conteudo" rows="4"
                                          placeholder="Resumo, instruções ou texto de apoio (opcional)"><?php echo htmlspecialchars((string) ($aulaEdicao['conteudo'] ?? '')); ?></textarea>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="aula_material">Link de material (opcional)</label>
                                    <input type="url" class="form-control" name="material_url" id="aula_material"
                                           placeholder="https://..."
                                           value="<?php echo htmlspecialchars((string) ($aulaEdicao['material_url'] ?? '')); ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="aula_duracao">Duração (min)</label>
                                    <input type="number" class="form-control" name="duracao_min" id="aula_duracao" min="0"
                                           value="<?php echo (int) ($aulaEdicao['duracao_min'] ?? 0); ?>">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm">
                                <?php echo $aulaEdicao ? 'Salvar alterações' : 'Cadastrar aula'; ?>
                            </button>
                            <?php if ($aulaEdicao): ?>
                                <a href="curso-conteudo.php?curso_id=<?php echo $cursoId; ?>" class="btn btn-secondary btn-sm ml-1">
                                    Cancelar edição
                                </a>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>

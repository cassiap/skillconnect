<?php
require_once __DIR__ . '/../config/db.php';

auth_check();

if (($_SESSION['perfil'] ?? '') === 'admin') {
    flash('info', 'Administradores usam o painel administrativo.');
    redirect(app_url('admin/admin.php'));
}

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
$nomeUsuario = trim((string) ($_SESSION['nome'] ?? 'Aluno'));

if (!function_exists('painel_scalar')) {
    /**
     * Executes a scalar COUNT or aggregate query and returns the result as an integer.
     *
     * Supports parameterized queries via mysqli prepared statements to prevent SQL injection.
     * Returns 0 on failure and logs the error for debugging.
     *
     * @param mysqli $cx     Active database connection.
     * @param string $sql    SQL query with optional ? placeholders.
     * @param string $types  bind_param type string (e.g. 'i', 'ss'). Empty for queries without parameters.
     * @param array  $params Ordered values to bind. Must match $types.
     * @return int           First column of first row cast to int, or 0 on error.
     */
    function painel_scalar(mysqli $cx, string $sql, string $types = '', array $params = []): int {
        try {
            if ($types !== '' && count($params) > 0) {
                $stmt = $cx->prepare($sql);
                if (!$stmt) {
                    error_log('painel_scalar prepare error: ' . $cx->error);
                    return 0;
                }
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                if (!$res) {
                    error_log('painel_scalar get_result error: ' . $stmt->error);
                    return 0;
                }
            } else {
                $res = $cx->query($sql);
                if (!$res) {
                    error_log('painel_scalar query error: ' . $cx->error);
                    return 0;
                }
            }
            $row = $res->fetch_row();
            $res->close();
            return (int) ($row[0] ?? 0);
        } catch (Throwable $e) {
            error_log('painel_scalar exception: ' . $e->getMessage());
            return 0;
        }
    }
}

if (!function_exists('painel_fetch_all')) {
    /**
     * Executes a SELECT query and returns all rows as an associative array.
     *
     * Supports parameterized queries via mysqli prepared statements to prevent SQL injection.
     * Returns an empty array on failure and logs the error for debugging.
     *
     * @param mysqli $cx     Active database connection.
     * @param string $sql    SQL query with optional ? placeholders.
     * @param string $types  bind_param type string (e.g. 'i', 'ss'). Empty for queries without parameters.
     * @param array  $params Ordered values to bind. Must match $types.
     * @return array         Array of associative rows, or [] on error.
     */
    function painel_fetch_all(mysqli $cx, string $sql, string $types = '', array $params = []): array {
        try {
            if ($types !== '' && count($params) > 0) {
                $stmt = $cx->prepare($sql);
                if (!$stmt) {
                    error_log('painel_fetch_all prepare error: ' . $cx->error);
                    return [];
                }
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                if (!$res) {
                    error_log('painel_fetch_all get_result error: ' . $stmt->error);
                    return [];
                }
            } else {
                $res = $cx->query($sql);
                if (!$res) {
                    error_log('painel_fetch_all query error: ' . $cx->error);
                    return [];
                }
            }
            $rows = [];
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
            $res->close();
            return $rows;
        } catch (Throwable $e) {
            error_log('painel_fetch_all exception: ' . $e->getMessage());
            return [];
        }
    }
}

$uid = (int) $usuarioId;

$kpis = [
    'inscricoes'        => painel_scalar($cx, "SELECT COUNT(*) FROM inscricoes_cursos WHERE usuario_id = ?", 'i', [$uid]),
    'cursos_concluidos' => painel_scalar($cx, "SELECT COUNT(*) FROM inscricoes_cursos WHERE usuario_id = ? AND status = 'concluido'", 'i', [$uid]),
    'candidaturas'      => painel_scalar($cx, "SELECT COUNT(*) FROM candidaturas WHERE usuario_id = ?", 'i', [$uid]),
    'cand_analise'      => painel_scalar($cx, "SELECT COUNT(*) FROM candidaturas WHERE usuario_id = ? AND status = 'em_analise'", 'i', [$uid]),
    'cand_aprovadas'    => painel_scalar($cx, "SELECT COUNT(*) FROM candidaturas WHERE usuario_id = ? AND status = 'aprovado'", 'i', [$uid]),
];

$perfilRows = painel_fetch_all(
    $cx,
    "SELECT telefone, cep, estado, cidade, logradouro, bairro FROM usuarios WHERE id = ? LIMIT 1",
    'i',
    [$uid]
);
$perfilDados = $perfilRows[0] ?? [];

$camposPerfil = [
    'telefone' => 'telefone',
    'cep' => 'cep',
    'estado' => 'estado',
    'cidade' => 'cidade',
    'logradouro' => 'logradouro',
    'bairro' => 'bairro',
];

$faltantes = [];
$preenchidos = 0;
foreach ($camposPerfil as $campo => $rotulo) {
    $valor = trim((string) ($perfilDados[$campo] ?? ''));
    if ($valor === '') {
        $faltantes[] = $rotulo;
    } else {
        $preenchidos++;
    }
}
$perfilPercentual = (int) floor(($preenchidos / max(1, count($camposPerfil))) * 100);

$curriculoRows = painel_fetch_all(
    $cx,
    "SELECT titulo_profissional, resumo, habilidades, experiencias, formacao, links
     FROM curriculos
     WHERE usuario_id = ?
     LIMIT 1",
    'i',
    [$uid]
);
$curriculo = $curriculoRows[0] ?? [];
$temCurriculo = false;
if ($curriculo !== []) {
    foreach (['titulo_profissional', 'resumo', 'habilidades', 'experiencias', 'formacao', 'links'] as $field) {
        if (trim((string) ($curriculo[$field] ?? '')) !== '') {
            $temCurriculo = true;
            break;
        }
    }
}

$inscricoesRecentes = painel_fetch_all(
    $cx,
    "SELECT ic.status, ic.criado_em, c.id AS curso_id, c.titulo, c.modalidade, c.nivel
     FROM inscricoes_cursos ic
     INNER JOIN cursos c ON c.id = ic.curso_id
     WHERE ic.usuario_id = ?
     ORDER BY ic.criado_em DESC
     LIMIT 5",
    'i',
    [$uid]
);

$candidaturasRecentes = painel_fetch_all(
    $cx,
    "SELECT c.status, c.criado_em, v.id AS vaga_id, v.titulo, v.empresa
     FROM candidaturas c
     INNER JOIN vagas v ON v.id = c.vaga_id
     WHERE c.usuario_id = ?
     ORDER BY c.criado_em DESC
     LIMIT 5",
    'i',
    [$uid]
);

$scoreProntidao = 0;
if ($perfilPercentual >= 70) {
    $scoreProntidao += 25;
}
if ($temCurriculo) {
    $scoreProntidao += 25;
}
if ($kpis['inscricoes'] > 0) {
    $scoreProntidao += 25;
}
if ($kpis['candidaturas'] > 0) {
    $scoreProntidao += 25;
}

$statusProntidao = 'Inicio';
$classProntidao = 'bg-danger';
if ($scoreProntidao >= 75) {
    $statusProntidao = 'Avancado';
    $classProntidao = 'bg-success';
} elseif ($scoreProntidao >= 50) {
    $statusProntidao = 'Em evolucao';
    $classProntidao = 'bg-info';
} elseif ($scoreProntidao >= 25) {
    $statusProntidao = 'Basico';
    $classProntidao = 'bg-warning';
}

$acoes = [];
if ($perfilPercentual < 100) {
    $acoes[] = [
        'titulo' => 'Complete seu perfil',
        'descricao' => 'Faltam dados em: ' . implode(', ', $faltantes) . '.',
        'href' => 'meus-dados.php',
        'botao' => 'Atualizar perfil',
        'classe' => 'btn-outline-primary',
    ];
}
if (!$temCurriculo) {
    $acoes[] = [
        'titulo' => 'Monte seu curriculo',
        'descricao' => 'Preencha seu perfil profissional para fortalecer candidaturas.',
        'href' => 'meu-curriculo.php',
        'botao' => 'Criar curriculo',
        'classe' => 'btn-outline-secondary',
    ];
}
if ($kpis['inscricoes'] === 0) {
    $acoes[] = [
        'titulo' => 'Inicie um curso',
        'descricao' => 'Escolha uma trilha para acelerar sua preparacao.',
        'href' => 'cursos.php',
        'botao' => 'Explorar cursos',
        'classe' => 'btn-outline-success',
    ];
}
if ($kpis['candidaturas'] === 0) {
    $acoes[] = [
        'titulo' => 'Candidate-se a uma vaga',
        'descricao' => 'Aplique para oportunidades alinhadas ao seu perfil.',
        'href' => 'vagas.php',
        'botao' => 'Ver vagas',
        'classe' => 'btn-outline-info',
    ];
}
$acoes[] = [
    'titulo' => 'Use o Assistente IA',
    'descricao' => 'Gere um plano pratico de estudo e busca de vagas.',
    'href' => 'assistente.php',
    'botao' => 'Abrir assistente',
    'classe' => 'btn-outline-dark',
];

$statusCurso = [
    STATUS_INSC_PENDENTE   => 'Pendente',
    STATUS_INSC_CONFIRMADO => 'Confirmado',
    STATUS_INSC_CANCELADO  => 'Cancelado',
    STATUS_INSC_CONCLUIDO  => 'Concluido',
];
$badgeCurso = [
    STATUS_INSC_PENDENTE   => 'badge-warning',
    STATUS_INSC_CONFIRMADO => 'badge-success',
    STATUS_INSC_CANCELADO  => 'badge-secondary',
    STATUS_INSC_CONCLUIDO  => 'badge-primary',
];

$statusCand = [
    STATUS_CAND_ENVIADA   => 'Enviada',
    STATUS_CAND_ANALISE   => 'Em analise',
    STATUS_CAND_APROVADO  => 'Aprovado',
    STATUS_CAND_REPROVADO => 'Reprovado',
];
$badgeCand = [
    STATUS_CAND_ENVIADA   => 'badge-info',
    STATUS_CAND_ANALISE   => 'badge-warning',
    STATUS_CAND_APROVADO  => 'badge-success',
    STATUS_CAND_REPROVADO => 'badge-danger',
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel do Aluno - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <div class="hero-painel mb-4">
        <h1 class="h4 mb-1">Painel do aluno</h1>
        <p class="mb-0">Bem-vindo, <strong><?php echo htmlspecialchars($nomeUsuario); ?></strong>. Acompanhe seu progresso de carreira em um so lugar.</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="kpi-card p-3">
                <div class="text-muted small">Cursos inscritos</div>
                <div class="kpi-value text-primary"><?php echo (int) $kpis['inscricoes']; ?></div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="kpi-card p-3">
                <div class="text-muted small">Cursos concluidos</div>
                <div class="kpi-value text-success"><?php echo (int) $kpis['cursos_concluidos']; ?></div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="kpi-card p-3">
                <div class="text-muted small">Candidaturas</div>
                <div class="kpi-value text-info"><?php echo (int) $kpis['candidaturas']; ?></div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="kpi-card p-3">
                <div class="text-muted small">Aprovadas</div>
                <div class="kpi-value text-warning"><?php echo (int) $kpis['cand_aprovadas']; ?></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="panel-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h5 mb-0">Proximas acoes</h2>
                    <span class="badge badge-light border">Em analise: <?php echo (int) $kpis['cand_analise']; ?></span>
                </div>
                <?php foreach ($acoes as $acao): ?>
                    <div class="border rounded p-3 mb-2">
                        <div class="font-weight-bold"><?php echo htmlspecialchars($acao['titulo']); ?></div>
                        <div class="small text-muted mb-2"><?php echo htmlspecialchars($acao['descricao']); ?></div>
                        <a href="<?php echo htmlspecialchars($acao['href']); ?>" class="btn btn-sm <?php echo htmlspecialchars($acao['classe']); ?>">
                            <?php echo htmlspecialchars($acao['botao']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="panel-card p-3 h-100">
                <h3 class="h6 text-primary">Prontidao para vagas</h3>
                <div class="d-flex justify-content-between small mb-1">
                    <span><?php echo htmlspecialchars($statusProntidao); ?></span>
                    <span><?php echo (int) $scoreProntidao; ?>%</span>
                </div>
                <div class="progress mb-3" style="height: 10px;">
                    <div class="progress-bar <?php echo htmlspecialchars($classProntidao); ?>" role="progressbar" style="width: <?php echo (int) $scoreProntidao; ?>%;"></div>
                </div>
                <ul class="small text-muted mb-0 pl-3">
                    <li>Perfil completo: <?php echo (int) $perfilPercentual; ?>%</li>
                    <li>Curriculo: <?php echo $temCurriculo ? 'ok' : 'pendente'; ?></li>
                    <li>Cursos inscritos: <?php echo (int) $kpis['inscricoes']; ?></li>
                    <li>Candidaturas feitas: <?php echo (int) $kpis['candidaturas']; ?></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="panel-card">
                <div class="card-header bg-white"><strong><i class="fas fa-book-open text-primary"></i> Cursos recentes</strong></div>
                <div class="card-body p-0">
                    <?php if (count($inscricoesRecentes) === 0): ?>
                        <div class="p-3 text-muted">Nenhuma inscricao encontrada.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Curso</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inscricoesRecentes as $item): ?>
                                        <?php $st = (string) ($item['status'] ?? 'pendente'); ?>
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold"><?php echo htmlspecialchars((string) ($item['titulo'] ?? 'Curso')); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars((string) ($item['modalidade'] ?? '-')); ?> | <?php echo htmlspecialchars((string) ($item['nivel'] ?? '-')); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $badgeCurso[$st] ?? 'badge-secondary'; ?>">
                                                    <?php echo htmlspecialchars($statusCurso[$st] ?? $st); ?>
                                                </span>
                                            </td>
                                            <td><?php echo !empty($item['criado_em']) ? date('d/m/Y', strtotime((string) $item['criado_em'])) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-right">
                    <a href="meus-cursos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="panel-card">
                <div class="card-header bg-white"><strong><i class="fas fa-briefcase text-info"></i> Candidaturas recentes</strong></div>
                <div class="card-body p-0">
                    <?php if (count($candidaturasRecentes) === 0): ?>
                        <div class="p-3 text-muted">Nenhuma candidatura encontrada.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Vaga</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($candidaturasRecentes as $item): ?>
                                        <?php $st = (string) ($item['status'] ?? 'enviada'); ?>
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold"><?php echo htmlspecialchars((string) ($item['titulo'] ?? 'Vaga')); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars((string) ($item['empresa'] ?? 'Empresa nao informada')); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $badgeCand[$st] ?? 'badge-secondary'; ?>">
                                                    <?php echo htmlspecialchars($statusCand[$st] ?? $st); ?>
                                                </span>
                                            </td>
                                            <td><?php echo !empty($item['criado_em']) ? date('d/m/Y', strtotime((string) $item['criado_em'])) : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-white text-right">
                    <a href="minhas-candidaturas.php" class="btn btn-sm btn-outline-info">Ver todas</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

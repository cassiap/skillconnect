<?php
require_once __DIR__ . '/../config/db.php';

admin_check();

if (!function_exists('bind_params_dynamic')) {
    function bind_params_dynamic(mysqli_stmt $stmt, string $types, array &$params): void {
        if ($types === '') {
            return;
        }
        $refs = [];
        $refs[] = $types;
        foreach ($params as $k => &$v) {
            $refs[] = &$v;
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
}

$status_validos = ['enviada', 'em_analise', 'aprovado', 'reprovado'];
$queryStringAtual = $_SERVER['QUERY_STRING'] ?? '';
$redirectPosAcao = 'candidaturas.php' . ($queryStringAtual ? '?' . $queryStringAtual : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidatura_id'], $_POST['novo_status'])) {
    if (!csrf_validate()) {
        flash('error', 'Sessao expirada. Tente novamente.');
        redirect($redirectPosAcao);
    }

    $candidatura_id = (int) $_POST['candidatura_id'];
    $novo_status = trim($_POST['novo_status']);

    if (!in_array($novo_status, $status_validos, true)) {
        flash('error', 'Status invalido.');
        redirect($redirectPosAcao);
    }

    $stmtU = $cx->prepare("UPDATE candidaturas SET status = ?, atualizado_em = NOW() WHERE id = ?");
    $stmtU->bind_param("si", $novo_status, $candidatura_id);
    if ($stmtU->execute() && $stmtU->affected_rows >= 0) {
        flash('success', 'Status atualizado com sucesso.');
    } else {
        flash('error', 'Nao foi possivel atualizar o status.');
    }
    $stmtU->close();
    redirect($redirectPosAcao);
}

$f_status = trim($_GET['status'] ?? '');
$f_vaga = (int) ($_GET['vaga_id'] ?? 0);
$f_q = trim($_GET['q'] ?? '');
$f_de = trim($_GET['de'] ?? '');
$f_ate = trim($_GET['ate'] ?? '');

if (!in_array($f_status, $status_validos, true)) {
    $f_status = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_de)) {
    $f_de = '';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_ate)) {
    $f_ate = '';
}

$vagas = [];
$rv = $cx->query("SELECT id, titulo FROM vagas WHERE ativo = 1 ORDER BY titulo ASC");
while ($rv && $v = $rv->fetch_assoc()) {
    $vagas[] = $v;
}

$sql = "SELECT c.id, c.vaga_id, u.nome, u.email, v.titulo, c.curriculo_path, c.status, c.criado_em, c.atualizado_em
        FROM candidaturas c
        JOIN usuarios u ON c.usuario_id = u.id
        JOIN vagas v ON c.vaga_id = v.id
        WHERE 1=1";
$types = '';
$params = [];

if ($f_status !== '') {
    $sql .= " AND c.status = ?";
    $types .= 's';
    $params[] = $f_status;
}
if ($f_vaga > 0) {
    $sql .= " AND c.vaga_id = ?";
    $types .= 'i';
    $params[] = $f_vaga;
}
if ($f_q !== '') {
    $sql .= " AND (u.nome LIKE ? OR u.email LIKE ? OR v.titulo LIKE ?)";
    $types .= 'sss';
    $qLike = '%' . $f_q . '%';
    $params[] = $qLike;
    $params[] = $qLike;
    $params[] = $qLike;
}
if ($f_de !== '') {
    $sql .= " AND DATE(c.criado_em) >= ?";
    $types .= 's';
    $params[] = $f_de;
}
if ($f_ate !== '') {
    $sql .= " AND DATE(c.criado_em) <= ?";
    $types .= 's';
    $params[] = $f_ate;
}

$sql .= " ORDER BY c.criado_em DESC";
$stmt = $cx->prepare($sql);
bind_params_dynamic($stmt, $types, $params);
$stmt->execute();
$res = $stmt->get_result();

$candidaturas = [];
$totais = ['enviada' => 0, 'em_analise' => 0, 'aprovado' => 0, 'reprovado' => 0];

while ($row = $res->fetch_assoc()) {
    $candidaturas[] = $row;
    if (isset($totais[$row['status']])) {
        $totais[$row['status']]++;
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Candidaturas - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        .hero-admin {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 55%, #334155 100%);
            color: #fff;
            border-radius: 16px;
            padding: 24px;
        }
        .stats-card {
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            background: #fff;
        }
        .filters-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
        }
        .table thead th {
            white-space: nowrap;
        }
    </style>
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <div class="hero-admin mb-4">
        <h1 class="h4 mb-1">Gerenciamento de Candidaturas</h1>
        <p class="mb-0">Filtre, acompanhe e atualize o status dos candidatos em um unico painel.</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="stats-card p-3">
                <div class="text-muted small">Enviadas</div>
                <div class="h4 mb-0 text-info"><?php echo $totais['enviada']; ?></div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stats-card p-3">
                <div class="text-muted small">Em Analise</div>
                <div class="h4 mb-0 text-warning"><?php echo $totais['em_analise']; ?></div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stats-card p-3">
                <div class="text-muted small">Aprovadas</div>
                <div class="h4 mb-0 text-success"><?php echo $totais['aprovado']; ?></div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stats-card p-3">
                <div class="text-muted small">Reprovadas</div>
                <div class="h4 mb-0 text-danger"><?php echo $totais['reprovado']; ?></div>
            </div>
        </div>
    </div>

    <div class="filters-wrap p-3 mb-4">
        <form method="GET" class="row">
            <div class="col-md-3 mb-2">
                <label class="small text-muted mb-1">Busca</label>
                <input type="text" name="q" class="form-control" placeholder="Nome, email ou vaga" value="<?php echo htmlspecialchars($f_q); ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted mb-1">Status</label>
                <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <option value="enviada" <?php echo $f_status === 'enviada' ? 'selected' : ''; ?>>Enviada</option>
                    <option value="em_analise" <?php echo $f_status === 'em_analise' ? 'selected' : ''; ?>>Em Analise</option>
                    <option value="aprovado" <?php echo $f_status === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                    <option value="reprovado" <?php echo $f_status === 'reprovado' ? 'selected' : ''; ?>>Reprovado</option>
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="small text-muted mb-1">Vaga</label>
                <select name="vaga_id" class="form-control">
                    <option value="0">Todas</option>
                    <?php foreach ($vagas as $vaga): ?>
                        <option value="<?php echo (int) $vaga['id']; ?>" <?php echo $f_vaga === (int) $vaga['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vaga['titulo']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted mb-1">De</label>
                <input type="date" name="de" class="form-control" value="<?php echo htmlspecialchars($f_de); ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted mb-1">Ate</label>
                <input type="date" name="ate" class="form-control" value="<?php echo htmlspecialchars($f_ate); ?>">
            </div>
            <div class="col-md-12 d-flex mt-2">
                <button class="btn btn-primary mr-2" type="submit">Aplicar filtros</button>
                <a href="candidaturas.php" class="btn btn-outline-secondary">Limpar</a>
            </div>
        </form>
    </div>

    <?php if (count($candidaturas) === 0): ?>
        <div class="alert alert-info">Nenhuma candidatura encontrada com os filtros atuais.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white">
                <thead class="thead-light">
                    <tr>
                        <th>Candidato</th>
                        <th>Contato</th>
                        <th>Vaga</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Curriculo</th>
                        <th>Atualizar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidaturas as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['nome']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['titulo']); ?></td>
                            <td>
                                <?php
                                $badges = [
                                    'enviada' => 'badge-info',
                                    'em_analise' => 'badge-warning',
                                    'aprovado' => 'badge-success',
                                    'reprovado' => 'badge-danger',
                                ];
                                $classe = $badges[$row['status']] ?? 'badge-secondary';
                                ?>
                                <span class="badge <?php echo $classe; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($row['criado_em'])); ?></td>
                            <td>
                                <?php if (!empty($row['curriculo_path'])): ?>
                                    <a href="download_curriculo.php?id=<?php echo (int) $row['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file-pdf"></i> Abrir
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">Nao enviado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="candidatura_id" value="<?php echo (int) $row['id']; ?>">
                                    <select name="novo_status" class="form-control form-control-sm mr-1">
                                        <option value="enviada" <?php echo $row['status'] === 'enviada' ? 'selected' : ''; ?>>Enviada</option>
                                        <option value="em_analise" <?php echo $row['status'] === 'em_analise' ? 'selected' : ''; ?>>Em Analise</option>
                                        <option value="aprovado" <?php echo $row['status'] === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                                        <option value="reprovado" <?php echo $row['status'] === 'reprovado' ? 'selected' : ''; ?>>Reprovado</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

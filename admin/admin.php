<?php
require_once __DIR__ . '/../config/db.php';

admin_check();

if (!function_exists('admin_scalar')) {
    function admin_scalar(mysqli $cx, string $sql): int {
        $res = $cx->query($sql);
        if (!$res) {
            return 0;
        }
        $row = $res->fetch_row();
        $res->close();
        return (int) ($row[0] ?? 0);
    }
}

$kpis = [
    'alunos_ativos' => admin_scalar($cx, "SELECT COUNT(*) FROM usuarios WHERE perfil = 'usuario' AND ativo = 1"),
    'cursos_ativos' => admin_scalar($cx, "SELECT COUNT(*) FROM cursos WHERE ativo = 1"),
    'vagas_ativas' => admin_scalar($cx, "SELECT COUNT(*) FROM vagas WHERE ativo = 1"),
    'cand_em_analise' => admin_scalar($cx, "SELECT COUNT(*) FROM candidaturas WHERE status = 'em_analise'"),
];

$candidaturasRecentes = [];
$sqlRecentes = "
    SELECT c.id, c.status, c.criado_em,
           u.nome AS candidato, u.email,
           v.titulo AS vaga
    FROM candidaturas c
    LEFT JOIN usuarios u ON u.id = c.usuario_id
    LEFT JOIN vagas v ON v.id = c.vaga_id
    ORDER BY c.criado_em DESC
    LIMIT 8
";

if ($resRecentes = $cx->query($sqlRecentes)) {
    while ($row = $resRecentes->fetch_assoc()) {
        $candidaturasRecentes[] = $row;
    }
    $resRecentes->close();
}

$statusLabel = [
    'enviada' => 'Enviada',
    'em_analise' => 'Em analise',
    'aprovado' => 'Aprovado',
    'reprovado' => 'Reprovado',
];
$statusClass = [
    'enviada' => 'badge-info',
    'em_analise' => 'badge-warning',
    'aprovado' => 'badge-success',
    'reprovado' => 'badge-danger',
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Admin - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        .hero-admin {
            background: linear-gradient(125deg, #0f172a 0%, #1e293b 55%, #0f766e 100%);
            color: #fff;
            border-radius: 16px;
            padding: 24px;
        }
        .kpi-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            height: 100%;
        }
        .kpi-value {
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1.1;
        }
        .quick-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #fff;
            height: 100%;
        }
    </style>
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <div class="hero-admin mb-4">
        <h1 class="h4 mb-1">Painel Administrativo</h1>
        <p class="mb-0">Bem-vindo(a), <strong><?php echo htmlspecialchars($_SESSION['nome'] ?? 'Administrador'); ?></strong>. Visao geral da plataforma em tempo real.</p>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="kpi-card p-3">
                <div class="text-muted small">Alunos ativos</div>
                <div class="kpi-value text-primary"><?php echo (int) $kpis['alunos_ativos']; ?></div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="kpi-card p-3">
                <div class="text-muted small">Cursos ativos</div>
                <div class="kpi-value text-success"><?php echo (int) $kpis['cursos_ativos']; ?></div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="kpi-card p-3">
                <div class="text-muted small">Vagas ativas</div>
                <div class="kpi-value text-info"><?php echo (int) $kpis['vagas_ativas']; ?></div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="kpi-card p-3">
                <div class="text-muted small">Candidaturas em analise</div>
                <div class="kpi-value text-warning"><?php echo (int) $kpis['cand_em_analise']; ?></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="quick-card p-3 text-center">
                <i class="fas fa-book fa-2x text-warning mb-2"></i>
                <h6 class="mb-2">Cursos</h6>
                <a href="cadastracurso.php" class="btn btn-sm btn-outline-warning mr-1">Cadastrar</a>
                <a href="../user/cursos.php" class="btn btn-sm btn-outline-primary">Gerenciar</a>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="quick-card p-3 text-center">
                <i class="fas fa-briefcase fa-2x text-success mb-2"></i>
                <h6 class="mb-2">Vagas</h6>
                <a href="cadastravaga.php" class="btn btn-sm btn-outline-success mr-1">Cadastrar</a>
                <a href="../user/vagas.php" class="btn btn-sm btn-outline-primary">Gerenciar</a>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="quick-card p-3 text-center">
                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                <h6 class="mb-2">Candidaturas</h6>
                <a href="candidaturas.php" class="btn btn-sm btn-outline-primary">Abrir painel</a>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="quick-card p-3 text-center">
                <i class="fas fa-user-friends fa-2x text-secondary mb-2"></i>
                <h6 class="mb-2">Usuarios</h6>
                <a href="listarclientes.php" class="btn btn-sm btn-outline-secondary">Listar usuarios</a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <strong><i class="fas fa-history text-primary"></i> Candidaturas recentes</strong>
        </div>
        <div class="card-body p-0">
            <?php if (count($candidaturasRecentes) === 0): ?>
                <div class="p-3 text-muted">Nenhuma candidatura registrada ainda.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="min-width: 170px;">Candidato</th>
                                <th>Contato</th>
                                <th>Vaga</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($candidaturasRecentes as $item): ?>
                                <?php $st = (string) ($item['status'] ?? 'enviada'); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) ($item['candidato'] ?? 'Nao informado')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($item['email'] ?? '-')); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($item['vaga'] ?? 'Vaga removida')); ?></td>
                                    <td>
                                        <span class="badge <?php echo $statusClass[$st] ?? 'badge-secondary'; ?>">
                                            <?php echo htmlspecialchars($statusLabel[$st] ?? $st); ?>
                                        </span>
                                    </td>
                                    <td><?php echo !empty($item['criado_em']) ? date('d/m/Y H:i', strtotime((string) $item['criado_em'])) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

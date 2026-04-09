<?php
/**
 * Página de Minhas Candidaturas
 * 
 * Esta página exibe todas as candidaturas do usuário logado,
 * mostrando o status de cada candidatura e informações das vagas.
 * 
 * @author Sistema SkillConnect
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
$candidaturas = [];

$stmt = $cx->prepare(
    "SELECT c.id, c.status, c.criado_em, c.atualizado_em, c.curriculo_path,
            v.id AS vaga_id, v.titulo, v.empresa, v.cidade, v.estado
     FROM candidaturas c
     INNER JOIN vagas v ON v.id = c.vaga_id
     WHERE c.usuario_id = ?
     ORDER BY c.criado_em DESC"
);
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $candidaturas[] = $row;
}
$stmt->close();

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
    <title>Minhas Vagas - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        .hero {
            border-radius: 14px;
            background: linear-gradient(120deg, #0e7490 0%, #1d4ed8 100%);
            color: #fff;
            padding: 22px;
        }
    </style>
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <div class="hero mb-4">
        <h1 class="h4 mb-1">Minhas vagas</h1>
        <p class="mb-0">Veja o andamento das vagas em que voce se candidatou.</p>
    </div>

    <?php if (count($candidaturas) === 0): ?>
        <div class="card shadow-sm">
            <div class="card-body">
                <p class="mb-3">Voce ainda nao se candidatou a nenhuma vaga.</p>
                <a href="vagas.php" class="btn btn-primary"><i class="fas fa-briefcase"></i> Ver vagas</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Vaga</th>
                                <th>Empresa</th>
                                <th>Local</th>
                                <th>Status</th>
                                <th>Enviado em</th>
                                <th>Curriculo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($candidaturas as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c['titulo']); ?></td>
                                <td><?php echo htmlspecialchars($c['empresa'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars(trim(($c['cidade'] ?? '') . ' / ' . ($c['estado'] ?? ''), ' /')); ?></td>
                                <td>
                                    <?php $st = $c['status']; ?>
                                    <span class="badge <?php echo $statusClass[$st] ?? 'badge-secondary'; ?>">
                                        <?php echo htmlspecialchars($statusLabel[$st] ?? $st); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($c['criado_em'])); ?></td>
                                <td>
                                    <?php if (!empty($c['curriculo_path'])): ?>
                                        <a class="btn btn-sm btn-outline-secondary" href="download_curriculo.php?id=<?php echo (int) $c['id']; ?>">
                                            <i class="fas fa-file-pdf"></i> Abrir
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right">
                                    <a class="btn btn-sm btn-outline-primary" href="vaga.php?id=<?php echo (int) $c['vaga_id']; ?>">Ver vaga</a>
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
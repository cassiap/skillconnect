<?php
require_once __DIR__ . '/../config/db.php';

$q = trim($_GET['q'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$modalidade = trim($_GET['modalidade'] ?? '');
$cidade = trim($_GET['cidade'] ?? '');
$ord = trim($_GET['ord'] ?? 'recentes');

$ordensPermitidas = [
    'recentes' => 'v.id DESC',
    'titulo' => 'v.titulo ASC',
    'empresa' => 'v.empresa ASC',
    'cidade' => 'v.cidade ASC',
];
$orderBy = $ordensPermitidas[$ord] ?? $ordensPermitidas['recentes'];

$qLike = '%' . $q . '%';
$cidadeLike = '%' . $cidade . '%';

$sql = "SELECT v.id, v.titulo, v.empresa, v.tipo, v.modalidade, v.salario, v.cidade, v.estado, v.descricao, v.requisitos
        FROM vagas v
        WHERE v.ativo = 1
          AND (? = '' OR v.titulo LIKE ? OR v.empresa LIKE ? OR v.descricao LIKE ? OR v.requisitos LIKE ?)
          AND (? = '' OR v.tipo = ?)
          AND (? = '' OR v.modalidade = ?)
          AND (? = '' OR v.cidade LIKE ?)
        ORDER BY {$orderBy}";

$stmt = $cx->prepare($sql);
$stmt->bind_param("sssssssssss", $q, $qLike, $qLike, $qLike, $qLike, $tipo, $tipo, $modalidade, $modalidade, $cidade, $cidadeLike);
$stmt->execute();
$resultado = $stmt->get_result();

$vagas = [];
while ($row = $resultado->fetch_assoc()) {
    $vagas[] = $row;
}
$stmt->close();

$tipos = [];
$r1 = $cx->query("SELECT DISTINCT tipo FROM vagas WHERE ativo = 1 AND tipo IS NOT NULL AND tipo <> '' ORDER BY tipo ASC");
while ($r1 && $t = $r1->fetch_assoc()) {
    $tipos[] = $t['tipo'];
}

$modalidades = [];
$r2 = $cx->query("SELECT DISTINCT modalidade FROM vagas WHERE ativo = 1 AND modalidade IS NOT NULL AND modalidade <> '' ORDER BY modalidade ASC");
while ($r2 && $m = $r2->fetch_assoc()) {
    $modalidades[] = $m['modalidade'];
}

function resumo_vaga(string $texto, int $limite = 125): string {
    $texto = trim(strip_tags($texto));
    if ($texto === '') {
        return 'Descricao em breve.';
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }
        return rtrim(mb_substr($texto, 0, $limite - 3)) . '...';
    }
    if (strlen($texto) <= $limite) {
        return $texto;
    }
    return rtrim(substr($texto, 0, $limite - 3)) . '...';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vagas de Emprego - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        .hero-vagas {
            background: linear-gradient(135deg, #164e63 0%, #0e7490 58%, #22d3ee 100%);
            color: #fff;
            border-radius: 18px;
            padding: 28px;
        }
        .filtro-box {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
        }
        .vaga-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            height: 100%;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .vaga-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(14, 116, 144, .15);
        }
        .chip {
            border: 1px solid #99f6e4;
            color: #134e4a;
            background: #f0fdfa;
            border-radius: 999px;
            font-size: 11px;
            padding: 4px 9px;
            margin-right: 6px;
        }
    </style>
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <div class="hero-vagas mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
            <div>
                <h1 class="h3 mb-1">Vagas de Emprego</h1>
                <p class="mb-0">Filtre oportunidades por perfil e encontre sua proxima etapa profissional.</p>
            </div>
            <?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin'): ?>
                <a href="../admin/cadastravaga.php" class="btn btn-light mt-3 mt-lg-0">
                    <i class="fas fa-plus"></i> Nova Vaga
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="filtro-box p-3 mb-4">
        <form method="GET" class="row">
            <div class="col-md-3 mb-2">
                <label class="small text-muted mb-1">Busca</label>
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Titulo, empresa ou requisito">
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted mb-1">Tipo</label>
                <select name="tipo" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $tipo === $t ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted mb-1">Modalidade</label>
                <select name="modalidade" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($modalidades as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>" <?php echo $modalidade === $m ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($m)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted mb-1">Cidade</label>
                <input type="text" name="cidade" class="form-control" value="<?php echo htmlspecialchars($cidade); ?>" placeholder="Ex.: Recife">
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted mb-1">Ordenar</label>
                <select name="ord" class="form-control">
                    <option value="recentes" <?php echo $ord === 'recentes' ? 'selected' : ''; ?>>Mais recentes</option>
                    <option value="titulo" <?php echo $ord === 'titulo' ? 'selected' : ''; ?>>Titulo A-Z</option>
                    <option value="empresa" <?php echo $ord === 'empresa' ? 'selected' : ''; ?>>Empresa A-Z</option>
                    <option value="cidade" <?php echo $ord === 'cidade' ? 'selected' : ''; ?>>Cidade A-Z</option>
                </select>
            </div>
            <div class="col-md-1 mb-2 d-flex align-items-end">
                <button class="btn btn-primary btn-block" type="submit">OK</button>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 text-primary mb-0">Resultados</h2>
        <span class="text-muted small"><?php echo count($vagas); ?> vaga(s) encontrada(s)</span>
    </div>

    <?php if (count($vagas) === 0): ?>
        <div class="alert alert-info">
            Nenhuma vaga encontrada com os filtros atuais.
            <a href="vagas.php" class="alert-link">Limpar filtros</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($vagas as $vaga): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card vaga-card">
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="chip"><?php echo htmlspecialchars($vaga['tipo'] ?: 'Tipo livre'); ?></span>
                                <span class="chip"><?php echo htmlspecialchars(ucfirst($vaga['modalidade'] ?: 'geral')); ?></span>
                            </div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($vaga['titulo']); ?></h5>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($vaga['empresa'] ?: 'Empresa nao informada'); ?></p>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars(resumo_vaga((string) ($vaga['descricao'] ?? ''))); ?></p>
                            <div class="small text-muted mb-3">
                                <div><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(trim(($vaga['cidade'] ?? '') . ' / ' . ($vaga['estado'] ?? ''), ' /')); ?></div>
                                <div><i class="fas fa-dollar-sign"></i> <?php echo htmlspecialchars($vaga['salario'] ?: 'Salario a combinar'); ?></div>
                            </div>
                            <a href="vaga.php?id=<?php echo (int) $vaga['id']; ?>" class="btn btn-outline-primary mt-auto">
                                Ver detalhes
                            </a>
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

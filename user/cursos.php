<?php
/**
 * Página de listagem e busca de cursos profissionalizantes
 * 
 * Este arquivo exibe uma página com filtros de busca, ordenação e listagem
 * dos cursos ativos cadastrados no sistema. Permite filtrar por modalidade,
 * nível e busca textual, além de diferentes opções de ordenação.
 * 
 * @author Sistema SkillConnect
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';

$q = trim($_GET['q'] ?? '');
$modalidade = trim($_GET['modalidade'] ?? '');
$nivel = trim($_GET['nivel'] ?? '');
$ord = trim($_GET['ord'] ?? 'recentes');

$ordensPermitidas = [
    'recentes' => 'c.id DESC',
    'titulo' => 'c.titulo ASC',
    'carga' => 'c.carga_horaria DESC',
    'preco_asc' => 'c.preco ASC',
    'preco_desc' => 'c.preco DESC',
];
$orderBy = $ordensPermitidas[$ord] ?? $ordensPermitidas['recentes'];

$qLike = '%' . $q . '%';

$sql = "SELECT c.id, c.titulo, c.descricao, c.carga_horaria, c.modalidade, c.nivel, c.preco, c.vagas
        FROM cursos c
        WHERE c.ativo = 1
          AND (? = '' OR c.titulo LIKE ? OR c.descricao LIKE ?)
          AND (? = '' OR c.modalidade = ?)
          AND (? = '' OR c.nivel = ?)
        ORDER BY {$orderBy}";

$stmt = $cx->prepare($sql);
$stmt->bind_param("sssssss", $q, $qLike, $qLike, $modalidade, $modalidade, $nivel, $nivel);
$stmt->execute();
$resultado = $stmt->get_result();

$cursos = [];
while ($row = $resultado->fetch_assoc()) {
    $cursos[] = $row;
}
$stmt->close();

$modalidades = [];
$r1 = $cx->query("SELECT DISTINCT modalidade FROM cursos WHERE ativo = 1 AND modalidade IS NOT NULL AND modalidade <> '' ORDER BY modalidade ASC");
while ($r1 && $m = $r1->fetch_assoc()) {
    $modalidades[] = $m['modalidade'];
}

$niveis = [];
$r2 = $cx->query("SELECT DISTINCT nivel FROM cursos WHERE ativo = 1 AND nivel IS NOT NULL AND nivel <> '' ORDER BY nivel ASC");
while ($r2 && $n = $r2->fetch_assoc()) {
    $niveis[] = $n['nivel'];
}

/**
 * Cria um resumo truncado de um texto para exibição em cards de curso
 * 
 * Remove tags HTML, limita o comprimento do texto e adiciona reticências
 * quando necessário. Utiliza funções multibyte quando disponíveis.
 * 
 * @param string $texto O texto original a ser resumido
 * @param int $limite Número máximo de caracteres para o resumo (padrão: 135)
 * 
 * @return string O texto resumido com reticências se truncado, ou mensagem padrão se vazio
 */
function resumo_curso(string $texto, int $limite = 135): string {
    $texto = trim(strip_tags($texto));
    if ($texto === '') {
        return 'Detalhes em breve.';
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
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Cursos Disponiveis - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        .hero-cursos {
            background: linear-gradient(135deg, #0f766e 0%, #0ea5a4 60%, #67e8f9 100%);
            color: #fff;
            border-radius: 18px;
            padding: 28px;
        }
        .filtros-box {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
        }
        .curso-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            height: 100%;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .curso-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(2, 132, 199, .12);
        }
        .badge-soft {
            background: #ecfeff;
            color: #155e75;
            border: 1px solid #bae6fd;
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
    <div class="hero-cursos mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
            <div>
                <h1 class="h3 mb-1">Cursos Profissionalizantes</h1>
                <p class="mb-0">Explore trilhas praticas para acelerar sua carreira.</p>
            </div>
            <?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin'): ?>
                <a href="../admin/cadastracurso.php" class="btn btn-light mt-3 mt-lg-0">
                    <i class="fas fa-plus"></i> Novo Curso
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="filtros-box p-3 mb-4">
        <form method="GET" class="row">
            <div class="col-md-4 mb-2">
                <label class="small text-muted mb-1">Busca</label>
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Titulo ou descricao">
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
                <label class="small text-muted mb-1">Nivel</label>
                <select name="nivel" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($niveis as $n): ?>
                        <option value="<?php echo htmlspecialchars($n); ?>" <?php echo $nivel === $n ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars(ucfirst($n)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="small text-muted mb-1">Ordenar</label>
                <select name="ord" class="form-control">
                    <option value="recentes" <?php echo $ord === 'recentes' ? 'selected' : ''; ?>>Mais recentes</option>
                    <option value="titulo" <?php echo $ord === 'titulo' ? 'selected' : ''; ?>>Titulo A-Z</option>
                    <option value="carga" <?php echo $ord === 'carga' ? 'selected' : ''; ?>>Maior carga</option>
                    <option value="preco_asc" <?php echo $ord === 'preco_asc' ? 'selected' : ''; ?>>Preco crescente</option>
                    <option value="preco_desc" <?php echo $ord === 'preco_desc' ? 'selected' : ''; ?>>Preco decrescente</option>
                </select>
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end">
                <button class="btn btn-primary btn-block" type="submit">Filtrar</button>
            </div>
        </form>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 text-primary mb-0">Resultados</h2>
        <span class="text-muted small"><?php echo count($cursos); ?> curso(s) encontrado(s)</span>
    </div>

    <?php if (count($cursos) === 0): ?>
        <div class="alert alert-info">
            Nenhum curso encontrado com os filtros atuais.
            <a href="cursos.php" class="alert-link">Limpar filtros</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($cursos as $curso): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card curso-card">
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="badge-soft"><?php echo htmlspecialchars(ucfirst($curso['modalidade'] ?: 'geral')); ?></span>
                                <span class="badge-soft"><?php echo htmlspecialchars(ucfirst($curso['nivel'] ?: 'nivel livre')); ?></span>
                            </div>
                            <h5 class="text-dark"><?php echo htmlspecialchars($curso['titulo']); ?></h5>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars(resumo_curso((string) ($curso['descricao'] ?? ''))); ?></p>
                            <div class="small text-muted mb-3">
                                <div><i class="far fa-clock"></i> <?php echo $curso['carga_horaria'] ? (int) $curso['carga_horaria'] . 'h' : 'Carga nao informada'; ?></div>
                                <div><i class="fas fa-users"></i> <?php echo ((int) $curso['vagas'] > 0) ? (int) $curso['vagas'] . ' vagas' : 'Vagas ilimitadas'; ?></div>
                                <div><i class="fas fa-dollar-sign"></i> <?php echo ((float) $curso['preco'] > 0) ? 'R$ ' . number_format((float) $curso['preco'], 2, ',', '.') : 'Gratuito'; ?></div>
                            </div>
                            <a href="curso.php?id=<?php echo (int) $curso['id']; ?>" class="btn btn-outline-primary mt-auto">
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
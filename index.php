<?php
require_once __DIR__ . '/config/db.php';

$cursosDestaque = [];
$rc = $cx->query("SELECT id, titulo, descricao, modalidade, nivel FROM cursos WHERE ativo = 1 ORDER BY id DESC LIMIT 3");
while ($rc && $c = $rc->fetch_assoc()) {
    $cursosDestaque[] = $c;
}

$vagasDestaque = [];
$rv = $cx->query("SELECT id, titulo, empresa, cidade, estado, tipo, modalidade FROM vagas WHERE ativo = 1 ORDER BY id DESC LIMIT 3");
while ($rv && $v = $rv->fetch_assoc()) {
    $vagasDestaque[] = $v;
}

function resumo_home(string $texto, int $limite = 110): string {
    $texto = trim(strip_tags($texto));
    if ($texto === '') {
        return 'Veja os detalhes para saber mais.';
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
    <title>SkillConnect - Cursos, Vagas e Assistente IA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        .hero-home {
            border-radius: 22px;
            background: linear-gradient(130deg, #1d4ed8 0%, #0369a1 52%, #0f766e 100%);
            color: #fff;
            padding: 42px 34px;
        }
        .soft-card {
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            height: 100%;
            transition: transform .15s ease, box-shadow .15s ease;
            background: #fff;
        }
        .soft-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(30, 64, 175, .12);
        }
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .feature-card {
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #f8fafc;
            height: 100%;
        }
    </style>
</head>
<body class="bg-light">

<?php include('includes/header.php'); ?>

<div class="container py-4">
    <section class="hero-home mb-4">
        <h1 class="display-5 font-weight-bold">Conecte formação profissional a oportunidades reais</h1>
        <p class="lead mb-4">Explore cursos, acompanhe vagas e use o Assistente IA para montar seu proximo passo de carreira.</p>
        <div class="d-flex flex-wrap">
            <a href="user/cursos.php" class="btn btn-light mr-2 mb-2"><i class="fas fa-book"></i> Ver cursos</a>
            <a href="user/vagas.php" class="btn btn-outline-light mr-2 mb-2"><i class="fas fa-briefcase"></i> Ver vagas</a>
            <a href="user/assistente.php" class="btn btn-warning mb-2"><i class="fas fa-robot"></i> Abrir assistente IA</a>
        </div>
    </section>

    <section class="mb-5">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="feature-card p-3">
                    <h3 class="h6 text-primary"><i class="fas fa-route"></i> Plano de carreira</h3>
                    <p class="small text-muted mb-0">Defina uma trilha de estudo com metas semanais e foco em empregabilidade.</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="feature-card p-3">
                    <h3 class="h6 text-primary"><i class="fas fa-laptop-code"></i> Cursos práticos</h3>
                    <p class="small text-muted mb-0">Aprenda habilidades aplicaveis para disputar vagas com mais preparo.</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="feature-card p-3">
                    <h3 class="h6 text-primary"><i class="fas fa-briefcase"></i> Oportunidades</h3>
                    <p class="small text-muted mb-0">Acesse vagas e candidate-se com curriculo em PDF direto pela plataforma.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mb-5">
        <div class="section-title">
            <h2 class="h4 text-primary mb-0">Cursos em destaque</h2>
            <a href="user/cursos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
        </div>
        <div class="row">
            <?php if (count($cursosDestaque) === 0): ?>
                <div class="col-12"><div class="alert alert-info mb-0">Nenhum curso ativo no momento.</div></div>
            <?php else: ?>
                <?php foreach ($cursosDestaque as $curso): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card soft-card">
                            <div class="card-body d-flex flex-column">
                                <div class="small text-muted mb-2">
                                    <?php echo htmlspecialchars(ucfirst($curso['modalidade'] ?? '')); ?>
                                    <?php if (!empty($curso['nivel'])): ?> - <?php echo htmlspecialchars(ucfirst($curso['nivel'])); ?><?php endif; ?>
                                </div>
                                <h5 class="mb-2"><?php echo htmlspecialchars($curso['titulo']); ?></h5>
                                <p class="text-muted small mb-3"><?php echo htmlspecialchars(resumo_home((string) ($curso['descricao'] ?? ''))); ?></p>
                                <a href="user/curso.php?id=<?php echo (int) $curso['id']; ?>" class="btn btn-outline-primary mt-auto">Ver detalhes</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="mb-5">
        <div class="section-title">
            <h2 class="h4 text-success mb-0">Vagas em destaque</h2>
            <a href="user/vagas.php" class="btn btn-sm btn-outline-success">Ver todas</a>
        </div>
        <div class="row">
            <?php if (count($vagasDestaque) === 0): ?>
                <div class="col-12"><div class="alert alert-info mb-0">Nenhuma vaga ativa no momento.</div></div>
            <?php else: ?>
                <?php foreach ($vagasDestaque as $vaga): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card soft-card">
                            <div class="card-body d-flex flex-column">
                                <div class="small text-muted mb-2">
                                    <?php echo htmlspecialchars($vaga['tipo'] ?? ''); ?>
                                    <?php if (!empty($vaga['modalidade'])): ?> - <?php echo htmlspecialchars(ucfirst($vaga['modalidade'])); ?><?php endif; ?>
                                </div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($vaga['titulo']); ?></h5>
                                <p class="text-muted mb-2"><?php echo htmlspecialchars($vaga['empresa'] ?? 'Empresa nao informada'); ?></p>
                                <p class="small text-muted mb-3"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(trim(($vaga['cidade'] ?? '') . ' / ' . ($vaga['estado'] ?? ''), ' /')); ?></p>
                                <a href="user/vaga.php?id=<?php echo (int) $vaga['id']; ?>" class="btn btn-outline-success mt-auto">Ver detalhes</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="card soft-card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="h5 text-dark">Assistente IA para carreira e empregabilidade</h3>
                    <p class="text-muted mb-0">Receba um plano pratico para estudar melhor, melhorar curriculo e buscar vagas alinhadas ao seu perfil.</p>
                </div>
                <div class="col-lg-4 text-lg-right mt-3 mt-lg-0">
                    <a href="user/assistente.php" class="btn btn-primary"><i class="fas fa-robot"></i> Usar agora</a>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include('includes/footer.php'); ?>

</body>
</html>

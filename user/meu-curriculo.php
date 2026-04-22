<?php
/**
 * Página de gerenciamento de currículo profissional
 * 
 * Este arquivo permite que usuários autenticados criem e editem seu currículo profissional,
 * visualizem um preview das informações e consultem o histórico de currículos enviados
 * em candidaturas para vagas.
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

if (($_SESSION['perfil'] ?? '') === 'admin') {
    flash('info', 'Area exclusiva para alunos.');
    redirect(app_url('admin/admin.php'));
}

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);

$curriculo = [
    'titulo_profissional' => '',
    'resumo' => '',
    'habilidades' => '',
    'experiencias' => '',
    'formacao' => '',
    'links' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        flash('error', 'Sessao expirada. Tente novamente.');
        redirect('meu-curriculo.php');
    }

    $curriculo['titulo_profissional'] = trim((string) ($_POST['titulo_profissional'] ?? ''));
    $curriculo['resumo'] = trim((string) ($_POST['resumo'] ?? ''));
    $curriculo['habilidades'] = trim((string) ($_POST['habilidades'] ?? ''));
    $curriculo['experiencias'] = trim((string) ($_POST['experiencias'] ?? ''));
    $curriculo['formacao'] = trim((string) ($_POST['formacao'] ?? ''));
    $curriculo['links'] = trim((string) ($_POST['links'] ?? ''));

    $sql = "
        INSERT INTO curriculos (usuario_id, titulo_profissional, resumo, habilidades, experiencias, formacao, links)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            titulo_profissional = VALUES(titulo_profissional),
            resumo = VALUES(resumo),
            habilidades = VALUES(habilidades),
            experiencias = VALUES(experiencias),
            formacao = VALUES(formacao),
            links = VALUES(links)
    ";
    $stmt = $cx->prepare($sql);
    $stmt->bind_param(
        "issssss",
        $usuarioId,
        $curriculo['titulo_profissional'],
        $curriculo['resumo'],
        $curriculo['habilidades'],
        $curriculo['experiencias'],
        $curriculo['formacao'],
        $curriculo['links']
    );
    $stmt->execute();
    $stmt->close();

    flash('success', 'Currículo profissional atualizado.');
    redirect('meu-curriculo.php');
}

// Carrega currículo salvo.
$loadStmt = $cx->prepare(
    "SELECT titulo_profissional, resumo, habilidades, experiencias, formacao, links
     FROM curriculos
     WHERE usuario_id = ?
     LIMIT 1"
);
$loadStmt->bind_param("i", $usuarioId);
$loadStmt->execute();
$row = $loadStmt->get_result()->fetch_assoc();
$loadStmt->close();

if ($row) {
    foreach ($curriculo as $k => $_v) {
        $curriculo[$k] = (string) ($row[$k] ?? '');
    }
}

// PDFs enviados em candidaturas.
$arquivos = [];
$fileStmt = $cx->prepare(
    "SELECT c.id, c.curriculo_path, c.status, c.criado_em,
            v.id AS vaga_id, v.titulo, v.empresa
     FROM candidaturas c
     INNER JOIN vagas v ON v.id = c.vaga_id
     WHERE c.usuario_id = ? AND c.curriculo_path IS NOT NULL AND c.curriculo_path <> ''
     ORDER BY c.criado_em DESC"
);
$fileStmt->bind_param("i", $usuarioId);
$fileStmt->execute();
$res = $fileStmt->get_result();
while ($r = $res->fetch_assoc()) {
    $arquivos[] = $r;
}
$fileStmt->close();

$statusLabel = [
    'enviada' => 'Enviada',
    'em_analise' => 'Em analise',
    'aprovado' => 'Aprovado',
    'reprovado' => 'Reprovado',
];

/**
 * Divide um texto em linhas não vazias
 * 
 * @param string $text O texto a ser dividido
 * @return array Array com as linhas não vazias após remoção de espaços
 */
function split_lines(string $text): array {
    $lines = preg_split('/\r\n|\r|\n/', $text);
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meu Currículo - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        .hero {
            border-radius: 14px;
            background: linear-gradient(120deg, #334155 0%, #0f766e 100%);
            color: #fff;
            padding: 22px;
        }
        .card-block {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
        }
        .file-name {
            font-family: Consolas, monospace;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <div class="hero mb-4">
        <h1 class="h4 mb-1">Meu currículo profissional</h1>
        <p class="mb-0">Mantenha seu perfil atualizado para melhorar sua candidatura nas vagas.</p>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card-block p-3 p-md-4">
                <h2 class="h5 mb-3">Editar perfil de currículo</h2>
                <form method="POST">
                    <?php echo csrf_field(); ?>

                    <div class="form-group">
                        <label for="titulo_profissional">Título profissional</label>
                        <input
                            type="text"
                            id="titulo_profissional"
                            name="titulo_profissional"
                            class="form-control"
                            placeholder="Ex.: Analista Administrativo Junior"
                            value="<?php echo htmlspecialchars($curriculo['titulo_profissional']); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="resumo">Resumo profissional</label>
                        <textarea id="resumo" name="resumo" class="form-control" rows="4" placeholder="Resumo objetivo do seu perfil"><?php echo htmlspecialchars($curriculo['resumo']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="habilidades">Habilidades (uma por linha)</label>
                        <textarea id="habilidades" name="habilidades" class="form-control" rows="4" placeholder="Ex.: Excel avancado&#10;Comunicacao&#10;Power BI"><?php echo htmlspecialchars($curriculo['habilidades']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="experiencias">Experiências (uma por linha)</label>
                        <textarea id="experiencias" name="experiencias" class="form-control" rows="4" placeholder="Ex.: Estagio em atendimento - Empresa X (2024)"><?php echo htmlspecialchars($curriculo['experiencias']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="formacao">Formação (uma por linha)</label>
                        <textarea id="formacao" name="formacao" class="form-control" rows="4" placeholder="Ex.: Tecnologo em Design Grafico - 2026"><?php echo htmlspecialchars($curriculo['formacao']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="links">Links relevantes (uma URL por linha)</label>
                        <textarea id="links" name="links" class="form-control" rows="3" placeholder="https://linkedin.com/in/seuperfil"><?php echo htmlspecialchars($curriculo['links']); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Salvar currículo</button>
                </form>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card-block p-3 p-md-4 mb-3">
                <h3 class="h6 text-primary mb-3">Preview rápido</h3>
                <p class="font-weight-bold mb-1"><?php echo htmlspecialchars($curriculo['titulo_profissional'] ?: ($_SESSION['nome'] ?? 'Seu nome')); ?></p>
                <p class="small text-muted"><?php echo nl2br(htmlspecialchars($curriculo['resumo'] ?: 'Adicione um resumo profissional para destacar seus objetivos e experiência.')); ?></p>

                <hr>
                <p class="small font-weight-bold mb-1">Habilidades</p>
                <ul class="small mb-2">
                    <?php foreach (split_lines($curriculo['habilidades']) as $line): ?>
                        <li><?php echo htmlspecialchars($line); ?></li>
                    <?php endforeach; ?>
                    <?php if (trim($curriculo['habilidades']) === ''): ?>
                        <li class="text-muted">Nenhuma habilidade cadastrada.</li>
                    <?php endif; ?>
                </ul>

                <p class="small font-weight-bold mb-1">Experiências</p>
                <ul class="small mb-2">
                    <?php foreach (split_lines($curriculo['experiencias']) as $line): ?>
                        <li><?php echo htmlspecialchars($line); ?></li>
                    <?php endforeach; ?>
                    <?php if (trim($curriculo['experiencias']) === ''): ?>
                        <li class="text-muted">Nenhuma experiência cadastrada.</li>
                    <?php endif; ?>
                </ul>

                <p class="small font-weight-bold mb-1">Formação</p>
                <ul class="small mb-0">
                    <?php foreach (split_lines($curriculo['formacao']) as $line): ?>
                        <li><?php echo htmlspecialchars($line); ?></li>
                    <?php endforeach; ?>
                    <?php if (trim($curriculo['formacao']) === ''): ?>
                        <li class="text-muted">Nenhuma formação cadastrada.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="card-block p-3 p-md-4">
        <h3 class="h5 mb-3">Histórico de currículos enviados</h3>
        <?php if (count($arquivos) === 0): ?>
            <p class="mb-0">Você ainda não enviou currículo em candidaturas.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Vaga</th>
                            <th>Empresa</th>
                            <th>Status</th>
                            <th>Enviado em</th>
                            <th>Arquivo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($arquivos as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['titulo']); ?></td>
                            <td><?php echo htmlspecialchars($a['empresa'] ?: '-'); ?></td>
                            <td><?php echo htmlspecialchars($statusLabel[$a['status']] ?? $a['status']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($a['criado_em'])); ?></td>
                            <td><span class="file-name"><?php echo htmlspecialchars($a['curriculo_path']); ?></span></td>
                            <td class="text-right">
                                <a class="btn btn-sm btn-outline-primary" href="download_curriculo.php?id=<?php echo (int) $a['id']; ?>">
                                    <i class="fas fa-download"></i> Baixar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
</body>
</html>

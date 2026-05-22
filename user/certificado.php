<?php
/**
 * Certificado de conclusão de curso
 *
 * Renderiza um certificado HTML estilizado para cursos concluídos a 100%.
 * O aluno utiliza a função de impressão do navegador (Ctrl+P) para salvar em PDF.
 * Redireciona com mensagem de erro se o curso não estiver concluído.
 *
 * @package SkillConnect
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

$cursoId   = (int) ($_GET['curso_id'] ?? 0);
$usuarioId = (int) ($_SESSION['user_id'] ?? 0);

if ($cursoId <= 0) {
    flash('error', 'Curso invalido.');
    redirect('meus-cursos.php');
}

// Verifica inscrição e calcula progresso
$stmt = $cx->prepare("
    SELECT c.titulo, c.carga_horaria, ic.criado_em,
           COALESCE(a.total, 0) AS total_aulas,
           COALESCE(p.concluidas, 0) AS concluidas
    FROM inscricoes_cursos ic
    INNER JOIN cursos c ON c.id = ic.curso_id
    LEFT JOIN (
        SELECT m.curso_id, COUNT(al.id) AS total
        FROM modulos m
        LEFT JOIN aulas al ON al.modulo_id = m.id AND al.ativo = 1
        WHERE m.ativo = 1
        GROUP BY m.curso_id
    ) a ON a.curso_id = c.id
    LEFT JOIN (
        SELECT m.curso_id, COUNT(pa.id) AS concluidas
        FROM progresso_aulas pa
        INNER JOIN aulas al ON al.id = pa.aula_id
        INNER JOIN modulos m ON m.id = al.modulo_id
        WHERE pa.usuario_id = ?
        GROUP BY m.curso_id
    ) p ON p.curso_id = c.id
    WHERE ic.usuario_id = ? AND ic.curso_id = ?
    LIMIT 1
");
$stmt->bind_param("iii", $usuarioId, $usuarioId, $cursoId);
$stmt->execute();
$dados = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dados) {
    flash('error', 'Voce nao esta inscrito neste curso.');
    redirect('meus-cursos.php');
}

$totalAulas = (int) $dados['total_aulas'];
$concluidas = (int) $dados['concluidas'];
$percentual = $totalAulas > 0 ? (int) floor($concluidas * 100 / $totalAulas) : 0;

if ($percentual < 100) {
    flash('error', 'Voce ainda nao concluiu este curso (' . $percentual . '% concluido).');
    redirect("meu-curso.php?curso_id={$cursoId}");
}

// Data da última aula concluída = data de conclusão do curso
$stmtData = $cx->prepare("
    SELECT MAX(pa.concluido_em) AS ultima
    FROM progresso_aulas pa
    INNER JOIN aulas al ON al.id = pa.aula_id
    INNER JOIN modulos m ON m.id = al.modulo_id
    WHERE pa.usuario_id = ? AND m.curso_id = ?
");
$stmtData->bind_param("ii", $usuarioId, $cursoId);
$stmtData->execute();
$rowData = $stmtData->get_result()->fetch_assoc();
$stmtData->close();

$dataConclusao = !empty($rowData['ultima'])
    ? strftime('%d de %B de %Y', strtotime((string) $rowData['ultima']))
    : date('d/m/Y');

// Fallback para strftime em sistemas que não suportam localização
if (str_contains($dataConclusao, '%') || $dataConclusao === false) {
    $meses = [
        1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',
        5=>'maio',6=>'junho',7=>'julho',8=>'agosto',
        9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'
    ];
    $ts = !empty($rowData['ultima']) ? strtotime((string) $rowData['ultima']) : time();
    $dataConclusao = (int)date('d', $ts) . ' de ' . $meses[(int)date('n', $ts)] . ' de ' . date('Y', $ts);
}

$nomeAluno   = htmlspecialchars((string) ($_SESSION['nome'] ?? 'Aluno'));
$tituloCurso = htmlspecialchars((string) ($dados['titulo'] ?? ''));
$cargaHoras  = (int) ($dados['carga_horaria'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Certificado — <?= $tituloCurso ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; }

        /* Botões — ocultos na impressão */
        .no-print { padding: 20px; text-align: center; }

        /* Página do certificado */
        .cert-page {
            width: 900px;
            max-width: 100%;
            margin: 0 auto;
            background: #fffdf7;
            border: 12px solid transparent;
            border-image: linear-gradient(135deg, #0f766e, #1d4ed8, #7c3aed, #0f766e) 1;
            box-shadow: 0 20px 60px rgba(0,0,0,.15);
            padding: 60px 70px;
            position: relative;
            font-family: Georgia, 'Times New Roman', serif;
        }

        /* Cantos decorativos */
        .cert-page::before, .cert-page::after,
        .cert-corner-bl, .cert-corner-br {
            content: '✦';
            position: absolute;
            font-size: 22px;
            color: #0f766e;
            opacity: .5;
        }
        .cert-page::before { top: 18px;    left: 20px; }
        .cert-page::after  { top: 18px;   right: 20px; }
        .cert-corner-bl    { bottom: 18px;  left: 20px; }
        .cert-corner-br    { bottom: 18px; right: 20px; }

        /* Logotipo */
        .cert-logo {
            font-family: Arial, sans-serif;
            font-weight: 900;
            font-size: 22px;
            letter-spacing: -0.5px;
            color: #0f766e;
            text-transform: uppercase;
        }
        .cert-logo span { color: #1d4ed8; }

        /* Título do certificado */
        .cert-title {
            font-size: 38px;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin: 28px 0 6px;
            line-height: 1.1;
        }
        .cert-subtitle {
            font-size: 14px;
            color: #64748b;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-family: Arial, sans-serif;
        }

        /* Separador */
        .cert-divider {
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, #0f766e, #1d4ed8);
            margin: 24px auto;
            border-radius: 99px;
        }

        /* Texto declarativo */
        .cert-declares {
            font-size: 15px;
            color: #475569;
            font-style: italic;
            font-family: Arial, sans-serif;
        }

        /* Nome do aluno */
        .cert-name {
            font-size: 44px;
            color: #0f172a;
            font-weight: 700;
            font-style: italic;
            margin: 12px 0 8px;
            line-height: 1.1;
        }

        /* Nome do curso */
        .cert-course-label {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #64748b;
            font-family: Arial, sans-serif;
        }
        .cert-course-name {
            font-size: 24px;
            font-weight: 700;
            color: #0f766e;
            margin: 8px 0;
        }

        /* Data e carga */
        .cert-meta {
            font-size: 13px;
            color: #64748b;
            font-family: Arial, sans-serif;
            margin-top: 32px;
        }
        .cert-meta strong { color: #0f172a; }

        /* Linha de assinatura */
        .cert-sign-line {
            border-top: 1px solid #cbd5e1;
            margin-top: 40px;
            padding-top: 8px;
        }
        .cert-sign-name { font-size: 14px; font-weight: 700; color: #0f172a; }
        .cert-sign-role { font-size: 12px; color: #64748b; font-family: Arial, sans-serif; }

        /* Impressão */
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .cert-page {
                box-shadow: none;
                margin: 0;
                padding: 40px 50px;
                border: 8px solid transparent;
                border-image: linear-gradient(135deg, #0f766e, #1d4ed8, #7c3aed, #0f766e) 1;
            }
        }
    </style>
</head>
<body>

<!-- Botões de ação (não imprimem) -->
<div class="no-print">
    <button onclick="window.print()" class="btn btn-primary mr-2">
        <i class="fas fa-print mr-1"></i> Imprimir / Salvar PDF
    </button>
    <a href="meu-curso.php?curso_id=<?= $cursoId ?>" class="btn btn-outline-secondary mr-2">
        <i class="fas fa-arrow-left mr-1"></i> Voltar ao curso
    </a>
    <a href="meus-cursos.php" class="btn btn-outline-secondary">
        <i class="fas fa-list mr-1"></i> Meus cursos
    </a>
</div>

<!-- Certificado -->
<div class="cert-page text-center">
    <span class="cert-corner-bl">✦</span>
    <span class="cert-corner-br">✦</span>

    <div class="cert-logo">Skill<span>Connect</span></div>

    <div class="cert-title">Certificado</div>
    <div class="cert-subtitle">de Conclusão</div>

    <div class="cert-divider"></div>

    <p class="cert-declares">Certificamos que</p>

    <div class="cert-name"><?= $nomeAluno ?></div>

    <p class="cert-declares">concluiu com êxito o curso</p>

    <div class="cert-course-label">Curso</div>
    <div class="cert-course-name"><?= $tituloCurso ?></div>

    <?php if ($cargaHoras > 0): ?>
        <div class="small text-muted" style="font-family: Arial, sans-serif;">
            Carga horária: <strong><?= $cargaHoras ?>h</strong>
        </div>
    <?php endif; ?>

    <div class="cert-meta">
        Emitido em <strong><?= htmlspecialchars($dataConclusao) ?></strong>
        pela plataforma SkillConnect.
    </div>

    <div class="row justify-content-center mt-4">
        <div class="col-5">
            <div class="cert-sign-line">
                <div class="cert-sign-name">SkillConnect</div>
                <div class="cert-sign-role">Plataforma de Desenvolvimento Profissional</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>

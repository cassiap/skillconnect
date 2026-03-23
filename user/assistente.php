<?php
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/env.php';

$prompt = trim($_POST['prompt'] ?? '');
$objetivo = trim($_POST['objetivo'] ?? 'plano_carreira');
$resposta = '';
$erro = '';
$modeloUsado = '';

if (!function_exists('anthropic_chat_with_fallback')) {
    function anthropic_chat_with_fallback(string $apiKey, string $systemPrompt, array $mensagens): array {
        $modelos = [];
        $preferidosRaw = trim((string) env('ANTHROPIC_MODEL', ''));
        if ($preferidosRaw !== '') {
            foreach (explode(',', $preferidosRaw) as $modeloEnv) {
                $modeloEnv = trim($modeloEnv);
                if ($modeloEnv !== '') {
                    $modelos[] = $modeloEnv;
                }
            }
        }
        if ($modelos === []) {
            $modelos[] = 'claude-3-5-sonnet-latest';
        }
        $modelos = array_values(array_unique($modelos));

        $ultimoErro = 'Assistente indisponivel no momento. Tente novamente em instantes.';

        foreach ($modelos as $modelo) {
            $payload = [
                'model' => $modelo,
                'system' => $systemPrompt,
                'messages' => $mensagens,
                'max_tokens' => 1200,
                'temperature' => 0.7,
            ];

            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($jsonPayload === false) {
                return [false, '', 'Assistente indisponivel no momento. Tente novamente em instantes.', ''];
            }

            $ch = curl_init("https://api.anthropic.com/v1/messages");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 40,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "x-api-key: {$apiKey}",
                    "anthropic-version: 2023-06-01",
                ],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonPayload,
            ]);

            $raw = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($raw === false || $curlError !== '') {
                $ultimoErro = $curlError !== ''
                    ? 'Erro de conexao com Anthropic: ' . $curlError
                    : 'Assistente indisponivel no momento. Tente novamente em instantes.';
                continue;
            }

            $decoded = json_decode($raw, true);
            $apiErrorMsg = trim((string) ($decoded['error']['message'] ?? ''));
            $apiErrorType = trim((string) ($decoded['error']['type'] ?? ''));
            $apiErrorCode = trim((string) ($decoded['error']['code'] ?? ''));

            if ($httpCode >= 400 || $apiErrorMsg !== '') {
                $msgLow = strtolower($apiErrorMsg);
                $isModelError = (strpos($msgLow, 'model') !== false)
                    && (
                        strpos($msgLow, 'not found') !== false
                        || strpos($msgLow, 'does not exist') !== false
                        || strpos($msgLow, 'not have access') !== false
                        || strpos($msgLow, 'permission') !== false
                        || strpos($msgLow, 'invalid model') !== false
                        || strpos($msgLow, 'model:') !== false
                        || $httpCode === 404
                    );

                if ($isModelError) {
                    $ultimoErro = "Modelo '{$modelo}' indisponivel. Ajuste ANTHROPIC_MODEL no Railway para um modelo habilitado na sua conta.";
                    continue;
                }

                if ($httpCode === 401) {
                    return [false, '', 'Chave da Anthropic invalida ou sem permissao. Confira ANTHROPIC_API_KEY no .env.', ''];
                }

                if ($httpCode === 403) {
                    return [false, '', 'Acesso negado pela Anthropic para este projeto/chave.', ''];
                }

                if ($httpCode === 429) {
                    return [false, '', 'Limite de uso da Anthropic atingido. Tente novamente em instantes.', ''];
                }

                $ultimoErro = $apiErrorMsg !== ''
                    ? "Falha na Anthropic (HTTP {$httpCode}): {$apiErrorMsg}"
                    : "Falha na Anthropic (HTTP {$httpCode}).";
                continue;
            }

            $conteudo = '';
            $blocos = $decoded['content'] ?? [];
            if (is_array($blocos)) {
                foreach ($blocos as $bloco) {
                    if (($bloco['type'] ?? '') !== 'text') {
                        continue;
                    }
                    $textoBloco = trim((string) ($bloco['text'] ?? ''));
                    if ($textoBloco === '') {
                        continue;
                    }
                    $conteudo .= ($conteudo === '' ? '' : "\n") . $textoBloco;
                }
            }
            if ($conteudo === '') {
                $ultimoErro = 'A IA respondeu sem conteudo.';
                continue;
            }

            return [true, $conteudo, '', $modelo];
        }

        return [false, '', $ultimoErro, ''];
    }
}

if (!function_exists('ai_resume_texto')) {
    function ai_resume_texto(string $texto, int $limite = 110): string {
        $texto = trim(preg_replace('/\s+/', ' ', strip_tags($texto)) ?? '');
        if ($texto === '') {
            return '';
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
}

if (!function_exists('ai_connect_db_optional')) {
    function ai_connect_db_optional(): ?mysqli {
        if (!extension_loaded('mysqli') || !class_exists('mysqli')) {
            return null;
        }

        $host = env('MYSQLHOST', env('DB_HOST', '127.0.0.1'));
        $user = env('MYSQLUSER', env('DB_USER', 'root'));
        $pass = env('MYSQLPASSWORD', env('DB_PASS', ''));
        $db   = env('MYSQLDATABASE', env('DB_NAME', 'skillconnect'));
        $port = (int) env('MYSQLPORT', env('DB_PORT', 3306));

        if ((!$host || !$user || !$db) && (env('MYSQL_URL') || env('DATABASE_URL'))) {
            $dbUrl = env('MYSQL_URL', env('DATABASE_URL', ''));
            $parts = parse_url($dbUrl);
            if (is_array($parts)) {
                $host = $parts['host'] ?? $host;
                $port = isset($parts['port']) ? (int) $parts['port'] : $port;
                $user = isset($parts['user']) ? rawurldecode((string) $parts['user']) : $user;
                $pass = isset($parts['pass']) ? rawurldecode((string) $parts['pass']) : $pass;
                $path = (string) ($parts['path'] ?? '');
                $dbFromUrl = ltrim($path, '/');
                if ($dbFromUrl !== '') {
                    $db = $dbFromUrl;
                }
            }
        }

        try {
            $cx = @new mysqli((string) $host, (string) $user, (string) $pass, (string) $db, $port);
            if ($cx->connect_errno) {
                return null;
            }
            $cx->set_charset('utf8mb4');
            return $cx;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('ai_catalogo_site_contexto')) {
    function ai_catalogo_site_contexto(): string {
        $cx = ai_connect_db_optional();
        if (!$cx) {
            return "Catalogo do site indisponivel no momento.";
        }

        $linhasCursos = [];
        $sqlCursos = "SELECT id, titulo, descricao, modalidade, nivel, carga_horaria, preco
                      FROM cursos
                      WHERE ativo = 1
                      ORDER BY id DESC
                      LIMIT 8";
        if ($resCursos = $cx->query($sqlCursos)) {
            while ($curso = $resCursos->fetch_assoc()) {
                $titulo = trim((string) ($curso['titulo'] ?? 'Curso sem titulo'));
                $modalidade = trim((string) ($curso['modalidade'] ?? ''));
                $nivel = trim((string) ($curso['nivel'] ?? ''));
                $carga = (int) ($curso['carga_horaria'] ?? 0);
                $precoRaw = (float) ($curso['preco'] ?? 0);
                $preco = $precoRaw > 0 ? 'R$ ' . number_format($precoRaw, 2, ',', '.') : 'Gratuito';
                $descricao = ai_resume_texto((string) ($curso['descricao'] ?? ''));
                $url = app_absolute_url('user/curso.php?id=' . (int) $curso['id']);

                $partes = [];
                if ($modalidade !== '') {
                    $partes[] = 'modalidade: ' . $modalidade;
                }
                if ($nivel !== '') {
                    $partes[] = 'nivel: ' . $nivel;
                }
                if ($carga > 0) {
                    $partes[] = 'carga: ' . $carga . 'h';
                }
                $partes[] = 'preco: ' . $preco;
                if ($descricao !== '') {
                    $partes[] = 'resumo: ' . $descricao;
                }
                $partes[] = 'link: ' . $url;

                $linhasCursos[] = '- ' . $titulo . ' | ' . implode(' | ', $partes);
            }
            $resCursos->close();
        }

        $linhasVagas = [];
        $sqlVagas = "SELECT id, titulo, empresa, tipo, modalidade, cidade, estado, salario, descricao
                     FROM vagas
                     WHERE ativo = 1
                     ORDER BY id DESC
                     LIMIT 10";
        if ($resVagas = $cx->query($sqlVagas)) {
            while ($vaga = $resVagas->fetch_assoc()) {
                $titulo = trim((string) ($vaga['titulo'] ?? 'Vaga sem titulo'));
                $empresa = trim((string) ($vaga['empresa'] ?? 'Empresa nao informada'));
                $tipo = trim((string) ($vaga['tipo'] ?? ''));
                $modalidade = trim((string) ($vaga['modalidade'] ?? ''));
                $cidade = trim((string) ($vaga['cidade'] ?? ''));
                $estado = trim((string) ($vaga['estado'] ?? ''));
                $salario = trim((string) ($vaga['salario'] ?? ''));
                $descricao = ai_resume_texto((string) ($vaga['descricao'] ?? ''));
                $url = app_absolute_url('user/vaga.php?id=' . (int) $vaga['id']);

                $local = trim($cidade . ' / ' . $estado, ' /');
                $partes = ['empresa: ' . $empresa];
                if ($tipo !== '') {
                    $partes[] = 'tipo: ' . $tipo;
                }
                if ($modalidade !== '') {
                    $partes[] = 'modalidade: ' . $modalidade;
                }
                if ($local !== '') {
                    $partes[] = 'local: ' . $local;
                }
                if ($salario !== '') {
                    $partes[] = 'salario: ' . $salario;
                }
                if ($descricao !== '') {
                    $partes[] = 'resumo: ' . $descricao;
                }
                $partes[] = 'link: ' . $url;

                $linhasVagas[] = '- ' . $titulo . ' | ' . implode(' | ', $partes);
            }
            $resVagas->close();
        }

        $cx->close();

        $txtCursos = $linhasCursos !== [] ? implode("\n", $linhasCursos) : '- Nenhum curso ativo encontrado.';
        $txtVagas = $linhasVagas !== [] ? implode("\n", $linhasVagas) : '- Nenhuma vaga ativa encontrada.';

        return "CATALOGO REAL DO SKILLCONNECT (use somente estes itens):\n"
            . "Cursos:\n{$txtCursos}\n\n"
            . "Vagas:\n{$txtVagas}";
    }
}

if (!function_exists('ai_format_inline')) {
    function ai_format_inline(string $text): string {
        $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $safe = preg_replace('/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $safe);
        $safe = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $safe);
        $safe = preg_replace('/`([^`]+)`/', '<code>$1</code>', $safe);
        return $safe ?? '';
    }
}

if (!function_exists('ai_render_response_html')) {
    function ai_render_response_html(string $markdown): string {
        $markdown = trim($markdown);
        if ($markdown === '') {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $htmlParts = [];
        $paragraph = [];
        $inUl = false;
        $inOl = false;

        $flushParagraph = function () use (&$paragraph, &$htmlParts): void {
            if ($paragraph === []) {
                return;
            }
            $joined = trim(implode(' ', $paragraph));
            if ($joined !== '') {
                $htmlParts[] = '<p>' . ai_format_inline($joined) . '</p>';
            }
            $paragraph = [];
        };

        $closeLists = function () use (&$inUl, &$inOl, &$htmlParts): void {
            if ($inUl) {
                $htmlParts[] = '</ul>';
                $inUl = false;
            }
            if ($inOl) {
                $htmlParts[] = '</ol>';
                $inOl = false;
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);

            if ($trimmed === '') {
                $flushParagraph();
                $closeLists();
                continue;
            }

            if (preg_match('/^(#{1,3})\s+(.+)$/', $trimmed, $m)) {
                $flushParagraph();
                $closeLists();
                $level = min(5, 2 + strlen($m[1])); // # => h3, ## => h4, ### => h5
                $htmlParts[] = '<h' . $level . '>' . ai_format_inline($m[2]) . '</h' . $level . '>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $m)) {
                $flushParagraph();
                if ($inOl) {
                    $htmlParts[] = '</ol>';
                    $inOl = false;
                }
                if (!$inUl) {
                    $htmlParts[] = '<ul>';
                    $inUl = true;
                }
                $htmlParts[] = '<li>' . ai_format_inline($m[1]) . '</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $m)) {
                $flushParagraph();
                if ($inUl) {
                    $htmlParts[] = '</ul>';
                    $inUl = false;
                }
                if (!$inOl) {
                    $htmlParts[] = '<ol>';
                    $inOl = true;
                }
                $htmlParts[] = '<li>' . ai_format_inline($m[1]) . '</li>';
                continue;
            }

            $paragraph[] = $trimmed;
        }

        $flushParagraph();
        $closeLists();

        return implode("\n", $htmlParts);
    }
}

$objetivosPermitidos = [
    'plano_carreira' => 'Plano de carreira',
    'curriculo' => 'Melhorar curriculo',
    'entrevista' => 'Treino de entrevista',
    'trilha_estudo' => 'Trilha de estudo',
];
if (!array_key_exists($objetivo, $objetivosPermitidos)) {
    $objetivo = 'plano_carreira';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $erro = 'Sessao expirada. Recarregue a pagina e tente novamente.';
    } elseif ($prompt === '') {
        $erro = 'Descreva sua situacao para receber recomendacoes personalizadas.';
    } else {
        $apiKey = trim((string) env('ANTHROPIC_API_KEY', ''));
        if ($apiKey === '') {
            $erro = 'Configure ANTHROPIC_API_KEY no arquivo .env.';
        } else {
            $contexto = "Voce e o Assistente de Carreira do SkillConnect. 
Seu papel: orientar alunos em cursos profissionalizantes e busca de vagas.
Responda em portugues do Brasil, com linguagem pratica e objetiva.
Estruture a resposta em:
1) Diagnostico rapido
2) Plano de acao em passos
3) Cursos recomendados (perfil geral)
4) Vagas-alvo e palavras-chave para busca
5) Proxima acao para hoje
Nao invente dados pessoais do usuario.";

            $catalogoSite = ai_catalogo_site_contexto();
            $regraCatalogo = "Regras obrigatorias de recomendacao:
- Use apenas cursos e vagas da lista 'CATALOGO REAL DO SKILLCONNECT'.
- Recomende de 2 a 4 cursos e de 2 a 4 vagas quando houver aderencia.
- Sempre inclua links clicaveis em markdown no formato [texto](url).
- Se nao houver aderencia, diga claramente que nenhum item atual encaixa bem e sugira refinamento.";

            $instrucaoObjetivo = "Objetivo principal do usuario: " . $objetivosPermitidos[$objetivo] . ".";
            $systemPrompt = $contexto . "\n\n" . $instrucaoObjetivo . "\n\n" . $regraCatalogo . "\n\n" . $catalogoSite;
            $mensagens = [
                ['role' => 'user', 'content' => $prompt],
            ];
            [$ok, $conteudo, $erroApi, $modelo] = anthropic_chat_with_fallback($apiKey, $systemPrompt, $mensagens);
            if ($ok) {
                $resposta = $conteudo;
                $modeloUsado = $modelo;
            } else {
                $erro = $erroApi;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assistente de Carreira - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        .hero-ai {
            background: radial-gradient(circle at 10% 10%, #7dd3fc 0%, #0ea5a4 35%, #155e75 100%);
            color: #fff;
            border-radius: 18px;
            padding: 30px;
        }
        .panel-card {
            border-radius: 14px;
            border: 1px solid #e2e8f0;
        }
        .shortcut-btn {
            margin: 0 8px 8px 0;
        }
        .response-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            line-height: 1.7;
            color: #0f172a;
        }
        .response-box h3,
        .response-box h4,
        .response-box h5 {
            margin-top: 0;
            margin-bottom: 12px;
            color: #0f766e;
            font-weight: 700;
        }
        .response-box p {
            margin-bottom: 12px;
        }
        .response-box ul,
        .response-box ol {
            margin-bottom: 14px;
            padding-left: 20px;
        }
        .response-box li {
            margin-bottom: 8px;
        }
        .response-box code {
            background: #e2e8f0;
            border-radius: 6px;
            padding: 1px 6px;
            font-size: 0.92em;
        }
    </style>
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <div class="hero-ai mb-4">
        <h1 class="h3 mb-2"><i class="fas fa-robot"></i> Assistente de Carreira SkillConnect</h1>
        <p class="mb-0">Receba um plano pratico para cursos, vagas e proximos passos com base no seu momento profissional.</p>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card panel-card">
                <div class="card-body">
                    <h2 class="h5 mb-3">Descreva seu objetivo</h2>
                    <form method="POST">
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label class="small text-muted mb-1">Foco da conversa</label>
                            <select name="objetivo" class="form-control">
                                <?php foreach ($objetivosPermitidos as $key => $label): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $objetivo === $key ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="small text-muted mb-1">Contexto</label>
                            <textarea name="prompt" rows="8" class="form-control" placeholder="Ex.: Sou iniciante em TI, tenho 2 horas por dia para estudar e quero uma vaga de suporte em ate 4 meses."><?php echo htmlspecialchars($prompt); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Gerar plano com IA
                        </button>
                    </form>

                    <hr>
                    <div class="small text-muted mb-2">Atalhos de prompt:</div>
                    <button type="button" class="btn btn-sm btn-outline-secondary shortcut-btn js-shortcut" data-text="Monte um plano de 8 semanas para eu conseguir uma vaga junior em TI estudando 2 horas por dia.">Plano 8 semanas</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary shortcut-btn js-shortcut" data-text="Revise meu posicionamento profissional para curriculo e LinkedIn de forma objetiva.">Curriculo e LinkedIn</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary shortcut-btn js-shortcut" data-text="Crie um roteiro de estudo focado em Excel e atendimento para vaga administrativa.">Roteiro administrativo</button>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card panel-card h-100">
                <div class="card-body">
                    <h3 class="h6 text-primary">Como usar melhor</h3>
                    <ul class="small mb-3">
                        <li>Inclua nivel atual, area de interesse e prazo.</li>
                        <li>Informe disponibilidade semanal real.</li>
                        <li>Peça plano em etapas com metas curtas.</li>
                    </ul>
                    <h3 class="h6 text-primary">Exemplo de prompt forte</h3>
                    <p class="small text-muted mb-0">"Tenho experiencia em vendas, quero migrar para suporte de TI em 6 meses e estudar 10h por semana. Monte trilha, cursos e palavras-chave de vagas."</p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php endif; ?>

    <?php if ($resposta !== ''): ?>
        <div class="card panel-card mb-4">
            <div class="card-header bg-white">
                <strong><i class="fas fa-lightbulb text-warning"></i> Plano sugerido pela IA</strong>
                <?php if ($modeloUsado !== ''): ?>
                    <small class="text-muted ml-2">(modelo: <?php echo htmlspecialchars($modeloUsado); ?>)</small>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="response-box"><?php echo ai_render_response_html($resposta); ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>

<script>
document.querySelectorAll('.js-shortcut').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var texto = btn.getAttribute('data-text') || '';
        var area = document.querySelector('textarea[name="prompt"]');
        if (area) {
            area.value = texto;
            area.focus();
        }
    });
});
</script>

</body>
</html>

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
        $preferido = trim((string) env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-latest'));
        if ($preferido !== '') {
            $modelos[] = $preferido;
        }
        $modelos[] = 'claude-3-5-sonnet-latest';
        $modelos[] = 'claude-3-5-haiku-latest';
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
                $ultimoErro = 'Assistente indisponivel no momento. Tente novamente em instantes.';
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
                    );

                if ($isModelError) {
                    $ultimoErro = "Modelo '{$modelo}' indisponivel para esta chave.";
                    continue;
                }

                if ($httpCode === 401 || $httpCode === 403 || $httpCode === 429) {
                    return [false, '', 'Assistente indisponivel no momento. Tente novamente em instantes.', ''];
                }

                $ultimoErro = 'Assistente indisponivel no momento. Tente novamente em instantes.';
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
        if (!str_starts_with($apiKey, 'sk-ant-')) {
            $erro = 'Assistente indisponivel no momento. Tente novamente em instantes.';
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

            $instrucaoObjetivo = "Objetivo principal do usuario: " . $objetivosPermitidos[$objetivo] . ".";
            $systemPrompt = $contexto . "\n\n" . $instrucaoObjetivo;
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
            white-space: pre-wrap;
            line-height: 1.55;
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
                <div class="response-box"><?php echo nl2br(htmlspecialchars($resposta)); ?></div>
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



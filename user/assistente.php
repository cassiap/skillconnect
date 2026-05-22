<?php
/**
 * Assistente de Carreira com IA
 *
 * Integra a API Anthropic Claude para gerar planos de carreira personalizados.
 * Injeta o perfil real do aluno (cursos, currículo, candidaturas) no contexto
 * enviado à IA para respostas verdadeiramente individualizadas.
 *
 * @package SkillConnect
 */

require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/env.php';

auth_check();

$prompt    = trim($_POST['prompt'] ?? '');
$objetivo  = trim($_POST['objetivo'] ?? 'plano_carreira');
$resposta  = '';
$erro      = '';
$modeloUsado = '';

// ===== Funções de integração com a API Anthropic =====

if (!function_exists('anthropic_chat_with_fallback')) {
    /**
     * Envia uma conversa à API Anthropic com suporte a múltiplos modelos de fallback.
     *
     * Tenta cada modelo listado em ANTHROPIC_MODEL (separados por vírgula) até
     * obter uma resposta bem-sucedida. Trata erros HTTP e erros de modelo individualmente.
     *
     * @param string $apiKey      Chave de API Anthropic.
     * @param string $systemPrompt Instrução de sistema enviada ao modelo.
     * @param array  $mensagens   Array de mensagens no formato [{role, content}].
     * @return array{0:bool, 1:string, 2:string, 3:string} [sucesso, conteudo, erro, modelo_usado]
     */
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
                'model'       => $modelo,
                'system'      => $systemPrompt,
                'messages'    => $mensagens,
                'max_tokens'  => 1200,
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
                CURLOPT_TIMEOUT        => 40,
                CURLOPT_HTTPHEADER     => [
                    "Content-Type: application/json",
                    "x-api-key: {$apiKey}",
                    "anthropic-version: 2023-06-01",
                ],
                CURLOPT_POST       => true,
                CURLOPT_POSTFIELDS => $jsonPayload,
            ]);

            $raw       = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($raw === false || $curlError !== '') {
                $ultimoErro = $curlError !== ''
                    ? 'Erro de conexao com Anthropic: ' . $curlError
                    : 'Assistente indisponivel no momento. Tente novamente em instantes.';
                continue;
            }

            $decoded      = json_decode($raw, true);
            $apiErrorMsg  = trim((string) ($decoded['error']['message'] ?? ''));
            $httpCode     = (int) $httpCode;

            if ($httpCode >= 400 || $apiErrorMsg !== '') {
                $msgLow      = strtolower($apiErrorMsg);
                $isModelError = (strpos($msgLow, 'model') !== false)
                    && (
                        strpos($msgLow, 'not found')      !== false
                        || strpos($msgLow, 'does not exist') !== false
                        || strpos($msgLow, 'not have access') !== false
                        || strpos($msgLow, 'permission')    !== false
                        || strpos($msgLow, 'invalid model') !== false
                        || strpos($msgLow, 'model:')        !== false
                        || $httpCode === 404
                    );

                if ($isModelError) {
                    $ultimoErro = "Modelo '{$modelo}' indisponivel. Ajuste ANTHROPIC_MODEL no Railway.";
                    continue;
                }
                if ($httpCode === 401) {
                    return [false, '', 'Chave Anthropic invalida. Confira ANTHROPIC_API_KEY no .env.', ''];
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
            $blocos   = $decoded['content'] ?? [];
            if (is_array($blocos)) {
                foreach ($blocos as $bloco) {
                    if (($bloco['type'] ?? '') !== 'text') {
                        continue;
                    }
                    $textoBloco = trim((string) ($bloco['text'] ?? ''));
                    if ($textoBloco !== '') {
                        $conteudo .= ($conteudo === '' ? '' : "\n") . $textoBloco;
                    }
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
    /**
     * Trunca um texto para uso em contextos de IA, preservando palavras completas.
     *
     * @param string $texto Texto bruto (pode conter HTML).
     * @param int    $limite Máximo de caracteres (padrão 110).
     * @return string Texto truncado com "..." se necessário.
     */
    function ai_resume_texto(string $texto, int $limite = 110): string {
        $texto = trim(preg_replace('/\s+/', ' ', strip_tags($texto)) ?? '');
        if ($texto === '') {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($texto) <= $limite ? $texto : rtrim(mb_substr($texto, 0, $limite - 3)) . '...';
        }
        return strlen($texto) <= $limite ? $texto : rtrim(substr($texto, 0, $limite - 3)) . '...';
    }
}

if (!function_exists('ai_connect_db_optional')) {
    /**
     * Cria uma conexão MySQL secundária opcional para o assistente de IA.
     *
     * Usa as mesmas variáveis de ambiente da conexão principal.
     * Retorna null sem lançar exceção se a conexão falhar, pois o assistente
     * pode operar sem acesso ao banco (catálogo e perfil ficam indisponíveis).
     *
     * @return mysqli|null Conexão ativa ou null em caso de falha.
     */
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
            error_log('assistente.php ai_connect_db_optional error: ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('ai_catalogo_site_contexto')) {
    /**
     * Carrega o catálogo atual de cursos e vagas para injetar no contexto da IA.
     *
     * Limita a 8 cursos e 10 vagas para não ultrapassar o limite de tokens.
     * A IA usa essas informações para recomendar itens reais do sistema.
     *
     * @return string Bloco de texto formatado com cursos e vagas ativos.
     */
    function ai_catalogo_site_contexto(): string {
        $cx = ai_connect_db_optional();
        if (!$cx) {
            return "Catalogo do site indisponivel no momento.";
        }

        $linhasCursos = [];
        $sqlCursos = "SELECT id, titulo, descricao, modalidade, nivel, carga_horaria, preco
                      FROM cursos WHERE ativo = 1 ORDER BY id DESC LIMIT 8";
        if ($resCursos = $cx->query($sqlCursos)) {
            while ($curso = $resCursos->fetch_assoc()) {
                $titulo     = trim((string) ($curso['titulo'] ?? 'Curso sem titulo'));
                $modalidade = trim((string) ($curso['modalidade'] ?? ''));
                $nivel      = trim((string) ($curso['nivel'] ?? ''));
                $carga      = (int) ($curso['carga_horaria'] ?? 0);
                $precoRaw   = (float) ($curso['preco'] ?? 0);
                $preco      = $precoRaw > 0 ? 'R$ ' . number_format($precoRaw, 2, ',', '.') : 'Gratuito';
                $descricao  = ai_resume_texto((string) ($curso['descricao'] ?? ''));
                $url        = app_absolute_url('user/curso.php?id=' . (int) $curso['id']);

                $partes = [];
                if ($modalidade !== '') $partes[] = 'modalidade: ' . $modalidade;
                if ($nivel !== '')      $partes[] = 'nivel: ' . $nivel;
                if ($carga > 0)        $partes[] = 'carga: ' . $carga . 'h';
                $partes[] = 'preco: ' . $preco;
                if ($descricao !== '') $partes[] = 'resumo: ' . $descricao;
                $partes[] = 'link: ' . $url;

                $linhasCursos[] = '- ' . $titulo . ' | ' . implode(' | ', $partes);
            }
            $resCursos->close();
        }

        $linhasVagas = [];
        $sqlVagas = "SELECT id, titulo, empresa, tipo, modalidade, cidade, estado, salario, descricao
                     FROM vagas WHERE ativo = 1 ORDER BY id DESC LIMIT 10";
        if ($resVagas = $cx->query($sqlVagas)) {
            while ($vaga = $resVagas->fetch_assoc()) {
                $titulo     = trim((string) ($vaga['titulo'] ?? 'Vaga sem titulo'));
                $empresa    = trim((string) ($vaga['empresa'] ?? 'Empresa nao informada'));
                $tipo       = trim((string) ($vaga['tipo'] ?? ''));
                $modalidade = trim((string) ($vaga['modalidade'] ?? ''));
                $cidade     = trim((string) ($vaga['cidade'] ?? ''));
                $estado     = trim((string) ($vaga['estado'] ?? ''));
                $salario    = trim((string) ($vaga['salario'] ?? ''));
                $descricao  = ai_resume_texto((string) ($vaga['descricao'] ?? ''));
                $url        = app_absolute_url('user/vaga.php?id=' . (int) $vaga['id']);

                $local  = trim($cidade . ' / ' . $estado, ' /');
                $partes = ['empresa: ' . $empresa];
                if ($tipo !== '')       $partes[] = 'tipo: ' . $tipo;
                if ($modalidade !== '') $partes[] = 'modalidade: ' . $modalidade;
                if ($local !== '')      $partes[] = 'local: ' . $local;
                if ($salario !== '')    $partes[] = 'salario: ' . $salario;
                if ($descricao !== '')  $partes[] = 'resumo: ' . $descricao;
                $partes[] = 'link: ' . $url;

                $linhasVagas[] = '- ' . $titulo . ' | ' . implode(' | ', $partes);
            }
            $resVagas->close();
        }

        $cx->close();

        $txtCursos = $linhasCursos !== [] ? implode("\n", $linhasCursos) : '- Nenhum curso ativo encontrado.';
        $txtVagas  = $linhasVagas  !== [] ? implode("\n", $linhasVagas)  : '- Nenhuma vaga ativa encontrada.';

        return "CATALOGO REAL DO SKILLCONNECT (use somente estes itens):\nCursos:\n{$txtCursos}\n\nVagas:\n{$txtVagas}";
    }
}

if (!function_exists('ai_perfil_aluno_contexto')) {
    /**
     * Carrega o perfil real do aluno para personalizar as respostas da IA.
     *
     * Injeta no contexto: nome, cidade, título profissional, habilidades,
     * cursos inscritos com percentual de progresso e candidaturas em aberto.
     * Retorna string vazia se a conexão ou o ID forem inválidos.
     *
     * @param mysqli|null $cx        Conexão MySQL opcional.
     * @param int         $usuarioId ID do usuário autenticado.
     * @return string Bloco de texto formatado com os dados do aluno.
     */
    function ai_perfil_aluno_contexto(?mysqli $cx, int $usuarioId): string {
        if (!$cx || $usuarioId <= 0) {
            return '';
        }

        $linhas = [];

        // Dados básicos de perfil
        $stmtPerfil = $cx->prepare("SELECT nome, cidade FROM usuarios WHERE id = ? LIMIT 1");
        $stmtPerfil->bind_param("i", $usuarioId);
        $stmtPerfil->execute();
        $perfil = $stmtPerfil->get_result()->fetch_assoc();
        $stmtPerfil->close();

        if ($perfil) {
            if (!empty($perfil['nome']))   $linhas[] = 'Nome: ' . $perfil['nome'];
            if (!empty($perfil['cidade'])) $linhas[] = 'Cidade: ' . $perfil['cidade'];
        }

        // Currículo
        $stmtCurr = $cx->prepare(
            "SELECT titulo_profissional, habilidades, resumo FROM curriculos WHERE usuario_id = ? LIMIT 1"
        );
        $stmtCurr->bind_param("i", $usuarioId);
        $stmtCurr->execute();
        $curriculo = $stmtCurr->get_result()->fetch_assoc();
        $stmtCurr->close();

        if ($curriculo) {
            if (!empty($curriculo['titulo_profissional'])) {
                $linhas[] = 'Titulo profissional: ' . $curriculo['titulo_profissional'];
            }
            if (!empty($curriculo['habilidades'])) {
                $hab = ai_resume_texto(strip_tags((string) $curriculo['habilidades']), 220);
                if ($hab !== '') $linhas[] = 'Habilidades: ' . $hab;
            }
            if (!empty($curriculo['resumo'])) {
                $res = ai_resume_texto(strip_tags((string) $curriculo['resumo']), 200);
                if ($res !== '') $linhas[] = 'Resumo profissional: ' . $res;
            }
        }

        // Cursos inscritos com progresso
        $stmtCursos = $cx->prepare("
            SELECT c.titulo, ic.status,
                   COALESCE(a.total, 0) AS total_aulas,
                   COALESCE(p.concluidas, 0) AS concluidas
            FROM inscricoes_cursos ic
            INNER JOIN cursos c ON c.id = ic.curso_id
            LEFT JOIN (
                SELECT m.curso_id, COUNT(al.id) AS total
                FROM modulos m
                LEFT JOIN aulas al ON al.modulo_id = m.id AND al.ativo = 1
                WHERE m.ativo = 1 GROUP BY m.curso_id
            ) a ON a.curso_id = c.id
            LEFT JOIN (
                SELECT m.curso_id, COUNT(pa.id) AS concluidas
                FROM progresso_aulas pa
                INNER JOIN aulas al ON al.id = pa.aula_id
                INNER JOIN modulos m ON m.id = al.modulo_id
                WHERE pa.usuario_id = ? GROUP BY m.curso_id
            ) p ON p.curso_id = c.id
            WHERE ic.usuario_id = ? AND ic.status != 'cancelado'
            ORDER BY ic.criado_em DESC LIMIT 5
        ");
        $stmtCursos->bind_param("ii", $usuarioId, $usuarioId);
        $stmtCursos->execute();
        $resCursos  = $stmtCursos->get_result();
        $cursoLinhas = [];
        while ($row = $resCursos->fetch_assoc()) {
            $total  = (int) $row['total_aulas'];
            $conc   = (int) $row['concluidas'];
            $pct    = $total > 0 ? (int) floor($conc * 100 / $total) : 0;
            $status = $row['status'] === 'concluido' ? 'concluido' : "{$pct}% concluido";
            $cursoLinhas[] = '- ' . $row['titulo'] . ' (' . $status . ')';
        }
        $stmtCursos->close();

        $linhas[] = $cursoLinhas !== []
            ? "Cursos inscritos:\n" . implode("\n", $cursoLinhas)
            : 'Cursos inscritos: nenhum ainda';

        // Candidaturas em aberto
        $stmtCand = $cx->prepare("
            SELECT v.titulo, c.status
            FROM candidaturas c
            INNER JOIN vagas v ON v.id = c.vaga_id
            WHERE c.usuario_id = ? AND c.status IN ('enviada', 'em_analise')
            ORDER BY c.criado_em DESC LIMIT 3
        ");
        $stmtCand->bind_param("i", $usuarioId);
        $stmtCand->execute();
        $resCand   = $stmtCand->get_result();
        $candLinhas = [];
        while ($row = $resCand->fetch_assoc()) {
            $candLinhas[] = '- ' . $row['titulo'] . ' (status: ' . $row['status'] . ')';
        }
        $stmtCand->close();

        if ($candLinhas !== []) {
            $linhas[] = "Candidaturas em aberto:\n" . implode("\n", $candLinhas);
        }

        return "PERFIL REAL DO ALUNO (use para personalizar — nao revele dados sensiveis):\n"
            . implode("\n", $linhas);
    }
}

if (!function_exists('ai_format_inline')) {
    /**
     * Converte formatação inline Markdown para HTML seguro.
     *
     * Processa: links [texto](url), negrito **texto** e código `inline`.
     * Aplica htmlspecialchars antes de inserir no DOM para evitar XSS.
     *
     * @param string $text Texto com marcação Markdown.
     * @return string HTML seguro com tags inline.
     */
    function ai_format_inline(string $text): string {
        $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $safe = preg_replace('/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $safe);
        $safe = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $safe);
        $safe = preg_replace('/`([^`]+)`/', '<code>$1</code>', $safe);
        return $safe ?? '';
    }
}

if (!function_exists('ai_render_response_html')) {
    /**
     * Converte a resposta Markdown da IA para HTML renderizável.
     *
     * Suporta: headings (# ## ###), listas não-ordenadas (- *),
     * listas ordenadas (1.) e parágrafos. Delega formatação inline
     * a ai_format_inline() para segurança contra XSS.
     *
     * @param string $markdown Texto bruto retornado pela API.
     * @return string HTML pronto para inserção em .response-box.
     */
    function ai_render_response_html(string $markdown): string {
        $markdown = trim($markdown);
        if ($markdown === '') {
            return '';
        }

        $lines     = preg_split('/\r\n|\r|\n/', $markdown) ?: [];
        $htmlParts = [];
        $paragraph = [];
        $inUl      = false;
        $inOl      = false;

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
            if ($inUl) { $htmlParts[] = '</ul>'; $inUl = false; }
            if ($inOl) { $htmlParts[] = '</ol>'; $inOl = false; }
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
                $level       = min(5, 2 + strlen($m[1]));
                $htmlParts[] = '<h' . $level . '>' . ai_format_inline($m[2]) . '</h' . $level . '>';
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $trimmed, $m)) {
                $flushParagraph();
                if ($inOl) { $htmlParts[] = '</ol>'; $inOl = false; }
                if (!$inUl) { $htmlParts[] = '<ul>'; $inUl = true; }
                $htmlParts[] = '<li>' . ai_format_inline($m[1]) . '</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/', $trimmed, $m)) {
                $flushParagraph();
                if ($inUl) { $htmlParts[] = '</ul>'; $inUl = false; }
                if (!$inOl) { $htmlParts[] = '<ol>'; $inOl = true; }
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

// ===== Objetivos disponíveis =====

$objetivosPermitidos = [
    'plano_carreira' => ['label' => 'Plano de carreira',    'icon' => 'fa-route',         'desc' => 'Monte um plano completo para sua área'],
    'curriculo'      => ['label' => 'Melhorar currículo',   'icon' => 'fa-file-alt',       'desc' => 'Dicas práticas para se destacar'],
    'entrevista'     => ['label' => 'Treino de entrevista', 'icon' => 'fa-comments',       'desc' => 'Prepare respostas para perguntas-chave'],
    'trilha_estudo'  => ['label' => 'Trilha de estudo',     'icon' => 'fa-graduation-cap', 'desc' => 'Sequência de cursos para seu objetivo'],
];

if (!array_key_exists($objetivo, $objetivosPermitidos)) {
    $objetivo = 'plano_carreira';
}

// ===== Carrega perfil do aluno para exibir no painel e enviar à IA =====

$cxOpt      = ai_connect_db_optional();
$uidSessao  = (int) ($_SESSION['user_id'] ?? 0);
$perfilIA   = ['cursos' => [], 'curriculo' => null, 'candidaturas' => 0];

if ($cxOpt && $uidSessao > 0) {
    // Cursos com progresso para exibição no painel
    $stmtCursos = $cxOpt->prepare("
        SELECT c.titulo,
               COALESCE(a.total, 0) AS total_aulas,
               COALESCE(p.concluidas, 0) AS concluidas
        FROM inscricoes_cursos ic
        INNER JOIN cursos c ON c.id = ic.curso_id
        LEFT JOIN (
            SELECT m.curso_id, COUNT(al.id) AS total
            FROM modulos m LEFT JOIN aulas al ON al.modulo_id = m.id AND al.ativo = 1
            WHERE m.ativo = 1 GROUP BY m.curso_id
        ) a ON a.curso_id = c.id
        LEFT JOIN (
            SELECT m.curso_id, COUNT(pa.id) AS concluidas
            FROM progresso_aulas pa
            INNER JOIN aulas al ON al.id = pa.aula_id
            INNER JOIN modulos m ON m.id = al.modulo_id
            WHERE pa.usuario_id = ? GROUP BY m.curso_id
        ) p ON p.curso_id = c.id
        WHERE ic.usuario_id = ? AND ic.status != 'cancelado'
        ORDER BY ic.criado_em DESC LIMIT 5
    ");
    $stmtCursos->bind_param("ii", $uidSessao, $uidSessao);
    $stmtCursos->execute();
    $resCursos = $stmtCursos->get_result();
    while ($row = $resCursos->fetch_assoc()) {
        $total = (int) $row['total_aulas'];
        $conc  = (int) $row['concluidas'];
        $pct   = $total > 0 ? (int) floor($conc * 100 / $total) : 0;
        $perfilIA['cursos'][] = ['titulo' => $row['titulo'], 'pct' => $pct];
    }
    $stmtCursos->close();

    // Currículo para exibição
    $stmtCurr = $cxOpt->prepare(
        "SELECT titulo_profissional, habilidades FROM curriculos WHERE usuario_id = ? LIMIT 1"
    );
    $stmtCurr->bind_param("i", $uidSessao);
    $stmtCurr->execute();
    $perfilIA['curriculo'] = $stmtCurr->get_result()->fetch_assoc();
    $stmtCurr->close();

    // Contagem de candidaturas abertas
    $stmtCand = $cxOpt->prepare(
        "SELECT COUNT(*) AS total FROM candidaturas
         WHERE usuario_id = ? AND status IN ('enviada', 'em_analise')"
    );
    $stmtCand->bind_param("i", $uidSessao);
    $stmtCand->execute();
    $resCand = $stmtCand->get_result()->fetch_assoc();
    $perfilIA['candidaturas'] = (int) ($resCand['total'] ?? 0);
    $stmtCand->close();
}

// ===== Processa POST =====

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
            $contextoBase = "Voce e o Assistente de Carreira do SkillConnect.
Seu papel: orientar alunos em cursos profissionalizantes e busca de vagas.
Responda em portugues do Brasil, com linguagem pratica e objetiva.
Estruture a resposta em:
1) Diagnostico rapido do momento atual
2) Plano de acao em passos claros
3) Cursos recomendados (use apenas o catalogo real)
4) Vagas-alvo e palavras-chave para busca
5) Proxima acao concreta para hoje
Nao invente dados pessoais do usuario alem do que foi informado.";

            $regraCatalogo = "Regras obrigatorias de recomendacao:
- Use apenas cursos e vagas da lista CATALOGO REAL DO SKILLCONNECT.
- Recomende de 2 a 4 cursos e de 2 a 4 vagas quando houver aderencia.
- Sempre inclua links clicaveis no formato [texto](url).
- Se o aluno ja esta inscrito em um curso com progresso baixo, mencione-o como prioridade.
- Se nao houver aderencia no catalogo, diga claramente e sugira refinamento.";

            $instrucaoObjetivo = "Objetivo principal do usuario: "
                . $objetivosPermitidos[$objetivo]['label'] . ".";

            $catalogoSite  = ai_catalogo_site_contexto();
            $perfilAluno   = ai_perfil_aluno_contexto($cxOpt, $uidSessao);

            $systemPrompt = implode("\n\n", array_filter([
                $contextoBase,
                $instrucaoObjetivo,
                $regraCatalogo,
                $catalogoSite,
                $perfilAluno,
            ]));

            $mensagens = [['role' => 'user', 'content' => $prompt]];
            [$ok, $conteudo, $erroApi, $modelo] = anthropic_chat_with_fallback($apiKey, $systemPrompt, $mensagens);

            if ($ok) {
                $resposta    = $conteudo;
                $modeloUsado = $modelo;
            } else {
                $erro = $erroApi;
            }
        }
    }
}

if ($cxOpt) {
    $cxOpt->close();
}

$nomeUsuario = htmlspecialchars((string) ($_SESSION['nome'] ?? 'Aluno'));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Assistente de Carreira - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Overlay de carregamento -->
<div id="loading-overlay" role="status" aria-live="polite">
    <div class="loading-card">
        <div class="spinner-ring"></div>
        <div class="font-weight-bold mb-1" style="color:#0f172a;">Consultando a IA...</div>
        <div class="msg" id="loading-msg">Analisando seu perfil...</div>
    </div>
</div>

<?php include('../includes/header.php'); ?>

<div class="container py-4">

    <!-- Hero -->
    <div class="hero-ai mb-4">
        <div class="d-flex align-items-center mb-2">
            <i class="fas fa-robot fa-2x mr-3" style="color:#38bdf8; opacity:.9;"></i>
            <div>
                <h1 class="h3 mb-0 font-weight-bold">Assistente de Carreira</h1>
                <div class="small" style="opacity:.7;">SkillConnect &mdash; Orientação profissional com IA</div>
            </div>
        </div>
        <p class="mb-0" style="opacity:.85; max-width:520px;">
            Olá, <strong><?= $nomeUsuario ?></strong>. Descreva seu momento profissional e receba um plano
            personalizado com cursos, vagas e próximos passos práticos.
        </p>
    </div>

    <div class="row">
        <!-- Formulário principal -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm" style="border-radius:16px; border:1px solid #e2e8f0;">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">O que você precisa hoje?</h2>
                    <form method="POST" id="ai-form">
                        <?= csrf_field() ?>

                        <!-- Cards de objetivo -->
                        <div class="row mb-3">
                            <?php foreach ($objetivosPermitidos as $key => $info): ?>
                                <div class="col-6 col-md-3 mb-2">
                                    <label class="objetivo-card d-block h-100">
                                        <input type="radio" name="objetivo" value="<?= htmlspecialchars($key) ?>"
                                               <?= $objetivo === $key ? 'checked' : '' ?>>
                                        <div class="objetivo-inner">
                                            <i class="fas <?= $info['icon'] ?>"></i>
                                            <strong><?= htmlspecialchars($info['label']) ?></strong>
                                            <span style="font-size:11px; color:#94a3b8;"><?= htmlspecialchars($info['desc']) ?></span>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-group">
                            <label class="small font-weight-bold text-muted mb-1">
                                Descreva seu contexto
                            </label>
                            <textarea name="prompt" rows="7" class="form-control"
                                      placeholder="Ex.: Sou iniciante em TI, tenho 2 horas por dia para estudar e quero uma vaga de suporte em até 4 meses. Trabalho atualmente como assistente administrativo."
                                      style="border-radius:10px; resize:vertical;"><?= htmlspecialchars($prompt) ?></textarea>
                        </div>

                        <button type="submit" id="btn-gerar" class="btn btn-primary px-4"
                                style="border-radius:10px;">
                            <i class="fas fa-paper-plane mr-1"></i> Gerar plano com IA
                        </button>

                        <div class="mt-3">
                            <div class="small text-muted mb-2">Atalhos rápidos:</div>
                            <span class="shortcut-chip js-shortcut"
                                  data-text="Monte um plano de 8 semanas para eu conseguir uma vaga junior em TI estudando 2 horas por dia.">
                                Plano 8 semanas
                            </span>
                            <span class="shortcut-chip js-shortcut"
                                  data-text="Revise meu posicionamento profissional para currículo e LinkedIn de forma objetiva.">
                                Currículo e LinkedIn
                            </span>
                            <span class="shortcut-chip js-shortcut"
                                  data-text="Crie um roteiro de estudo focado em Excel e atendimento para vaga administrativa.">
                                Roteiro administrativo
                            </span>
                            <span class="shortcut-chip js-shortcut"
                                  data-text="Quais são as perguntas mais comuns em entrevistas para minha área e como respondê-las?">
                                Perguntas de entrevista
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Painel lateral: O que a IA sabe sobre você -->
        <div class="col-lg-4 mb-4">
            <div class="profile-panel h-100">
                <div class="panel-header">
                    <i class="fas fa-brain mr-1"></i> O que a IA sabe sobre você
                </div>
                <div class="panel-body">
                    <?php if (!empty($perfilIA['curriculo']['titulo_profissional'])): ?>
                        <div class="mb-3">
                            <div class="small text-muted mb-1">Título profissional</div>
                            <div class="font-weight-bold" style="font-size:13px;">
                                <?= htmlspecialchars((string) $perfilIA['curriculo']['titulo_profissional']) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($perfilIA['cursos'])): ?>
                        <div class="mb-3">
                            <div class="small text-muted mb-2">Cursos em andamento</div>
                            <?php foreach ($perfilIA['cursos'] as $c): ?>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small text-truncate mr-2" style="max-width:160px;">
                                        <?= htmlspecialchars($c['titulo']) ?>
                                    </span>
                                    <span class="small text-muted"><?= $c['pct'] ?>%</span>
                                </div>
                                <div class="mini-progress mb-2">
                                    <div class="progress-bar <?= $c['pct'] >= 100 ? 'bg-success' : 'bg-primary' ?>"
                                         style="height:4px; width:<?= $c['pct'] ?>%; border-radius:999px;"></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="small text-muted mb-3">
                            <i class="fas fa-info-circle mr-1"></i>
                            Nenhum curso inscrito ainda.
                        </div>
                    <?php endif; ?>

                    <?php if ($perfilIA['candidaturas'] > 0): ?>
                        <div class="mb-3">
                            <span class="badge badge-info" style="font-size:11px;">
                                <i class="fas fa-briefcase mr-1"></i>
                                <?= $perfilIA['candidaturas'] ?> candidatura<?= $perfilIA['candidaturas'] > 1 ? 's' : '' ?> em aberto
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($perfilIA['curriculo']['habilidades'])): ?>
                        <div>
                            <div class="small text-muted mb-1">Habilidades no currículo</div>
                            <p class="small mb-0" style="color:#475569; line-height:1.5;">
                                <?= htmlspecialchars(ai_resume_texto(
                                    strip_tags((string) $perfilIA['curriculo']['habilidades']), 120
                                )) ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($perfilIA['curriculo']) && empty($perfilIA['cursos'])): ?>
                        <div class="small text-muted">
                            <i class="fas fa-user-edit mr-1"></i>
                            Complete seu perfil e currículo para que a IA gere recomendações mais precisas.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Erros -->
    <?php if ($erro !== ''): ?>
        <div class="alert alert-danger d-flex align-items-center" style="border-radius:12px;">
            <i class="fas fa-exclamation-circle fa-lg mr-3"></i>
            <div><?= htmlspecialchars($erro) ?></div>
        </div>
    <?php endif; ?>

    <!-- Resposta da IA -->
    <?php if ($resposta !== ''): ?>
        <div class="card shadow-sm mb-4" style="border-radius:16px; border:1px solid #e2e8f0;">
            <div class="card-header bg-white d-flex align-items-center justify-content-between"
                 style="border-radius:16px 16px 0 0;">
                <div>
                    <i class="fas fa-lightbulb text-warning mr-1"></i>
                    <strong>Plano sugerido pela IA</strong>
                </div>
                <?php if ($modeloUsado !== ''): ?>
                    <span class="badge badge-light" style="font-size:10px;">
                        <?= htmlspecialchars($modeloUsado) ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="response-box"><?= ai_render_response_html($resposta) ?></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Atalhos de prompt
document.querySelectorAll('.js-shortcut').forEach(function(chip) {
    chip.addEventListener('click', function() {
        var area = document.querySelector('textarea[name="prompt"]');
        if (area) {
            area.value = chip.getAttribute('data-text') || '';
            area.focus();
        }
    });
});

// Spinner de carregamento com mensagens progressivas
(function() {
    var form    = document.getElementById('ai-form');
    var overlay = document.getElementById('loading-overlay');
    var msgEl   = document.getElementById('loading-msg');
    if (!form || !overlay) return;

    var msgs = [
        'Analisando seu perfil...',
        'Consultando o catálogo de cursos...',
        'Cruzando vagas disponíveis...',
        'Gerando plano personalizado...',
        'Finalizando recomendações...'
    ];
    var idx      = 0;
    var interval = null;

    form.addEventListener('submit', function() {
        var textarea = form.querySelector('textarea[name="prompt"]');
        if (textarea && textarea.value.trim() === '') return; // deixa a validação nativa agir
        overlay.classList.add('active');
        msgEl.textContent = msgs[0];
        idx = 0;
        interval = setInterval(function() {
            idx = (idx + 1) % msgs.length;
            msgEl.textContent = msgs[idx];
        }, 2200);
    });
})();
</script>
</body>
</html>

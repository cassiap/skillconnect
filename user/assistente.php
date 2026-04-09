<?php
/**
 * Assistente de Carreira SkillConnect
 * 
 * Sistema de assistente virtual para orientação profissional e de carreira,
 * integrado com IA da Anthropic para fornecer recomendações personalizadas
 * sobre cursos, vagas e planejamento de carreira.
 * 
 * @author SkillConnect
 * @version 1.0
 */
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../config/env.php';

$prompt = trim($_POST['prompt'] ?? '');
$objetivo = trim($_POST['objetivo'] ?? 'plano_carreira');
$resposta = '';
$erro = '';
$modeloUsado = '';

if (!function_exists('anthropic_chat_with_fallback')) {
    /**
     * Realiza chat com a API da Anthropic usando fallback entre múltiplos modelos
     * 
     * @param string $apiKey Chave da API da Anthropic
     * @param string $systemPrompt Prompt do sistema para contexto da IA
     * @param array $mensagens Array de mensagens do diálogo
     * @return array Array com [sucesso, conteudo, erro, modelo_usado]
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
    /**
     * Resume um texto para um limite de caracteres
     * 
     * @param string $texto Texto a ser resumido
     * @param int $limite Limite máximo de caracteres
     * @return string Texto resumido com reticências se necessário
     */
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
    /**
     * Conecta ao banco de dados MySQL de forma opcional
     * 
     * @return mysqli|null Conexão MySQLi ou null em caso de falha
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
            return null;
        }
    }
}

if (!function_exists('ai_catalogo_site_contexto')) {
    /**
     * Obtém o catálogo atual de cursos e vagas do site para contexto da IA
     * 
     * @return string String formatada com informações de cursos e vagas disponíveis
     */
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
    /**
     * Formata elementos inline do markdown para HTML
     * 
     * @param string $text Texto em markdown para formatação
     * @return string Texto formatado com HTML inline
     */
    function ai_format_inline(string $text): string {
        $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $safe = preg_replace('/\[(.+?)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$
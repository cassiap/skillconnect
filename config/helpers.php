<?php
/**
 * Funcoes auxiliares do SkillConnect
 * Este arquivo e incluido automaticamente via config/db.php
 */

require_once __DIR__ . '/constants.php';

/** Tempo máximo de inatividade em segundos antes de expirar a sessão autenticada. */
define('SESSION_TIMEOUT', 1800);

// ===== SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ===== CSRF =====

/**
 * Gera ou retorna o token CSRF atual da sessão
 *
 * @return string O token CSRF em formato hexadecimal
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Gera um campo HTML oculto com o token CSRF
 *
 * @return string Campo input hidden com o token CSRF
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Valida se o token CSRF enviado via POST é válido
 *
 * @return bool True se o token for válido, false caso contrário
 */
function csrf_validate(): bool {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// ===== FLASH MESSAGES =====

/**
 * Define uma mensagem flash na sessão
 *
 * @param string $type Tipo da mensagem (success, error, info, etc)
 * @param string $msg Conteúdo da mensagem
 * @return void
 */
function flash(string $type, string $msg): void {
    $_SESSION["flash_{$type}"] = $msg;
}

/**
 * Recupera e remove uma mensagem flash da sessão
 *
 * @param string $type Tipo da mensagem a ser recuperada
 * @return string|null A mensagem flash ou null se não existir
 */
function get_flash(string $type): ?string {
    $msg = $_SESSION["flash_{$type}"] ?? null;
    unset($_SESSION["flash_{$type}"]);
    return $msg;
}

// ===== APP URL =====

/**
 * Determina o caminho base da aplicação
 *
 * @return string O caminho base da aplicação sem barra final
 */
function app_base_path(): string {
    $projectRoot = realpath(__DIR__ . '/..');
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;

    if ($projectRoot !== false && $documentRoot !== false) {
        $projectRoot = str_replace('\\', '/', $projectRoot);
        $documentRoot = str_replace('\\', '/', $documentRoot);

        if (strpos($projectRoot, $documentRoot) === 0) {
            $relative = trim(substr($projectRoot, strlen($documentRoot)), '/');
            if ($relative === '') {
                return '';
            }
            return '/' . $relative;
        }
    }

    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    foreach (['/auth/', '/user/', '/admin/', '/includes/', '/config/'] as $segment) {
        $pos = strpos($script, $segment);
        if ($pos !== false) {
            $base = rtrim(substr($script, 0, $pos), '/');
            return $base === '' ? '' : $base;
        }
    }

    $dir = rtrim(dirname($script), '/');
    if ($dir === '' || $dir === '.' || $dir === '/') {
        return '';
    }
    return $dir;
}

/**
 * Gera uma URL relativa da aplicação
 *
 * @param string $path Caminho a ser adicionado à URL base
 * @return string A URL completa relativa
 */
function app_url(string $path = ''): string {
    $base = app_base_path();
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return $base === '' ? '/' . $path : $base . '/' . $path;
}

/**
 * Gera uma URL absoluta da aplicação
 *
 * @param string $path Caminho a ser adicionado à URL base
 * @return string A URL completa absoluta com protocolo e domínio
 */
function app_absolute_url(string $path = ''): string {
    if (function_exists('env')) {
        $configured = rtrim((string) env('APP_URL', ''), '/');
        if ($configured !== '') {
            return $configured . app_url($path);
        }
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

    return $scheme . '://' . $host . app_url($path);
}

// ===== AUTH =====

/**
 * Verifica se a sessão autenticada expirou por inatividade e encerra se necessário.
 *
 * Deve ser chamada no início de auth_check() e admin_check(). Se o usuário estiver
 * autenticado e a última atividade registrada exceder SESSION_TIMEOUT segundos,
 * a sessão é destruída e o usuário é redirecionado para o login com aviso.
 * Caso contrário, atualiza o timestamp de última atividade.
 *
 * @return void
 */
function session_check_timeout(): void {
    if (empty($_SESSION['logado'])) {
        return;
    }

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        flash('info', 'Sua sessao expirou por inatividade. Faca login novamente.');
        redirect(app_url('auth/login.php'));
    }

    $_SESSION['last_activity'] = time();
}

/**
 * Verifica se o usuário está autenticado, redirecionando para login se não estiver.
 *
 * Também encerra sessões expiradas por inatividade via session_check_timeout().
 *
 * @return void
 */
function auth_check(): void {
    session_check_timeout();

    if (empty($_SESSION['logado'])) {
        $_SESSION['url_destino'] = $_SERVER['REQUEST_URI'];
        redirect(app_url('auth/login.php'));
    }
}

/**
 * Verifica se o usuário é administrador, redirecionando para login se não for.
 *
 * Também encerra sessões expiradas por inatividade via session_check_timeout().
 *
 * @return void
 */
function admin_check(): void {
    session_check_timeout();

    if (empty($_SESSION['logado']) || ($_SESSION['perfil'] ?? '') !== 'admin') {
        redirect(app_url('auth/login.php'));
    }
}

// ===== REDIRECT =====

/**
 * Redireciona o usuário para a URL especificada e encerra a execução
 *
 * @param string $url A URL de destino do redirecionamento
 * @return void
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}
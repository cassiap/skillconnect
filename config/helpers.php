<?php
/**
 * Funcoes auxiliares do SkillConnect
 * Este arquivo e incluido automaticamente via config/db.php
 */

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

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_validate(): bool {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// ===== FLASH MESSAGES =====

function flash(string $type, string $msg): void {
    $_SESSION["flash_{$type}"] = $msg;
}

function get_flash(string $type): ?string {
    $msg = $_SESSION["flash_{$type}"] ?? null;
    unset($_SESSION["flash_{$type}"]);
    return $msg;
}

// ===== APP URL =====

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

function app_url(string $path = ''): string {
    $base = app_base_path();
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return $base === '' ? '/' . $path : $base . '/' . $path;
}

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

function auth_check(): void {
    if (empty($_SESSION['logado'])) {
        $_SESSION['url_destino'] = $_SERVER['REQUEST_URI'];
        redirect(app_url('auth/login.php'));
    }
}

function admin_check(): void {
    if (empty($_SESSION['logado']) || ($_SESSION['perfil'] ?? '') !== 'admin') {
        redirect(app_url('auth/login.php'));
    }
}

// ===== REDIRECT =====

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

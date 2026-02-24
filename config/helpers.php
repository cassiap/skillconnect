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

// ===== AUTH =====

function auth_check(): void {
    if (empty($_SESSION['logado'])) {
        $_SESSION['url_destino'] = $_SERVER['REQUEST_URI'];
        redirect('../auth/login.php');
    }
}

function admin_check(): void {
    if (empty($_SESSION['logado']) || ($_SESSION['perfil'] ?? '') !== 'admin') {
        redirect('../auth/login.php');
    }
}

// ===== REDIRECT =====

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

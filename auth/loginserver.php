<?php
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';

/* ===== Apenas POST ===== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

/* ===== CSRF opcional ===== */
if (isset($_POST['csrf_token'], $_SESSION['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash_error'] = "Sessão expirada. Tente novamente.";
        header("Location: login.php");
        exit;
    }
}

/* ===== Entrada ===== */
$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    $_SESSION['flash_error'] = "Informe e-mail e senha.";
    header("Location: login.php");
    exit;
}

/* ===== Busca por e-mail ===== */
$sql  = "SELECT id, nome, email, perfil, senha FROM usuarios WHERE email = ?";
$stmt = mysqli_prepare($cx, $sql);
if (!$stmt) {
    $_SESSION['flash_error'] = "Erro interno.";
    header("Location: login.php");
    exit;
}
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

/* ===== Verificação/Migração de senha ===== */
$autenticado = false;
if ($user) {
    $hashArmazenado = $user['senha'];

    if (password_verify($senha, $hashArmazenado)) {
        $autenticado = true;

        if (password_needs_rehash($hashArmazenado, PASSWORD_DEFAULT)) {
            $novoHash = password_hash($senha, PASSWORD_DEFAULT);
            $u = mysqli_prepare($cx, "UPDATE usuarios SET senha=? WHERE id=?");
            if ($u) {
                mysqli_stmt_bind_param($u, "si", $novoHash, $user['id']);
                mysqli_stmt_execute($u);
                mysqli_stmt_close($u);
            }
        }
    } else {
        // compat: se ainda estiver em texto puro, aceita 1x e migra
        $pareceHash = (strlen($hashArmazenado) >= 55) &&
                      (strpos($hashArmazenado, '$2y$') === 0 || strpos($hashArmazenado, '$argon2') === 0);
        if (!$pareceHash && hash_equals($hashArmazenado, $senha)) {
            $autenticado = true;
            $novoHash = password_hash($senha, PASSWORD_DEFAULT);
            $u = mysqli_prepare($cx, "UPDATE usuarios SET senha=? WHERE id=?");
            if ($u) {
                mysqli_stmt_bind_param($u, "si", $novoHash, $user['id']);
                mysqli_stmt_execute($u);
                mysqli_stmt_close($u);
            }
        }
    }
}

if (!$autenticado) {
    $_SESSION['flash_error'] = "E-mail ou senha inválidos.";
    header("Location: login.php");
    exit;
}

/* ===== Sessão segura ===== */
session_regenerate_id(true);
$_SESSION['logado']  = true;
$_SESSION['user_id'] = $user['id'];
$_SESSION['nome']    = $user['nome'];
$_SESSION['usuario'] = $user['nome'];
$_SESSION['email']   = $user['email'];
$_SESSION['perfil']  = $user['perfil'];

/* ===== url_destino (se houver) ===== */
if (!empty($_SESSION['url_destino'])) {
    $dest = $_SESSION['url_destino'];
    unset($_SESSION['url_destino']);
    // se vier relativo, manda pra raiz
    if (stripos($dest, 'http') !== 0) {
        $dest = '/' . ltrim($dest, '/');
    }
    header("Location: $dest");
    ob_end_flush();
    exit;
}

/* ===== Redireciona fixo para a RAIZ =====
   Seu index está em /htdocs/index.php  -> URL: /index.php
   Se tiver dashboard admin (/admin/index.php), descomente abaixo.
*/
$base = '/skillconnect/';

if ($_SESSION['perfil'] === 'admin') {
    header("Location: " . $base . "admin/admin.php");
} else {
    header("Location: " . $base . "index.php");
}

ob_end_flush();
exit;

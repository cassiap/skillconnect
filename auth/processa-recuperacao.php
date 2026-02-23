<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: forgot-password.php");
    exit;
}

if (!csrf_validate()) {
    echo "<script>alert('Sessão expirada. Tente novamente.'); window.location.href='forgot-password.php';</script>";
    exit;
}

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    echo "<script>alert('Informe o e-mail.'); window.location.href='forgot-password.php';</script>";
    exit;
}

// Busca usuario por email
$stmt = $cx->prepare("SELECT id FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $usuario = $result->fetch_assoc();
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt2 = $cx->prepare("INSERT INTO recuperacao_senha (usuario_id, token, expira_em) VALUES (?, ?, ?)");
    $stmt2->bind_param("iss", $usuario['id'], $token, $expira);

    if ($stmt2->execute()) {
        $link = "http://localhost/skillconnect/auth/redefinir-senha.php?token=" . $token;

        $assunto = "Recuperação de Senha - SkillConnect";
        $mensagem = "Olá! Clique no link abaixo para redefinir sua senha:\n\n$link\n\nEste link expira em 1 hora.\n\nSe não foi você, ignore este e-mail.";
        $headers = "From: suporte@skillconnect.com\r\n";
        $headers .= "Content-Type: text/plain; charset=utf-8\r\n";

        mail($email, $assunto, $mensagem, $headers);
    }
}

// Sempre mostra a mesma mensagem (nao revela se o email existe)
echo "<script>alert('Se o e-mail estiver cadastrado, você receberá um link de recuperação.'); window.location.href='login.php';</script>";

<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['logado']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listarclientes.php');
    exit();
}

if (!csrf_validate()) {
    echo "<script>alert('Sessão expirada. Tente novamente.'); window.location.href='listarclientes.php';</script>";
    exit();
}

$id       = intval($_POST['id']);
$nome     = trim($_POST['nome'] ?? '');
$cpf      = trim($_POST['cpf'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email    = trim($_POST['email'] ?? '');

$stmt = $cx->prepare("UPDATE usuarios SET nome=?, cpf=?, telefone=?, email=? WHERE id=?");
$stmt->bind_param("ssssi", $nome, $cpf, $telefone, $email, $id);

if ($stmt->execute()) {
    echo "<script>alert('Usuário atualizado com sucesso!'); window.location.href='listarclientes.php';</script>";
} else {
    echo "<script>alert('Erro ao atualizar usuário.'); window.location.href='listarclientes.php';</script>";
}
$stmt->close();

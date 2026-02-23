<?php
require_once __DIR__ . '/../config/db.php';

admin_check();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: listarclientes.php');
    exit();
}

if (!csrf_validate()) {
    flash('error', 'Sessão expirada. Tente novamente.');
    redirect('listarclientes.php');
}

$id       = intval($_POST['id']);
$nome     = trim($_POST['nome'] ?? '');
$cpf      = trim($_POST['cpf'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$email    = trim($_POST['email'] ?? '');

$stmt = $cx->prepare("UPDATE usuarios SET nome=?, cpf=?, telefone=?, email=? WHERE id=?");
$stmt->bind_param("ssssi", $nome, $cpf, $telefone, $email, $id);

if ($stmt->execute()) {
    $stmt->close();
    flash('success', 'Usuário atualizado com sucesso!');
    redirect('listarclientes.php');
} else {
    $stmt->close();
    flash('error', 'Erro ao atualizar usuário.');
    redirect('listarclientes.php');
}

<?php
require_once __DIR__ . '/../config/db.php';

// Dados do POST
$nome     = trim($_POST['usuario'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$cpf      = preg_replace('/\D/', '', $_POST['cpf_cnpj'] ?? '');
$cep      = trim($_POST['cep'] ?? '');
$estado   = strtoupper(trim($_POST['uf'] ?? ''));
$cidade   = trim($_POST['municipio'] ?? '');
$logradouro = trim($_POST['endereco'] ?? '');
$bairro   = trim($_POST['bairro'] ?? '');
$senha    = $_POST['senha'] ?? '';
$confirmar = $_POST['confirmar_senha'] ?? '';

// Valida CSRF
if (!csrf_validate()) {
    $_SESSION['flash_error'] = "Sessão expirada. Tente novamente.";
    header("Location: ../auth/register.php");
    exit;
}

// Validacoes basicas
if (!$nome || !$email || !$senha) {
    $_SESSION['flash_error'] = "Campos obrigatórios ausentes.";
    header("Location: ../auth/register.php");
    exit;
}
if ($senha !== $confirmar) {
    $_SESSION['flash_error'] = "As senhas não coincidem.";
    header("Location: ../auth/register.php");
    exit;
}
if ($cpf && !preg_match('/^\d{11}$/', $cpf)) {
    $_SESSION['flash_error'] = "CPF inválido.";
    header("Location: ../auth/register.php");
    exit;
}

// Checa duplicidade (email ou cpf)
$stmt = $cx->prepare("SELECT id FROM usuarios WHERE email = ? OR (cpf IS NOT NULL AND cpf = ?)");
$cpfNull = $cpf ?: null;
$stmt->bind_param("ss", $email, $cpfNull);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $_SESSION['flash_error'] = "E-mail ou CPF já cadastrado.";
    header("Location: ../auth/register.php");
    exit;
}
$stmt->close();

// Hash da senha
$hash = password_hash($senha, PASSWORD_DEFAULT);

// Insere no banco
$stmt = $cx->prepare("INSERT INTO usuarios (nome, email, senha, cpf, telefone, cep, logradouro, bairro, cidade, estado, perfil)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'usuario')");
$stmt->bind_param("ssssssssss", $nome, $email, $hash, $cpfNull, $telefone, $cep, $logradouro, $bairro, $cidade, $estado);

if (!$stmt->execute()) {
    $err = $cx->error;
    $stmt->close();
    $_SESSION['flash_error'] = "Erro ao salvar: $err";
    header("Location: ../auth/register.php");
    exit;
}
$newId = $stmt->insert_id;
$stmt->close();

// Login automatico
session_regenerate_id(true);
$_SESSION['logado']  = true;
$_SESSION['user_id'] = $newId;
$_SESSION['nome']    = $nome;
$_SESSION['usuario'] = $nome;
$_SESSION['email']   = $email;
$_SESSION['perfil']  = 'usuario';

header("Location: ../index.php");
exit;

<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$token = $_GET['token'] ?? '';

if (!$token) {
    echo "<script>alert('Token ausente.'); window.location.href='login.php';</script>";
    exit;
}

// Busca token valido (nao usado e nao expirado)
$stmt = $cx->prepare("SELECT r.id, r.usuario_id, r.token, u.email
                       FROM recuperacao_senha r
                       JOIN usuarios u ON u.id = r.usuario_id
                       WHERE r.token = ? AND r.usado = 0 AND r.expira_em > NOW()
                       LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<script>alert('Token inválido, já utilizado ou expirado.'); window.location.href='login.php';</script>";
    exit;
}

$dados = $result->fetch_assoc();
$stmt->close();

// Processa nova senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        echo "<script>alert('Sessão expirada. Recarregue a página.');</script>";
    } else {
    $novaSenha = $_POST['nova_senha'] ?? '';

    if (strlen($novaSenha) < 6) {
        echo "<script>alert('A senha deve ter pelo menos 6 caracteres.');</script>";
    } else {
        $hash = password_hash($novaSenha, PASSWORD_DEFAULT);

        // Atualiza senha
        $stmt1 = $cx->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        $stmt1->bind_param("si", $hash, $dados['usuario_id']);
        $stmt1->execute();
        $stmt1->close();

        // Marca token como usado
        $stmt2 = $cx->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE id = ?");
        $stmt2->bind_param("i", $dados['id']);
        $stmt2->execute();
        $stmt2->close();

        echo "<script>alert('Senha redefinida com sucesso!'); window.location.href='login.php';</script>";
        exit;
    }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-primary">Redefinir Senha</h2>
    <form method="POST">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label>Nova Senha</label>
            <input type="password" name="nova_senha" class="form-control" required minlength="6">
        </div>
        <button type="submit" class="btn btn-success">Salvar nova senha</button>
        <a href="login.php" class="btn btn-secondary ml-2">Cancelar</a>
    </form>
</div>
</body>
</html>

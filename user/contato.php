<?php
require_once __DIR__ . '/../config/db.php';

$flash = ['ok' => null, 'err' => null];

// Processa envio do formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    if (!csrf_validate()) {
        $flash['err'] = 'Sessão expirada. Recarregue a página.';
    } elseif (!$nome || !$email || !$mensagem) {
        $flash['err'] = 'Preencha todos os campos obrigatórios.';
    } else {
        // Salva no banco
        $stmt = $cx->prepare("INSERT INTO contatos (nome, email, mensagem) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $email, $mensagem);

        if ($stmt->execute()) {
            $flash['ok'] = 'Mensagem enviada com sucesso! Entraremos em contato em breve.';
        } else {
            $flash['err'] = 'Erro ao enviar a mensagem. Tente novamente mais tarde.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Contato - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-5">
    <h2 class="mb-4 text-primary">Fale conosco</h2>

    <?php if ($flash['ok']): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($flash['ok']); ?></div>
    <?php endif; ?>
    <?php if ($flash['err']): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($flash['err']); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" class="form-control" required
                   value="<?php echo htmlspecialchars($_SESSION['nome'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" class="form-control" required
                   value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label for="mensagem">Mensagem</label>
            <textarea id="mensagem" name="mensagem" class="form-control" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-success">Enviar mensagem</button>
    </form>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

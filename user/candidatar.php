<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Exige login
if (!isset($_SESSION['logado'])) {
    $_SESSION['url_destino'] = $_SERVER['REQUEST_URI'];
    header('Location: ../auth/login.php');
    exit();
}

$vaga_id = intval($_GET['vaga_id'] ?? $_POST['vaga_id'] ?? 0);
$usuario_id = $_SESSION['user_id'];
$error = '';

// Processamento do formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Sessão expirada. Recarregue a página.';
    } else {
        $vaga_id = intval($_POST['vaga_id'] ?? 0);

        // Verifica candidatura duplicada
        $check = $cx->prepare("SELECT id FROM candidaturas WHERE usuario_id = ? AND vaga_id = ?");
        $check->bind_param("ii", $usuario_id, $vaga_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $check->close();
            echo "<script>alert('Você já se candidatou a esta vaga.'); window.location.href='vagas.php';</script>";
            exit;
        }
        $check->close();

        // Upload do arquivo PDF
        $maxSize = 5 * 1024 * 1024; // 5 MB
        if (isset($_FILES['curriculo']) && $_FILES['curriculo']['error'] === UPLOAD_ERR_OK) {
            // Valida tamanho
            if ($_FILES['curriculo']['size'] > $maxSize) {
                $error = 'Arquivo muito grande. Tamanho máximo: 5 MB.';
            } else {
                $ext = strtolower(pathinfo($_FILES['curriculo']['name'], PATHINFO_EXTENSION));
                // Valida MIME type
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['curriculo']['tmp_name']);

                if ($ext === 'pdf' && $mime === 'application/pdf') {
                    $pasta = __DIR__ . '/../uploads/';
                    if (!is_dir($pasta)) mkdir($pasta, 0755, true);

                    $novoNome = uniqid('curriculo_', true) . '.pdf';
                    $destino  = $pasta . $novoNome;
                    if (move_uploaded_file($_FILES['curriculo']['tmp_name'], $destino)) {
                        $stmt = $cx->prepare("INSERT INTO candidaturas (usuario_id, vaga_id, curriculo_path) VALUES (?, ?, ?)");
                        $stmt->bind_param("iis", $usuario_id, $vaga_id, $novoNome);
                        if ($stmt->execute()) {
                            $stmt->close();
                            header("Location: vagas.php?sucesso=1");
                            exit();
                        } else {
                            $error = 'Erro ao salvar no banco de dados.';
                        }
                        $stmt->close();
                    } else {
                        $error = 'Erro ao mover o arquivo.';
                    }
                } else {
                    $error = 'Formato de arquivo inválido. Envie um PDF válido.';
                }
            }
        } else {
            $error = 'Erro no upload do currículo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Candidatar-se - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-5">
    <h2 class="mb-4 text-primary">Quero me candidatar</h2>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="vaga_id" value="<?php echo $vaga_id; ?>">

        <div class="form-group">
            <label>Nome</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['nome'] ?? ''); ?>" disabled>
        </div>
        <div class="form-group">
            <label>E-mail</label>
            <input type="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" disabled>
        </div>
        <div class="form-group">
            <label for="curriculo">Curriculo (PDF)</label>
            <input type="file" id="curriculo" name="curriculo" class="form-control-file" accept="application/pdf" required>
        </div>
        <button type="submit" class="btn btn-success">Enviar candidatura</button>
        <a href="vaga.php?id=<?php echo $vaga_id; ?>" class="btn btn-secondary ml-2">Cancelar</a>
    </form>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

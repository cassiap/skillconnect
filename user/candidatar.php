<?php
require_once __DIR__ . '/../config/db.php';

auth_check();

$vaga_id = (int) ($_GET['vaga_id'] ?? $_POST['vaga_id'] ?? 0);
$usuario_id = (int) ($_SESSION['user_id'] ?? 0);
$error = '';

if ($vaga_id <= 0) {
    flash('error', 'Vaga invalida.');
    redirect('vagas.php');
}

// Garante que a vaga existe e esta ativa.
$vagaStmt = $cx->prepare("SELECT id, titulo FROM vagas WHERE id = ? AND ativo = 1 LIMIT 1");
$vagaStmt->bind_param("i", $vaga_id);
$vagaStmt->execute();
$vagaRes = $vagaStmt->get_result();
$vaga = $vagaRes->fetch_assoc();
$vagaStmt->close();

if (!$vaga) {
    flash('error', 'A vaga nao foi encontrada ou esta inativa.');
    redirect('vagas.php');
}

// Se ja houver candidatura, evita abrir/submeter novamente.
$jaCandStmt = $cx->prepare("SELECT id FROM candidaturas WHERE usuario_id = ? AND vaga_id = ? LIMIT 1");
$jaCandStmt->bind_param("ii", $usuario_id, $vaga_id);
$jaCandStmt->execute();
$jaCand = $jaCandStmt->get_result()->fetch_assoc();
$jaCandStmt->close();

if ($jaCand) {
    flash('info', 'Voce ja se candidatou a esta vaga. Acompanhe em Minhas candidaturas.');
    redirect('minhas-candidaturas.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Sessao expirada. Recarregue a pagina.';
    } else {
        $maxSize = 5 * 1024 * 1024; // 5 MB
        if (isset($_FILES['curriculo']) && $_FILES['curriculo']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['curriculo']['size'] > $maxSize) {
                $error = 'Arquivo muito grande. Tamanho maximo: 5 MB.';
            } else {
                $ext = strtolower(pathinfo($_FILES['curriculo']['name'], PATHINFO_EXTENSION));
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['curriculo']['tmp_name']);

                if ($ext === 'pdf' && $mime === 'application/pdf') {
                    $pasta = __DIR__ . '/../uploads/';
                    if (!is_dir($pasta)) {
                        mkdir($pasta, 0755, true);
                    }

                    $novoNome = uniqid('curriculo_', true) . '.pdf';
                    $destino = $pasta . $novoNome;

                    if (move_uploaded_file($_FILES['curriculo']['tmp_name'], $destino)) {
                        try {
                            $stmt = $cx->prepare("INSERT INTO candidaturas (usuario_id, vaga_id, curriculo_path) VALUES (?, ?, ?)");
                            $stmt->bind_param("iis", $usuario_id, $vaga_id, $novoNome);
                            $stmt->execute();
                            $stmt->close();

                            flash('success', 'Candidatura enviada com sucesso!');
                            redirect('minhas-candidaturas.php');
                        } catch (mysqli_sql_exception $e) {
                            if ((int) $e->getCode() === 1062) {
                                if (is_file($destino)) {
                                    @unlink($destino);
                                }
                                flash('info', 'Voce ja se candidatou a esta vaga.');
                                redirect('minhas-candidaturas.php');
                            }
                            $error = 'Erro ao salvar candidatura. Tente novamente.';
                        }
                    } else {
                        $error = 'Erro ao mover o arquivo enviado.';
                    }
                } else {
                    $error = 'Formato invalido. Envie um arquivo PDF valido.';
                }
            }
        } else {
            $uploadError = $_FILES['curriculo']['error'] ?? UPLOAD_ERR_NO_FILE;
            switch ($uploadError) {
                case UPLOAD_ERR_NO_FILE:
                    $error = 'Nenhum arquivo foi enviado. Selecione seu curriculo em PDF.';
                    break;
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = 'Arquivo muito grande. Maximo: 5 MB.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = 'Upload interrompido. Tente novamente.';
                    break;
                default:
                    $error = 'Erro interno no upload. Tente novamente.';
                    break;
            }
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
    <h2 class="mb-2 text-primary">Quero me candidatar</h2>
    <p class="text-muted mb-4">Vaga: <?php echo htmlspecialchars($vaga['titulo']); ?></p>

    <?php if ($error !== ''): ?>
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
            <label for="curriculo">Curriculo (PDF, maximo 5MB)</label>
            <input type="file" id="curriculo" name="curriculo" class="form-control-file" accept="application/pdf" required>
        </div>

        <button type="submit" class="btn btn-success">Enviar candidatura</button>
        <a href="vaga.php?id=<?php echo $vaga_id; ?>" class="btn btn-secondary ml-2">Cancelar</a>
    </form>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

<?php
require_once __DIR__ . '/../config/db.php';

$erroLocal = '';
$nome = trim($_POST['nome'] ?? ($_SESSION['nome'] ?? ''));
$email = trim($_POST['email'] ?? ($_SESSION['email'] ?? ''));
$mensagem = trim($_POST['mensagem'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $erroLocal = 'Sessao expirada. Recarregue a pagina.';
    } elseif ($nome === '' || $email === '' || $mensagem === '') {
        $erroLocal = 'Preencha todos os campos obrigatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erroLocal = 'Informe um e-mail valido.';
    } elseif (strlen($mensagem) < 20) {
        $erroLocal = 'Escreva uma mensagem com pelo menos 20 caracteres.';
    } else {
        $stmt = $cx->prepare("INSERT INTO contatos (nome, email, mensagem) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nome, $email, $mensagem);
        if ($stmt->execute()) {
            $stmt->close();
            flash('success', 'Mensagem enviada com sucesso! Retornaremos em breve.');
            redirect('contato.php');
        }
        $stmt->close();
        $erroLocal = 'Nao foi possivel enviar no momento. Tente novamente.';
    }
}

$flashSuccess = get_flash('success');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contato - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        .hero-contact {
            border-radius: 18px;
            background: linear-gradient(130deg, #0f766e 0%, #0e7490 55%, #1d4ed8 100%);
            color: #fff;
            padding: 30px;
        }
        .contact-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
        }
        .info-card {
            border: 1px solid #dbeafe;
            border-radius: 14px;
            background: #f8fafc;
        }
        .counter {
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body class="bg-light">

<?php include('../includes/header.php'); ?>

<div class="container py-4">
    <section class="hero-contact mb-4">
        <h1 class="h3 mb-2"><i class="fas fa-envelope-open-text"></i> Fale com o time SkillConnect</h1>
        <p class="mb-0">Envie duvidas, sugestoes ou feedback sobre cursos, vagas e assistente IA.</p>
    </section>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card contact-card">
                <div class="card-body">
                    <h2 class="h5 mb-3">Formulario de contato</h2>

                    <?php if ($flashSuccess): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
                    <?php endif; ?>
                    <?php if ($erroLocal !== ''): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($erroLocal); ?></div>
                    <?php endif; ?>

                    <form method="POST" id="contactForm" novalidate>
                        <?php echo csrf_field(); ?>
                        <div class="form-group">
                            <label for="nome">Nome</label>
                            <input type="text" id="nome" name="nome" class="form-control" required value="<?php echo htmlspecialchars($nome); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                        <div class="form-group">
                            <label for="mensagem">Mensagem</label>
                            <textarea id="mensagem" name="mensagem" rows="7" class="form-control" required><?php echo htmlspecialchars($mensagem); ?></textarea>
                            <div class="counter mt-1"><span id="charCount">0</span>/1000 caracteres</div>
                            <div id="clientError" class="text-danger small mt-1"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">Enviar mensagem</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card info-card mb-3">
                <div class="card-body">
                    <h3 class="h6 text-primary">Quando usar este canal</h3>
                    <ul class="small pl-3 mb-0">
                        <li>Dificuldades com cadastro ou login</li>
                        <li>Sugestoes para cursos e vagas</li>
                        <li>Feedback sobre o assistente IA</li>
                    </ul>
                </div>
            </div>
            <div class="card info-card">
                <div class="card-body">
                    <h3 class="h6 text-primary">Prazo medio</h3>
                    <p class="small mb-0">Em ambiente academico, o retorno pode ocorrer em ate 2 dias uteis.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
const mensagem = document.getElementById('mensagem');
const charCount = document.getElementById('charCount');
const form = document.getElementById('contactForm');

function updateCounter() {
    const len = (mensagem.value || '').length;
    charCount.textContent = len;
    charCount.style.color = len > 1000 ? '#b91c1c' : '#64748b';
}
updateCounter();
mensagem.addEventListener('input', updateCounter);

form.addEventListener('submit', function(e) {
    const len = (mensagem.value || '').trim().length;
    const clientError = document.getElementById('clientError');
    if (len < 20 || len > 1000) {
        e.preventDefault();
        clientError.textContent = 'A mensagem deve ter entre 20 e 1000 caracteres.';
    } else {
        clientError.textContent = '';
    }
});
</script>

</body>
</html>

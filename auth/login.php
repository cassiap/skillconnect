<?php
require_once __DIR__ . '/../config/helpers.php';

if (!empty($_SESSION['logado'])) {
    redirect('../index.php');
}

$csrf = csrf_token();
$flash_error = get_flash('error');
$flash_info = get_flash('info');
$flash_success = get_flash('success');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top right, #2563eb 0%, #1e3a8a 45%, #0f172a 100%);
            padding: 26px 10px;
        }
        .auth-wrap {
            min-height: calc(100vh - 52px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .auth-box {
            width: 100%;
            max-width: 560px;
            border: 0;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .35);
            overflow: hidden;
        }
        .auth-head {
            background: linear-gradient(135deg, #eff6ff 0%, #ecfeff 100%);
            border-bottom: 1px solid #dbeafe;
            padding: 18px 24px;
        }
        .auth-body {
            background: #fff;
            padding: 26px 24px 22px;
        }
        .pwd-wrap {
            position: relative;
        }
        .pwd-btn {
            position: absolute;
            right: 10px;
            top: 38px;
            border: 0;
            background: transparent;
            color: #64748b;
        }
        .caps {
            display: none;
            color: #b91c1c;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div class="auth-wrap">
    <div class="card auth-box">
        <div class="auth-head">
            <strong class="text-primary">SkillConnect</strong>
            <div class="small text-muted">Acesse sua conta para continuar.</div>
        </div>
        <div class="auth-body">
            <h1 class="h4 mb-1">Entrar</h1>
            <p class="text-muted mb-4">Use seu e-mail e senha.</p>

            <?php if ($flash_error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($flash_error); ?></div>
            <?php endif; ?>
            <?php if ($flash_success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div>
            <?php endif; ?>
            <?php if ($flash_info): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($flash_info); ?></div>
            <?php endif; ?>

            <form method="POST" action="loginserver.php" id="loginForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                    <small class="form-text text-muted">
                        <label class="mb-0"><input type="checkbox" id="rememberEmail"> Lembrar meu e-mail</label>
                    </small>
                </div>

                <div class="form-group pwd-wrap">
                    <label for="senha">Senha</label>
                    <input type="password" name="senha" id="senha" class="form-control" required>
                    <button type="button" class="pwd-btn" id="togglePass" aria-label="Mostrar senha">
                        <i class="far fa-eye"></i>
                    </button>
                    <div id="capsHint" class="caps mt-1">Caps Lock ativo.</div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Entrar</button>
            </form>

            <hr>
            <div class="d-flex justify-content-between flex-wrap">
                <a href="forgot-password.php">Esqueci a senha</a>
                <a href="register.php">Criar conta</a>
            </div>
        </div>
    </div>
</div>

<script>
const email = document.getElementById('email');
const remember = document.getElementById('rememberEmail');
const saved = localStorage.getItem('sc_email');
if (saved) {
    email.value = saved;
    remember.checked = true;
}

document.getElementById('loginForm').addEventListener('submit', function() {
    if (remember.checked) {
        localStorage.setItem('sc_email', email.value.trim());
    } else {
        localStorage.removeItem('sc_email');
    }
});

const pwd = document.getElementById('senha');
const toggle = document.getElementById('togglePass');
const caps = document.getElementById('capsHint');

toggle.addEventListener('click', function() {
    const hidden = pwd.type === 'password';
    pwd.type = hidden ? 'text' : 'password';
    toggle.innerHTML = hidden ? '<i class="far fa-eye-slash"></i>' : '<i class="far fa-eye"></i>';
});

pwd.addEventListener('keyup', function(e) {
    const active = e.getModifierState && e.getModifierState('CapsLock');
    caps.style.display = active ? 'block' : 'none';
});
pwd.addEventListener('blur', function() {
    caps.style.display = 'none';
});
</script>

</body>
</html>

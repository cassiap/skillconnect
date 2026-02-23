<?php
session_start();

// Se já logado, redireciona
if (!empty($_SESSION['logado'])) {
    header('Location: ../index.php');
    exit();
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Flash
$flash_error = $_SESSION['flash_error'] ?? null;
$flash_info  = $_SESSION['flash_info']  ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_info']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Login - SkillConnect</title>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="../css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    body.bg-gradient-primary {
      background: linear-gradient(120deg, #3b82f6 0%, #5c6bc0 100%);
    }
    /* Sidebar visual (oculta no mobile) */
    .login-left {
      background: linear-gradient(180deg, #f9fafb 0%, #f1f5ff 100%);
      border-right: 1px solid #e5e7eb;
      padding: 32px 24px;
    }
    .ad-placeholder {
      border: 1px dashed #cbd5e1; border-radius: 12px;
      min-height: 160px; display:flex; align-items:center; justify-content:center;
      background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
      font-size: 14px; color: #475569; text-align:center; padding: 12px;
    }
    .checklist { padding-left: 18px; }
    .checklist li { margin-bottom: .45rem; }
    .badge-soft { background:#eef3ff; color:#3b5bcc; border-radius:999px; padding:2px 8px; font-size:11px; }

    /* Form pequenos toques */
    .caps-hint { display:none; font-size: 12px; color:#b91c1c; }
    .form-inline-help { font-size: 12px; color:#64748b; }
    .btn-icon {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      border: 0; background: transparent; color:#64748b;
    }
  </style>
</head>
<body class="bg-gradient-primary">

<div class="container">
  <div class="row justify-content-center">
    <div class="col-xl-10 col-lg-12 col-md-10">
      <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-0">
          <div class="row no-gutters">
            <!-- Lado esquerdo (CTA/benefícios) -->
            <div class="col-lg-5 d-none d-lg-block login-left">
              <h5 class="text-primary mb-2">Bem-vindo(a) ao SkillConnect</h5>
              <p class="small text-muted mb-3">
                Acesse sua conta para continuar explorando cursos e oportunidades.
              </p>

              <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                  <h6 class="mb-2">Por que usar o SkillConnect?</h6>
                  <ul class="small checklist">
                    <li>Recomendações <strong>personalizadas</strong> de cursos</li>
                    <li>Histórico e progresso <strong>salvos</strong></li>
                    <li>Vagas e oportunidades <strong>curadas</strong></li>
                  </ul>
                  <span class="badge-soft">Dica</span>
                  <span class="small text-muted">Ative “Lembrar meu e-mail” se for um dispositivo pessoal.</span>
                </div>
              </div>

              <div class="card border-0 shadow-sm">
                <div class="card-body p-2">
                  <div class="ad-placeholder">
                    Espaço para banner<br>300 × 250<br>
                    <small class="text-muted">Insira uma imagem ou script do seu ad server.</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Lado direito (formulário) -->
            <div class="col-lg-7">
              <div class="p-5">
                <div class="text-center">
                  <h1 class="h4 text-gray-900 mb-2">Bem-vindo de volta!</h1>
                  <p class="mb-4">Digite suas credenciais para acessar sua conta</p>
                </div>

                <?php if ($flash_error): ?>
                  <div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div>
                <?php endif; ?>
                <?php if ($flash_info): ?>
                  <div class="alert alert-info"><?= htmlspecialchars($flash_info) ?></div>
                <?php endif; ?>

                <form class="user" method="POST" action="loginserver.php" autocomplete="off" id="loginForm">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

                  <div class="form-group">
                    <input type="email" class="form-control form-control-user" name="email" id="email"
                           placeholder="Digite seu e-mail..." required>
                    <div class="form-inline-help mt-1">
                      <label class="mb-0"><input type="checkbox" id="rememberEmail"> Lembrar meu e-mail</label>
                    </div>
                  </div>

                  <div class="form-group position-relative">
                    <input type="password" class="form-control form-control-user" name="senha" id="senha"
                           placeholder="Digite sua senha" required>
                    <button type="button" class="btn-icon" id="togglePass" aria-label="Mostrar/ocultar senha">
                      <i class="far fa-eye"></i>
                    </button>
                    <div id="capsHint" class="caps-hint mt-1">Caps Lock está ativo.</div>
                  </div>

                  <button type="submit" class="btn btn-primary btn-user btn-block">Login</button>
                </form>

                <hr>
                <div class="text-center">
                  <a class="small" href="forgot-password.php">Esqueceu a senha?</a>
                </div>
                <div class="text-center">
                  <a class="small" href="register.php">Criar uma conta!</a>
                </div>
              </div>
            </div>
          </div><!-- row -->
        </div><!-- card-body -->
      </div><!-- card -->
    </div>
  </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<script>
  // Mostrar/ocultar senha
  const toggle = document.getElementById('togglePass');
  const pwd = document.getElementById('senha');
  toggle.addEventListener('click', () => {
    const isPwd = pwd.type === 'password';
    pwd.type = isPwd ? 'text' : 'password';
    toggle.innerHTML = isPwd ? '<i class="far fa-eye-slash"></i>' : '<i class="far fa-eye"></i>';
    pwd.focus();
  });

  // Aviso de Caps Lock
  const capsHint = document.getElementById('capsHint');
  pwd.addEventListener('keyup', (e) => {
    const caps = e.getModifierState && e.getModifierState('CapsLock');
    capsHint.style.display = caps ? 'block' : 'none';
  });
  pwd.addEventListener('focus', (e) => {
    const caps = e.getModifierState && e.getModifierState('CapsLock');
    capsHint.style.display = caps ? 'block' : 'none';
  });
  pwd.addEventListener('blur', () => capsHint.style.display = 'none');

  // Lembrar e-mail (localStorage)
  const email = document.getElementById('email');
  const remember = document.getElementById('rememberEmail');
  // Carrega se existir
  const saved = localStorage.getItem('sc_email');
  if (saved) { email.value = saved; remember.checked = true; }
  // Salva/limpa ao enviar
  document.getElementById('loginForm').addEventListener('submit', () => {
    if (remember.checked) localStorage.setItem('sc_email', email.value.trim());
    else localStorage.removeItem('sc_email');
  });
</script>
</body>
</html>

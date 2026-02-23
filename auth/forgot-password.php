<?php
session_start();
require_once __DIR__ . '/../config/helpers.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <title>Recuperar Senha - SkillConnect</title>

  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="../css/sb-admin-2.min.css" rel="stylesheet">

  <style>
    body.bg-gradient-primary {
      background: linear-gradient(120deg, #3b82f6 0%, #5c6bc0 100%);
    }
    /* Sidebar (oculta no mobile) */
    .sidebar-panel {
      background: linear-gradient(180deg, #f9fafb 0%, #f1f5ff 100%);
      border-right: 1px solid #e5e7eb;
      padding: 28px 22px;
      min-height: 100%;
    }
    .ad-placeholder {
      border: 1px dashed #cbd5e1; border-radius: 12px;
      min-height: 160px; display:flex; align-items:center; justify-content:center;
      background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
      font-size: 14px; color: #475569; text-align:center; padding: 12px;
    }
    .steps { counter-reset: n; padding-left: 0; list-style: none; }
    .steps li { counter-increment: n; margin: 0 0 .6rem 0; display: flex; align-items: baseline; }
    .steps li::before {
      content: counter(n);
      display:inline-grid; place-items:center;
      width: 22px; height: 22px; margin-right: 8px;
      border-radius: 999px; background:#e0e7ff; color:#374151; font-size: 12px; font-weight: 700;
    }
    .small-muted { font-size:12px; color:#64748b; }
    .btn-spinner {
      display: none; margin-right: .5rem;
      width: 1rem; height: 1rem; border: .15rem solid rgba(255,255,255,.5);
      border-top-color: #fff; border-radius: 50%; animation: spin .75s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
  </style>
</head>

<body class="bg-gradient-primary">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-xl-10 col-lg-12 col-md-10">
      <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-0">
          <div class="row no-gutters">
            <!-- Sidebar (ESQUERDA) -->
            <aside class="col-lg-3 d-none d-lg-block sidebar-panel">
              <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                  <h6 class="text-primary mb-2">Esqueceu sua senha?</h6>
                  <ul class="steps">
                    <li>Digite seu e-mail cadastrado</li>
                    <li>Receba <strong>link de redefinição</strong></li>
                    <li>Crie uma nova senha</li>
                  </ul>
                  <p class="small-muted mb-0">
                    Dica: procure o e-mail também na pasta <em>Spam</em> ou <em>Promoções</em>.
                  </p>
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
            </aside>

            <!-- Conteúdo principal (DIREITA) -->
            <div class="col-lg-9">
              <div class="p-5">
                <div class="text-center">
                  <h1 class="h4 text-gray-900 mb-2">Esqueceu sua Senha?</h1>
                  <p class="mb-4">Acontece! Basta inserir seu e-mail abaixo e enviaremos um link para redefinir sua senha.</p>
                </div>

                <form class="user" method="POST" action="processa-recuperacao.php" id="recoverForm" autocomplete="off">
                  <?php echo csrf_field(); ?>
                  <div class="form-group">
                    <input type="email" class="form-control form-control-user" name="email" id="email"
                           placeholder="Digite seu e-mail..." required>
                    <div class="small-muted mt-1">
                      <label class="mb-0"><input type="checkbox" id="rememberEmail"> Lembrar meu e-mail</label>
                    </div>
                  </div>

                  <button type="submit" class="btn btn-primary btn-user btn-block" id="submitBtn">
                    <span class="btn-spinner" id="btnSpinner"></span>
                    Redefinir Senha
                  </button>
                </form>

                <hr>
                <div class="text-center">
                  <a class="small" href="register.php">Criar uma conta!</a>
                </div>
                <div class="text-center">
                  <a class="small" href="login.php">Já tem uma conta? Login!</a>
                </div>
              </div>
            </div>
          </div><!-- /row -->
        </div><!-- /card-body -->
      </div><!-- /card -->
    </div>
  </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<script>
  // Lembrar e-mail (reutiliza o mesmo storage da tela de login)
  const email = document.getElementById('email');
  const remember = document.getElementById('rememberEmail');
  const saved = localStorage.getItem('sc_email');
  if (saved) { email.value = saved; remember.checked = true; }

  document.getElementById('recoverForm').addEventListener('submit', () => {
    const btn = document.getElementById('submitBtn');
    const spn = document.getElementById('btnSpinner');
    btn.setAttribute('disabled','disabled');
    spn.style.display = 'inline-block';

    if (remember.checked) localStorage.setItem('sc_email', (email.value||'').trim());
    else localStorage.removeItem('sc_email');
  });
</script>
</body>
</html>

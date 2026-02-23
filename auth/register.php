<?php
session_start();
require_once __DIR__ . '/../config/helpers.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Cadastro - SkillConnect</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
  <link href="../css/sb-admin-2.min.css" rel="stylesheet">
  <style>
    body.bg-gradient-primary {
      background: linear-gradient(120deg, #3b82f6 0%, #5c6bc0 100%);
    }

    .autofill-highlight { box-shadow: 0 0 0 3px rgba(66,133,244,.2); transition: box-shadow .6s; }
    .legend-badge { font-size: 11px; padding: 2px 8px; border-radius: 999px; background:#eef3ff; color:#3b5bcc; }

    /* Sidebar estilizada */
    .sidebar-panel {
      background: linear-gradient(180deg, #f9fafb 0%, #f1f5ff 100%);
      border-right: 1px solid #e5e7eb;
      padding: 25px;
      border-top-left-radius: 8px;
      border-bottom-left-radius: 8px;
      min-height: 100%;
    }

    .sidebar-sticky {
      position: sticky;
      top: 1rem;
    }

    .ad-placeholder {
      border: 1px dashed #cbd5e1;
      border-radius: 12px;
      min-height: 200px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
      font-size: 14px;
      color: #475569;
      text-align: center;
      padding: 12px;
    }

    .checklist li { margin-bottom: .4rem; }
    .badge-soft {
      background: #eef3ff;
      color: #3b5bcc;
      border-radius: 999px;
      padding: 2px 8px;
      font-size: 11px;
    }

    @media (min-width: 992px) {
      .card-body>.row {
        align-items: stretch;
      }
    }
  </style>
</head>
<body class="bg-gradient-primary">
<div class="container">
  <div class="card o-hidden border-0 shadow-lg my-5">
    <div class="card-body p-0">
      <div class="row no-gutters">
        <!-- Sidebar (ESQUERDA) -->
        <div class="col-lg-3 d-none d-lg-block sidebar-panel">
          <div class="sidebar-sticky">
            <!-- Card 1: CTA -->
            <div class="card mb-3 border-0 shadow-sm">
              <div class="card-body">
                <h6 class="mb-1 text-primary">Bem-vindo(a) ao SkillConnect</h6>
                <p class="mb-2 small text-muted">Cadastre-se e encontre cursos e oportunidades alinhadas ao seu perfil.</p>
                <ul class="small checklist pl-3">
                  <li>Cadastro <strong>100% gratuito</strong></li>
                  <li>Integração com <strong>Receita Federal</strong></li>
                  <li>Atualização de dados <strong>automática</strong></li>
                </ul>
                <span class="badge-soft">Dica</span>
                <span class="small text-muted">Revise os dados da Receita antes de enviar.</span>
              </div>
            </div>

            <!-- Card 2: Banner -->
            <div class="card mb-3 border-0 shadow-sm">
              <div class="card-body p-2">
                <div class="ad-placeholder">
                  Espaço para banner<br>300 × 250<br>
                  <small class="text-muted">Insira uma imagem ou script do seu ad server.</small>
                </div>
              </div>
            </div>

            <!-- Card 3: Ajuda -->
            <div class="card border-0 shadow-sm">
              <div class="card-body">
                <h6 class="mb-2">Precisa de ajuda?</h6>
                <p class="small text-muted mb-2">Se os dados não aparecerem, verifique o formato:</p>
                <ul class="small checklist pl-3">
                  <li>CPF: 11 dígitos</li>
                  <li>CNPJ: 14 dígitos</li>
                  <li>Use apenas números</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Conteúdo principal -->
        <div class="col-lg-9">
          <div class="p-5">
            <div class="text-center">
              <h1 class="h4 text-gray-900 mb-4">Crie sua conta gratuita!</h1>
            </div>

            <!-- Consulta CPF/CNPJ -->
            <div class="mb-3">
              <div class="form-group d-flex align-items-center">
                <div class="form-check mr-3">
                  <input class="form-check-input" type="radio" name="tipoDoc" id="tipoCPF" value="cpf" checked>
                  <label class="form-check-label" for="tipoCPF">CPF</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="tipoDoc" id="tipoCNPJ" value="cnpj">
                  <label class="form-check-label" for="tipoCNPJ">CNPJ</label>
                </div>
              </div>

              <div class="input-group">
                <input type="text" id="doc" class="form-control" placeholder="Digite CPF/CNPJ (apenas números)">
                <div class="input-group-append">
                  <button class="btn btn-secondary" type="button" id="btnBuscar">Buscar na Receita</button>
                </div>
              </div>
              <small class="text-muted">Dados validados na Receita Federal</small>

              <!-- Resumo automático -->
              <div id="resumoAuto" class="card mt-2 d-none">
                <div class="card-body py-3">
                  <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <strong class="text-primary">Dados validados</strong>
                    <small id="rAtualizadoEm" class="text-muted"></small>
                  </div>
                  <div class="row small">
                    <div class="col-md-12 mb-2">
                      <div class="text-muted">Razão Social / Nome</div>
                      <div id="rRazao" class="font-weight-bold"></div>
                    </div>
                    <div class="col-md-6 mb-2">
                      <div class="text-muted">Telefone</div>
                      <div id="rTelefone" class="font-weight-bold"></div>
                    </div>
                    <div class="col-md-6 mb-2">
                      <div class="text-muted">UF</div>
                      <div id="rUf" class="font-weight-bold"></div>
                    </div>
                    <div class="col-md-6 mb-2">
                      <div class="text-muted">Município</div>
                      <div id="rMunicipio" class="font-weight-bold"></div>
                    </div>
                    <div class="col-md-12 mb-2">
                      <div class="text-muted">Endereço</div>
                      <div id="rEndereco" class="font-weight-bold"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form principal -->
            <form class="user" method="POST" action="../user/incluirsalvarlistar.php" onsubmit="return validarSenha();">
              <?php echo csrf_field(); ?>
              <input type="hidden" name="perfil" value="usuario">
              <input type="hidden" name="tipo_doc" id="tipo_doc">
              <input type="hidden" name="cpf_cnpj" id="cpf_cnpj">

              <fieldset id="grpAuto" class="border rounded p-3 mb-3 d-none">
                <legend class="w-auto px-2 small mb-0">
                  Dados encontrados <span class="legend-badge">preenchido automaticamente</span>
                </legend>
                <div class="form-group">
                  <input type="text" name="usuario" id="nome" class="form-control form-control-user" placeholder="Nome/Razão Social">
                </div>
                <div class="form-group">
                  <input type="text" name="telefone" id="telefone" class="form-control form-control-user" placeholder="Telefone">
                </div>
                <div class="form-group row">
                  <div class="col-sm-6 mb-3 mb-sm-0">
                    <input type="text" name="cep" id="cep" class="form-control form-control-user" placeholder="CEP">
                  </div>
                  <div class="col-sm-6">
                    <input type="text" name="uf" id="uf" class="form-control form-control-user" placeholder="UF">
                  </div>
                </div>
                <div class="form-group">
                  <input type="text" name="municipio" id="municipio" class="form-control form-control-user" placeholder="Município">
                </div>
                <div class="form-group">
                  <input type="text" name="endereco" id="endereco" class="form-control form-control-user" placeholder="Endereço">
                </div>
                <div class="form-group">
                  <input type="text" name="bairro" id="bairro" class="form-control form-control-user" placeholder="Bairro">
                </div>
              </fieldset>

              <fieldset class="border rounded p-3 mb-3">
                <legend class="w-auto px-2 small mb-0">Complete seus dados</legend>
                <div class="form-group">
                  <input type="email" name="email" class="form-control form-control-user" placeholder="E-mail principal" required>
                </div>
                <div class="form-group">
                  <input type="email" name="email_recuperacao" class="form-control form-control-user" placeholder="E-mail de recuperação (opcional)">
                </div>
                <div class="form-group row">
                  <div class="col-sm-6 mb-3 mb-sm-0">
                    <input type="password" name="senha" id="senha" class="form-control form-control-user" placeholder="Senha" required>
                  </div>
                  <div class="col-sm-6">
                    <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control form-control-user" placeholder="Repita a senha" required>
                  </div>
                </div>
              </fieldset>

              <button type="submit" class="btn btn-primary btn-user btn-block">Cadastrar Conta</button>
            </form>

            <hr>
            <div class="text-center"><a class="small" href="forgot-password.php">Esqueceu sua senha?</a></div>
            <div class="text-center"><a class="small" href="login.php">Já tem uma conta? Login!</a></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../vendor/jquery/jquery.min.js"></script>
<script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="../js/sb-admin-2.min.js"></script>

<script>
  function validarSenha() {
    const s = document.getElementById("senha").value;
    const c = document.getElementById("confirmar_senha").value;
    if (s !== c) { alert("As senhas não coincidem!"); return false; }
    return true;
  }

  const onlyDigits = (s) => (s || '').replace(/\D/g, '');
  const highlight = (el) => { if (el) { el.classList.add('autofill-highlight'); setTimeout(() => el.classList.remove('autofill-highlight'), 1200); } };

  document.getElementById('btnBuscar').addEventListener('click', async () => {
    const tipo = document.getElementById('tipoCNPJ').checked ? 'cnpj' : 'cpf';
    const raw = document.getElementById('doc').value;
    const doc = onlyDigits(raw);
    if (!doc) return alert('Informe o CPF/CNPJ.');
    if (tipo === 'cpf' && doc.length !== 11) return alert('CPF deve ter 11 dígitos.');
    if (tipo === 'cnpj' && doc.length !== 14) return alert('CNPJ deve ter 14 dígitos.');

    try {
      const r = await fetch(`api_lookup.php?type=${tipo}&doc=${doc}`);
      const data = await r.json();
      if (!r.ok) return alert(data?.message || 'Erro ao consultar');

      document.getElementById('tipo_doc').value = tipo.toUpperCase();
      document.getElementById('cpf_cnpj').value = doc;
      const m = (id,val)=>{const el=document.getElementById(id);if(el){el.value=val||'';highlight(el);}};
      m('nome',data.nome??data.razao_social);m('telefone',data.telefone);
      m('cep',data.cep);m('uf',data.uf);m('municipio',data.municipio??data.municipio_fiscal);
      m('endereco',data.logradouro??data.endereco);m('bairro',data.bairro);
      document.getElementById('grpAuto').classList.remove('d-none');
      const resumo=document.getElementById('resumoAuto');
      resumo.classList.remove('d-none');
      document.getElementById('rRazao').textContent=data.razao_social??data.nome??'';
      document.getElementById('rTelefone').textContent=data.telefone??'';
      document.getElementById('rUf').textContent=data.uf??'';
      document.getElementById('rMunicipio').textContent=data.municipio??data.municipio_fiscal??'';
      document.getElementById('rEndereco').textContent=[data.logradouro??data.endereco,data.bairro,data.cep].filter(Boolean).join(' • ');
      document.getElementById('rAtualizadoEm').textContent=new Date().toLocaleString('pt-BR');
      document.getElementById('grpAuto').scrollIntoView({behavior:'smooth',block:'start'});
    } catch(e){console.error(e);alert('Falha na consulta. Tente novamente.');}
  });
</script>
</body>
</html>

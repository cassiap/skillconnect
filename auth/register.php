<?php
require_once __DIR__ . '/../config/helpers.php';

if (!empty($_SESSION['logado'])) {
    redirect('../index.php');
}

$flash_error = get_flash('error');
$flash_success = get_flash('success');
$flash_info = get_flash('info');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar Conta - SkillConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top right, #2563eb 0%, #1e3a8a 45%, #0f172a 100%);
            padding: 20px 10px;
        }
        .register-wrap {
            min-height: calc(100vh - 40px);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-box {
            width: 100%;
            max-width: 980px;
            border: 0;
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .35);
            overflow: hidden;
        }
        .register-head {
            background: linear-gradient(135deg, #eff6ff 0%, #ecfeff 100%);
            border-bottom: 1px solid #dbeafe;
            padding: 18px 24px;
        }
        .register-main {
            background: #fff;
            padding: 24px;
        }
        .soft-box {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            background: #fff;
        }
        .status-msg {
            font-size: 13px;
        }
        .auto-highlight {
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .22);
            transition: box-shadow .8s;
        }
        @media (max-width: 991px) {
            .register-main { padding: 18px; }
            .register-wrap { min-height: auto; }
        }
    </style>
</head>
<body>

<div class="register-wrap">
    <div class="card register-box">
        <div class="register-head">
            <strong class="text-primary">Criar conta SkillConnect</strong>
            <div class="small text-muted">Preencha seus dados para comecar.</div>
        </div>
        <div class="register-main">
            <?php if ($flash_error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($flash_error); ?></div>
            <?php endif; ?>
            <?php if ($flash_success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($flash_success); ?></div>
            <?php endif; ?>
            <?php if ($flash_info): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($flash_info); ?></div>
            <?php endif; ?>

            <div class="soft-box p-3 mb-3">
                <label class="small text-muted d-block mb-2">Consulta opcional CPF/CNPJ</label>
                <div class="form-row">
                    <div class="col-md-2">
                        <select id="tipoDoc" class="form-control">
                            <option value="cpf">CPF</option>
                            <option value="cnpj">CNPJ</option>
                        </select>
                    </div>
                    <div class="col-md-7 mt-2 mt-md-0">
                        <input type="text" id="doc" class="form-control" placeholder="Somente numeros">
                    </div>
                    <div class="col-md-3 mt-2 mt-md-0">
                        <button type="button" id="btnBuscar" class="btn btn-outline-primary btn-block">Buscar</button>
                    </div>
                </div>
                <div id="lookupStatus" class="status-msg mt-2 text-muted">Opcional: voce pode preencher manualmente.</div>
            </div>

            <form method="POST" action="../user/incluirsalvarlistar.php" id="registerForm" novalidate>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="perfil" value="usuario">
                <input type="hidden" name="tipo_doc" id="tipo_doc">
                <input type="hidden" name="cpf_cnpj" id="cpf_cnpj">

                <div class="soft-box p-3 mb-3">
                    <h2 class="h6 text-primary mb-3">Dados principais</h2>
                    <div class="form-group">
                        <label for="nome">Nome completo / Razao social</label>
                        <input type="text" id="nome" name="usuario" class="form-control" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="email">E-mail</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="telefone">Telefone</label>
                            <input type="text" id="telefone" name="telefone" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="soft-box p-3 mb-3">
                    <h2 class="h6 text-primary mb-3">Endereco</h2>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" class="form-control">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="uf">UF</label>
                            <input type="text" id="uf" name="uf" maxlength="2" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="municipio">Cidade</label>
                            <input type="text" id="municipio" name="municipio" class="form-control">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="endereco">Endereco</label>
                            <input type="text" id="endereco" name="endereco" class="form-control">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="bairro">Bairro</label>
                            <input type="text" id="bairro" name="bairro" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="soft-box p-3 mb-3">
                    <h2 class="h6 text-primary mb-3">Senha</h2>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="senha">Senha</label>
                            <input type="password" id="senha" name="senha" class="form-control" minlength="8" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="confirmar_senha">Confirmar senha</label>
                            <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" minlength="8" required>
                            <small id="senhaStatus" class="form-text text-muted"></small>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Criar conta</button>
            </form>

            <hr>
            <div class="d-flex justify-content-between flex-wrap">
                <a href="login.php">Ja tenho conta</a>
                <a href="forgot-password.php">Esqueci a senha</a>
            </div>
        </div>
    </div>
</div>

<script>
const docInput = document.getElementById('doc');
const tipoDoc = document.getElementById('tipoDoc');
const statusLookup = document.getElementById('lookupStatus');

function onlyDigits(value) {
    return (value || '').replace(/\D/g, '');
}

function fill(id, value) {
    const el = document.getElementById(id);
    if (!el) return;
    el.value = value || '';
    if (value) {
        el.classList.add('auto-highlight');
        setTimeout(() => el.classList.remove('auto-highlight'), 900);
    }
}

document.getElementById('btnBuscar').addEventListener('click', async function() {
    const tipo = tipoDoc.value === 'cnpj' ? 'cnpj' : 'cpf';
    const doc = onlyDigits(docInput.value);

    if (!doc) {
        statusLookup.className = 'status-msg mt-2 text-danger';
        statusLookup.textContent = 'Informe o CPF/CNPJ.';
        return;
    }
    if (tipo === 'cpf' && doc.length !== 11) {
        statusLookup.className = 'status-msg mt-2 text-danger';
        statusLookup.textContent = 'CPF deve ter 11 digitos.';
        return;
    }
    if (tipo === 'cnpj' && doc.length !== 14) {
        statusLookup.className = 'status-msg mt-2 text-danger';
        statusLookup.textContent = 'CNPJ deve ter 14 digitos.';
        return;
    }

    statusLookup.className = 'status-msg mt-2 text-info';
    statusLookup.textContent = 'Consultando...';

    try {
        const r = await fetch(`api_lookup.php?type=${tipo}&doc=${encodeURIComponent(doc)}`);
        const data = await r.json();
        if (!r.ok) {
            throw new Error(data && data.message ? data.message : 'Falha na consulta.');
        }

        document.getElementById('tipo_doc').value = tipo.toUpperCase();
        document.getElementById('cpf_cnpj').value = doc;
        fill('nome', data.nome || data.razao_social || '');
        fill('telefone', data.telefone || '');
        fill('cep', data.cep || '');
        fill('uf', data.uf || '');
        fill('municipio', data.municipio || data.municipio_fiscal || '');
        fill('endereco', data.logradouro || data.endereco || '');
        fill('bairro', data.bairro || '');

        statusLookup.className = 'status-msg mt-2 text-success';
        statusLookup.textContent = 'Dados preenchidos. Revise antes de enviar.';
    } catch (error) {
        statusLookup.className = 'status-msg mt-2 text-danger';
        statusLookup.textContent = error.message || 'Nao foi possivel consultar.';
    }
});

const senha = document.getElementById('senha');
const confirmar = document.getElementById('confirmar_senha');
const senhaStatus = document.getElementById('senhaStatus');

function validarSenhaClient() {
    if (!senha.value && !confirmar.value) {
        senhaStatus.textContent = '';
        return true;
    }
    if (senha.value.length < 8) {
        senhaStatus.className = 'form-text text-danger';
        senhaStatus.textContent = 'Minimo de 8 caracteres.';
        return false;
    }
    if (senha.value !== confirmar.value) {
        senhaStatus.className = 'form-text text-danger';
        senhaStatus.textContent = 'As senhas nao coincidem.';
        return false;
    }
    senhaStatus.className = 'form-text text-success';
    senhaStatus.textContent = 'Senhas conferem.';
    return true;
}

senha.addEventListener('input', validarSenhaClient);
confirmar.addEventListener('input', validarSenhaClient);

document.getElementById('registerForm').addEventListener('submit', function(e) {
    if (!validarSenhaClient()) {
        e.preventDefault();
    }
});
</script>

</body>
</html>

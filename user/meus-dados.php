<?php
/**
 * PÃ¡gina para gerenciamento dos dados pessoais do usuÃ¡rio
 * 
 * Esta pÃ¡gina permite ao usuÃ¡rio visualizar e editar seus dados pessoais,
 * incluindo informaÃ§Ãµes de contato e endereÃ§o. Requer autenticaÃ§Ã£o.
 * 
 * @author Sistema SkillConnect
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

$usuario_id = $_SESSION['user_id'];
$EMAIL  = $_SESSION['email'];
$PERFIL = $_SESSION['perfil'] ?? 'usuario';

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf_token'];

// Carrega dados do usuario
$dados = ['nome' => '', 'email' => $EMAIL, 'telefone' => '', 'cep' => '', 'estado' => '', 'cidade' => '', 'logradouro' => '', 'bairro' => ''];

$stmt = $cx->prepare("SELECT nome, email, telefone, cep, estado, cidade, logradouro, bairro FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    foreach ($row as $k => $v) {
        if (array_key_exists($k, $dados)) $dados[$k] = $v ?? '';
    }
}
$stmt->close();

// POST (salvar)
$flash = ['ok' => null, 'err' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($CSRF, $_POST['csrf_token'] ?? '')) {
        $flash['err'] = 'SessÃ£o expirada. Recarregue a pÃ¡gina.';
    } else {
        $in = [
            'nome'       => trim($_POST['nome'] ?? ''),
            'telefone'   => trim($_POST['telefone'] ?? ''),
            'cep'        => trim($_POST['cep'] ?? ''),
            'estado'     => trim($_POST['estado'] ?? ''),
            'cidade'     => trim($_POST['cidade'] ?? ''),
            'logradouro' => trim($_POST['logradouro'] ?? ''),
            'bairro'     => trim($_POST['bairro'] ?? ''),
        ];

        if ($in['nome'] === '') {
            $flash['err'] = 'Informe seu nome.';
        } else {
            $stmt = $cx->prepare("UPDATE usuarios SET nome=?, telefone=?, cep=?, estado=?, cidade=?, logradouro=?, bairro=? WHERE id=?");
            $stmt->bind_param("sssssssi", $in['nome'], $in['telefone'], $in['cep'], $in['estado'], $in['cidade'], $in['logradouro'], $in['bairro'], $usuario_id);

            if ($stmt->execute()) {
                $flash['ok'] = 'Dados atualizados com sucesso!';
                foreach ($in as $k => $v) $dados[$k] = $v;
                $_SESSION['nome'] = $dados['nome'];
                $_SESSION['usuario'] = $dados['nome'];
            } else {
                $flash['err'] = 'Erro ao salvar no banco.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Meus Dados - SkillConnect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
    <style>
        body{background:#f5f7fb;}
        .sidebar-sticky{position:sticky; top:1rem;}
        .badge-role{font-size:11px; padding:2px 8px; border-radius:999px; background:#eef3ff; color:#3b5bcc;}
        .card-section{border:1px solid #e5e7eb; border-radius:12px;}
    </style>
</head>
<body>

<?php include('../includes/header.php'); ?>

<div class="container my-4">
    <div class="row">
        <!-- Resumo -->
        <aside class="col-lg-4 mb-3">
            <div class="sidebar-sticky">
                <div class="card shadow-sm mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Meu perfil</h5>
                            <span class="badge-role"><?php echo htmlspecialchars(ucfirst($PERFIL)); ?></span>
                        </div>
                        <hr>
                        <p class="mb-1"><strong>Nome</strong><br><?php echo htmlspecialchars($dados['nome']); ?></p>
                        <p class="mb-1"><strong>E-mail</strong><br><?php echo htmlspecialchars($dados['email']); ?></p>
                        <?php if ($dados['telefone']): ?>
                            <p class="mb-1"><strong>Telefone</strong><br><?php echo htmlspecialchars($dados['telefone']); ?></p>
                        <?php endif; ?>
                        <?php if ($dados['logradouro'] || $dados['bairro'] || $dados['cidade']): ?>
                            <p class="mb-0"><strong>EndereÃ§o</strong><br>
                                <?php echo htmlspecialchars($dados['logradouro']); ?>
                                <?php echo $dados['bairro'] ? ' - ' . htmlspecialchars($dados['bairro']) : ''; ?>
                                <?php echo $dados['cidade'] ? ' - ' . htmlspecialchars($dados['cidade']) : ''; ?>
                                <?php echo $dados['estado'] ? '/' . htmlspecialchars($dados['estado']) : ''; ?>
                                <?php echo $dados['cep'] ? ' - CEP ' . htmlspecialchars($dados['cep']) : ''; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="d-flex">
                    <a href="../index.php" class="btn btn-secondary mr-2">Voltar</a>
                    <a href="../auth/logout.php" class="btn btn-danger ml-auto">Sair</a>
                </div>
                <div class="card shadow-sm mt-3">
                    <div class="card-body py-3">
                        <h6 class="mb-2"><?php echo $PERFIL === 'admin' ? 'Area admin' : 'Meu espaco'; ?></h6>
                        <?php if ($PERFIL === 'admin'): ?>
                            <a class="d-block small mb-1" href="../admin/admin.php"><i class="fas fa-cogs"></i> Painel admin</a>
                            <a class="d-block small mb-1" href="cursos.php"><i class="fas fa-book"></i> Gerenciar cursos</a>
                            <a class="d-block small mb-1" href="vagas.php"><i class="fas fa-briefcase"></i> Gerenciar vagas</a>
                            <a class="d-block small mb-1" href="../admin/candidaturas.php"><i class="fas fa-users"></i> Candidaturas</a>
                            <a class="d-block small" href="../admin/listarclientes.php"><i class="fas fa-user-friends"></i> Usuarios</a>
                        <?php else: ?>
                            <a class="d-block small mb-1" href="meus-cursos.php"><i class="fas fa-book-open"></i> Meus cursos</a>
                            <a class="d-block small mb-1" href="minhas-candidaturas.php"><i class="fas fa-briefcase"></i> Minhas vagas</a>
                            <a class="d-block small" href="meu-curriculo.php"><i class="fas fa-file-pdf"></i> Meu curriculo</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Form -->
        <main class="col-lg-8">
            <div class="card shadow-sm card-section">
                <div class="card-body">
                    <h5 class="mb-3">Editar dados</h5>

                    <?php if ($flash['ok']): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($flash['ok']); ?></div>
                    <?php endif; ?>
                    <?php if ($flash['err']): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($flash['err']); ?></div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($CSRF); ?>">

                        <div class="form-group">
                            <label for="nome">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome"
                                   value="<?php echo htmlspecialchars($dados['nome']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email_display">E-mail (nÃ£o editÃ¡vel)</label>
                            <input type="email" class="form-control" id="email_display"
                                   value="<?php echo htmlspecialchars($dados['email']); ?>" disabled>
                        </div>

                        <div class="form-group">
                            <label for="telefone">Telefone</label>
                            <input type="text" class="form-control" id="telefone" name="telefone"
                                   value="<?php echo htmlspecialchars($dados['telefone']); ?>" placeholder="(DDD) 99999-9999">
                        </div>

                        <div class="form-row">
                            <div class="form-group col-sm-6">
                                <label for="cep">CEP</label>
                                <input type="text" class="form-control" id="cep" name="cep"
                                       value="<?php echo htmlspecialchars($dados['cep']); ?>">
                            </div>
                            <div class="form-group col-sm-6">
                                <label for="estado">Estado</label>
                                <input type="text" class="form-control" id="estado" name="estado"
                                       value="<?php echo htmlspecialchars($dados['estado']); ?>" maxlength="2">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" class="form-control" id="cidade" name="cidade"
                                   value="<?php echo htmlspecialchars($dados['cidade']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="logradouro">Logradouro</label>
                            <input type="text" class="form-control" id="logradouro" name="logradouro"
                                   value="<?php echo htmlspecialchars($dados['logradouro']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="bairro">Bairro</label>
                            <input type="text" class="form-control" id="bairro" name="bairro"
                                   value="<?php echo htmlspecialchars($dados['bairro']); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">Salvar alteraÃ§Ãµes</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

</body>
</html>

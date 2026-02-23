<?php
require_once __DIR__ . '/config/helpers.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>SkillConnect - Início</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php include('includes/header.php'); ?>

<!-- Banner principal -->
<section class="text-center bg-white py-5 border-bottom">
    <div class="container">
        <h1 class="display-4 font-weight-bold text-primary">SkillConnect</h1>
        <p class="lead">Conectando você a cursos profissionalizantes e oportunidades de carreira.</p>
        <a href="user/cursos.php" class="btn btn-success btn-lg mt-3">Ver Cursos</a>
    </div>
</section>

<!-- Seção de destaques -->
<div class="container py-5">
    <div class="row text-center">
        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-body">
                    <i class="fas fa-book fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Cursos Profissionalizantes</h5>
                    <p class="card-text">Desenvolva habilidades que o mercado exige com formações acessíveis e de qualidade.</p>
                    <a href="user/cursos.php" class="btn btn-outline-primary">Explorar</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-body">
                    <i class="fas fa-briefcase fa-3x text-success mb-3"></i>
                    <h5 class="card-title">Vagas de Emprego</h5>
                    <p class="card-text">Acesse oportunidades alinhadas à sua formação e objetivos profissionais.</p>
                    <a href="user/vagas.php" class="btn btn-outline-success">Ver Vagas</a>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-body">
                    <i class="fas fa-user-plus fa-3x text-warning mb-3"></i>
                    <h5 class="card-title">Inscreva-se</h5>
                    <p class="card-text">Participe dos cursos e aumente suas chances de conquistar uma vaga no mercado.</p>
                    <a href="user/cursos.php" class="btn btn-outline-warning">Inscrever-se</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seção de números -->
<div class="bg-white py-5">
    <div class="container text-center">
        <div class="row">
            <div class="col-md-4">
                <h2 class="text-primary font-weight-bold">+150</h2>
                <p>Cursos Disponíveis</p>
            </div>
            <div class="col-md-4">
                <h2 class="text-success font-weight-bold">+350</h2>
                <p>Inscrições Realizadas</p>
            </div>
            <div class="col-md-4">
                <h2 class="text-warning font-weight-bold">+90%</h2>
                <p>Alunos Satisfeitos</p>
            </div>
        </div>
    </div>
</div>

<!-- Cursos em destaque -->
<div class="container py-5">
    <div class="text-center mb-4">
        <h3 class="text-primary font-weight-bold">Cursos em Destaque</h3>
        <p class="text-muted">Veja o que temos de melhor esta semana!</p>
    </div>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">Curso de Desenvolvimento Web</h5>
                    <p class="card-text">Aprenda HTML, CSS, JavaScript e PHP e construa sites modernos do zero.</p>
                    <a href="user/curso.php?id=1" class="btn btn-primary">Saiba Mais</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary">Curso de Excel Avançado</h5>
                    <p class="card-text">Domine técnicas e fórmulas para se destacar em análises de dados e relatórios.</p>
                    <a href="user/curso.php?id=2" class="btn btn-primary">Saiba Mais</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Carrossel de depoimentos -->
<div class="bg-light py-5">
    <div class="container">
        <h4 class="text-center text-primary mb-5">O que nossos alunos dizem</h4>
        <div id="carouselDepoimentos" class="carousel slide" data-ride="carousel" data-interval="5000">
            <div class="carousel-inner">
                <div class="carousel-item active text-center">
                    <blockquote class="blockquote">
                        <p class="mb-4">“Os cursos me ajudaram a conquistar meu primeiro emprego na área de TI!”</p>
                        <footer class="blockquote-footer">Ana Paula, 23 anos</footer>
                    </blockquote>
                </div>
                <div class="carousel-item text-center">
                    <blockquote class="blockquote">
                        <p class="mb-4">“Material excelente e professores muito atenciosos. Super recomendo!”</p>
                        <footer class="blockquote-footer">Carlos Eduardo, 30 anos</footer>
                    </blockquote>
                </div>
                <div class="carousel-item text-center">
                    <blockquote class="blockquote">
                        <p class="mb-4">“Me formei em 3 meses e consegui um estágio na área!”</p>
                        <footer class="blockquote-footer">Juliana Ferreira, 19 anos</footer>
                    </blockquote>
                </div>
            </div>
            <a class="carousel-control-prev" href="#carouselDepoimentos" role="button" data-slide="prev">
                <span class="carousel-control-prev-icon"></span>
            </a>
            <a class="carousel-control-next" href="#carouselDepoimentos" role="button" data-slide="next">
                <span class="carousel-control-next-icon"></span>
            </a>
        </div>
    </div>
</div>

<!-- INTEGRAÇÃO DO GPT SOBRE O PROJETO -->
<div class="container mt-5">
    <h2 class="mb-4">Pergunte algo sobre o Projeto SkillConnect</h2>
    <form method="POST" action="">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <textarea
                name="prompt_usuario"
                class="form-control"
                rows="4"
                placeholder="Descreva sua dúvida sobre o projeto SkillConnect..."
                required><?= isset($_POST['prompt_usuario']) ? htmlspecialchars($_POST['prompt_usuario']) : '' ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Enviar para a IA</button>
    </form>
</div>

<div class="container mt-5">
    <h2 class="mb-4">Resposta da IA (OpenAI) sobre o SkillConnect</h2>
    <?php
    // Captura o texto que o usuário digitou (ou string vazia, se nada foi enviado ainda)
    $promptDoUsuario = "";
    if (isset($_POST['prompt_usuario']) && trim($_POST['prompt_usuario']) !== "") {
        $promptDoUsuario = trim($_POST['prompt_usuario']);
    }

    if ($promptDoUsuario !== "" && csrf_validate()) {
        require_once __DIR__ . '/config/env.php';
        $apiKey = env('OPENAI_API_KEY', '');

        if (str_starts_with($apiKey, 'sk-') && strlen($apiKey) > 30) {
            // Aqui definimos o contexto do projeto SkillConnect para a IA:
            $contextoDoProjeto = "
Você é um assistente especializado em projetos de plataforma de cursos online chamada SkillConnect. 
O SkillConnect é um site que conecta alunos a cursos profissionalizantes e oportunidades de emprego. 
Ele possui:
- Página inicial com banner, cards destacando cursos, vagas de emprego e chamada para inscrição.
- Um carrossel com depoimentos de alunos.
- Área administrativa para gerenciar clientes (nome, CPF, telefone, CEP, e-mail, senha).
- Integração com API da OpenAI para responder perguntas sobre o próprio projeto.

Sempre que o usuário fizer uma pergunta, responda de forma detalhada sobre o funcionamento, design ou lógica do projeto SkillConnect,
sem falar nada sobre você ser uma IA. Simule que você é parte da equipe de desenvolvimento e conhece toda a arquitetura do site.
";

            // Agora montamos as mensagens:
            $messages = [
                ["role" => "system", "content" => $contextoDoProjeto],
                ["role" => "user",   "content" => $promptDoUsuario]
            ];

            $payload = [
                "model"    => "gpt-3.5-turbo",
                "messages" => $messages
            ];

            $ch = curl_init("https://api.openai.com/v1/chat/completions");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    "Content-Type: application/json",
                    "Authorization: Bearer $apiKey"
                ],
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
            ]);

            $rawResponse = curl_exec($ch);
            curl_close($ch);

            $decoded = json_decode($rawResponse);
            if (isset($decoded->error)) {
                echo "<div class='alert alert-danger'>";
                echo "Erro da API: " . htmlspecialchars($decoded->error->message);
                echo "</div>";
            } else {
                $respostaDaIA = $decoded->choices[0]->message->content ?? "Sem resposta.";
                echo "<div class='card border-secondary mb-3'>";
                echo "<div class='card-header bg-secondary text-white'>GPT sobre SkillConnect</div>";
                echo "<div class='card-body'>";
                echo nl2br(htmlspecialchars($respostaDaIA));
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>";
            echo "⚠️ Chave da OpenAI ausente ou inválida.";
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-info'>Digite algo sobre o projeto SkillConnect no campo acima e clique em “Enviar para a IA”.</div>";
    }
    ?>
</div>
<!-- FIM DA INTEGRAÇÃO DO GPT SOBRE O PROJETO SkillConnect -->

<?php include('includes/footer.php'); ?>

</body>
</html>

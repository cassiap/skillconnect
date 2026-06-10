# Documentação Técnica — SkillConnect

## 1. Arquitetura

O SkillConnect é uma aplicação web monolítica em PHP, sem framework MVC formal, com separação por responsabilidade de pastas. Roda sobre Apache (XAMPP local / Railway em produção) com banco de dados MySQL.

```
[Navegador] -> [Apache/PHP] -> [MySQL]
                    |
               [API Anthropic Claude]   (assistente de IA via cURL)
               [API de CEP externa]     (consulta de endereço via cURL)
```

O fluxo é direto: o usuário acessa pelo navegador, o PHP processa a requisição, consulta o banco via `mysqli` e retorna HTML renderizado. Para o assistente de IA, o backend faz uma chamada à API Anthropic Claude via cURL e devolve a resposta ao usuário.

---

## 2. Tecnologias

| Tecnologia | Versão | Uso |
|------------|--------|-----|
| PHP | 8.2+ | Backend e renderização HTML |
| MySQL/MariaDB | 8.0+ | Banco de dados relacional |
| Bootstrap | 4.6 | Interface responsiva |
| cURL (PHP nativo) | — | Integração Anthropic e API de CEP |
| GitHub Actions | — | CI/CD e automação |
| PHPUnit | 11.x | Testes unitários automatizados |
| phpDocumentor | latest | Documentação automática |
| Railway | — | Deploy em nuvem |

---

## 3. Estrutura de Pastas

```
skillconnect/
├── index.php                          # Landing page pública
├── composer.json                      # Dependências PHP (prod + dev)
├── phpunit.xml                        # Configuração da suíte de testes
├── .env.example                       # Variáveis de ambiente de exemplo
├── .htaccess                          # Regras Apache (HTTPS, segurança)
│
├── config/
│   ├── constants.php                  # Constantes de status e perfil
│   ├── db.php                         # Conexão com o banco (mysqli)
│   ├── env.php                        # Leitura de variáveis de ambiente
│   └── helpers.php                    # Funções auxiliares globais
│
├── css/
│   └── skillconnect.css               # Estilos globais da aplicação
│
├── auth/
│   ├── login.php                      # Tela e lógica de login
│   ├── loginserver.php                # Processamento do login
│   ├── register.php                   # Cadastro de usuário
│   ├── registeralterarcliente.php     # Edição de dados do usuário
│   ├── logout.php                     # Encerramento de sessão
│   ├── forgot-password.php            # Solicitação de recuperação de senha
│   ├── processa-recuperacao.php       # Envio do e-mail de recuperação
│   ├── redefinir-senha.php            # Redefinição via token
│   └── api_lookup.php                 # Consulta de CEP via API externa
│
├── user/
│   ├── cursos.php                     # Catálogo de cursos
│   ├── curso.php                      # Detalhe de um curso
│   ├── meu-curso.php                  # Área de aulas do curso inscrito
│   ├── meus-cursos.php                # Cursos inscritos do aluno
│   ├── inscrever.php                  # Inscrição em curso
│   ├── certificado.php                # Certificado de conclusão (HTML imprimível)
│   ├── vagas.php                      # Catálogo de vagas
│   ├── vaga.php                       # Detalhe de uma vaga
│   ├── candidatar.php                 # Candidatura a vaga
│   ├── minhas-candidaturas.php        # Candidaturas do aluno
│   ├── meu-curriculo.php              # Currículo digital
│   ├── meus-dados.php                 # Perfil e dados pessoais
│   ├── assistente.php                 # Assistente de carreira com IA
│   ├── contato.php                    # Formulário de contato
│   └── download_curriculo.php         # Download do currículo em PDF
│
├── admin/
│   ├── admin.php                      # Dashboard administrativo
│   ├── cadastracurso.php              # Cadastro de cursos
│   ├── cadastravaga.php               # Cadastro de vagas
│   ├── candidaturas.php               # Gestão de candidaturas
│   ├── listarclientes.php             # Listagem de usuários
│   ├── alterarclienteserver.php       # Edição de usuário pelo admin
│   └── download_curriculo.php         # Download de currículo pelo admin
│
├── includes/
│   ├── header.php                     # Header compartilhado (navbar + CSS global)
│   └── footer.php                     # Footer compartilhado
│
├── database/
│   └── migrations/
│       ├── 2026-02-24_mvp_aluno.sql              # Schema: módulos, aulas, progresso, currículo
│       ├── 2026-02-24_railway_seed_utf8.sql       # Seed completo com dados iniciais
│       └── 2026-05-22_expiracao_avaliacoes.sql    # Expiração de acesso + avaliações de aulas
│
├── tests/
│   ├── bootstrap.php                  # Setup da suíte PHPUnit
│   └── Unit/
│       ├── ConstantsTest.php          # Testa integridade das constantes de status
│       └── HelpersTest.php            # Testa CSRF, flash, URL helpers, session timeout
│
└── .github/
    └── workflows/
        ├── main.yml                   # Sintaxe PHP + testes PHPUnit
        ├── relatorio.yml              # Relatório de métricas do repositório
        ├── segurança.yml              # Verificação de segredos expostos
        ├── validar-html.yml           # Validação W3C de arquivos HTML
        └── documentacao.yml           # Geração e publicação da documentação
```

---

## 4. Banco de Dados

### Relacionamento entre tabelas

```
usuarios
  |-- inscricoes_cursos --> cursos --> modulos --> aulas
  |         |                                       |
  |   acesso_expira_em                      progresso_aulas
  |                                         avaliacoes_aulas
  |-- candidaturas --> vagas
  |-- curriculos
  |-- contatos
  |-- recuperacao_senha
```

### Tabelas

| Tabela | Descrição |
|--------|-----------|
| `usuarios` | Dados de cadastro, autenticação e perfil |
| `cursos` | Catálogo de cursos (nome, modalidade, nível, valor, prazo de conclusão) |
| `modulos` | Módulos de cada curso (ordem, título) |
| `aulas` | Aulas por módulo (conteúdo, vídeo, material, duração) |
| `progresso_aulas` | Aulas concluídas por aluno |
| `avaliacoes_aulas` | Avaliações de 1–5 estrelas por aula, por aluno |
| `vagas` | Catálogo de vagas (tipo, modalidade, localização, empresa) |
| `candidaturas` | Candidaturas de usuários a vagas |
| `inscricoes_cursos` | Matrículas de usuários em cursos (inclui data de expiração) |
| `curriculos` | Currículo digital (experiências, formação, habilidades) |
| `recuperacao_senha` | Tokens temporários para redefinição de senha |
| `contatos` | Mensagens do formulário de contato |

### Colunas adicionadas (migração 2026-05-22)

| Tabela | Coluna | Tipo | Descrição |
|--------|--------|------|-----------|
| `cursos` | `duracao_dias` | `INT UNSIGNED NULL DEFAULT 180` | Prazo em dias para conclusão a partir da inscrição. NULL = sem prazo. |
| `inscricoes_cursos` | `acesso_expira_em` | `DATETIME NULL` | Data-limite calculada na inscrição com base em `duracao_dias`. |

---

## 5. Arquitetura de Segurança

Esta seção documenta os mecanismos de segurança implementados, seus pontos de aplicação e o raciocínio por trás de cada decisão.

### 5.1 Autenticação e Senhas

**Arquivo:** `auth/loginserver.php`

- Senhas armazenadas com `password_hash($senha, PASSWORD_DEFAULT)` — usa bcrypt por padrão, com custo ajustado automaticamente pelo PHP.
- Verificação via `password_verify()` — resistente a timing attacks por design.
- Ao fazer login com senha válida, o sistema verifica se o hash precisa ser atualizado via `password_needs_rehash()` e aplica rehash transparente se necessário.

### 5.2 Proteção CSRF

**Arquivo:** `config/helpers.php` — funções `csrf_token()`, `csrf_field()`, `csrf_validate()`

Todos os formulários POST incluem um token CSRF gerado com `random_bytes(32)` e armazenado na sessão. Antes de processar qualquer POST, o servidor compara o token enviado com o da sessão usando `hash_equals()` (comparação de tempo constante, evita timing attack).

```
[Formulário HTML] -> csrf_field() embute token como <input hidden>
[Submit POST]     -> csrf_validate() compara com $_SESSION['csrf_token']
[Falha]           -> flash('error', ...) + redirect — nenhum dado processado
```

### 5.3 Controle de Acesso por Sessão

**Arquivo:** `config/helpers.php` — funções `auth_check()`, `admin_check()`, `session_check_timeout()`

Toda página autenticada chama `auth_check()` ou `admin_check()` no início. Essas funções:

1. Chamam `session_check_timeout()` — verifica se a sessão está ativa há mais de `SESSION_TIMEOUT` segundos (1800 s = 30 min). Se expirada, destrói a sessão, exibe aviso e redireciona para login.
2. Verificam `$_SESSION['logado']` e, no caso admin, `$_SESSION['perfil'] === 'admin'`.
3. Se não autenticado, redirecionam para `auth/login.php` com a URL destino salva em `$_SESSION['url_destino']`.

```
[Requisição]
    |
    +-- session_check_timeout()
    |       |-- ativo? → atualiza last_activity
    |       |-- expirado? → destroy + redirect login
    |
    +-- logado? → continua
    +-- não logado? → redirect login
```

Cookie de sessão configurado com: `httponly=true`, `secure=true` (em HTTPS), `samesite=Lax`.

### 5.4 Prevenção de SQL Injection

**Padrão:** todos os arquivos PHP usam queries parametrizadas via `mysqli->prepare()` + `bind_param()`.

```php
// Correto — em todos os arquivos do projeto
$stmt = $cx->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();

// Nunca usado — strings não são interpoladas em queries
// "SELECT * FROM usuarios WHERE email = '{$email}'"  ← não existe no código
```

A única exceção intencional são queries KPI sem entrada do usuário em `painel.php` e `admin/admin.php`, que usam SQL literal por legibilidade.

### 5.5 Prevenção de XSS

**Saída de dados:** todo conteúdo dinâmico exibido ao usuário passa por `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` antes de ser inserido no HTML.

**Headers:** `.htaccess` define `X-Content-Type-Options: nosniff` e `X-Frame-Options: SAMEORIGIN`.

### 5.6 Upload de Arquivos

**Arquivo:** `user/candidatar.php`

Validação de segurança em três camadas antes de mover o arquivo:
1. Verificação de `$_FILES['curriculo']['error'] === UPLOAD_ERR_OK`
2. Limite de tamanho: 5 MB (verificado no PHP, além do limite do PHP.ini)
3. Dupla verificação MIME: extensão do arquivo **e** tipo real via `finfo_file()` — `application/pdf` obrigatório

O arquivo é salvo com nome gerado por `uniqid('curriculo_', true)` para evitar colisões e não expor o nome original.

### 5.7 Recuperação de Senha

**Arquivos:** `auth/forgot-password.php`, `auth/processa-recuperacao.php`, `auth/redefinir-senha.php`

Token gerado com `random_bytes()`, armazenado com expiração na tabela `recuperacao_senha`. O token é de uso único: deletado após uso ou expiração. A resposta ao usuário é sempre a mesma, independente de o e-mail existir ou não (evita enumeração de usuários).

### 5.8 Proteção de Segredos em Produção

- Arquivo `.env` bloqueado pelo `.htaccess` (regra `Deny from all` para `.env*`)
- Variáveis de ambiente lidas via `config/env.php` com fallback para variáveis do sistema (Railway)
- Chave da API Anthropic em `ANTHROPIC_API_KEY` — nunca hardcoded
- CI/CD (`segurança.yml`) verifica a cada push se há `.env` exposto ou credenciais hardcoded no repositório

---

## 6. Fluxos Críticos

### 6.1 Fluxo de Login

```
POST /auth/loginserver.php
  1. Busca usuário pelo e-mail (prepared statement)
  2. password_verify() contra hash armazenado
  3. Se OK: rehash se necessário, cria sessão, redireciona
  4. Se falha: flash('error', ...) + redirect para login
```

### 6.2 Fluxo de Inscrição em Curso

```
GET  /user/inscrever.php?curso_id=X  → exibe confirmação
POST /user/inscrever.php
  1. csrf_validate()
  2. Verifica se curso existe e está ativo
  3. Verifica inscrição duplicada
  4. INSERT com acesso_expira_em = NOW() + duracao_dias
  5. Captura erro 1062 (duplicate) → flash info
```

### 6.3 Fluxo de Candidatura a Vaga

```
POST /user/candidatar.php
  1. csrf_validate()
  2. Verifica vaga ativa + candidatura duplicada
  3. Valida arquivo: UPLOAD_ERR_OK + tamanho ≤5MB + MIME=application/pdf
  4. move_uploaded_file() para /uploads/
  5. INSERT candidatura com nome do arquivo gerado
  6. Captura erro 1062 (duplicate) → flash info + remove arquivo
```

### 6.4 Fluxo do Assistente de IA

```
POST /user/assistente.php
  1. csrf_validate()
  2. Carrega contexto: catálogo de cursos/vagas do banco
  3. Carrega perfil real do aluno: cursos+progresso, currículo, candidaturas
  4. Monta system prompt com contexto + perfil + objetivo selecionado
  5. cURL para https://api.anthropic.com/v1/messages
  6. Tenta modelos em fallback (ANTHROPIC_MODEL env var)
  7. Renderiza resposta Markdown como HTML seguro
```

---

## 7. Assistente de IA

**Arquivo:** `user/assistente.php`

Integra a API **Anthropic Claude** (não OpenAI) via cURL. O assistente é personalizado com dados reais do aluno:

- Cursos inscritos e percentual de progresso
- Título profissional e habilidades do currículo
- Candidaturas em aberto

**Variáveis de ambiente necessárias:**

| Variável | Obrigatória | Descrição |
|----------|-------------|-----------|
| `ANTHROPIC_API_KEY` | Sim | Chave de API da Anthropic |
| `ANTHROPIC_MODEL` | Não | Modelo a usar (padrão: `claude-3-5-sonnet-latest`) |

Suporta múltiplos modelos em fallback (lista separada por vírgula em `ANTHROPIC_MODEL`).

---

## 8. GitHub Actions (CI/CD)

> Pipelines de CI/CD implementadas para garantir qualidade, segurança e conformidade do projeto.

---

**`main.yml`:** verifica sintaxe PHP (`php -l`) em todos os arquivos `.php` e executa a suíte de testes unitários com PHPUnit. Roda a cada push e pull request na `main`.

**`relatorio.yml`:** conta arquivos por extensão e linhas de código.

**`segurança.yml`:** verifica presença de `.env` exposto no repositório e senhas hardcoded.

**`validar-html.yml`:** valida os arquivos `.html` do projeto com W3C.

**`documentacao.yml`:** executa o phpDocumentor e publica o resultado no GitHub Pages a cada push.
Disponível em: https://cassiap.github.io/skillconnect/

---

## 9. Testes Automatizados

A suíte de testes usa **PHPUnit 11** (instalado via `composer install --dev`).

### Executar localmente

```bash
composer install
vendor/bin/phpunit --testdox
```

### Cobertura atual

| Arquivo de Teste | O que cobre |
|------------------|-------------|
| `tests/Unit/ConstantsTest.php` | Integridade de todos os 10 valores de constantes de status e perfil. Protege contra renomeações que quebrariam queries SQL. |
| `tests/Unit/HelpersTest.php` | Geração e validação de token CSRF; mensagens flash (set/get/consume); geração de URLs; constante SESSION_TIMEOUT; caminho não-expirado de session_check_timeout. |

### O que não é testado unitariamente (e por quê)

| Funcionalidade | Razão |
|----------------|-------|
| Queries ao banco (inscrição, candidatura, progresso) | Dependem de banco real — pertencem a testes de integração |
| `redirect()` | Chama `exit` — mata o processo de teste. Testado implicitamente via testes E2E manuais |
| `auth_check()` / `admin_check()` no caminho de falha | Chamam `redirect()` |
| Chamada à API Anthropic | Requer chave externa + rede |

### Testes manuais realizados

- Cadastro e login de usuário aluno
- Inscrição em curso e acesso às aulas com expiração
- Avaliação de aulas com 1–5 estrelas
- Marcação de progresso e auto-conclusão de curso
- Geração e impressão de certificado
- Candidatura a vaga e listagem na área do aluno
- Preenchimento e download de currículo em PDF
- Cadastro de cursos e vagas pelo admin
- Recuperação de senha via token
- Assistente de IA com personalização por perfil

---

## 10. Deploy no Railway

### Passo a passo

1. Conectar o repositório GitHub ao Railway
2. Criar serviço MySQL e vincular ao serviço web
3. Configurar variáveis de ambiente (ver `.env.example`)
4. Importar o schema e seed:
   ```
   database/migrations/2026-02-24_mvp_aluno.sql
   database/migrations/2026-02-24_railway_seed_utf8.sql
   database/migrations/2026-05-22_expiracao_avaliacoes.sql
   ```

### Variáveis obrigatórias em produção

```
MYSQLHOST=
MYSQLUSER=
MYSQLPASSWORD=
MYSQLDATABASE=
MYSQLPORT=
ANTHROPIC_API_KEY=
APP_URL=https://seu-dominio
```

---

## 11. PHPDoc

Todos os arquivos PHP do projeto contêm docblocks no padrão PHPDoc com `@param`, `@return` e descrição de comportamento. Funções que têm comportamentos não óbvios (tratamento de falha silenciosa, uso de `hash_equals` para timing-safe comparison, etc.) incluem comentários explicativos do *porquê*, não do *o quê*.

A documentação gerada automaticamente pelo phpDocumentor está publicada em:
**https://cassiap.github.io/skillconnect/**

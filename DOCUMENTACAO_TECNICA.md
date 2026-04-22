# Documentação Técnica — SkillConnect

## 1. Arquitetura

O SkillConnect é uma aplicação web monolítica em PHP, sem framework MVC formal, com separação por responsabilidade de pastas. Roda sobre Apache (XAMPP local / Railway em produção) com banco de dados MySQL.

```
[Navegador] -> [Apache/PHP] -> [MySQL]
                    |
               [API OpenAI]   (assistente de IA via cURL)
```

O fluxo é simples: o usuário acessa pelo navegador, o PHP processa a requisição e consulta o banco via `mysqli`, e o HTML é retornado. Para o assistente de IA, o backend faz uma chamada à API OpenAI via cURL e devolve a resposta ao usuário.

---

## 2. Tecnologias

| Tecnologia | Versão | Uso |
|------------|--------|-----|
| PHP | 8.2+ | Backend e renderização |
| MySQL/MariaDB | 8.0+ | Banco de dados |
| Bootstrap | 4.6 | Interface responsiva |
| cURL (PHP) | nativo | Integração OpenAI |
| GitHub Actions | - | CI/CD e automação |
| phpDocumentor | latest | Documentação automática |
| Railway | - | Deploy em nuvem |

---

## 3. Estrutura de Pastas

```
skillconnect/
├── index.php                        # Landing page pública
├── composer.json                    # Dependências PHP
├── .env.example                     # Variáveis de ambiente de exemplo
├── .htaccess                        # Regras Apache
│
├── config/
│   ├── db.php                       # Conexão com o banco (mysqli)
│   ├── env.php                      # Leitura de variáveis de ambiente
│   └── helpers.php                  # Funções auxiliares globais
│
├── auth/
│   ├── login.php                    # Tela e lógica de login
│   ├── loginserver.php              # Processamento do login
│   ├── register.php                 # Cadastro de usuário
│   ├── registeralterarcliente.php   # Edição de dados do usuário
│   ├── logout.php                   # Encerramento de sessão
│   ├── forgot-password.php          # Solicitação de recuperação de senha
│   ├── processa-recuperacao.php     # Envio do e-mail de recuperação
│   ├── redefinir-senha.php          # Redefinição via token
│   └── api_lookup.php               # Consulta de CEP via API externa
│
├── user/
│   ├── cursos.php                   # Catálogo de cursos
│   ├── curso.php                    # Detalhe de um curso
│   ├── meu-curso.php                # Área de aulas do curso inscrito
│   ├── meus-cursos.php              # Cursos do aluno
│   ├── inscrever.php                # Inscrição em curso
│   ├── vagas.php                    # Catálogo de vagas
│   ├── vaga.php                     # Detalhe de uma vaga
│   ├── candidatar.php               # Candidatura a vaga
│   ├── minhas-candidaturas.php      # Candidaturas do aluno
│   ├── meu-curriculo.php            # Currículo digital
│   ├── meus-dados.php               # Perfil e dados pessoais
│   ├── assistente.php               # Chat com assistente de IA
│   ├── contato.php                  # Formulário de contato
│   └── download_curriculo.php       # Download do currículo em PDF
│
├── admin/
│   ├── admin.php                    # Dashboard administrativo
│   ├── cadastracurso.php            # Cadastro de cursos
│   ├── cadastravaga.php             # Cadastro de vagas
│   ├── candidaturas.php             # Gestão de candidaturas
│   ├── listarclientes.php           # Listagem de usuários
│   ├── alterarclienteserver.php     # Edição de usuário pelo admin
│   └── download_curriculo.php       # Download de currículo pelo admin
│
├── includes/
│   ├── header.php                   # Header compartilhado
│   └── footer.php                   # Footer compartilhado
│
├── database/
│   └── migrations/
│       ├── 2026-02-24_mvp_aluno.sql           # Schema: módulos, aulas, progresso, currículo
│       └── 2026-02-24_railway_seed_utf8.sql   # Seed completo com dados iniciais
│
└── .github/
    └── workflows/
        ├── main.yml                 # Verificação de sintaxe PHP
        ├── relatorio.yml            # Relatório de métricas
        ├── segurança.yml            # Verificação de segurança
        ├── validar-html.yml         # Validação HTML
        └── documentacao.yml         # Geração e publicação da documentação
```

---

## 4. Banco de Dados

### Relacionamento entre tabelas

```
usuarios
  |-- inscricoes_cursos --> cursos --> modulos --> aulas
  |                                       |
  |                               progresso_aulas
  |-- candidaturas --> vagas
  |-- curriculos
  |-- contatos
  |-- recuperacao_senha
```

### Tabelas

| Tabela | Descrição |
|--------|-----------|
| `usuarios` | Dados de cadastro, autenticação e perfil |
| `cursos` | Catálogo de cursos (nome, modalidade, nível, valor) |
| `modulos` | Módulos de cada curso (ordem, título) |
| `aulas` | Aulas por módulo (conteúdo, vídeo, material, duração) |
| `progresso_aulas` | Aulas concluídas por aluno |
| `vagas` | Catálogo de vagas (tipo, modalidade, localização, empresa) |
| `candidaturas` | Candidaturas de usuários a vagas |
| `inscricoes_cursos` | Matrículas de usuários em cursos |
| `curriculos` | Currículo digital (experiências, formação, habilidades) |
| `recuperacao_senha` | Tokens temporários para redefinição de senha |
| `contatos` | Mensagens do formulário de contato |

---

## 5. Autenticação e Segurança

- Senhas armazenadas com `password_hash()` (bcrypt) e verificadas com `password_verify()`
- Controle de sessão via sessões PHP nativas
- Recuperação de senha por token único com expiração
- Pasta `uploads/` protegida por `.htaccess` contra acesso direto
- Workflow de CI verifica `.env` exposto e senhas hardcoded a cada push

---

## 6. Assistente de IA

O arquivo `user/assistente.php` monta o histórico da conversa e envia para a API OpenAI via cURL, devolvendo a resposta ao usuário em formato de chat.

Variáveis necessárias:
- `OPENAI_API_KEY` (obrigatória)
- `OPENAI_MODEL` (opcional, padrão `gpt-3.5-turbo`)

---

## 7. GitHub Actions (CI/CD)

**`main.yml`:** executa `php -l` em todos os arquivos `.php` a cada push na `main`.

**`relatorio.yml`:** conta arquivos por extensão e linhas de código. Útil para acompanhar o crescimento do projeto.

**`segurança.yml`:** verifica presença de `.env` exposto no repositório e senhas hardcoded no código.

**`validar-html.yml`:** valida os arquivos `.html` do projeto.

**`documentacao.yml`:** executa o phpDocumentor e publica o resultado no GitHub Pages a cada push.
Disponível em: https://cassiap.github.io/skillconnect/

---

## 8. Docblocks

Todos os arquivos PHP do projeto têm docblocks no padrão PHPDoc, com descrição de funções, parâmetros (`@param`), retorno (`@return`) e exceções (`@throws`). Os docblocks foram adicionados com um script Python que usou a API da Anthropic para gerar e inserir a documentação automaticamente nos 37 arquivos PHP do projeto.

---

## 9. Deploy no Railway

1. Conectar o repositório GitHub ao Railway
2. Criar serviço MySQL e vincular ao serviço web
3. Configurar variáveis de ambiente (ver `.env.example`)
4. Importar o seed: `database/migrations/2026-02-24_railway_seed_utf8.sql`

Variáveis obrigatórias em produção:

```
MYSQLHOST=
MYSQLUSER=
MYSQLPASSWORD=
MYSQLDATABASE=
MYSQLPORT=
OPENAI_API_KEY=
APP_URL=https://seu-dominio
```

---

## 10. Testes

Os testes estão documentados em [`TESTES.md`](../TESTES.md) e no histórico de execuções dos workflows no GitHub Actions.

**Testes manuais realizados:**
- Cadastro e login de usuário aluno
- Inscrição em curso e acesso às aulas
- Candidatura a vaga e listagem na área do aluno
- Preenchimento e download de currículo em PDF
- Cadastro de cursos e vagas pelo admin
- Recuperação de senha via token
- Funcionamento do assistente de IA

**Testes automatizados (CI):**
- Sintaxe PHP: todos os arquivos passaram na verificação `php -l`
- Validação HTML: arquivos `.html` validados
- Segurança: nenhum `.env` exposto ou credencial hardcoded detectada

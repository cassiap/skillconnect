# SkillConnect

SkillConnect é uma plataforma web desenvolvida em PHP que conecta pessoas a oportunidades de desenvolvimento profissional. O sistema reúne cursos profissionalizantes, vagas de emprego e estágio, e um assistente de inteligência artificial para orientação de carreira.

---

## Integrantes do Grupo

| Nome | Matrícula |
|------|-----------|
| Cássia Gabriela | 22252157 |
| Arthur Gomes Figueira | 22250160 |
| Brenno Jonas Brito de Miranda Queiros | 22205163|

**Orientador:** Prof. Tiago Leite
**Instituição:** Centro Universitário de Brasília (CEUB)
**Curso:** Ciência da Computação, 8º Semestre
**Data:** Abril de 2026

---

## Descrição Geral

O sistema tem dois perfis: **aluno** (cadastro, inscrição em cursos, candidatura a vagas, currículo digital) e **administrador** (gestão de cursos, vagas, candidaturas e usuários).

Funcionalidades principais:

- Catálogo de cursos profissionalizantes com informações de modalidade, nível e custo
- Catálogo de vagas de emprego e estágio com filtros por tipo e localização
- Currículo digital com exportação em PDF
- Assistente de IA para orientação de carreira e empregabilidade
- Painel administrativo completo

---

## Documentação

Toda a documentação do projeto está na pasta [`docs/`](./docs/):

- [`docs/RESUMO_EXECUTIVO.md`](./docs/RESUMO_EXECUTIVO.md)
- [`docs/DOCUMENTACAO_TECNICA.md`](./docs/DOCUMENTACAO_TECNICA.md)
- [`docs/DOCUMENTACAO_NEGOCIAL.md`](./docs/DOCUMENTACAO_NEGOCIAL.md)

Documentação PHP gerada automaticamente:
🔗 https://cassiap.github.io/skillconnect/

---

## Como Executar (XAMPP local)

1. Clonar o repositório em `c:\xampp\htdocs\skillconnect`
2. Criar o banco de dados `skillconnect` no MySQL
3. Importar o seed:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root skillconnect < c:\xampp\htdocs\skillconnect\database\migrations\2026-02-24_railway_seed_utf8.sql
```

4. Copiar `.env.example` para `.env` e ajustar as variáveis de conexão
5. Acessar `http://localhost/skillconnect`

### Deploy no Railway

O projeto está configurado para Railway com variáveis de ambiente automáticas (`MYSQLHOST`, `MYSQLUSER`, `MYSQLPASSWORD`, `MYSQLDATABASE`, `MYSQLPORT`). Para o assistente de IA, configurar `OPENAI_API_KEY`.

---

## Tech Stack

- PHP 8.2+
- MySQL/MariaDB via `mysqli`
- Bootstrap 4
- cURL para integração com a API OpenAI
- GitHub Actions para CI/CD e documentação automática

---

## Estrutura do Projeto

```
skillconnect/
├── index.php                  # Landing page
├── config/                    # Conexão, env e helpers
├── auth/                      # Login, cadastro, recuperação de senha
├── user/                      # Páginas do aluno
├── admin/                     # Painel administrativo
├── includes/                  # Header e footer compartilhados
├── database/migrations/       # Scripts SQL
├── docs/                      # Documentação técnica e negocial
└── .github/workflows/         # GitHub Actions
```

---
## Melhorias Implementadas Após os Feedbacks

Durante o desenvolvimento do projeto, a equipe analisou os feedbacks recebidos pelo professor e identificou oportunidades de melhoria para tornar a plataforma mais completa e próxima de um ambiente real de ensino e empregabilidade.

### Funcionalidades adicionadas

* Inclusão de vídeos nas aulas para enriquecer a experiência de aprendizagem;
* Sistema de avaliação de cursos pelos alunos;
* Sistema de avaliação individual das aulas;
* Controle de ativação e desativação de aulas pelo administrador;
* Acompanhamento do progresso dos alunos ao longo dos cursos;
* Emissão automática de certificados após a conclusão dos cursos;
* Funcionalidade para cancelamento ou saída de cursos;
* Melhorias de interface e usabilidade em diferentes páginas da plataforma.

### Evolução do Projeto

As melhorias implementadas permitiram transformar o SkillConnect em uma solução mais completa, oferecendo não apenas cursos e oportunidades profissionais, mas também recursos de acompanhamento, avaliação e certificação, aumentando a qualidade da experiência dos usuários e a capacidade de gestão dos administradores.
## Evidências de Desenvolvimento

As evidências de desenvolvimento individual podem ser verificadas através dos seguintes elementos presentes no repositório:

* Histórico de commits dos integrantes;
* Branches utilizadas durante o desenvolvimento;
* Pull Requests;
* Issues registradas;
* Documentação de evolução do projeto;
* Registros de implementação das funcionalidades.

Também foram adicionadas evidências visuais na documentação do projeto demonstrando as principais funcionalidades implementadas e as melhorias realizadas após os feedbacks recebidos.


## GitHub Actions

| Workflow | Arquivo | O que faz |
|----------|---------|-----------|
| Verificar PHP | `main.yml` | Checa sintaxe de todos os `.php` |
| Relatório do Projeto | `relatorio.yml` | Conta arquivos e linhas de código |
| Verificação de Segurança | `segurança.yml` | Verifica `.env` exposto e senhas hardcoded |
| Validar HTML | `validar-html.yml` | Valida arquivos `.html` |
| Gerar Documentação | `documentacao.yml` | Publica doc PHP no GitHub Pages |

---

## 1. Verificar PHP - main.yml

## O que é:

Um pipeline de qualidade de código. Ele instala o PHP numa máquina virtual Ubuntu e executa o comando php -1 em todos os arquivos .php do projeto, verificando se há erros de sintaxe.
Por que é importante:
Garante que nenhum código quebrado seja enviado para o repositório. Se você esquecer de fechar uma função ou errar um símbolo, o pipeline avisa na hora.
Resultado:

- ✅ Nenhum erro de sintaxe nos arquivos PHP
- ❌ Erro de sintaxe detectado — precisa corrigir antes de continuar

---

## 2. Relatório do Projeto — relatorio.yml
## O que é:

Um pipeline de rastreabilidade. A cada push ele gera automaticamente um relatório no log com informações do projeto como: quem fez o commit, data e hora, quantos arquivos .php, .html, .css e .js existem, total de linhas de código e tamanho do repositório.
## Por que é importante:
Permite acompanhar o crescimento do projeto ao longo do tempo e registrar quem fez cada alteração.
Resultado:

- ✅ Relatório gerado com sucesso a cada push
- Nunca falha,apenas exibe as métricas do projeto

---

## 3. Verificação de Segurança — seguranca.yml
## O que é:

Um pipeline de segurança. Ele faz uma varredura em todos os arquivos do projeto procurando três tipos de vulnerabilidades comuns que desenvolvedores iniciantes costumam cometer por descuido.
## O que verifica:

| Verificação | Exemplo do problema |
|-------------|---------------------|
| Arquivo .env |  Subir senhas e configurações sensíveis sem querer |
| Senhas fracas | senha123, password123, admin123 no código |
| Chaves de API | Tokens de acesso expostos publicamente |

## Por que é importante:
É muito comum subir acidentalmente dados sensíveis para o GitHub. Essa action age como um guarda de segurança que barra isso antes que vire um problema.
Resultado:

- ✅ Nenhuma vulnerabilidade encontrada
- ❌ Dado sensível detectado — requer ação imediata

---

## 4. Validar HTML — validar-html.yml
## O que é:
Um pipeline de conformidade web. Ele instala a ferramenta html-validate e verifica se todos os arquivos .html do projeto seguem os padrões da W3C.
## O que detecta:
| Problema | Exemplo |
|----------|---------|
| Tag não fechada | `<div>` sem `</div>` |
| Atributo obrigatório ausente | `<img>` sem alt |
| Tags obsoletas | `<center>`, `<font>` | 
| Link inválido | `<a>` sem `href` |

## Por que é importante:
HTML mal escrito pode causar problemas de exibição em diferentes navegadores e prejudicar a acessibilidade do site.
Resultado:

- ✅ Todos os arquivos HTML válidos
- ❌ Erros de estrutura ou acessibilidade encontrados
---




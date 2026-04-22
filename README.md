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

## GitHub Actions

| Workflow | Arquivo | O que faz |
|----------|---------|-----------|
| Verificar PHP | `main.yml` | Checa sintaxe de todos os `.php` |
| Relatório do Projeto | `relatorio.yml` | Conta arquivos e linhas de código |
| Verificação de Segurança | `segurança.yml` | Verifica `.env` exposto e senhas hardcoded |
| Validar HTML | `validar-html.yml` | Valida arquivos `.html` |
| Gerar Documentação | `documentacao.yml` | Publica doc PHP no GitHub Pages |

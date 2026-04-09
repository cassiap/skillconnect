# SkillConnect

- Skill Connect é uma plataforma web desenvolvida em PHP que tem como objetivo conectar pessoas a oportunidades de desenvolvimento profissional. O sistema reúne cursos profissionalizantes, divulgação de vagas de emprego e um assistente de inteligência artificial que auxilia os usuários com orientações de carreira e crescimento no mercado de trabalho.

## Visao geral

O projeto possui dois perfis principais:

- Usuario/aluno: cadastro, login, inscricao em cursos, candidatura em vagas, area de curriculo e area de aulas.
- Admin: gerenciamento de dados da plataforma (vagas, cursos, candidaturas e usuarios).

## Objetivo da Plataforma

- O objetivo do Skill Connect é facilitar o acesso à qualificação profissional e ao mercado de trabalho por meio de uma plataforma digital que reúne cursos profissionalizantes, oportunidades de emprego e um assistente de inteligência artificial voltado para orientação de carreira e desenvolvimento profissional.

## Funcionalidades Gerais

- Acesso a curso profissionalizantes : tendo a liberdade de visualizar a modalidade ,  nível do curso , e custo total.
- Acesso a vagas de emprego/estágio: tipo de vagas de emprego, modalidade(Híbrido ou presencial), e sua localização .
- Plano de carreira 
- Assistente de IA para carreira e empregabilidade : plano prático para estudar melhor , melhorar currículo e buscar vagas alinhadas ao seu perfil.

## Tech Stack

- PHP 8.2+
- MySQL/MariaDB
- mysqli (obrigatorio)
- Bootstrap 4
- cURL (assistente IA)

## Estrutura principal

- `index.php`: landing/home.
- `config/`: conexao, env e helpers globais.
- `auth/`: login, cadastro, recuperacao de senha.
- `user/`: paginas do aluno (cursos, vagas, assistente, curriculo, etc).
- `admin/`: paginas administrativas.
- `includes/`: header/footer compartilhados.
- `database/migrations/`: scripts SQL de schema/dados.
- `models`: botões,cartões,gráficos,cores.
- `uploads`: curriculo.
## Banco de dados (tabelas esperadas)

- `usuarios`
- `cursos`
- `vagas`
- `candidaturas`
- `inscricoes_cursos`
- `recuperacao_senha`
- `contatos`
- `modulos`
- `aulas`
- `progresso_aulas`
- `curriculos`

## Setup local (XAMPP)

1. Clonar projeto em `c:\xampp\htdocs\skillconnect`.
2. Criar banco (ex.: `skillconnect`) no MySQL local.
3. Importar o seed UTF-8:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root skillconnect < c:\xampp\htdocs\skillconnect\database\migrations\2026-02-24_railway_seed_utf8.sql
```

## Deploy no Railway

### Requisitos importantes

- `composer.json` deve manter `php` em `^8.2` (ou superior suportado).
- Servico MySQL precisa estar criado e conectado ao servico web.

### Variaveis de ambiente

O projeto aceita automaticamente variaveis do Railway:

- `MYSQLHOST`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQLDATABASE`
- `MYSQLPORT`

Tambem suporta fallback por URL:

- `MYSQL_URL` ou `DATABASE_URL`

E para assistente IA:

- `OPENAI_API_KEY`
- `OPENAI_MODEL` (opcional)

Recomendado em producao:

- `APP_URL=https://seu-dominio`

### Importar banco no Railway

Use o arquivo:

- `database/migrations/2026-02-24_railway_seed_utf8.sql`

Observacao: `mysql.railway.internal` funciona apenas dentro da infra da Railway.
Para importar do seu PC, use host/porta de **Public Networking**.

## Troubleshooting

### 1) `No version available for php 8.1`

Causa: `composer.json` pedindo PHP 8.1 em ambiente sem essa versao.

Correcao: usar `^8.2` em `composer.json`.

### 2) `Call to undefined function mysqli_report()`

Causa: runtime sem extensao MySQL/mysqli.

Correcao:

- garantir extensao `mysqli` no ambiente de deploy;
- manter `ext-mysqli` no `composer.json`.

### 3) `Table '...cursos' doesn't exist`

Causa: banco remoto sem schema/dados.

Correcao: importar `2026-02-24_railway_seed_utf8.sql`.

### 4) `Unknown MySQL server host 'mysql.railway.internal'`

Causa: tentativa de conectar ao host interno da Railway a partir da maquina local.

Correcao: usar host/porta de **Public Networking** no cliente local.

### 5) `Plugin caching_sha2_password could not be loaded`

Causa: cliente MySQL local antigo/incompativel.

Correcao: usar cliente compativel (MySQL 8+) ou importar via PHP/codigo.

## Comandos uteis

Validar sintaxe PHP de um arquivo:

```powershell
C:\xampp\php\php.exe -l caminho\arquivo.php
```

Exemplo:

```powershell
C:\xampp\php\php.exe -l config\db.php
```
---

---

---

## Automação com GitHub Actions

O projeto utiliza GitHub Actions para automatizar tarefas a cada push na branch `main`.

### Workflows disponíveis

| Workflow | Arquivo | O que faz |
|---|---|---|
| Verificar PHP | `main.yml` | Checa sintaxe de todos os arquivos `.php` |
| Relatório do Projeto | `relatorio.yml` | Gera relatório com contagem de arquivos e linhas de código |
| Verificação de Segurança | `segurança.yml` | Verifica `.env` exposto e senhas fracas no código |
| Validar HTML | `validar-html.yml` | Valida os arquivos `.html` do projeto |
| Gerar Documentação | `documentacao.yml` | Gera documentação PHP e publica no GitHub Pages |

### Documentação automática

A cada push na `main`, o workflow `documentacao.yml` executa o phpDocumentor e publica a documentação gerada automaticamente em:

**https://cassiap.github.io/skillconnect/**

A documentação lista todas as funções, parâmetros e descrições extraídas dos docblocks `/** */` presentes nos arquivos PHP.

### Docblocks

Durante o desenvolvimento, identificamos que a documentação gerada pelo phpDocumentor ficava incompleta: as funções apareciam listadas mas sem descrição, parâmetros ou contexto, o que tornava a doc pouco útil na prática.

Para resolver isso, adicionamos docblocks no padrão PHPDoc em todos os arquivos PHP do projeto. Cada docblock descreve o propósito da função, seus parâmetros (`@param`), o valor de retorno (`@return`) e possíveis exceções (`@throws`), servindo como base para a documentação gerada automaticamente pelo phpDocumentor a cada push.

Os docblocks foram adicionados utilizando uma API da Anthropic e um script Python que realizou a atualização em todos eles de forma automática. 

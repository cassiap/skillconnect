# CENTRO UNIVERSITÁRIO DE BRASÍLIA
## FACULDADE DE TECNOLOGIA E CIÊNCIAS SOCIAIS APLICADAS

---

# RESUMO EXECUTIVO

# SkillConnect

**Membros do Projeto**

| Nome | Matrícula |
|------|-----------|
| Cássia Gabriela | 22252157 |
| Arthur Gomes | (22250160) |
| Brenno Jonas | (22205163) |

**Orientador:** Prof. Tiago Leite

**Brasília, Abril de 2026**

---

## AGRADECIMENTOS

Agradecemos ao Prof. Tiago Leite pela orientação ao longo do semestre. Aos colegas de turma, pelo apoio durante o desenvolvimento. Às nossas famílias, pelo incentivo constante durante toda a trajetória acadêmica.

---

## RESUMO

O SkillConnect é uma plataforma web desenvolvida em PHP que conecta pessoas a oportunidades de desenvolvimento profissional. O sistema reúne cursos profissionalizantes, vagas de emprego e estágio, e um assistente de inteligência artificial para orientação de carreira, tudo em um único lugar. O projeto foi desenvolvido como Projeto Integrador 2 do curso de Ciência da Computação do CEUB, com foco em resolver um problema real de acesso à qualificação profissional e ao mercado de trabalho.

**Palavras-chave:** qualificação profissional, vagas de emprego, assistente de IA, plataforma web, empregabilidade

---

## SUMÁRIO

1. [Problema/Oportunidade](#1-problemaoportunidade)
2. [Benefícios da Solução](#2-benefícios-da-solução)
3. [Público-Alvo](#3-público-alvo)
4. [Protótipo Visual](#4-protótipo-visual)
5. [Considerações Finais](#5-considerações-finais)
6. [Referências](#referências)

---

## 1. PROBLEMA/OPORTUNIDADE

Quem está começando no mercado de trabalho ou tentando se requalificar enfrenta um problema concreto: as informações estão espalhadas. Cursos ficam em uma plataforma, vagas em outra, e orientação de carreira normalmente exige pagar por uma consultoria ou conhecer alguém da área.

Dados do IBGE (2024) apontam que o Brasil tem cerca de **8,7 milhões de desempregados**, com concentração maior entre jovens de 18 a 29 anos. Pesquisa da FGV indica que **70% dos trabalhadores brasileiros nunca tiveram acesso a nenhuma forma de orientação profissional estruturada**.

O SkillConnect foi criado para centralizar isso: cursos, vagas e orientação de carreira com IA em uma única plataforma gratuita e acessível.

---

## 2. BENEFÍCIOS DA SOLUÇÃO

**Para o aluno:** um único cadastro dá acesso a cursos, vagas, currículo digital e assistente de IA, sem precisar criar conta em vários sites.

**Para empresas e instituições:** o painel administrativo permite cadastrar vagas e cursos de forma simples, sem precisar de infraestrutura própria.

**Econômico:** reduz o custo de acesso à informação profissional, que hoje está espalhada em serviços pagos ou de difícil acesso.

**Social:** o assistente de IA torna acessível uma orientação de carreira que normalmente exige pagar por consultoria.

---

## 3. PÚBLICO-ALVO

**Alunos:** jovens e adultos entre 18 e 35 anos que buscam qualificação ou recolocação no mercado. Em geral, pessoas com acesso à internet mas sem condições de pagar por plataformas especializadas.

**Administradores:** pequenas instituições de ensino profissionalizante e empresas que querem divulgar oportunidades sem custo de infraestrutura.

O sistema é acessado principalmente pelo navegador, desktop ou mobile, fora do horário comercial.

---

## 4. PROTÓTIPO VISUAL

O projeto foi desenvolvido com interface funcional e responsiva em Bootstrap 4. As principais telas são:

- **Landing page** (`index.php`): apresentação da plataforma com cursos e vagas em destaque
- **Área do aluno**: acesso a cursos inscritos, vagas candidatadas e currículo
- **Catálogo de cursos**: listagem com informações de modalidade, nível e custo
- **Catálogo de vagas**: listagem com filtros por tipo e localização
- **Assistente de IA**: chat para orientação de carreira
- **Painel administrativo**: gestão de usuários, cursos, vagas e candidaturas

A documentação PHP gerada automaticamente pelo phpDocumentor está publicada em:
🔗 **https://cassiap.github.io/skillconnect/** 
🔗 **[https://cassiap.github.io/skillconnect/](https://youtu.be/NUynnh-I-uA)** 

---

## 5. CONSIDERAÇÕES FINAIS

O SkillConnect foi entregue como uma aplicação web funcional, com deploy em nuvem (Railway), banco de dados estruturado, autenticação segura e integração com IA. O projeto atingiu o que foi proposto para o PI2.

Durante o desenvolvimento foram implementados: automação de CI/CD com GitHub Actions, geração de documentação PHP automática via phpDocumentor publicada no GitHub Pages, e adição de docblocks em todos os arquivos PHP com auxílio de script Python e API da Anthropic.

Como próximos passos, o sistema pode evoluir para incluir avaliações de cursos e vagas, relatórios para administradores, notificações por e-mail e versão mobile.

---

## REFERÊNCIAS

IBGE. **Pesquisa Nacional por Amostra de Domicílios Contínua (PNAD Contínua)**. Rio de Janeiro: IBGE, 2024.

FUNDAÇÃO GETÚLIO VARGAS. **Relatório de Empregabilidade e Qualificação Profissional no Brasil**. São Paulo: FGV, 2023.

SOMMERVILLE, Ian. **Engenharia de software**. 10. ed. São Paulo: Pearson, 2019.

PRESSMAN, Roger S.; MAXIM, Bruce R. **Engenharia de software: uma abordagem profissional**. 8. ed. Porto Alegre: AMGH, 2016.

PHP GROUP. **PHP Manual**. Disponível em: https://www.php.net/manual/pt_BR/. Acesso em: abr. 2026.

BOOTSTRAP. **Bootstrap 4 Documentation**. Disponível em: https://getbootstrap.com/docs/4.6/. Acesso em: abr. 2026.

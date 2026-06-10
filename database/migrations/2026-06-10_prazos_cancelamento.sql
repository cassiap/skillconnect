-- Prazos de inscricao/candidatura e cancelamento pelo aluno
-- Rodar no Railway (console SQL) e no XAMPP local.

-- Data limite para novas inscricoes no curso (NULL = sem prazo)
ALTER TABLE cursos
    ADD COLUMN inscricoes_ate DATE NULL DEFAULT NULL AFTER duracao_dias;

-- Data limite para novas candidaturas na vaga (NULL = sem prazo)
ALTER TABLE vagas
    ADD COLUMN candidaturas_ate DATE NULL DEFAULT NULL AFTER estado;

-- Novo status para candidatura cancelada pelo proprio aluno
ALTER TABLE candidaturas
    MODIFY COLUMN status ENUM('enviada','em_analise','aprovado','reprovado','cancelada') NOT NULL DEFAULT 'enviada';

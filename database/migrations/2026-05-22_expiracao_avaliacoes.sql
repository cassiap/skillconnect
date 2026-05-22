-- 2026-05-22: Expiração de acesso por curso e avaliações de aulas

-- Prazo de conclusão configurável por curso (NULL = sem prazo)
ALTER TABLE cursos
    ADD COLUMN duracao_dias INT UNSIGNED NULL DEFAULT 180
    COMMENT 'Prazo em dias para conclusão a partir da inscrição. NULL = sem prazo.';

-- Data-limite de acesso calculada no momento da inscrição
ALTER TABLE inscricoes_cursos
    ADD COLUMN acesso_expira_em DATETIME NULL DEFAULT NULL
    COMMENT 'Data limite de acesso ao curso. NULL = sem prazo.';

-- Avaliações de 1 a 5 estrelas por aula, uma por aluno
CREATE TABLE IF NOT EXISTS avaliacoes_aulas (
    id            INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT(10) UNSIGNED NOT NULL,
    aula_id       INT(10) UNSIGNED NOT NULL,
    nota          TINYINT UNSIGNED NOT NULL DEFAULT 5,
    comentario    TEXT NULL,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_avaliacao_usuario_aula (usuario_id, aula_id),
    KEY idx_avaliacao_aula (aula_id),
    CONSTRAINT fk_avaliacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_avaliacao_aula    FOREIGN KEY (aula_id)    REFERENCES aulas(id)    ON DELETE CASCADE,
    CONSTRAINT chk_avaliacao_nota   CHECK (nota BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Avaliações de aulas pelos alunos';

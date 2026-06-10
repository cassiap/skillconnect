-- Avaliacao do curso como um todo (1-5 estrelas + comentario), uma por aluno
-- Complementa avaliacoes_aulas (que avalia aula por aula).
-- Rodar no Railway (console SQL) e no XAMPP local.

CREATE TABLE IF NOT EXISTS avaliacoes_cursos (
    id            INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id    INT(10) UNSIGNED NOT NULL,
    curso_id      INT(10) UNSIGNED NOT NULL,
    nota          TINYINT UNSIGNED NOT NULL DEFAULT 5,
    comentario    TEXT NULL,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_avaliacao_usuario_curso (usuario_id, curso_id),
    KEY idx_avaliacao_curso (curso_id),
    CONSTRAINT fk_avalcurso_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_avalcurso_curso   FOREIGN KEY (curso_id)   REFERENCES cursos(id)   ON DELETE CASCADE,
    CONSTRAINT chk_avalcurso_nota   CHECK (nota BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Avaliações de cursos pelos alunos';

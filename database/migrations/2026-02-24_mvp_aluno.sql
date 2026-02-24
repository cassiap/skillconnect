-- MVP aluno: cursos com aulas/progresso e curriculo geral

CREATE TABLE IF NOT EXISTS modulos (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    curso_id INT(10) UNSIGNED NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    ordem INT(10) UNSIGNED NOT NULL DEFAULT 1,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_modulos_curso (curso_id),
    CONSTRAINT fk_modulos_curso FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS aulas (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    modulo_id INT(10) UNSIGNED NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    conteudo TEXT NULL,
    video_url VARCHAR(255) NULL,
    material_url VARCHAR(255) NULL,
    duracao_min INT(10) UNSIGNED NULL,
    ordem INT(10) UNSIGNED NOT NULL DEFAULT 1,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_aulas_modulo (modulo_id),
    CONSTRAINT fk_aulas_modulo FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS progresso_aulas (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT(10) UNSIGNED NOT NULL,
    aula_id INT(10) UNSIGNED NOT NULL,
    concluido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_progresso_usuario_aula (usuario_id, aula_id),
    KEY idx_progresso_aula (aula_id),
    CONSTRAINT fk_progresso_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_progresso_aula FOREIGN KEY (aula_id) REFERENCES aulas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS curriculos (
    id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT(10) UNSIGNED NOT NULL,
    titulo_profissional VARCHAR(120) NULL,
    resumo TEXT NULL,
    habilidades TEXT NULL,
    experiencias TEXT NULL,
    formacao TEXT NULL,
    links TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_curriculo_usuario (usuario_id),
    CONSTRAINT fk_curriculo_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


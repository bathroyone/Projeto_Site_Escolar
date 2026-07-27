-- Tabela de Disciplinas
CREATE TABLE IF NOT EXISTS disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    area_conhecimento ENUM('linguagens', 'matematica', 'ciencias_natureza', 'ciencias_humanas', 'artes', 'educacao_fisica', 'tecnologia') NOT NULL,
    carga_horaria_anual INT DEFAULT 100,
    carga_horaria_semanal INT DEFAULT 4,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Currículos
CREATE TABLE IF NOT EXISTS curriculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    ano_letivo INT NOT NULL,
    serie VARCHAR(50) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (ano_letivo, serie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Disciplinas por Currículo
CREATE TABLE IF NOT EXISTS curriculo_disciplinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curriculo_id INT NOT NULL,
    disciplina_id INT NOT NULL,
    ordem INT DEFAULT 0,
    obrigatoria TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (curriculo_id) REFERENCES curriculos(id) ON DELETE CASCADE,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE,
    UNIQUE KEY (curriculo_id, disciplina_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Conteúdos Programáticos
CREATE TABLE IF NOT EXISTS conteudos_programaticos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    disciplina_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    bimestre INT NOT NULL,
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir disciplinas padrão
INSERT INTO disciplinas (codigo, nome, descricao, area_conhecimento, carga_horaria_anual, carga_horaria_semanal) VALUES
('PORT', 'Língua Portuguesa', 'Ensino de língua portuguesa e literatura', 'linguagens', 200, 5),
('MAT', 'Matemática', 'Ensino de matemática', 'matematica', 200, 5),
('CIE', 'Ciências', 'Ensino de ciências naturais', 'ciencias_natureza', 100, 3),
('HIS', 'História', 'Ensino de história', 'ciencias_humanas', 100, 3),
('GEO', 'Geografia', 'Ensino de geografia', 'ciencias_humanas', 100, 3),
('ART', 'Artes', 'Ensino de artes', 'artes', 100, 2),
('EDF', 'Educação Física', 'Educação física e esportes', 'educacao_fisica', 100, 2),
('ING', 'Inglês', 'Ensino de língua inglesa', 'linguagens', 100, 3),
('INF', 'Informática', 'Educação digital e tecnologia', 'tecnologia', 50, 1),
('FIL', 'Filosofia', 'Ensino de filosofia', 'ciencias_humanas', 50, 1),
('SOC', 'Sociologia', 'Ensino de sociologia', 'ciencias_humanas', 50, 1)
ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao);

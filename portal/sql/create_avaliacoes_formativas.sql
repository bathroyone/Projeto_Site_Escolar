-- Tabela de Avaliações Formativas
CREATE TABLE IF NOT EXISTS avaliacoes_formativas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100),
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_avaliacao DATE NOT NULL,
    peso DECIMAL(5,2) DEFAULT 1.0,
    status ENUM('planejada', 'aplicada', 'finalizada') DEFAULT 'planejada',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Resultados de Avaliações Formativas
CREATE TABLE IF NOT EXISTS avaliacao_formativa_resultados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    avaliacao_id INT NOT NULL,
    aluno_id INT NOT NULL,
    nota DECIMAL(5,2),
    feedback TEXT,
    data_avaliacao DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

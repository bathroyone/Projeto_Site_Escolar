-- Tabela de Planejamento de Aulas
CREATE TABLE IF NOT EXISTS planejamento_aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    objetivos TEXT,
    conteudo_programatico TEXT,
    metodologia TEXT,
    recursos TEXT,
    avaliacao TEXT,
    data_planejamento DATE NOT NULL,
    data_prevista DATE,
    status ENUM('planejado', 'em_andamento', 'concluido', 'cancelado') DEFAULT 'planejado',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Etapas do Planejamento
CREATE TABLE IF NOT EXISTS planejamento_etapas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    planejamento_id INT NOT NULL,
    ordem INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    duracao_minutos INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

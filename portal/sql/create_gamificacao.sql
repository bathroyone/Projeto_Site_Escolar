-- Tabela de Gamificação
CREATE TABLE IF NOT EXISTS gamificacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    aluno_id INT NOT NULL,
    pontos INT DEFAULT 0,
    nivel INT DEFAULT 1,
    xp INT DEFAULT 0,
    conquistas JSON,
    badges JSON,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Conquistas Disponíveis
CREATE TABLE IF NOT EXISTS conquistas_disponiveis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('pontos', 'participacao', 'tarefas', 'comportamento', 'outro') NOT NULL,
    pontos_requeridos INT DEFAULT 0,
    icone VARCHAR(50),
    ativo BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Projetos e Atividades
CREATE TABLE IF NOT EXISTS projetos_atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100),
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    objetivo TEXT,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    status ENUM('planejado', 'em_andamento', 'concluido', 'cancelado') DEFAULT 'planejado',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Tarefas do Projeto
CREATE TABLE IF NOT EXISTS projeto_tarefas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    projeto_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    responsavel_id INT,
    data_prazo DATE,
    status ENUM('pendente', 'em_progresso', 'concluida', 'cancelada') DEFAULT 'pendente',
    ordem INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

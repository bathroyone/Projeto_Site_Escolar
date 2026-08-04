-- Tabela de Controle de Entregas
CREATE TABLE IF NOT EXISTS controle_entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100),
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('trabalho', 'projeto', 'prova', 'outro') NOT NULL,
    data_entrega DATE NOT NULL,
    status ENUM('aberto', 'fechado', 'prorrogado') DEFAULT 'aberto',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Entregas de Alunos
CREATE TABLE IF NOT EXISTS controle_entregas_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entrega_id INT NOT NULL,
    aluno_id INT NOT NULL,
    status ENUM('pendente', 'entregue', 'atrasado', 'nao_entregue') DEFAULT 'pendente',
    data_entrega DATETIME,
    nota DECIMAL(5,2),
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

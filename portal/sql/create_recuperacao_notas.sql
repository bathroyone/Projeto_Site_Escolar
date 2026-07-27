-- Tabela de Recuperação de Notas
CREATE TABLE IF NOT EXISTS recuperacao_notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100),
    nota_original DECIMAL(5,2),
    nota_recuperacao DECIMAL(5,2),
    nota_final DECIMAL(5,2),
    data_recuperacao DATE,
    tipo_recuperacao ENUM('prova', 'trabalho', 'projeto', 'outro') DEFAULT 'prova',
    observacoes TEXT,
    status ENUM('pendente', 'aplicada', 'aprovado', 'reprovado') DEFAULT 'pendente',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

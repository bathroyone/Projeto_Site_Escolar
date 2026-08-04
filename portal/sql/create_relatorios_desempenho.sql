-- Tabela de Relatórios de Desempenho
CREATE TABLE IF NOT EXISTS relatorios_desempenho (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100),
    periodo VARCHAR(50),
    data_geracao DATE NOT NULL,
    media_turma DECIMAL(5,2),
    media_geral DECIMAL(5,2),
    alunos_acima_media INT DEFAULT 0,
    alunos_abaixo_media INT DEFAULT 0,
    alunos_recuperacao INT DEFAULT 0,
    observacoes TEXT,
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Desempenho Individual no Relatório
CREATE TABLE IF NOT EXISTS relatorio_desempenho_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    relatorio_id INT NOT NULL,
    aluno_id INT NOT NULL,
    nota DECIMAL(5,2),
    frequencia DECIMAL(5,2),
    status ENUM('aprovado', 'reprovado', 'recuperacao', 'em_andamento') DEFAULT 'em_andamento',
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

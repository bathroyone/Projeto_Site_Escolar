-- Tabela de Acompanhamento Individual de Alunos
CREATE TABLE IF NOT EXISTS acompanhamento_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    turma_id INT,
    data_registro DATE NOT NULL,
    tipo ENUM('observacao', 'feedback', 'recomendacao', 'alerta') DEFAULT 'observacao',
    conteudo TEXT NOT NULL,
    status_aluno ENUM('em_dia', 'atrasado', 'em_risco', 'recuperacao') DEFAULT 'em_dia',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Metas de Acompanhamento
CREATE TABLE IF NOT EXISTS acompanhamento_metas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    acompanhamento_id INT NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    data_prazo DATE,
    status ENUM('pendente', 'em_progresso', 'concluida', 'cancelada') DEFAULT 'pendente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

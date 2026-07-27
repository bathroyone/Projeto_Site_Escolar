-- Tabela de Solicitações de Transferência
CREATE TABLE IF NOT EXISTS transferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    turma_origem_id INT NOT NULL,
    turma_destino_id INT NOT NULL,
    tipo ENUM('interna', 'externa_entrada', 'externa_saida') NOT NULL,
    motivo TEXT,
    status ENUM('pendente', 'aprovada', 'rejeitada', 'concluida') DEFAULT 'pendente',
    data_solicitacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_aprovacao DATETIME,
    aprovado_por INT,
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Histórico de Transferências
CREATE TABLE IF NOT EXISTS transferencias_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transferencia_id INT NOT NULL,
    aluno_id INT NOT NULL,
    turma_origem VARCHAR(255),
    turma_destino VARCHAR(255),
    tipo VARCHAR(50),
    data_transferencia DATETIME DEFAULT CURRENT_TIMESTAMP,
    responsavel_id INT NOT NULL,
    observacoes TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

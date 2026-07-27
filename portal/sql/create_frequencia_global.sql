-- Tabela de Resumo de Frequência Global
CREATE TABLE IF NOT EXISTS frequencia_resumo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    turma_id INT NOT NULL,
    ano_letivo INT NOT NULL,
    mes INT NOT NULL,
    total_aulas INT DEFAULT 0,
    presentes INT DEFAULT 0,
    ausentes INT DEFAULT 0,
    atrasados INT DEFAULT 0,
    justificados INT DEFAULT 0,
    percentual_frequencia DECIMAL(5,2) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    UNIQUE KEY (aluno_id, turma_id, ano_letivo, mes)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Alertas de Frequência
CREATE TABLE IF NOT EXISTS frequencia_alertas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    turma_id INT NOT NULL,
    tipo_alerto ENUM('baixa_frequencia', 'falta_excessiva', 'risco_reprovacao') NOT NULL,
    percentual_atual DECIMAL(5,2) NOT NULL,
    limite_percentual DECIMAL(5,2) NOT NULL,
    notificado TINYINT(1) DEFAULT 0,
    data_notificacao DATETIME,
    resolvido TINYINT(1) DEFAULT 0,
    data_resolucao DATETIME,
    observacao TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Configuração de Limites de Frequência
CREATE TABLE IF NOT EXISTS frequencia_configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo_alerto ENUM('baixa_frequencia', 'falta_excessiva', 'risco_reprovacao') NOT NULL,
    limite_percentual DECIMAL(5,2) NOT NULL,
    notificar_responsavel TINYINT(1) DEFAULT 1,
    notificar_professor TINYINT(1) DEFAULT 1,
    notificar_secretaria TINYINT(1) DEFAULT 1,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configurações padrão
INSERT INTO frequencia_configuracoes (tipo_alerto, limite_percentual) VALUES
('baixa_frequencia', 75.00),
('falta_excessiva', 60.00),
('risco_reprovacao', 50.00)
ON DUPLICATE KEY UPDATE limite_percentual=VALUES(limite_percentual);

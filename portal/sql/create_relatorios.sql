-- Tabela de Relatórios Personalizados
CREATE TABLE IF NOT EXISTS relatorios_personalizados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('usuarios', 'notas', 'frequencia', 'financeiro', 'turmas', 'matriculas') NOT NULL,
    configuracao JSON NOT NULL,
    criado_por INT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Histórico de Exportações
CREATE TABLE IF NOT EXISTS relatorios_exportacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    relatorio_id INT,
    usuario_id INT NOT NULL,
    formato ENUM('pdf', 'excel', 'csv') NOT NULL,
    filtros JSON,
    arquivo VARCHAR(255),
    status ENUM('pendente', 'processando', 'concluido', 'erro') DEFAULT 'pendente',
    erro_mensagem TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    concluido_at DATETIME,
    FOREIGN KEY (relatorio_id) REFERENCES relatorios_personalizados(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Agendamentos de Relatórios
CREATE TABLE IF NOT EXISTS relatorios_agendamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    relatorio_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    frequencia ENUM('diario', 'semanal', 'mensal', 'trimestral', 'semestral', 'anual') NOT NULL,
    proxima_execucao DATETIME NOT NULL,
    ultima_execucao DATETIME,
    ativo TINYINT(1) DEFAULT 1,
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (relatorio_id) REFERENCES relatorios_personalizados(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

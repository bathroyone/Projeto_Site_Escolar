-- Tabela de Métricas do Sistema
CREATE TABLE IF NOT EXISTS analytics_metricas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('usuarios', 'frequencia', 'notas', 'eventos', 'sistema') NOT NULL,
    valor DECIMAL(15,2),
    valor_texto TEXT,
    data_registro DATE NOT NULL,
    periodo ENUM('diario', 'semanal', 'mensal', 'anual') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Relatórios de Analytics
CREATE TABLE IF NOT EXISTS analytics_relatorios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    tipo ENUM('usuarios', 'frequencia', 'notas', 'eventos', 'sistema', 'completo') NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    dados_json TEXT,
    gerado_por INT NOT NULL,
    arquivo VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

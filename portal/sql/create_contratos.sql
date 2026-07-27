-- Tabela de Contratos
CREATE TABLE IF NOT EXISTS contratos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(50) NOT NULL UNIQUE,
    tipo ENUM('trabalho', 'prestacao_servicos', 'fornecedor', 'aluguel', 'seguro', 'outros') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    parte_interessada VARCHAR(255) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    valor DECIMAL(15,2),
    status ENUM('ativo', 'vencido', 'cancelado', 'renovado') DEFAULT 'ativo',
    arquivo VARCHAR(255),
    observacoes TEXT,
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Documentos Legais
CREATE TABLE IF NOT EXISTS documentos_legais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('alvara', 'licenca', 'registro', 'certificado', 'outros') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    numero_registro VARCHAR(100),
    orgao_emissor VARCHAR(255),
    data_emissao DATE,
    data_validade DATE,
    arquivo VARCHAR(255),
    observacoes TEXT,
    status ENUM('ativo', 'vencido', 'renovacao_pendente') DEFAULT 'ativo',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Equipamentos
CREATE TABLE IF NOT EXISTS equipamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('informatica', 'mobiliario', 'laboratorio', 'esporte', 'audiovisual', 'outros') NOT NULL,
    numero_patrimonio VARCHAR(50),
    marca VARCHAR(100),
    modelo VARCHAR(100),
    numero_serie VARCHAR(100),
    data_aquisicao DATE,
    valor DECIMAL(15,2),
    localizacao VARCHAR(255),
    estado_conservacao ENUM('novo', 'bom', 'regular', 'ruim', 'danificado') DEFAULT 'bom',
    status ENUM('ativo', 'inativo', 'manutencao', 'baixado') DEFAULT 'ativo',
    observacoes TEXT,
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Movimentações de Equipamentos
CREATE TABLE IF NOT EXISTS equipamentos_movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipamento_id INT NOT NULL,
    tipo_movimentacao ENUM('entrada', 'saida', 'transferencia', 'baixa', 'manutencao') NOT NULL,
    data_movimentacao DATE NOT NULL,
    origem VARCHAR(255),
    destino VARCHAR(255),
    responsavel VARCHAR(255),
    motivo TEXT,
    registrado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

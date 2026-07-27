-- Tabela de Cardápios
CREATE TABLE IF NOT EXISTS merenda_cardapios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data DATE NOT NULL,
    tipo_refeicao ENUM('cafe_manha', 'almoco', 'lanche', 'jantar') NOT NULL,
    descricao TEXT NOT NULL,
    calorias INT,
    observacoes TEXT,
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (data, tipo_refeicao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Consumo de Merenda
CREATE TABLE IF NOT EXISTS merenda_consumo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    cardapio_id INT NOT NULL,
    data_consumo DATE NOT NULL,
    tipo_refeicao ENUM('cafe_manha', 'almoco', 'lanche', 'jantar') NOT NULL,
    consumiu TINYINT(1) DEFAULT 1,
    observacoes TEXT,
    registrado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (aluno_id, data_consumo, tipo_refeicao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Estoque de Alimentos
CREATE TABLE IF NOT EXISTS merenda_estoque (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alimento VARCHAR(255) NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    unidade_medida VARCHAR(20) DEFAULT 'kg',
    estoque_minimo DECIMAL(10,2) DEFAULT 10,
    categoria ENUM('carnes', 'graos', 'legumes', 'frutas', 'laticinios', 'bebidas', 'outros') NOT NULL,
    validade DATE,
    fornecedor VARCHAR(255),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Movimentação de Estoque
CREATE TABLE IF NOT EXISTS merenda_movimentacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alimento_id INT NOT NULL,
    tipo ENUM('entrada', 'saida', 'ajuste') NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    motivo TEXT,
    responsavel_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

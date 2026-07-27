-- Tabela de Materiais
CREATE TABLE IF NOT EXISTS materiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    categoria ENUM('papelaria', 'limpeza', 'informatica', 'mobiliario', 'livros', 'esportes', 'outros') NOT NULL,
    unidade_medida VARCHAR(20) DEFAULT 'un',
    estoque_minimo INT DEFAULT 10,
    estoque_atual INT DEFAULT 0,
    localizacao VARCHAR(100),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Movimentações de Estoque
CREATE TABLE IF NOT EXISTS estoque_movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    tipo ENUM('entrada', 'saida', 'ajuste', 'devolucao') NOT NULL,
    quantidade INT NOT NULL,
    motivo TEXT,
    responsavel_id INT NOT NULL,
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Solicitações de Materiais
CREATE TABLE IF NOT EXISTS solicitacoes_materiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    solicitante_id INT NOT NULL,
    quantidade_solicitada INT NOT NULL,
    motivo TEXT,
    status ENUM('pendente', 'aprovada', 'rejeitada', 'entregue') DEFAULT 'pendente',
    data_solicitacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_aprovacao DATETIME,
    aprovado_por INT,
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir materiais padrão
INSERT INTO materiais (codigo, nome, descricao, categoria, unidade_medida, estoque_minimo) VALUES
('PAP001', 'Papel A4', 'Pacote de 500 folhas', 'papelaria', 'cx', 20),
('CAN001', 'Caneta Azul', 'Caixa com 12 unidades', 'papelaria', 'cx', 10),
('BOR001', 'Borracha', 'Caixa com 50 unidades', 'papelaria', 'cx', 5),
('LIM001', 'Detergente', 'Galão de 5 litros', 'limpeza', 'un', 5),
('LIM002', 'Sabão em Pó', 'Pacote de 1kg', 'limpeza', 'un', 10),
('INF001', 'Mouse USB', 'Mouse sem fio', 'informatica', 'un', 5),
('INF002', 'Teclado USB', 'Teclado padrão', 'informatica', 'un', 5),
('LIV001', 'Caderno 10 matérias', 'Caderno capa dura', 'livros', 'un', 20)
ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao);

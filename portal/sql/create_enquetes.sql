-- Tabela de Enquetes
CREATE TABLE IF NOT EXISTS enquetes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('pesquisa', 'enquete', 'avaliacao', 'satisfacao') NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    anonima TINYINT(1) DEFAULT 0,
    ativa TINYINT(1) DEFAULT 1,
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Perguntas
CREATE TABLE IF NOT EXISTS enquete_perguntas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enquete_id INT NOT NULL,
    pergunta TEXT NOT NULL,
    tipo_pergunta ENUM('texto', 'opcao_unica', 'opcao_multipla', 'escala') NOT NULL,
    ordem INT DEFAULT 0,
    obrigatoria TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Opções de Resposta
CREATE TABLE IF NOT EXISTS enquete_opcoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pergunta_id INT NOT NULL,
    opcao VARCHAR(255) NOT NULL,
    ordem INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Respostas
CREATE TABLE IF NOT EXISTS enquete_respostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enquete_id INT NOT NULL,
    pergunta_id INT NOT NULL,
    usuario_id INT,
    resposta TEXT,
    opcao_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

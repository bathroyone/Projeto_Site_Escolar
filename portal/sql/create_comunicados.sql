-- Tabela de Comunicados
CREATE TABLE IF NOT EXISTS comunicados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    tipo ENUM('geral', 'pais', 'alunos', 'professores', 'secretaria', 'admin') NOT NULL,
    prioridade ENUM('baixa', 'normal', 'alta', 'urgente') DEFAULT 'normal',
    data_publicacao DATE NOT NULL,
    data_expiracao DATE,
    autor_id INT NOT NULL,
    anexo VARCHAR(255),
    lido_por TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Confirmação de Leitura
CREATE TABLE IF NOT EXISTS comunicado_leituras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comunicado_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_leitura DATETIME DEFAULT CURRENT_TIMESTAMP,
    confirmado TINYINT(1) DEFAULT 1,
    UNIQUE KEY (comunicado_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

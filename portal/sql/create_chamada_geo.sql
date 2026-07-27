-- Tabela de Chamadas Digitais
CREATE TABLE IF NOT EXISTS chamadas_digitais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    professor_id INT NOT NULL,
    data_chamada DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME,
    status ENUM('aberta', 'fechada') DEFAULT 'aberta',
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Presença com Geolocalização
CREATE TABLE IF NOT EXISTS chamada_presenca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chamada_id INT NOT NULL,
    aluno_id INT NOT NULL,
    status ENUM('presente', 'ausente', 'atrasado', 'justificado') DEFAULT 'presente',
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    localizacao_texto VARCHAR(255),
    ip_registro VARCHAR(45),
    data_registro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

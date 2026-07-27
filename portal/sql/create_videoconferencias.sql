-- Tabela de Videoconferências
CREATE TABLE IF NOT EXISTS videoconferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100),
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    plataforma VARCHAR(100),
    link VARCHAR(500),
    codigo_acesso VARCHAR(100),
    senha VARCHAR(100),
    data_hora DATETIME NOT NULL,
    duracao_minutos INT DEFAULT 60,
    status ENUM('agendada', 'em_andamento', 'finalizada', 'cancelada') DEFAULT 'agendada',
    gravacao_disponivel BOOLEAN DEFAULT FALSE,
    link_gravacao VARCHAR(500),
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Participantes da Videoconferência
CREATE TABLE IF NOT EXISTS videoconferencia_participantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    videoconferencia_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_entrada DATETIME,
    data_saida DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

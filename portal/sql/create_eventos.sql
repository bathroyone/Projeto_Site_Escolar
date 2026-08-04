-- Tabela de Eventos e Atividades
CREATE TABLE IF NOT EXISTS eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('evento', 'atividade_extracurricular', 'passeio', 'palestra', 'esporte', 'cultural', 'outros') NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME,
    local VARCHAR(255),
    vagas INT,
    vagas_preenchidas INT DEFAULT 0,
    responsavel VARCHAR(255),
    telefone_contato VARCHAR(20),
    valor DECIMAL(10,2) DEFAULT 0,
    status ENUM('planejado', 'aberto_inscricoes', 'em_andamento', 'concluido', 'cancelado') DEFAULT 'planejado',
    imagem VARCHAR(255),
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Inscrições em Eventos
CREATE TABLE IF NOT EXISTS evento_inscricoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    aluno_id INT NOT NULL,
    data_inscricao DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('confirmada', 'pendente', 'cancelada') DEFAULT 'confirmada',
    observacoes TEXT,
    UNIQUE KEY (evento_id, aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Presença em Eventos
CREATE TABLE IF NOT EXISTS evento_presenca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    aluno_id INT NOT NULL,
    presente TINYINT(1) DEFAULT 0,
    observacoes TEXT,
    registrado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (evento_id, aluno_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

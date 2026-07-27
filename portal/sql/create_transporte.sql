-- Tabela de Veículos
CREATE TABLE IF NOT EXISTS transportes_veiculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placa VARCHAR(20) NOT NULL UNIQUE,
    modelo VARCHAR(255) NOT NULL,
    ano INT,
    capacidade INT DEFAULT 40,
    tipo ENUM('onibus', 'van', 'microonibus', 'outros') NOT NULL,
    motorista VARCHAR(255),
    telefone_motorista VARCHAR(20),
    status ENUM('ativo', 'manutencao', 'inativo') DEFAULT 'ativo',
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Rotas
CREATE TABLE IF NOT EXISTS transportes_rotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    ponto_partida VARCHAR(255) NOT NULL,
    ponto_chegada VARCHAR(255) NOT NULL,
    distancia_km DECIMAL(6,2),
    tempo_estimado_minutos INT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Alunos no Transporte
CREATE TABLE IF NOT EXISTS transportes_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    veiculo_id INT NOT NULL,
    rota_id INT NOT NULL,
    tipo ENUM('ida', 'volta', 'ida_volta') NOT NULL,
    ponto_embarque VARCHAR(255),
    horario_embarque TIME,
    horario_chegada TIME,
    responsavel_busca VARCHAR(255),
    telefone_responsavel VARCHAR(20),
    observacoes TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (aluno_id, veiculo_id, rota_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Ocorrências no Transporte
CREATE TABLE IF NOT EXISTS transportes_ocorrencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT,
    veiculo_id INT NOT NULL,
    tipo ENUM('atraso', 'ausencia', 'problema_veiculo', 'acidente', 'outros') NOT NULL,
    descricao TEXT,
    data_ocorrencia DATETIME DEFAULT CURRENT_TIMESTAMP,
    reportado_por INT NOT NULL,
    status ENUM('pendente', 'resolvido', 'cancelado') DEFAULT 'pendente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

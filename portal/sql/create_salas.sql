-- Tabela de Salas e Espaços Físicos
CREATE TABLE IF NOT EXISTS salas_espacos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('sala_aula', 'laboratorio', 'biblioteca', 'auditorio', 'quadra', 'recreio', 'administracao', 'outros') NOT NULL,
    capacidade INT,
    andar VARCHAR(10),
    bloco VARCHAR(50),
    descricao TEXT,
    recursos TEXT,
    status ENUM('disponivel', 'ocupada', 'manutencao', 'indisponivel') DEFAULT 'disponivel',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Reservas de Salas
CREATE TABLE IF NOT EXISTS salas_reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sala_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_reserva DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    motivo TEXT,
    status ENUM('pendente', 'confirmada', 'cancelada') DEFAULT 'pendente',
    aprovado_por INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

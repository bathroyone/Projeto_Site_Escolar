-- Tabela de Calendário Pessoal
CREATE TABLE IF NOT EXISTS calendario_pessoal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    tipo ENUM('aula', 'reuniao', 'evento', 'compromisso', 'outro') DEFAULT 'compromisso',
    local VARCHAR(255),
    status ENUM('confirmado', 'pendente', 'cancelado') DEFAULT 'confirmado',
    notificar BOOLEAN DEFAULT FALSE,
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

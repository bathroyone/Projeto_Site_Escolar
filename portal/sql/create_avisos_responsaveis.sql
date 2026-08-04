-- Tabela de Avisos aos Responsáveis
CREATE TABLE IF NOT EXISTS avisos_responsaveis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    turma_id INT,
    tipo ENUM('comportamento', 'academico', 'frequencia', 'elogio', 'alerta', 'outro') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    status ENUM('pendente', 'enviado', 'lido') DEFAULT 'pendente',
    data_envio DATETIME,
    data_leitura DATETIME,
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Compartilhamento de Recursos
CREATE TABLE IF NOT EXISTS compartilhamento_recursos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('arquivo', 'link', 'texto', 'outro') NOT NULL,
    conteudo TEXT,
    arquivo VARCHAR(255),
    link VARCHAR(500),
    visibilidade ENUM('publico', 'privado', 'turma') DEFAULT 'turma',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

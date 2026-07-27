-- Tabela de Bibliografia e Referências
CREATE TABLE IF NOT EXISTS bibliografia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT,
    disciplina VARCHAR(100),
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255),
    tipo ENUM('livro', 'artigo', 'site', 'video', 'outro') NOT NULL,
    ano_publicacao INT,
    editora VARCHAR(255),
    link VARCHAR(500),
    isbn VARCHAR(20),
    descricao TEXT,
    tags TEXT,
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

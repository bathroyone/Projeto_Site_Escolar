-- Tabela de Materiais Didáticos Digitais
CREATE TABLE IF NOT EXISTS materiais_didaticos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT,
    disciplina VARCHAR(100),
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    tipo ENUM('pdf', 'video', 'apresentacao', 'documento', 'link', 'audio', 'outro') NOT NULL,
    arquivo VARCHAR(255),
    link_externo VARCHAR(500),
    tamanho_arquivo BIGINT,
    formato VARCHAR(50),
    tags TEXT,
    visibilidade ENUM('publico', 'privado', 'turma') DEFAULT 'turma',
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Downloads de Materiais
CREATE TABLE IF NOT EXISTS materiais_downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    usuario_id INT NOT NULL,
    data_download DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

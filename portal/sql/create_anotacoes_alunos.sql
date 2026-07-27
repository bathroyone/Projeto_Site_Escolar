-- Tabela de Anotações sobre Alunos
CREATE TABLE IF NOT EXISTS anotacoes_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100),
    tipo ENUM('comportamental', 'academico', 'social', 'outro') DEFAULT 'academico',
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    data_anotacao DATE NOT NULL,
    visibilidade ENUM('privado', 'compartilhado') DEFAULT 'privado',
    status ENUM('ativo', 'arquivado') DEFAULT 'ativo',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Feedback para Alunos
CREATE TABLE IF NOT EXISTS feedback_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    turma_id INT,
    disciplina VARCHAR(100),
    tipo ENUM('elogio', 'melhoria', 'orientacao', 'avaliacao') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    data_feedback DATE NOT NULL,
    status ENUM('pendente', 'enviado', 'lido') DEFAULT 'pendente',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

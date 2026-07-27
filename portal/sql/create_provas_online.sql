-- Tabela de Provas Online
CREATE TABLE IF NOT EXISTS provas_online (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    duracao_minutos INT NOT NULL,
    tentativas_permitidas INT DEFAULT 1,
    nota_maxima DECIMAL(5,2) DEFAULT 10.00,
    status ENUM('rascunho', 'agendada', 'em_andamento', 'finalizada') DEFAULT 'rascunho',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Questões da Prova
CREATE TABLE IF NOT EXISTS prova_questoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prova_id INT NOT NULL,
    tipo ENUM('multipla_escolha', 'verdadeiro_falso', 'texto', 'associacao') NOT NULL,
    enunciado TEXT NOT NULL,
    opcoes TEXT,
    resposta_correta TEXT,
    pontos DECIMAL(5,2) DEFAULT 1.00,
    ordem INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Respostas dos Alunos
CREATE TABLE IF NOT EXISTS prova_respostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prova_id INT NOT NULL,
    aluno_id INT NOT NULL,
    tentativa INT DEFAULT 1,
    respostas TEXT,
    nota DECIMAL(5,2),
    data_inicio DATETIME,
    data_entrega DATETIME,
    status ENUM('nao_iniciada', 'em_andamento', 'entregue', 'corrigida') DEFAULT 'nao_iniciada',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Trabalhos em Grupo
CREATE TABLE IF NOT EXISTS trabalhos_grupo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_entrega DATE NOT NULL,
    nota_maxima DECIMAL(5,2) DEFAULT 10.00,
    grupos_max_alunos INT DEFAULT 4,
    status ENUM('aberto', 'em_andamento', 'finalizado') DEFAULT 'aberto',
    criado_por INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Grupos
CREATE TABLE IF NOT EXISTS trabalhos_grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trabalho_id INT NOT NULL,
    nome_grupo VARCHAR(255) NOT NULL,
    lider_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Membros do Grupo
CREATE TABLE IF NOT EXISTS trabalhos_grupo_membros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    aluno_id INT NOT NULL,
    funcao ENUM('membro', 'lider') DEFAULT 'membro',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Entregas de Grupo
CREATE TABLE IF NOT EXISTS trabalhos_grupo_entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo_id INT NOT NULL,
    arquivo VARCHAR(255),
    descricao TEXT,
    nota DECIMAL(5,2),
    feedback TEXT,
    data_entrega DATETIME,
    status ENUM('pendente', 'entregue', 'corrigido') DEFAULT 'pendente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

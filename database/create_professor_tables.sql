-- Tabelas do Painel do Professor
-- Sistema Escolar CEAA

-- Tabela: forum_topicos
CREATE TABLE IF NOT EXISTS forum_topicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: forum_respostas
CREATE TABLE IF NOT EXISTS forum_respostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topico_id INT NOT NULL,
    usuario_id INT NOT NULL,
    conteudo TEXT NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topico_id) REFERENCES forum_topicos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: acompanhamento_individual
CREATE TABLE IF NOT EXISTS acompanhamento_individual (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    tipo ENUM('comportamental', 'academico', 'social', 'pedagogico') NOT NULL,
    descricao TEXT NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: avisos_responsaveis
CREATE TABLE IF NOT EXISTS avisos_responsaveis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    prioridade ENUM('alta', 'normal', 'baixa') DEFAULT 'normal',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: projetos
CREATE TABLE IF NOT EXISTS projetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: videoconferencias
CREATE TABLE IF NOT EXISTS videoconferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_hora DATETIME NOT NULL,
    link VARCHAR(500) NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: materiais_didaticos
CREATE TABLE IF NOT EXISTS materiais_didaticos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT,
    disciplina VARCHAR(100),
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    arquivo_nome VARCHAR(255) NOT NULL,
    arquivo_caminho VARCHAR(500) NOT NULL,
    arquivo_tipo VARCHAR(50) NOT NULL,
    arquivo_tamanho INT,
    data_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: provas
CREATE TABLE IF NOT EXISTS provas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    duracao_minutos INT DEFAULT 60,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: trabalhos_grupo
CREATE TABLE IF NOT EXISTS trabalhos_grupo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_entrega DATE NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: planejamento_aulas
CREATE TABLE IF NOT EXISTS planejamento_aulas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    data_aula DATE NOT NULL,
    conteudo TEXT NOT NULL,
    objetivos TEXT,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: chamada (se ainda não existir)
CREATE TABLE IF NOT EXISTS chamada (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    turma_id INT NOT NULL,
    professor_id INT NOT NULL,
    data DATE NOT NULL,
    status ENUM('presente', 'ausente', 'atraso') DEFAULT 'presente',
    observacoes TEXT,
    data_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_chamada (aluno_id, turma_id, data)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: notas (se ainda não existir)
CREATE TABLE IF NOT EXISTS notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    turma_id INT NOT NULL,
    professor_id INT NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    bimestre INT NOT NULL,
    nota DECIMAL(5,2) NOT NULL,
    observacoes TEXT,
    data_lancamento DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_nota (aluno_id, turma_id, disciplina, bimestre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para melhorar performance
CREATE INDEX idx_forum_topicos_professor ON forum_topicos(professor_id);
CREATE INDEX idx_forum_topicos_turma ON forum_topicos(turma_id);
CREATE INDEX idx_forum_respostas_topico ON forum_respostas(topico_id);
CREATE INDEX idx_acompanhamento_professor ON acompanhamento_individual(professor_id);
CREATE INDEX idx_acompanhamento_aluno ON acompanhamento_individual(aluno_id);
CREATE INDEX idx_avisos_professor ON avisos_responsaveis(professor_id);
CREATE INDEX idx_avisos_turma ON avisos_responsaveis(turma_id);
CREATE INDEX idx_projetos_professor ON projetos(professor_id);
CREATE INDEX idx_videoconferencias_professor ON videoconferencias(professor_id);
CREATE INDEX idx_materiais_professor ON materiais_didaticos(professor_id);
CREATE INDEX idx_provas_professor ON provas(professor_id);
CREATE INDEX idx_trabalhos_professor ON trabalhos_grupo(professor_id);
CREATE INDEX idx_planejamento_professor ON planejamento_aulas(professor_id);
CREATE INDEX idx_chamada_professor ON chamada(professor_id);
CREATE INDEX idx_chamada_data ON chamada(data);
CREATE INDEX idx_notas_professor ON notas(professor_id);
CREATE INDEX idx_notas_aluno ON notas(aluno_id);

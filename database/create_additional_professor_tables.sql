-- Tabelas Adicionais do Painel do Professor
-- Sistema Escolar CEAA

-- Tabela: bibliografia
CREATE TABLE IF NOT EXISTS bibliografia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255) NOT NULL,
    tipo VARCHAR(50),
    ano INT,
    descricao TEXT,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: recursos_compartilhados
CREATE TABLE IF NOT EXISTS recursos_compartilhados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    link VARCHAR(500),
    tipo VARCHAR(50),
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: recuperacao_notas
CREATE TABLE IF NOT EXISTS recuperacao_notas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    disciplina VARCHAR(100) NOT NULL,
    bimestre INT NOT NULL,
    nota_recuperacao DECIMAL(5,2) NOT NULL,
    observacoes TEXT,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: feedback_alunos
CREATE TABLE IF NOT EXISTS feedback_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    conteudo TEXT NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: horarios_atendimento
CREATE TABLE IF NOT EXISTS horarios_atendimento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    dia_semana VARCHAR(20) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    local VARCHAR(100),
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: controle_entregas
CREATE TABLE IF NOT EXISTS controle_entregas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    atividade VARCHAR(255) NOT NULL,
    data_entrega DATE NOT NULL,
    status ENUM('pendente', 'entregue', 'atrasado') DEFAULT 'pendente',
    observacoes TEXT,
    data_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: avaliacoes_formativas
CREATE TABLE IF NOT EXISTS avaliacoes_formativas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data DATE NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: gamificacao
CREATE TABLE IF NOT EXISTS gamificacao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    turma_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    pontos INT NOT NULL,
    descricao TEXT,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: anotacoes_alunos
CREATE TABLE IF NOT EXISTS anotacoes_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    aluno_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    conteudo TEXT NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: comunicacao_professores
CREATE TABLE IF NOT EXISTS comunicacao_professores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    remetente_id INT NOT NULL,
    destinatario_id INT NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remetente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: calendario_pessoal
CREATE TABLE IF NOT EXISTS calendario_pessoal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    professor_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    tipo VARCHAR(50),
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para melhorar performance
CREATE INDEX idx_bibliografia_professor ON bibliografia(professor_id);
CREATE INDEX idx_bibliografia_turma ON bibliografia(turma_id);
CREATE INDEX idx_recursos_professor ON recursos_compartilhados(professor_id);
CREATE INDEX idx_recursos_turma ON recursos_compartilhados(turma_id);
CREATE INDEX idx_recuperacao_professor ON recuperacao_notas(professor_id);
CREATE INDEX idx_recuperacao_aluno ON recuperacao_notas(aluno_id);
CREATE INDEX idx_feedback_professor ON feedback_alunos(professor_id);
CREATE INDEX idx_feedback_aluno ON feedback_alunos(aluno_id);
CREATE INDEX idx_atendimento_professor ON horarios_atendimento(professor_id);
CREATE INDEX idx_entregas_professor ON controle_entregas(professor_id);
CREATE INDEX idx_entregas_aluno ON controle_entregas(aluno_id);
CREATE INDEX idx_formativas_professor ON avaliacoes_formativas(professor_id);
CREATE INDEX idx_gamificacao_professor ON gamificacao(professor_id);
CREATE INDEX idx_anotacoes_professor ON anotacoes_alunos(professor_id);
CREATE INDEX idx_anotacoes_aluno ON anotacoes_alunos(aluno_id);
CREATE INDEX idx_comunicacao_remetente ON comunicacao_professores(remetente_id);
CREATE INDEX idx_comunicacao_destinatario ON comunicacao_professores(destinatario_id);
CREATE INDEX idx_calendario_professor ON calendario_pessoal(professor_id);

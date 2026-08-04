-- Tabela de Visitas
CREATE TABLE IF NOT EXISTS visitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitante_nome VARCHAR(255) NOT NULL,
    visitante_documento VARCHAR(50),
    visitante_telefone VARCHAR(20),
    tipo_visita ENUM('pais', 'autoridade', 'fornecedor', 'manutencao', 'outros') NOT NULL,
    motivo TEXT,
    data_visita DATE NOT NULL,
    hora_entrada TIME,
    hora_saida TIME,
    setor VARCHAR(100),
    autorizado_por INT NOT NULL,
    status ENUM('agendada', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'agendada',
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Autorizações
CREATE TABLE IF NOT EXISTS autorizacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    tipo ENUM('saida_escolar', 'atividade_extracurricular', 'passeio', 'medical', 'outros') NOT NULL,
    descricao TEXT NOT NULL,
    data_solicitacao DATE NOT NULL,
    data_validade DATE,
    responsavel_nome VARCHAR(255) NOT NULL,
    responsavel_documento VARCHAR(50),
    responsavel_telefone VARCHAR(20),
    status ENUM('pendente', 'aprovada', 'rejeitada', 'expirada') DEFAULT 'pendente',
    aprovado_por INT,
    data_aprovacao DATETIME,
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

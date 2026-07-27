-- Tabelas Adicionais do Painel do Admin
-- Sistema Escolar CEAA

-- Tabela: arquivos
CREATE TABLE IF NOT EXISTS arquivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_original VARCHAR(255) NOT NULL,
    nome_unico VARCHAR(255) NOT NULL,
    tipo VARCHAR(100),
    tamanho BIGINT,
    caminho VARCHAR(500) NOT NULL,
    usuario_id INT NOT NULL,
    data_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: notificacoes
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    destinatario ENUM('todos', 'alunos', 'professores', 'secretaria') DEFAULT 'todos',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: integracoes
CREATE TABLE IF NOT EXISTS integracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    api_key VARCHAR(255),
    endpoint VARCHAR(500),
    descricao TEXT,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: suporte_tickets
CREATE TABLE IF NOT EXISTS suporte_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    categoria VARCHAR(50),
    prioridade ENUM('baixa', 'normal', 'alta', 'urgente') DEFAULT 'normal',
    status ENUM('aberto', 'em_andamento', 'resolvido', 'fechado') DEFAULT 'aberto',
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: escala_horarios
CREATE TABLE IF NOT EXISTS escala_horarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    dia_semana VARCHAR(20) NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    local VARCHAR(100),
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: patrimonio
CREATE TABLE IF NOT EXISTS patrimonio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('equipamento', 'mobilio', 'material', 'software') NOT NULL,
    descricao TEXT,
    local VARCHAR(100),
    quantidade INT DEFAULT 1,
    valor DECIMAL(10,2) DEFAULT 0.00,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabela: logs_acesso (se não existir)
CREATE TABLE IF NOT EXISTS logs_acesso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    data_login DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para melhorar performance
CREATE INDEX idx_arquivos_usuario ON arquivos(usuario_id);
CREATE INDEX idx_notificacoes_tipo ON notificacoes(tipo);
CREATE INDEX idx_integracoes_status ON integracoes(status);
CREATE INDEX idx_suporte_status ON suporte_tickets(status);
CREATE INDEX idx_escala_usuario ON escala_horarios(usuario_id);
CREATE INDEX idx_patrimonio_tipo ON patrimonio(tipo);
CREATE INDEX idx_logs_usuario ON logs_acesso(usuario_id);
CREATE INDEX idx_logs_data ON logs_acesso(data_login);

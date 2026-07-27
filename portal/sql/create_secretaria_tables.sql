-- Tabela para gerenciar valores de mensalidade por série
CREATE TABLE IF NOT EXISTS mensalidade_valores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serie VARCHAR(50) NOT NULL UNIQUE,
    valor_base DECIMAL(10, 2) NOT NULL,
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para gerenciar datas de vencimento
CREATE TABLE IF NOT EXISTS mensalidade_vencimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    dia_vencimento INT NOT NULL,
    valor_adicional DECIMAL(10, 2) DEFAULT 0,
    obrigatorio TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    ordem INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para gerenciar descontos
CREATE TABLE IF NOT EXISTS mensalidade_descontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('irmao', 'antes_vencimento') NOT NULL,
    nome VARCHAR(100) NOT NULL,
    valor_percentual DECIMAL(5, 2) NOT NULL,
    valor_fixo DECIMAL(10, 2) DEFAULT 0,
    condicao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para gerenciar mensalidades dos alunos
CREATE TABLE IF NOT EXISTS aluno_mensalidades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    serie VARCHAR(50) NOT NULL,
    valor_base DECIMAL(10, 2) NOT NULL,
    desconto_irmao DECIMAL(10, 2) DEFAULT 0,
    desconto_vencimento DECIMAL(10, 2) DEFAULT 0,
    valor_final DECIMAL(10, 2) NOT NULL,
    ano_referencia INT NOT NULL,
    mes_referencia INT NOT NULL,
    data_vencimento DATE NOT NULL,
    data_pagamento DATE,
    status ENUM('pendente', 'pago', 'atrasado') DEFAULT 'pendente',
    forma_pagamento VARCHAR(50),
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY (aluno_id, mes_referencia, ano_referencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para gerenciar irmãos (para desconto)
CREATE TABLE IF NOT EXISTS aluno_irmaos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    irmao_id INT NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (irmao_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para gerenciar documentação de alunos
CREATE TABLE IF NOT EXISTS aluno_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    tipo_documento VARCHAR(50) NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_arquivo VARCHAR(255) NOT NULL,
    data_validade DATE,
    status ENUM('pendente', 'aprovado', 'reprovado', 'vencido') DEFAULT 'pendente',
    observacoes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para gerenciar histórico financeiro
CREATE TABLE IF NOT EXISTS financeiro_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    tipo_movimento ENUM('receita', 'despesa') NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    descricao TEXT,
    valor DECIMAL(10, 2) NOT NULL,
    data_movimento DATE NOT NULL,
    forma_pagamento VARCHAR(50),
    referencia_mensalidade_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aluno_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (referencia_mensalidade_id) REFERENCES aluno_mensalidades(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir dados iniciais de vencimentos
INSERT INTO mensalidade_vencimentos (nome, dia_vencimento, valor_adicional, obrigatorio, ordem) VALUES
('Vencimento Padrão', 10, 0, 1, 1),
('Vencimento Antecipado (5 dias)', 5, -5, 0, 2),
('Vencimento Tardio (5 dias)', 15, 5, 0, 3);

-- Inserir dados iniciais de descontos
INSERT INTO mensalidade_descontos (tipo, nome, valor_percentual, valor_fixo, condicao) VALUES
('irmao', 'Desconto Irmão', 5, 0, 'Desconto para cada irmão matriculado'),
('antes_vencimento', 'Pagamento Antecipado', 3, 0, 'Pagamento até 5 dias antes do vencimento');

-- Inserir valores iniciais de mensalidade por série
INSERT INTO mensalidade_valores (serie, valor_base, descricao) VALUES
('1º Ano', 500.00, 'Mensalidade base para 1º Ano'),
('2º Ano', 500.00, 'Mensalidade base para 2º Ano'),
('3º Ano', 500.00, 'Mensalidade base para 3º Ano'),
('4º Ano', 500.00, 'Mensalidade base para 4º Ano'),
('5º Ano', 500.00, 'Mensalidade base para 5º Ano'),
('6º Ano', 550.00, 'Mensalidade base para 6º Ano'),
('7º Ano', 550.00, 'Mensalidade base para 7º Ano'),
('8º Ano', 550.00, 'Mensalidade base para 8º Ano'),
('9º Ano', 600.00, 'Mensalidade base para 9º Ano'),
('Ensino Médio', 650.00, 'Mensalidade base para Ensino Médio');

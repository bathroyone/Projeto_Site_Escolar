-- Tabela de Boletins Gerados
CREATE TABLE IF NOT EXISTS boletins_gerados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    turma_id INT NOT NULL,
    ano_letivo INT NOT NULL,
    bimestre INT NOT NULL,
    media_geral DECIMAL(5,2) DEFAULT 0,
    total_faltas INT DEFAULT 0,
    percentual_frequencia DECIMAL(5,2) DEFAULT 0,
    status ENUM('aprovado', 'reprovado', 'recuperacao', 'pendente') DEFAULT 'pendente',
    observacoes TEXT,
    gerado_por INT NOT NULL,
    arquivo VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (aluno_id, turma_id, ano_letivo, bimestre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Configuração de Boletins
CREATE TABLE IF NOT EXISTS boletins_configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ano_letivo INT NOT NULL,
    media_minima_aprovacao DECIMAL(5,2) DEFAULT 7.00,
    frequencia_minima_aprovacao DECIMAL(5,2) DEFAULT 75.00,
    numero_bimestres INT DEFAULT 4,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (ano_letivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Histórico de Geração
CREATE TABLE IF NOT EXISTS boletins_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    boletim_id INT NOT NULL,
    acao ENUM('criado', 'atualizado', 'excluido', 'impresso') NOT NULL,
    usuario_id INT NOT NULL,
    dados_antigos TEXT,
    dados_novos TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configuração padrão para o ano atual
INSERT INTO boletins_configuracoes (ano_letivo, media_minima_aprovacao, frequencia_minima_aprovacao, numero_bimestres) VALUES
(YEAR(CURDATE()), 7.00, 75.00, 4)
ON DUPLICATE KEY UPDATE media_minima_aprovacao=VALUES(media_minima_aprovacao);

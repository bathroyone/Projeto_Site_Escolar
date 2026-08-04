-- Tabela de Tickets de Suporte
CREATE TABLE IF NOT EXISTS suporte_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    categoria ENUM('tecnico', 'acesso', 'duvida', 'sugestao', 'bug', 'outros') NOT NULL,
    prioridade ENUM('baixa', 'normal', 'alta', 'urgente') DEFAULT 'normal',
    status ENUM('aberto', 'em_analise', 'resolvido', 'fechado') DEFAULT 'aberto',
    solicitante_id INT NOT NULL,
    atribuido_a INT,
    data_abertura DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_fechamento DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Respostas de Suporte
CREATE TABLE IF NOT EXISTS suporte_respostas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    usuario_id INT NOT NULL,
    mensagem TEXT NOT NULL,
    interno TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

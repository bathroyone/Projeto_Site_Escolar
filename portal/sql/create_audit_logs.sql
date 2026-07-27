-- Tabela de logs de auditoria
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    usuario_nome VARCHAR(255),
    usuario_tipo VARCHAR(50),
    acao VARCHAR(100) NOT NULL,
    tabela VARCHAR(100),
    registro_id INT,
    dados_antigos TEXT,
    dados_novos TEXT,
    ip VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_acao (acao),
    INDEX idx_tabela (tabela),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de tipos de ações para facilitar o gerenciamento
CREATE TABLE IF NOT EXISTS audit_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    descricao VARCHAR(255) NOT NULL,
    categoria VARCHAR(50) NOT NULL,
    ativo TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir ações padrão
INSERT INTO audit_actions (codigo, descricao, categoria) VALUES
-- Usuários
('USUARIO_CREATE', 'Criação de usuário', 'usuarios'),
('USUARIO_UPDATE', 'Atualização de usuário', 'usuarios'),
('USUARIO_DELETE', 'Exclusão de usuário', 'usuarios'),
('USUARIO_LOGIN', 'Login de usuário', 'usuarios'),
('USUARIO_LOGOUT', 'Logout de usuário', 'usuarios'),
-- Turmas
('TURMA_CREATE', 'Criação de turma', 'turmas'),
('TURMA_UPDATE', 'Atualização de turma', 'turmas'),
('TURMA_DELETE', 'Exclusão de turma', 'turmas'),
-- Notas
('NOTA_CREATE', 'Lançamento de nota', 'notas'),
('NOTA_UPDATE', 'Atualização de nota', 'notas'),
('NOTA_DELETE', 'Exclusão de nota', 'notas'),
-- Financeiro
('FINANCEIRO_CREATE', 'Registro financeiro', 'financeiro'),
('FINANCEIRO_UPDATE', 'Atualização financeira', 'financeiro'),
('FINANCEIRO_DELETE', 'Exclusão financeira', 'financeiro'),
-- Sistema
('BACKUP_CREATE', 'Criação de backup', 'sistema'),
('BACKUP_RESTORE', 'Restauração de backup', 'sistema'),
('CONFIG_UPDATE', 'Atualização de configuração', 'sistema')
ON DUPLICATE KEY UPDATE descricao=VALUES(descricao);

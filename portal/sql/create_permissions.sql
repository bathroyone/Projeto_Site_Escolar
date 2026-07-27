-- Tabela de Roles
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    nivel INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Permissões
CREATE TABLE IF NOT EXISTS permissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(100) NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    modulo VARCHAR(50) NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_modulo (modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Permissões por Role
CREATE TABLE IF NOT EXISTS role_permissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permissao_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permissao_id) REFERENCES permissoes(id) ON DELETE CASCADE,
    UNIQUE KEY (role_id, permissao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de Roles por Usuário
CREATE TABLE IF NOT EXISTS usuario_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY (usuario_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir Roles padrão
INSERT INTO roles (nome, descricao, nivel) VALUES
('Super Admin', 'Acesso total ao sistema', 100),
('Admin', 'Administrador geral', 90),
('Secretaria', 'Acesso à secretaria', 70),
('Professor', 'Acesso a funções de professor', 50),
('Aluno', 'Acesso limitado de aluno', 30),
('Visitante', 'Acesso mínimo', 10)
ON DUPLICATE KEY UPDATE descricao=VALUES(descricao), nivel=VALUES(nivel);

-- Inserir Permissões padrão
INSERT INTO permissoes (codigo, nome, descricao, modulo) VALUES
-- Usuários
('usuario.view', 'Visualizar usuários', 'Permite visualizar lista de usuários', 'usuarios'),
('usuario.create', 'Criar usuários', 'Permite criar novos usuários', 'usuarios'),
('usuario.edit', 'Editar usuários', 'Permite editar usuários existentes', 'usuarios'),
('usuario.delete', 'Excluir usuários', 'Permite excluir usuários', 'usuarios'),
('usuario.roles', 'Gerenciar roles', 'Permite atribuir roles aos usuários', 'usuarios'),
-- Turmas
('turma.view', 'Visualizar turmas', 'Permite visualizar lista de turmas', 'turmas'),
('turma.create', 'Criar turmas', 'Permite criar novas turmas', 'turmas'),
('turma.edit', 'Editar turmas', 'Permite editar turmas existentes', 'turmas'),
('turma.delete', 'Excluir turmas', 'Permite excluir turmas', 'turmas'),
-- Notas
('nota.view', 'Visualizar notas', 'Permite visualizar notas dos alunos', 'notas'),
('nota.create', 'Lançar notas', 'Permite lançar notas', 'notas'),
('nota.edit', 'Editar notas', 'Permite editar notas', 'notas'),
('nota.delete', 'Excluir notas', 'Permite excluir notas', 'notas'),
-- Financeiro
('financeiro.view', 'Visualizar financeiro', 'Permite visualizar dados financeiros', 'financeiro'),
('financeiro.create', 'Criar registros', 'Permite criar registros financeiros', 'financeiro'),
('financeiro.edit', 'Editar registros', 'Permite editar registros financeiros', 'financeiro'),
('financeiro.delete', 'Excluir registros', 'Permite excluir registros financeiros', 'financeiro'),
('financeiro.relatorios', 'Relatórios financeiros', 'Permite gerar relatórios financeiros', 'financeiro'),
-- Sistema
('sistema.backup', 'Backup do sistema', 'Permite fazer backup do sistema', 'sistema'),
('sistema.restore', 'Restaurar backup', 'Permite restaurar backup', 'sistema'),
('sistema.config', 'Configurações', 'Permite alterar configurações do sistema', 'sistema'),
('sistema.audit', 'Logs de auditoria', 'Permite visualizar logs de auditoria', 'sistema'),
('sistema.roles', 'Gerenciar roles', 'Permite gerenciar roles e permissões', 'sistema'),
-- Secretaria
('secretaria.matricula', 'Gerenciar matrículas', 'Permite gerenciar matrículas', 'secretaria'),
('secretaria.declaracoes', 'Emitir declarações', 'Permite emitir declarações', 'secretaria'),
('secretaria.documentos', 'Gerenciar documentos', 'Permite gerenciar documentos', 'secretaria')
ON DUPLICATE KEY UPDATE nome=VALUES(nome), descricao=VALUES(descricao);

-- Atribuir permissões ao Super Admin (todas)
INSERT INTO role_permissoes (role_id, permissao_id)
SELECT 1, id FROM permissoes
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Atribuir permissões ao Admin (exceto sistema.roles)
INSERT INTO role_permissoes (role_id, permissao_id)
SELECT 2, id FROM permissoes WHERE codigo != 'sistema.roles'
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Atribuir permissões à Secretaria
INSERT INTO role_permissoes (role_id, permissao_id)
SELECT 3, id FROM permissoes WHERE modulo IN ('usuarios', 'turmas', 'financeiro', 'secretaria')
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Atribuir permissões ao Professor
INSERT INTO role_permissoes (role_id, permissao_id)
SELECT 4, id FROM permissoes WHERE modulo IN ('turmas', 'notas')
ON DUPLICATE KEY UPDATE role_id=role_id;

-- Atribuir permissões ao Aluno
INSERT INTO role_permissoes (role_id, permissao_id)
SELECT 5, id FROM permissoes WHERE codigo IN ('nota.view', 'turma.view')
ON DUPLICATE KEY UPDATE role_id=role_id;

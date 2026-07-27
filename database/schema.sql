-- Banco de dados para Sistema de Gestão Escolar
-- Criado em: 23/07/2026
-- Este é um schema genérico que pode ser adaptado para qualquer instituição educacional

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS escola_gestao CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE escola_gestao;

-- Tabela de usuários (alunos, professores e admin)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NULL,
    senha VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('aluno', 'professor', 'admin') NOT NULL,
    turma VARCHAR(50) NULL,
    serie VARCHAR(50) NULL,
    matricula VARCHAR(50) UNIQUE NULL,
    cpf VARCHAR(14) UNIQUE NULL,
    usuario_login VARCHAR(50) UNIQUE NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE
);

-- Tabela de turmas
CREATE TABLE turmas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    serie VARCHAR(50) NOT NULL,
    ano_letivo INT NOT NULL,
    professor_id INT NULL,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY (nome, serie, ano_letivo)
);

-- Tabela de arquivos (trabalhos, correções, materiais)
CREATE TABLE arquivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    tipo_arquivo ENUM('trabalho', 'correcao', 'material', 'video') NOT NULL,
    caminho_arquivo VARCHAR(500) NOT NULL,
    turma_id INT NULL,
    serie VARCHAR(50) NULL,
    disciplina VARCHAR(100) NULL,
    professor_id INT NOT NULL,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_expiracao TIMESTAMP NULL,
    visibilidade ENUM('publico', 'turma', 'serie', 'privado') DEFAULT 'turma',
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de eventos do calendário
CREATE TABLE eventos_calendario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NULL,
    tipo_evento ENUM('prova', 'trabalho', 'reuniao', 'feriado', 'evento_escolar', 'outro') NOT NULL,
    turma_id INT NULL,
    serie VARCHAR(50) NULL,
    criador_id INT NOT NULL,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (criador_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de avisos
CREATE TABLE avisos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    tipo_aviso ENUM('geral', 'turma', 'serie', 'urgente') DEFAULT 'geral',
    turma_id INT NULL,
    serie VARCHAR(50) NULL,
    professor_id INT NOT NULL,
    data_publicacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_expiracao TIMESTAMP NULL,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
    FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de notificações
CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    tipo_notificacao ENUM('arquivo', 'aviso', 'evento', 'sistema') NOT NULL,
    link VARCHAR(500) NULL,
    lida BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Índices para melhorar performance
CREATE INDEX idx_usuarios_tipo ON usuarios(tipo_usuario);
CREATE INDEX idx_usuarios_turma ON usuarios(turma);
CREATE INDEX idx_usuarios_serie ON usuarios(serie);
CREATE INDEX idx_arquivos_turma ON arquivos(turma_id);
CREATE INDEX idx_arquivos_serie ON arquivos(serie);
CREATE INDEX idx_arquivos_tipo ON arquivos(tipo_arquivo);
CREATE INDEX idx_arquivos_professor ON arquivos(professor_id);
CREATE INDEX idx_eventos_data ON eventos_calendario(data_inicio);
CREATE INDEX idx_eventos_turma ON eventos_calendario(turma_id);
CREATE INDEX idx_avisos_turma ON avisos(turma_id);
CREATE INDEX idx_notificacoes_usuario ON notificacoes(usuario_id);
CREATE INDEX idx_notificacoes_lida ON notificacoes(lida);

-- Inserir usuário admin padrão
-- Usuário: admin | Senha: admin123 (deve ser alterada após primeiro acesso)
INSERT INTO usuarios (nome_completo, email, senha, tipo_usuario, usuario_login) 
VALUES ('Administrador', 'admin@escola.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'admin');

-- Inserir usuário professor de teste
-- Matrícula: PRO2026001 | Senha: prof123
INSERT INTO usuarios (nome_completo, email, senha, tipo_usuario, matricula) 
VALUES ('Professor Teste', 'professor@escola.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'professor', 'PRO2026001');

-- Inserir usuário aluno de teste
-- CPF: 123.456.789-00 | Senha: aluno123
INSERT INTO usuarios (nome_completo, email, senha, tipo_usuario, cpf, turma, serie) 
VALUES ('Aluno Teste', 'aluno@escola.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'aluno', '123.456.789-00', 'Turma A', '1º Ano');

-- Inserir turmas de exemplo
INSERT INTO turmas (nome, serie, ano_letivo) VALUES
('Turma A', '1º Ano', 2026),
('Turma B', '1º Ano', 2026),
('Turma A', '2º Ano', 2026),
('Turma B', '2º Ano', 2026),
('Turma A', '3º Ano', 2026),
('Turma B', '3º Ano', 2026),
('Turma A', '4º Ano', 2026),
('Turma B', '4º Ano', 2026),
('Turma A', '5º Ano', 2026),
('Turma B', '5º Ano', 2026),
('Turma A', '6º Ano', 2026),
('Turma B', '6º Ano', 2026),
('Turma A', '7º Ano', 2026),
('Turma B', '7º Ano', 2026),
('Turma A', '8º Ano', 2026),
('Turma B', '8º Ano', 2026),
('Turma A', '9º Ano', 2026),
('Turma B', '9º Ano', 2026);

-- Tabela para gerenciar livros da biblioteca virtual
CREATE TABLE IF NOT EXISTS biblioteca_livros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    autor VARCHAR(255) NOT NULL,
    categoria VARCHAR(100) NOT NULL,
    subcategoria VARCHAR(100) NOT NULL,
    arquivo_pdf VARCHAR(255) NOT NULL,
    capa_imagem VARCHAR(255),
    descricao TEXT,
    data_upload DATETIME DEFAULT CURRENT_TIMESTAMP,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para gerenciar categorias da biblioteca
CREATE TABLE IF NOT EXISTS biblioteca_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL UNIQUE,
    icone VARCHAR(50),
    cor_gradiente_inicio VARCHAR(20),
    cor_gradiente_fim VARCHAR(20),
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela para gerenciar subcategorias
CREATE TABLE IF NOT EXISTS biblioteca_subcategorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    arquivo_html VARCHAR(100),
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES biblioteca_categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir categorias iniciais
INSERT INTO biblioteca_categorias (nome, icone, cor_gradiente_inicio, cor_gradiente_fim, ordem) VALUES
('Livro Infantil', 'fa-child', 'from-verde-complementar', 'to-verde-claro', 1),
('Livro Didático', 'fa-book', 'from-azul-principal', 'to-azul-claro', 2),
('Ciências', 'fa-flask', 'from-amarelo-destaque', 'to-orange-400', 3),
('Literatura', 'fa-pencil', 'from-purple-600', 'to-pink-500', 4),
('Diversão', 'fa-star', 'from-pink-500', 'to-rose-400', 5),
('Família', 'fa-home', 'from-orange-500', 'to-amber-500', 6);

-- Inserir subcategorias iniciais
INSERT INTO biblioteca_subcategorias (categoria_id, nome, arquivo_html, ordem) VALUES
-- Livro Infantil
(1, '03 a 05 anos', 'livro1.html', 1),
(1, '06 a 08 anos', 'livro2.html', 2),
(1, '08 a 12 anos', 'livro3.html', 3),
-- Livro Didático
(2, 'Educação Infantil', 'livro4.html', 1),
(2, 'Ensino Fundamental', 'livro5.html', 2),
(2, 'Ensino Médio', 'livro6.html', 3),
(2, 'ENEM', 'livro7.html', 4),
-- Ciências
(3, 'Ciências Biológicas', 'livro8.html', 1),
(3, 'Ciências Exatas', 'livro9.html', 2),
(3, 'Ciências Humanas', 'livro10.html', 3),
-- Literatura
(4, 'Literatura Brasileira', 'livro11.html', 1),
(4, 'Literatura Estrangeira', 'livro12.html', 2),
(4, 'Literatura de Cordel', 'livro13.html', 3),
-- Diversão
(5, 'Paradidáticos', 'livro14.html', 1),
(5, 'Ação e Aventura', 'livro15.html', 2),
(5, 'Romance', 'livro16.html', 3),
-- Família
(6, 'Cursos', 'livro17.html', 1),
(6, 'Religioso', 'livro18.html', 2),
(6, 'Receitas', 'livro19.html', 3);

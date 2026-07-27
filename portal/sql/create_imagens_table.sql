-- Tabela para gerenciar todas as imagens do site
CREATE TABLE IF NOT EXISTS site_imagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria VARCHAR(50) NOT NULL,
    subcategoria VARCHAR(50),
    nome_arquivo VARCHAR(255) NOT NULL,
    caminho_completo VARCHAR(255) NOT NULL,
    descricao TEXT,
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir categorias iniciais
INSERT INTO site_imagens (categoria, subcategoria, nome_arquivo, caminho_completo, descricao, ordem) VALUES
('logo', NULL, 'logo.jpg', 'img/logo.jpg', 'Logo principal do site', 1),
('carousel', 'slide1', 'carousel-slide-1.jpg', 'img/carousel/slide-1.jpg', 'Primeiro slide do carousel', 1),
('carousel', 'slide2', 'carousel-slide-2.jpg', 'img/carousel/slide-2.jpg', 'Segundo slide do carousel', 2),
('carousel', 'slide3', 'carousel-slide-3.jpg', 'img/carousel/slide-3.jpg', 'Terceiro slide do carousel', 3),
('carousel', 'slide4', 'carousel-slide-4.jpg', 'img/carousel/slide-4.jpg', 'Quarto slide do carousel', 4);

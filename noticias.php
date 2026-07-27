<?php
require_once 'portal/config.php';

// Criar tabela de notícias se não existir
$conn = getDBConnection();
$conn->query("CREATE TABLE IF NOT EXISTS noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    imagem VARCHAR(255),
    categoria ENUM('geral', 'eventos', 'academico', 'esportes', 'cultura') DEFAULT 'geral',
    destaque TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter notícias
$noticias = [];
try {
    $stmt = $conn->query("SELECT * FROM noticias WHERE ativo = 1 ORDER BY data_publicacao DESC LIMIT 20");
    $noticias = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter notícias: " . $e->getMessage());
}

// Obter notícias em destaque
$noticias_destaque = [];
try {
    $stmt = $conn->query("SELECT * FROM noticias WHERE destaque = 1 AND ativo = 1 ORDER BY data_publicacao DESC LIMIT 3");
    $noticias_destaque = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter notícias em destaque: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notícias | [Inserir nome da escola aqui]</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        azul: {
                            principal: '#063b7a',
                            escuro: '#082b54',
                            claro: '#0b4a8c'
                        },
                        amarelo: {
                            destaque: '#ffd000',
                            claro: '#ffe033'
                        },
                        verde: {
                            complementar: '#13843b',
                            claro: '#15a048'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Poppins', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2">
                        <img src="img/logo.jpg" alt="Logo" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">[Inserir nome da escola aqui]</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">[Inserir nome da escola aqui]</span>
                        </div>
                    </a>
                </div>
                
                <nav class="hidden md:flex items-center gap-6">
                    <a href="index.php" class="text-gray-600 hover:text-azul-principal transition-colors">Início</a>
                    <a href="noticias.php" class="text-azul-principal font-semibold">Notícias</a>
                    <a href="eventos/eventos.html" class="text-gray-600 hover:text-azul-principal transition-colors">Eventos</a>
                    <a href="biblioteca_vrtual/livro.html" class="text-gray-600 hover:text-azul-principal transition-colors">Biblioteca</a>
                    <a href="portal/login.php" class="px-4 py-2 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                        Acesso
                    </a>
                </nav>
                
                <button onclick="toggleMobileMenu()" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-bars text-gray-600"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="fixed inset-0 z-50 md:hidden hidden">
        <div id="menu-overlay" class="absolute inset-0 bg-black/80 backdrop-blur-sm opacity-0 transition-opacity duration-300"></div>
        <div id="menu-drawer" class="absolute right-0 top-0 h-full w-80 bg-gray-900 shadow-2xl transform translate-x-full transition-transform duration-300">
            <div class="p-6">
                <div class="flex items-center justify-between mb-8">
                    <img src="img/logo.jpg" alt="Logo" class="h-10">
                    <button onclick="toggleMobileMenu()" class="p-2 rounded-lg hover:bg-gray-800">
                        <i class="fas fa-times text-white"></i>
                    </button>
                </div>
                <nav class="flex flex-col gap-4">
                    <a href="index.php" class="text-white hover:text-amarelo-destaque transition-colors py-2">Início</a>
                    <a href="noticias.php" class="text-amarelo-destaque font-semibold py-2">Notícias</a>
                    <a href="eventos/eventos.html" class="text-white hover:text-amarelo-destaque transition-colors py-2">Eventos</a>
                    <a href="biblioteca_vrtual/livro.html" class="text-white hover:text-amarelo-destaque transition-colors py-2">Biblioteca</a>
                    <a href="portal/login.php" class="px-4 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl text-center mt-4">
                        Acesso
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header da Página -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-display font-bold text-azul-principal mb-4">Notícias e Atualizações</h1>
            <p class="text-gray-600 text-lg">Fique por dentro de tudo que acontece na escola</p>
        </div>

        <!-- Notícias em Destaque -->
        <?php if (!empty($noticias_destaque)): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-display font-bold text-azul-principal mb-6">Em Destaque</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($noticias_destaque as $noticia): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow">
                            <?php if ($noticia['imagem']): ?>
                                <img src="<?php echo htmlspecialchars($noticia['imagem']); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>" class="w-full h-48 object-cover">
                            <?php else: ?>
                                <div class="w-full h-48 bg-gradient-to-br from-azul-principal to-verde-complementar flex items-center justify-center">
                                    <i class="fas fa-newspaper text-white text-4xl"></i>
                                </div>
                            <?php endif; ?>
                            <div class="p-6">
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_categoria = match($noticia['categoria']) {
                                            'geral' => 'bg-gray-100 text-gray-600',
                                            'eventos' => 'bg-blue-100 text-blue-600',
                                            'academico' => 'bg-green-100 text-green-600',
                                            'esportes' => 'bg-orange-100 text-orange-600',
                                            'cultura' => 'bg-purple-100 text-purple-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_categoria;
                                        ?>">
                                        <?php echo ucfirst($noticia['categoria']); ?>
                                    </span>
                                    <span class="text-gray-500 text-xs"><?php echo date('d/m/Y', strtotime($noticia['data_publicacao'])); ?></span>
                                </div>
                                <h3 class="font-bold text-gray-800 text-lg mb-2"><?php echo htmlspecialchars($noticia['titulo']); ?></h3>
                                <p class="text-gray-600 text-sm line-clamp-3"><?php echo htmlspecialchars(substr(strip_tags($noticia['conteudo']), 0, 150)) . '...'; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Todas as Notícias -->
        <div>
            <h2 class="text-2xl font-display font-bold text-azul-principal mb-6">Todas as Notícias</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($noticias as $noticia): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow">
                        <?php if ($noticia['imagem']): ?>
                            <img src="<?php echo htmlspecialchars($noticia['imagem']); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>" class="w-full h-48 object-cover">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                <i class="fas fa-newspaper text-gray-400 text-4xl"></i>
                            </div>
                        <?php endif; ?>
                        <div class="p-6">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                    <?php 
                                    $cor_categoria = match($noticia['categoria']) {
                                        'geral' => 'bg-gray-100 text-gray-600',
                                        'eventos' => 'bg-blue-100 text-blue-600',
                                        'academico' => 'bg-green-100 text-green-600',
                                        'esportes' => 'bg-orange-100 text-orange-600',
                                        'cultura' => 'bg-purple-100 text-purple-600',
                                        default => 'bg-gray-100 text-gray-600'
                                    };
                                    echo $cor_categoria;
                                    ?>">
                                    <?php echo ucfirst($noticia['categoria']); ?>
                                </span>
                                <span class="text-gray-500 text-xs"><?php echo date('d/m/Y', strtotime($noticia['data_publicacao'])); ?></span>
                            </div>
                            <h3 class="font-bold text-gray-800 text-lg mb-2"><?php echo htmlspecialchars($noticia['titulo']); ?></h3>
                            <p class="text-gray-600 text-sm line-clamp-3"><?php echo htmlspecialchars(substr(strip_tags($noticia['conteudo']), 0, 150)) . '...'; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($noticias)): ?>
                <div class="text-center py-12 text-gray-500">
                    <i class="fas fa-newspaper text-4xl mb-2"></i>
                    <p class="text-lg">Nenhuma notícia publicada ainda.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <img src="img/logo.jpg" alt="Logo" class="h-12 mb-4">
                    <p class="text-gray-400 text-sm">[Inserir nome da escola aqui]</p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Links Rápidos</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="index.php" class="hover:text-white transition-colors">Início</a></li>
                        <li><a href="noticias.php" class="hover:text-white transition-colors">Notícias</a></li>
                        <li><a href="eventos/eventos.html" class="hover:text-white transition-colors">Eventos</a></li>
                        <li><a href="biblioteca_vrtual/livro.html" class="hover:text-white transition-colors">Biblioteca</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Contato</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><i class="fas fa-phone mr-2"></i>(XX) XXXX-XXXX</li>
                        <li><i class="fas fa-envelope mr-2"></i>contato@escola.com</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i>Endereço da escola</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Redes Sociais</h4>
                    <div class="flex gap-4">
                        <a href="#" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-facebook text-xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-instagram text-xl"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition-colors"><i class="fab fa-youtube text-xl"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p>© <?php echo date('Y'); ?> [Inserir nome da escola aqui]. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            const overlay = document.getElementById('menu-overlay');
            const drawer = document.getElementById('menu-drawer');
            
            menu.classList.toggle('hidden');
            
            if (!menu.classList.contains('hidden')) {
                setTimeout(() => {
                    overlay.classList.remove('opacity-0');
                    drawer.classList.remove('translate-x-full');
                }, 10);
            } else {
                overlay.classList.add('opacity-0');
                drawer.classList.add('translate-x-full');
            }
        }

        document.getElementById('menu-overlay').addEventListener('click', toggleMobileMenu);
    </script>
</body>
</html>

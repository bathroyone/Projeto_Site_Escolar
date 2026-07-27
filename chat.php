<?php
require_once 'portal/config.php';

// Criar tabela de mensagens de chat se não existir
$conn = getDBConnection();
$conn->query("CREATE TABLE IF NOT EXISTS chat_mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    mensagem TEXT NOT NULL,
    status ENUM('pendente', 'respondido', 'fechado') DEFAULT 'pendente',
    ip VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$success = '';
$error = '';

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (empty($nome) || empty($mensagem)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO chat_mensagens (nome, email, mensagem, ip) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $mensagem, $ip]);
            $success = 'Mensagem enviada com sucesso! Entraremos em contato em breve.';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar mensagem.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Online | [Inserir nome da escola aqui]</title>
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
                    <a href="noticias.php" class="text-gray-600 hover:text-azul-principal transition-colors">Notícias</a>
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
                    <a href="noticias.php" class="text-white hover:text-amarelo-destaque transition-colors py-2">Notícias</a>
                    <a href="eventos/eventos.html" class="text-white hover:text-amarelo-destaque transition-colors py-2">Eventos</a>
                    <a href="biblioteca_vrtual/livro.html" class="text-white hover:text-amarelo-destaque transition-colors py-2">Biblioteca</a>
                    <a href="portal/login.php" class="px-4 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl text-center mt-4">
                        Acesso
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-display font-bold text-azul-principal mb-4">Chat Online</h1>
            <p class="text-gray-600 text-lg">Entre em contato conosco</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-verde-complementar">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-comments text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-display font-bold text-white">Suporte Online</h2>
                        <p class="text-white/80 text-sm">Horário de funcionamento: Seg-Sex, 8h às 18h</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="enviar">
                    
                    <div class="mb-4">
                        <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome *</label>
                        <input type="text" id="nome" name="nome" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Seu nome">
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input type="email" id="email" name="email"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="seu@email.com">
                    </div>
                    
                    <div class="mb-4">
                        <label for="mensagem" class="block text-sm font-semibold text-gray-700 mb-2">Mensagem *</label>
                        <textarea id="mensagem" name="mensagem" rows="5" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Como podemos ajudar?"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Enviar Mensagem
                    </button>
                </form>
            </div>
        </div>

        <!-- Informações de Contato -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-14 h-14 bg-azul-principal/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-phone text-azul-principal text-2xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Telefone</h3>
                <p class="text-gray-600">(XX) XXXX-XXXX</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-envelope text-green-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Email</h3>
                <p class="text-gray-600">contato@escola.com</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-map-marker-alt text-orange-600 text-2xl"></i>
                </div>
                <h3 class="font-bold text-gray-800 mb-2">Endereço</h3>
                <p class="text-gray-600">Rua da Escola, 123</p>
            </div>
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

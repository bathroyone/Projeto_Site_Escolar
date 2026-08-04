<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acessibilidade | Site da Escola</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .alto-contraste {
            --bg-primary: #000000;
            --bg-secondary: #1a1a1a;
            --text-primary: #ffffff;
            --text-secondary: #ffff00;
        }
        
        .alto-contraste body {
            background-color: var(--bg-primary) !important;
            color: var(--text-primary) !important;
        }
        
        .alto-contraste .bg-gray-900,
        .alto-contraste .bg-gray-800,
        .alto-contraste .bg-white/5 border border-white/10 backdrop-blur-sm/10,
        .alto-contraste .bg-gradient-to-r {
            background-color: var(--bg-secondary) !important;
            color: var(--text-primary) !important;
        }
        
        .alto-contraste .text-white {
            color: var(--text-primary) !important;
        }
        
        .alto-contraste .text-gray-400,
        .alto-contraste .text-white/50 {
            color: var(--text-secondary) !important;
        }
        
        .alto-contraste .border-white/20 {
            border-color: #ffffff !important;
        }
        
        .alto-contraste button,
        .alto-contraste a {
            color: var(--text-primary) !important;
            border: 2px solid #ffffff !important;
        }
        
        .alto-contraste input,
        .alto-contraste textarea,
        .alto-contraste select {
            background-color: #ffffff !important;
            color: #000000 !important;
            border: 2px solid #ffffff !important;
        }
        
        .libras-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .libras-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #063b7a 0%, #13843b 100%);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(6, 59, 122, 0.4);
            transition: all 0.3s ease;
        }
        
        .libras-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(6, 59, 122, 0.5);
        }
        
        .libras-window {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 350px;
            max-width: calc(100vw - 40px);
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            display: none;
            flex-direction: column;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .libras-header {
            background: linear-gradient(135deg, #063b7a 0%, #13843b 100%);
            padding: 20px;
            color: white;
        }
        
        .libras-content {
            padding: 20px;
            background: #f8f9fa;
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-azul-principal to-verde-complementar shadow-[0_8px_30px_rgb(0,0,0,0.5)] sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 group">
                        <img src="img/logo.jpg" alt="Logo" class="h-12">
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs tracking-wide">ACESSIBILIDADE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">E INCLUSÃO</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="index.php" class="px-6 py-2.5 bg-white/5 border border-white/10 backdrop-blur-sm/20 text-white rounded-full font-semibold hover:bg-white/5 border border-white/10 backdrop-blur-sm/30 transition-all">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Banner -->
        <div class="bg-gradient-to-r from-azul-principal to-verde-complementar rounded-3xl p-8 mb-12 text-center">
            <h1 class="text-3xl md:text-4xl font-display font-bold text-white mb-4">
                <i class="fas fa-universal-access mr-3"></i>Acessibilidade e Inclusão
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Recursos de acessibilidade para garantir que todos tenham acesso ao conteúdo do site.
            </p>
        </div>

        <!-- Ferramentas de Acessibilidade -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 mb-12 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-tools mr-2 text-amarelo-destaque"></i>Ferramentas de Acessibilidade
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <button onclick="toggleAltoContraste()" class="bg-white/5 border border-white/10 backdrop-blur-sm/10 rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all text-center group">
                    <div class="w-16 h-16 bg-gradient-to-br from-black to-gray-800 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-adjust text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Alto Contraste</h3>
                    <p class="text-gray-400 text-sm">Aumentar contraste de cores</p>
                </button>

                <button onclick="aumentarFonte()" class="bg-white/5 border border-white/10 backdrop-blur-sm/10 rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all text-center group">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-text-height text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Aumentar Fonte</h3>
                    <p class="text-gray-400 text-sm">Aumentar tamanho do texto</p>
                </button>

                <button onclick="diminuirFonte()" class="bg-white/5 border border-white/10 backdrop-blur-sm/10 rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all text-center group">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-text-height text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Diminuir Fonte</h3>
                    <p class="text-gray-400 text-sm">Diminuir tamanho do texto</p>
                </button>

                <button onclick="resetarAcessibilidade()" class="bg-white/5 border border-white/10 backdrop-blur-sm/10 rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all text-center group">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-undo text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Resetar</h3>
                    <p class="text-gray-400 text-sm">Voltar ao padrão</p>
                </button>
            </div>
        </div>

        <!-- Libras -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 mb-12 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-hands mr-2 text-amarelo-destaque"></i>Tradutor Libras
            </h2>
            <div class="text-center mb-6">
                <p class="text-gray-400 mb-4">Nossa plataforma oferece suporte à Língua Brasileira de Sinais (Libras) para garantir acessibilidade a todos os usuários.</p>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/5 border border-white/10 backdrop-blur-sm/10 rounded-full">
                    <i class="fas fa-hands text-amarelo-destaque"></i>
                    <span class="text-white text-sm">VLibras Widget disponível em todo o site</span>
                </div>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-video text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Vídeos com Libras</h3>
                    <p class="text-gray-400 text-sm">Conteúdo em vídeo traduzido para Libras.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-closed-captioning text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Legendas</h3>
                    <p class="text-gray-400 text-sm">Legendas em todos os vídeos do site.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-universal-access text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Acessível</h3>
                    <p class="text-gray-400 text-sm">Interface adaptada para usuários surdos.</p>
                </div>
            </div>
        </div>

        <!-- Recursos Adicionais -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-info-circle mr-2 text-amarelo-destaque"></i>Outros Recursos
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white/5 border border-white/10 backdrop-blur-sm/5 rounded-xl p-6">
                    <i class="fas fa-keyboard text-amarelo-destaque text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-2">Navegação por Teclado</h3>
                    <p class="text-gray-400 text-sm">Todo o site é navegável usando apenas o teclado.</p>
                </div>
                <div class="bg-white/5 border border-white/10 backdrop-blur-sm/5 rounded-xl p-6">
                    <i class="fas fa-eye text-amarelo-destaque text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-2">Leitores de Tela</h3>
                    <p class="text-gray-400 text-sm">Compatível com leitores de tela como NVDA e JAWS.</p>
                </div>
                <div class="bg-white/5 border border-white/10 backdrop-blur-sm/5 rounded-xl p-6">
                    <i class="fas fa-mobile-alt text-amarelo-destaque text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-2">Responsivo</h3>
                    <p class="text-gray-400 text-sm">Adaptado para diferentes dispositivos e tamanhos de tela.</p>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-gray-400 text-sm">© <?php echo date('Y'); ?> [Inserir nome da escola aqui]. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        let fontSize = 100;
        let altoContraste = false;

        function toggleAltoContraste() {
            altoContraste = !altoContraste;
            document.body.classList.toggle('alto-contraste');
        }

        function aumentarFonte() {
            if (fontSize < 150) {
                fontSize += 10;
                document.documentElement.style.fontSize = fontSize + '%';
            }
        }

        function diminuirFonte() {
            if (fontSize > 80) {
                fontSize -= 10;
                document.documentElement.style.fontSize = fontSize + '%';
            }
        }

        function resetarAcessibilidade() {
            fontSize = 100;
            altoContraste = false;
            document.documentElement.style.fontSize = '100%';
            document.body.classList.remove('alto-contraste');
        }

        // Navegação por teclado
        document.addEventListener('keydown', function(e) {
            // Alt + C para alto contraste
            if (e.altKey && e.key === 'c') {
                e.preventDefault();
                toggleAltoContraste();
            }
            // Alt + + para aumentar fonte
            if (e.altKey && (e.key === '+' || e.key === '=')) {
                e.preventDefault();
                aumentarFonte();
            }
            // Alt + - para diminuir fonte
            if (e.altKey && e.key === '-') {
                e.preventDefault();
                diminuirFonte();
            }
            // Alt + R para resetar
            if (e.altKey && e.key === 'r') {
                e.preventDefault();
                resetarAcessibilidade();
            }
        });
    </script>
</body>
</html>


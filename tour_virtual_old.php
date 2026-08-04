<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Virtual 360° | Site da Escola</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
                            <span class="text-white font-bold text-xs tracking-wide">TOUR VIRTUAL</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">360°</span>
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
                <i class="fas fa-vr-cardboard mr-3"></i>Tour Virtual 360°
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Conheça nossa escola sem sair de casa com o tour virtual interativo.
            </p>
        </div>

        <!-- Locais -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white-20 hover:border-amarelo-destaque/50 transition-all">
                <div class="h-48 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-school text-white text-5xl"></i>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">Entrada Principal</h3>
                <p class="text-gray-400 text-sm mb-4">Acesso principal e área de recepção da escola.</p>
                <button class="w-full py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 text-white rounded-xl font-semibold hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 transition-colors">
                    <i class="fas fa-play mr-2"></i>Iniciar Tour
                </button>
            </div>

            <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white-20 hover:border-amarelo-destaque/50 transition-all">
                <div class="h-48 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-chalkboard-teacher text-white text-5xl"></i>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">Sala de Aula</h3>
                <p class="text-gray-400 text-sm mb-4">Ambiente moderno e equipado para o aprendizado.</p>
                <button class="w-full py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 text-white rounded-xl font-semibold hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 transition-colors">
                    <i class="fas fa-play mr-2"></i>Iniciar Tour
                </button>
            </div>

            <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white-20 hover:border-amarelo-destaque/50 transition-all">
                <div class="h-48 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-flask text-white text-5xl"></i>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">Laboratório</h3>
                <p class="text-gray-400 text-sm mb-4">Laboratório de ciências equipado para experimentos.</p>
                <button class="w-full py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 text-white rounded-xl font-semibold hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 transition-colors">
                    <i class="fas fa-play mr-2"></i>Iniciar Tour
                </button>
            </div>

            <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white-20 hover:border-amarelo-destaque/50 transition-all">
                <div class="h-48 bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-book text-white text-5xl"></i>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">Biblioteca</h3>
                <p class="text-gray-400 text-sm mb-4">Espaço de leitura e pesquisa com acervo completo.</p>
                <button class="w-full py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 text-white rounded-xl font-semibold hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 transition-colors">
                    <i class="fas fa-play mr-2"></i>Iniciar Tour
                </button>
            </div>

            <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white-20 hover:border-amarelo-destaque/50 transition-all">
                <div class="h-48 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-running text-white text-5xl"></i>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">Quadra Esportiva</h3>
                <p class="text-gray-400 text-sm mb-4">Área para atividades físicas e esportes.</p>
                <button class="w-full py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 text-white rounded-xl font-semibold hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 transition-colors">
                    <i class="fas fa-play mr-2"></i>Iniciar Tour
                </button>
            </div>

            <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white-20 hover:border-amarelo-destaque/50 transition-all">
                <div class="h-48 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center mb-4">
                    <i class="fas fa-utensils text-white text-5xl"></i>
                </div>
                <h3 class="text-white font-semibold text-lg mb-2">Refeitório</h3>
                <p class="text-gray-400 text-sm mb-4">Espaço de alimentação com refeições balanceadas.</p>
                <button class="w-full py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 text-white rounded-xl font-semibold hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 transition-colors">
                    <i class="fas fa-play mr-2"></i>Iniciar Tour
                </button>
            </div>
        </div>

        <!-- Informações -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 border border-white-20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-info-circle mr-2 text-amarelo-destaque"></i>Como Funciona
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-mouse text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Navegação</h3>
                    <p class="text-gray-400 text-sm">Use o mouse para arrastar e explorar o ambiente 360°.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-expand-arrows-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Zoom</h3>
                    <p class="text-gray-400 text-sm">Aproxime-se dos detalhes usando o scroll do mouse.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-map-marker-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Pontos de Interesse</h3>
                    <p class="text-gray-400 text-sm">Clique nos hotspots para informações detalhadas.</p>
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
</body>
</html>


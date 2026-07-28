<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redes Sociais | Site da Escola</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-azul-principal to-verde-complementar shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 group">
                        <img src="img/logo.jpg" alt="Logo" class="h-12">
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs tracking-wide">REDES</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">SOCIAIS</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="index.php" class="px-6 py-2.5 bg-white/20 text-white rounded-full font-semibold hover:bg-white/30 transition-all">
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
                <i class="fas fa-share-alt mr-3"></i>Redes Sociais
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Siga-nos nas redes sociais e fique por dentro de todas as novidades da escola.
            </p>
        </div>

        <!-- Redes Sociais -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <a href="https://facebook.com" target="_blank" class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:border-blue-500/50 transition-all group">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fab fa-facebook-f text-white text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-xl">Facebook</h3>
                        <p class="text-gray-400 text-sm">Siga nossa página</p>
                    </div>
                </div>
                <p class="text-gray-400 text-sm">Compartilhe novidades, fotos e eventos da nossa comunidade escolar.</p>
            </a>

            <a href="https://instagram.com" target="_blank" class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:border-pink-500/50 transition-all group">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-purple-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fab fa-instagram text-white text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-xl">Instagram</h3>
                        <p class="text-gray-400 text-sm">@escola</p>
                    </div>
                </div>
                <p class="text-gray-400 text-sm">Momentos especiais, fotos do dia a dia e stories da escola.</p>
            </a>

            <a href="https://youtube.com" target="_blank" class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:border-red-500/50 transition-all group">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fab fa-youtube text-white text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-xl">YouTube</h3>
                        <p class="text-gray-400 text-sm">Canal oficial</p>
                    </div>
                </div>
                <p class="text-gray-400 text-sm">Vídeos de eventos, aulas e conteúdo institucional.</p>
            </a>

            <a href="https://twitter.com" target="_blank" class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:border-sky-500/50 transition-all group">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-sky-500 to-sky-600 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fab fa-twitter text-white text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-xl">Twitter</h3>
                        <p class="text-gray-400 text-sm">@escola</p>
                    </div>
                </div>
                <p class="text-gray-400 text-sm">Avisos rápidos e notícias em tempo real.</p>
            </a>

            <a href="https://linkedin.com" target="_blank" class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:border-blue-700/50 transition-all group">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-700 to-blue-800 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fab fa-linkedin-in text-white text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-xl">LinkedIn</h3>
                        <p class="text-gray-400 text-sm">Empresa</p>
                    </div>
                </div>
                <p class="text-gray-400 text-sm">Vagas de emprego e networking profissional.</p>
            </a>

            <a href="https://tiktok.com" target="_blank" class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 hover:border-black/50 transition-all group">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-black to-gray-800 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fab fa-tiktok text-white text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold text-xl">TikTok</h3>
                        <p class="text-gray-400 text-sm">@escola</p>
                    </div>
                </div>
                <p class="text-gray-400 text-sm">Conteúdo criativo e tendências educacionais.</p>
            </a>
        </div>

        <!-- Widget de Feed -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white-20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-hashtag mr-2 text-amarelo-destaque"></i>Siga-nos
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-pink-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fab fa-instagram text-white text-4xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Instagram</h3>
                    <p class="text-gray-400 text-sm mb-4">Veja nosso feed</p>
                    <a href="https://instagram.com" target="_blank" class="inline-block px-6 py-2 bg-white/10 text-white rounded-full font-semibold hover:bg-white/20 transition-colors">
                        Acessar
                    </a>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fab fa-facebook-f text-white text-4xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Facebook</h3>
                    <p class="text-gray-400 text-sm mb-4">Curta nossa página</p>
                    <a href="https://facebook.com" target="_blank" class="inline-block px-6 py-2 bg-white/10 text-white rounded-full font-semibold hover:bg-white/20 transition-colors">
                        Acessar
                    </a>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fab fa-youtube text-white text-4xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">YouTube</h3>
                    <p class="text-gray-400 text-sm mb-4">Inscreva-se</p>
                    <a href="https://youtube.com" target="_blank" class="inline-block px-6 py-2 bg-white/10 text-white rounded-full font-semibold hover:bg-white/20 transition-colors">
                        Acessar
                    </a>
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

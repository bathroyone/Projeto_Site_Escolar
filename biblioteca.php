<?php
session_start();
require_once 'portal/config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Biblioteca Virtual | Site da Escola</title>
  <link rel="stylesheet" href="css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Inter', system-ui, sans-serif;
      background: linear-gradient(135deg, #0a2463 0%, #1e3a8a 50%, #0a2463 100%);
      min-height: 100vh;
      overflow-x: hidden;
    }
    
    .bg-animation {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      overflow: hidden;
    }
    .bg-animation::before {
      content: '';
      position: absolute;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle at 20% 80%, rgba(255, 215, 0, 0.1) 0%, transparent 50%),
                  radial-gradient(circle at 80% 20%, rgba(34, 197, 94, 0.1) 0%, transparent 50%),
                  radial-gradient(circle at 40% 40%, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
      animation: bgMove 20s ease-in-out infinite;
    }
    @keyframes bgMove {
      0%, 100% { transform: translate(0, 0); }
      50% { transform: translate(-50%, -50%); }
    }
    
    .glass {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .particle {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 215, 0, 0.3);
      animation: float 15s infinite;
    }
    @keyframes float {
      0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0; }
      10% { opacity: 1; }
      90% { opacity: 1; }
      100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
    }
    
    .header-animate {
      animation: slideDown 0.8s ease-out;
    }
    @keyframes slideDown {
      from { transform: translateY(-100%); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    
    .hero-animate {
      animation: fadeInUp 1s ease-out 0.3s both;
    }
    @keyframes fadeInUp {
      from { transform: translateY(50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    
    .btn-animate {
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease;
    }
    .btn-animate::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      transform: translate(-50%, -50%);
      transition: width 0.6s ease, height 0.6s ease;
    }
    .btn-animate:hover::before {
      width: 300px;
      height: 300px;
    }
    .btn-animate:hover {
      transform: translateY(-3px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }
    
    .card-3d {
      transition: all 0.5s ease;
      transform-style: preserve-3d;
    }
    .card-3d:hover {
      transform: translateY(-10px) rotateX(5deg);
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
    }
    
    .gradient-text {
      background: linear-gradient(135deg, #ffd700, #ffed4a, #fbbf24);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .pulse {
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    .glow {
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
    }
    
    .mobile-menu {
      transform: translateX(100%);
      transition: transform 0.3s ease;
    }
    .mobile-menu.active {
      transform: translateX(0);
    }
  </style>
</head>
<body class="min-h-screen text-white">
  
  <div class="bg-animation"></div>
  <div id="particles"></div>
  
  <header class="fixed top-0 left-0 right-0 z-50 glass header-animate">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">
        <a href="index.php" class="flex items-center gap-3 group">
          <img src="img/logo.jpg" alt="Logo" class="h-14 w-auto transition-transform duration-300 group-hover:scale-110">
        </a>

        <nav class="hidden lg:flex items-center gap-8">
          <a href="index.php" class="text-white/90 hover:text-white font-medium transition-colors relative group">
            Início
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-yellow-400 to-yellow-600 transition-all duration-300 group-hover:w-full"></span>
          </a>
          <a href="#about" class="text-white/90 hover:text-white font-medium transition-colors relative group">
            Sobre
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-yellow-400 to-yellow-600 transition-all duration-300 group-hover:w-full"></span>
          </a>
          <a href="#projects" class="text-white/90 hover:text-white font-medium transition-colors relative group">
            Projetos
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-yellow-400 to-yellow-600 transition-all duration-300 group-hover:w-full"></span>
          </a>
          <a href="#contact" class="text-white/90 hover:text-white font-medium transition-colors relative group">
            Contato
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-yellow-400 to-yellow-600 transition-all duration-300 group-hover:w-full"></span>
          </a>
        </nav>

        <div class="hidden md:flex items-center gap-3">
          <a href="index.php" class="btn-animate px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-full font-semibold text-sm flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
            <span>Voltar</span>
          </a>
        </div>

        <button id="mobile-menu-btn" class="lg:hidden p-3 rounded-xl text-white hover:bg-white/10 transition-colors">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>
    </div>
  </header>

  <div id="mobile-menu" class="fixed inset-0 z-50 lg:hidden mobile-menu">
    <div id="menu-overlay" class="absolute inset-0 bg-black/90 backdrop-blur-sm"></div>
    <div class="absolute right-0 top-0 h-full w-80 bg-gradient-to-b from-blue-900 to-blue-950 shadow-2xl p-6">
      <div class="flex items-center justify-between mb-8">
        <img src="img/logo.jpg" alt="Logo" class="h-12">
        <button id="close-menu" class="p-3 rounded-xl hover:bg-white/10 transition-colors">
          <i class="fas fa-times text-xl text-white"></i>
        </button>
      </div>
      <nav class="flex flex-col gap-4">
        <a href="index.php" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-xl transition-colors">Início</a>
        <a href="#about" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-xl transition-colors">Sobre</a>
        <a href="#projects" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-xl transition-colors">Projetos</a>
        <a href="#contact" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-xl transition-colors">Contato</a>
        <div class="border-t border-white/10 pt-4 mt-4">
          <a href="index.php" class="block px-4 py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-xl font-semibold text-center hover:shadow-lg transition-all flex items-center justify-center gap-2">
            <i class="fas fa-arrow-left"></i>Voltar
          </a>
        </div>
      </nav>
    </div>
  </div>

  <section class="relative min-h-screen flex items-center justify-center pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
      <div class="text-center hero-animate">
        <div class="inline-block mb-6 px-6 py-3 glass rounded-full">
          <span class="text-emerald-400 font-semibold text-sm">
            <i class="fas fa-book mr-2"></i>Biblioteca Virtual
          </span>
        </div>
        
        <h1 class="text-5xl md:text-7xl lg:text-8xl font-display font-bold mb-6 leading-tight">
          <span class="gradient-text">Biblioteca</span>
          <br>
          <span class="text-white">Virtual</span>
        </h1>
        
        <p class="text-xl md:text-2xl text-white/80 mb-10 max-w-3xl mx-auto leading-relaxed">
          Acesse livros, revistas e materiais educacionais de onde estiver.
        </p>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto mb-16">
          <div class="card-3d glass rounded-2xl p-6 cursor-pointer">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-book text-white text-2xl"></i>
            </div>
            <h3 class="text-white font-semibold text-lg mb-2">Livros</h3>
            <p class="text-white/60 text-sm">Acervo completo de livros digitais</p>
          </div>
          
          <div class="card-3d glass rounded-2xl p-6 cursor-pointer">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-newspaper text-white text-2xl"></i>
            </div>
            <h3 class="text-white font-semibold text-lg mb-2">Revistas</h3>
            <p class="text-white/60 text-sm">Publicações e periódicos</p>
          </div>
          
          <div class="card-3d glass rounded-2xl p-6 cursor-pointer">
            <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-file-alt text-white text-2xl"></i>
            </div>
            <h3 class="text-white font-semibold text-lg mb-2">Apostilas</h3>
            <p class="text-white/60 text-sm">Materiais didáticos e apostilas</p>
          </div>
          
          <div class="card-3d glass rounded-2xl p-6 cursor-pointer">
            <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-video text-white text-2xl"></i>
            </div>
            <h3 class="text-white font-semibold text-lg mb-2">Vídeos</h3>
            <p class="text-white/60 text-sm">Conteúdo audiovisual educativo</p>
          </div>
          
          <div class="card-3d glass rounded-2xl p-6 cursor-pointer">
            <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-pink-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-headphones text-white text-2xl"></i>
            </div>
            <h3 class="text-white font-semibold text-lg mb-2">Áudio</h3>
            <p class="text-white/60 text-sm">Podcasts e audiolivros</p>
          </div>
          
          <div class="card-3d glass rounded-2xl p-6 cursor-pointer">
            <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-cyan-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-search text-white text-2xl"></i>
            </div>
            <h3 class="text-white font-semibold text-lg mb-2">Pesquisa</h3>
            <p class="text-white/60 text-sm">Busca avançada no acervo</p>
          </div>
        </div>
        
        <div class="glass rounded-2xl p-8 max-w-2xl mx-auto">
          <h3 class="text-2xl font-bold text-white mb-6 text-center">
            <i class="fas fa-search mr-2 text-emerald-400"></i>Buscar no Acervo
          </h3>
          <div class="flex gap-4">
            <input type="text" placeholder="Digite o título, autor ou assunto..." class="flex-1 px-6 py-4 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-emerald-400 transition-all">
            <button class="btn-animate px-8 py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-bold flex items-center gap-2 glow">
              <i class="fas fa-search"></i>
              <span>Buscar</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <script>
    function createParticles() {
      const container = document.getElementById('particles');
      for (let i = 0; i < 50; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.width = Math.random() * 10 + 5 + 'px';
        particle.style.height = particle.style.width;
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = Math.random() * 10 + 10 + 's';
        container.appendChild(particle);
      }
    }
    createParticles();

    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const closeMenuBtn = document.getElementById('close-menu');
    const menuOverlay = document.getElementById('menu-overlay');

    mobileMenuBtn.addEventListener('click', () => {
      mobileMenu.classList.add('active');
    });

    closeMenuBtn.addEventListener('click', () => {
      mobileMenu.classList.remove('active');
    });

    menuOverlay.addEventListener('click', () => {
      mobileMenu.classList.remove('active');
    });

    gsap.from('.hero-animate', {
      opacity: 0,
      y: 50,
      duration: 1,
      delay: 0.3
    });

    gsap.from('.card-3d', {
      opacity: 0,
      y: 30,
      duration: 0.8,
      stagger: 0.1,
      delay: 0.5
    });
  </script>
</body>
</html>

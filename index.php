<?php
session_start();
require_once 'portal/config.php';
$isLoggedIn = isLoggedIn();
$userName = $_SESSION['nome'] ?? '';
$userType = $_SESSION['tipo_usuario'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Meta Tags SEO -->
  <title>Site Institucional Moderno e Sistema de Gestão Escolar</title>
  <meta name="description" content="Site institucional moderno e sistema de gestão escolar completo para instituições educacionais. Portal para alunos, professores e administradores.">
  <meta name="keywords" content="escola, educação, gestão escolar, portal educacional, sistema escolar, biblioteca virtual, eventos escolares">
  <meta name="author" content="Sistema de Gestão Escolar">
  <meta name="robots" content="index, follow">
  
  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://seusite.com.br/">
  <meta property="og:title" content="Site Institucional Moderno e Sistema de Gestão Escolar">
  <meta property="og:description" content="Site institucional moderno e sistema de gestão escolar completo para instituições educacionais.">
  <meta property="og:image" content="https://seusite.com.br/img/og-image.jpg">
  
  <!-- Twitter -->
  <meta property="twitter:card" content="summary_large_image">
  <meta property="twitter:url" content="https://seusite.com.br/">
  <meta property="twitter:title" content="Site Institucional Moderno e Sistema de Gestão Escolar">
  <meta property="twitter:description" content="Site institucional moderno e sistema de gestão escolar completo para instituições educacionais.">
  <meta property="twitter:image" content="https://seusite.com.br/img/og-image.jpg">
  
  <!-- Canonical URL -->
  <link rel="canonical" href="https://seusite.com.br/">
  
  <!-- PWA Manifest -->
  <link rel="manifest" href="manifest.json">
  
  <!-- Theme Color -->
  <meta name="theme-color" content="#0a2463">
  
  <!-- Apple Touch Icon -->
  <link rel="apple-touch-icon" href="img/logo.jpg">
  
  <!-- Favicon -->
  <link rel="shortcut icon" type="image/x-icon" href="img/logo.jpg">
  <link rel="stylesheet" href="css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="js/form-validation.js"></script>
  <script src="js/loading-states.js"></script>
  <script src="js/accessibility.js"></script>
  <script src="js/performance-animations.js"></script>
  
  <!-- Service Worker Registration -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
          .then((registration) => {
            console.log('Service Worker registrado com sucesso:', registration.scope);
          })
          .catch((error) => {
            console.log('Falha ao registrar Service Worker:', error);
          });
      });
    }
  </script>
  <style>
    body {
      font-family: 'Inter', system-ui, sans-serif;
    }
    .glass-dark {
      background: rgba(10, 36, 99, 0.85);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
    }
    .carousel-slide {
      position: absolute;
      inset: 0;
      opacity: 0;
      transition: opacity 0.8s ease;
    }
    .carousel-slide.active {
      opacity: 1;
    }
    .carousel-dot {
      transition: all 0.3s ease;
    }
    .carousel-dot.active {
      transform: scale(1.2);
    }
    .card-hover {
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .card-hover:hover {
      transform: translateY(-12px);
      box-shadow: 0 25px 50px rgba(10, 36, 99, 0.2);
    }
    .input-error {
      border-color: #ef4444 !important;
      box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }
    .error-message {
      animation: shake 0.5s ease-in-out;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }
    .floating {
      animation: floating 3s ease-in-out infinite;
    }
    @keyframes floating {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-10px); }
    }
    .gradient-text {
      background: linear-gradient(135deg, #ffd700, #ffed4a);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .nav-link {
      position: relative;
    }
    .nav-link::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 0;
      width: 0;
      height: 2px;
      background: #ffd700;
      transition: width 0.3s ease;
    }
    .nav-link:hover::after {
      width: 100%;
    }
  </style>
</head>
<body class="bg-gray-900 min-h-screen">
  
  <header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" id="main-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">
        <a href="#" class="flex items-center gap-3 group">
          <img src="img/logo.jpg" alt="Logo [Inserir nome da escola aqui]" class="h-14 w-auto">
        </a>

        <nav class="hidden lg:flex items-center gap-8">
          <a href="#" class="nav-link text-white/90 hover:text-white font-medium transition-colors">Início</a>
          <a href="#about" class="nav-link text-white/90 hover:text-white font-medium transition-colors">Sobre</a>
          <a href="#projects" class="nav-link text-white/90 hover:text-white font-medium transition-colors">Projetos</a>
          <a href="#gallery" class="nav-link text-white/90 hover:text-white font-medium transition-colors">Fotos</a>
          <a href="#contact" class="nav-link text-white/90 hover:text-white font-medium transition-colors">Contato</a>
        </nav>

        <div class="hidden md:flex items-center gap-2">
          <a href="biblioteca_vrtual/biblioteca.html" class="px-4 py-2.5 bg-gradient-to-r from-verde-complementar to-verde-claro text-white rounded-full font-semibold hover:shadow-lg hover:shadow-green-500/30 transition-all duration-300 text-sm whitespace-nowrap">
            <i class="fas fa-book mr-1"></i>Biblioteca
          </a>
          <?php if ($isLoggedIn): ?>
            <!-- Avatar do usuário logado -->
            <div class="relative ml-2">
              <button id="user-menu-btn" class="flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-azul-principal to-azul-claro text-white rounded-full font-semibold hover:shadow-lg hover:shadow-blue-500/30 transition-all duration-300 text-sm whitespace-nowrap">
                <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                  <i class="fas fa-user text-xs"></i>
                </div>
                <span class="max-w-[80px] truncate"><?php echo htmlspecialchars(substr($userName, 0, 10)); ?></span>
                <i class="fas fa-chevron-down text-xs flex-shrink-0"></i>
              </button>
              <!-- Menu dropdown -->
              <div id="user-menu-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl py-2 hidden z-50">
                <div class="px-4 py-2 border-b border-gray-100">
                  <p class="text-xs text-gray-500">Logado como</p>
                  <p class="text-sm font-semibold text-gray-800 truncate"><?php echo htmlspecialchars($userName); ?></p>
                  <p class="text-xs text-azul-principal font-medium capitalize"><?php echo htmlspecialchars($userType); ?></p>
                </div>
                <?php if ($userType === 'admin'): ?>
                  <a href="portal/admin/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-azul-principal transition-colors">
                    <i class="fas fa-cog mr-2"></i>Painel Admin
                  </a>
                <?php elseif ($userType === 'professor'): ?>
                  <a href="portal/professor/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-azul-principal transition-colors">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>Painel Professor
                  </a>
                <?php elseif ($userType === 'aluno'): ?>
                  <a href="portal/aluno/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-azul-principal transition-colors">
                    <i class="fas fa-graduation-cap mr-2"></i>Painel Aluno
                  </a>
                <?php endif; ?>
                <div class="border-t border-gray-100 mt-2 pt-2">
                  <a href="portal/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                    <i class="fas fa-sign-out-alt mr-2"></i>Sair
                  </a>
                </div>
              </div>
            </div>
          <?php else: ?>
            <button id="acesso-sistema-btn" class="ml-2 px-4 py-2.5 bg-gradient-to-r from-azul-principal to-azul-claro text-white rounded-full font-semibold hover:shadow-lg hover:shadow-blue-500/30 transition-all duration-300 text-sm whitespace-nowrap">
              <i class="fas fa-sign-in-alt mr-1"></i>Acesso
            </button>
          <?php endif; ?>
        </div>

        <button id="mobile-menu-btn" class="lg:hidden p-2 rounded-lg text-white hover:bg-white/10 transition-colors">
          <i class="fas fa-bars text-2xl"></i>
        </button>
      </div>
    </div>

    <div id="mobile-menu" class="fixed inset-0 z-50 lg:hidden">
      <div id="menu-overlay" class="absolute inset-0 bg-black/95 backdrop-blur-sm opacity-0 transition-opacity duration-300"></div>
      <div id="menu-drawer" class="absolute right-0 top-0 h-full w-80 bg-gray-900 shadow-2xl transform translate-x-full transition-transform duration-300">
        <div class="p-6">
          <div class="flex items-center justify-between mb-8">
            <img src="img/logo.jpg" alt="Logo [Inserir nome da escola aqui]" class="h-12">
            <button id="close-menu" class="p-2 rounded-lg hover:bg-white/10 transition-colors">
              <i class="fas fa-times text-xl text-white"></i>
            </button>
          </div>
          <nav class="flex flex-col gap-4">
            <a href="#" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-lg transition-colors">Início</a>
            <a href="#about" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-lg transition-colors">Sobre</a>
            <a href="#projects" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-lg transition-colors">Projetos</a>
            <a href="#gallery" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-lg transition-colors">Fotos</a>
            <a href="#contact" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-lg transition-colors">Contato</a>
            <div class="border-t border-white/10 pt-4 mt-4 space-y-3">
              <a href="biblioteca_vrtual/biblioteca.html" class="block px-4 py-3 bg-gradient-to-r from-verde-complementar to-verde-claro text-white rounded-lg font-semibold text-center hover:shadow-lg transition-all">
                <i class="fas fa-book mr-2"></i>Biblioteca
              </a>
              <?php if ($isLoggedIn): ?>
                <div class="px-4 py-3 bg-white/10 rounded-lg">
                  <p class="text-white/70 text-xs mb-1">Logado como</p>
                  <p class="text-white font-semibold"><?php echo htmlspecialchars(substr($userName, 0, 20)); ?></p>
                  <p class="text-amarelo-destaque text-xs font-medium capitalize"><?php echo htmlspecialchars($userType); ?></p>
                </div>
                <a href="portal/dashboard.php" class="block px-4 py-3 bg-gradient-to-r from-azul-principal to-azul-claro text-white rounded-lg font-semibold text-center hover:shadow-lg transition-all">
                  <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                </a>
                <?php if ($userType === 'admin'): ?>
                  <a href="portal/admin/index.php" class="block  px-4 py-3 bg-white/10 text-white rounded-lg font-semibold text-center hover:bg-white/20 transition-all">
                    <i class="fas fa-cog mr-2"></i>Painel Admin
                  </a>
                <?php endif; ?>
                <a href="portal/logout.php" class="block px-4 py-3 bg-red-500/20 text-red-300 rounded-lg font-semibold text-center hover:bg-red-500/30 transition-all">
                  <i class="fas fa-sign-out-alt mr-2"></i>Sair
                </a>
              <?php else: ?>
                <button id="acesso-sistema-btn-mobile" class="block w-full px-4 py-3 bg-gradient-to-r from-azul-principal to-azul-claro text-white rounded-lg font-semibold text-center hover:shadow-lg transition-all">
                  <i class="fas fa-sign-in-alt mr-2"></i>Acesso ao Sistema
                </button>
              <?php endif; ?>
              <a href="aviso2/passo1.html" target="_blank" class="block px-4 py-3 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-lg font-bold text-center hover:shadow-lg transition-all">
                <i class="fas fa-edit mr-2"></i>Correções
              </a>
            </div>
          </nav>
        </div>
      </div>
    </div>
  </header>

  <main class="pt-0">
    
    <section id="hero-carousel" class="relative h-screen overflow-hidden">
      <div class="relative h-full">
        <div class="carousel-slide active absolute inset-0 w-full h-full bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
          <div class="text-center p-8">
            <i class="fas fa-image text-6xl text-white/30 mb-4"></i>
            <p class="text-white/50 text-lg">Coloque aqui as fotos da sua escola</p>
          </div>
        </div>
        <div class="carousel-slide absolute inset-0 w-full h-full bg-gradient-to-br from-azul-principal to-azul-escuro flex items-center justify-center">
          <div class="text-center p-8">
            <i class="fas fa-image text-6xl text-white/30 mb-4"></i>
            <p class="text-white/50 text-lg">Coloque aqui as fotos da sua escola</p>
          </div>
        </div>
        <div class="carousel-slide absolute inset-0 w-full h-full bg-gradient-to-br from-verde-complementar to-verde-claro flex items-center justify-center">
          <div class="text-center p-8">
            <i class="fas fa-image text-6xl text-white/30 mb-4"></i>
            <p class="text-white/50 text-lg">Coloque aqui as fotos da sua escola</p>
          </div>
        </div>
        <div class="carousel-slide absolute inset-0 w-full h-full bg-gradient-to-br from-amarelo-destaque to-orange-500 flex items-center justify-center">
          <div class="text-center p-8">
            <i class="fas fa-image text-6xl text-white/30 mb-4"></i>
            <p class="text-white/50 text-lg">Coloque aqui as fotos da sua escola</p>
          </div>
        </div>
        
        <div class="absolute inset-0 bg-gradient-to-r from-gray-900/95 via-gray-900/80 to-transparent"></div>
        
        <div class="absolute inset-0 flex items-center">
          <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
            <div class="max-w-3xl">
              <span class="inline-block px-6 py-3 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-full font-bold text-sm mb-8 animate-on-scroll shadow-lg shadow-yellow-500/30">
                <i class="fas fa-star mr-2"></i>Matrículas 2026 Abertas
              </span>
              <h1 class="text-5xl md:text-7xl lg:text-8xl font-display font-bold text-white mb-6 leading-tight animate-on-scroll">
                Inserir o Nome da Escola Aqui
              </h1>
              <p class="text-xl md:text-2xl text-white/80 mb-10 animate-on-scroll leading-relaxed">
                Educação de excelência, acolhimento e tecnologia para formar grandes futuros.
              </p>
              <div class="flex flex-wrap gap-4 animate-on-scroll">
                <a href="#contact" class="px-8 py-4 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-full font-bold hover:shadow-xl hover:shadow-yellow-500/30 transition-all duration-300 transform hover:scale-105">
                  <i class="fas fa-phone mr-2"></i>Fale Conosco
                </a>
                <a href="pre_matricula.php" class="px-8 py-4 bg-gradient-to-r from-purple-600 to-purple-400 text-white rounded-full font-bold hover:shadow-xl hover:shadow-purple-500/30 transition-all duration-300 transform hover:scale-105">
                  <i class="fas fa-user-graduate mr-2"></i>Pré-Matrícula
                </a>
                <a href="agendar_visita.php" class="px-8 py-4 bg-gradient-to-r from-verde-complementar to-verde-claro text-white rounded-full font-bold hover:shadow-xl hover:shadow-green-500/30 transition-all duration-300 transform hover:scale-105">
                  <i class="fas fa-calendar-check mr-2"></i>Agendar Visita
                </a>
                <a href="biblioteca_vrtual/biblioteca.html" target="_blank" class="px-8 py-4 bg-white/10 backdrop-blur-md text-white rounded-full font-semibold hover:bg-white/20 transition-all duration-300 border border-white/30 transform hover:scale-105">
                  <i class="fas fa-book mr-2"></i>Biblioteca Virtual
                </a>
              </div>
            </div>
          </div>
        </div>

        <div class="absolute bottom-12 left-1/2 transform -translate-x-1/2 flex gap-3">
          <button class="carousel-dot w-3 h-3 rounded-full bg-white/50 hover:bg-white/80 active" data-index="0"></button>
          <button class="carousel-dot w-3 h-3 rounded-full bg-white/50 hover:bg-white/80" data-index="1"></button>
          <button class="carousel-dot w-3 h-3 rounded-full bg-white/50 hover:bg-white/80" data-index="2"></button>
          <button class="carousel-dot w-3 h-3 rounded-full bg-white/50 hover:bg-white/80" data-index="3"></button>
        </div>
      </div>
    </section>

    <section id="stats" class="py-20 bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 relative overflow-hidden">
      <div class="absolute inset-0 opacity-10">
        <div class="absolute top-0 left-0 w-96 h-96 bg-amarelo-destaque rounded-full filter blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-azul-principal rounded-full filter blur-3xl"></div>
      </div>
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
          <div class="text-center animate-on-scroll p-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
            <div class="text-5xl md:text-6xl font-display font-bold gradient-text mb-2" data-count="28" data-suffix="">0</div>
            <div class="text-white/80 font-semibold">Anos de Experiência</div>
          </div>
          <div class="text-center animate-on-scroll p-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
            <div class="text-5xl md:text-6xl font-display font-bold gradient-text mb-2" data-count="14" data-suffix="">0</div>
            <div class="text-white/80 font-semibold">Salas Climatizadas</div>
          </div>
          <div class="text-center animate-on-scroll p-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
            <div class="text-5xl md:text-6xl font-display font-bold gradient-text mb-2" data-count="54" data-suffix="">0</div>
            <div class="text-white/80 font-semibold">Câmeras de Segurança</div>
          </div>
          <div class="text-center animate-on-scroll p-6 bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10">
            <div class="text-5xl md:text-6xl font-display font-bold gradient-text mb-2" data-count="4" data-suffix="">0</div>
            <div class="text-white/80 font-semibold">Alarmes</div>
          </div>
        </div>
      </div>
    </section>

    <section id="about" class="py-24 bg-gradient-to-b from-gray-900 to-gray-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-2 gap-16 items-center">
          <div class="animate-on-scroll">
            <span class="text-verde-complementar font-bold text-sm uppercase tracking-wider">Sobre Nós</span>
            <h2 class="text-4xl md:text-5xl font-display font-bold text-white mt-3 mb-8">
              Um pouco sobre a nossa escola
            </h2>
            <p class="text-gray-300 mb-6 leading-relaxed text-lg">
              Nossa escola conta com um ambiente agradável e confortável para o bem-estar dos alunos e dos nossos funcionários. Montamos um sistema de segurança para manter nossas crianças e jovens mais protegidos.
            </p>
            <p class="text-gray-300 mb-8 leading-relaxed text-lg">
              O [Inserir nome da escola aqui] oferece formação da educação infantil ao 9º ano, circuito interno de câmera, aulas de informática, língua inglesa e espanhola, atividades extracurriculares, quadra, seguro escolar 24h, Karatê, dança, projetos multidisciplinares, ética e cidadania.
            </p>
            <div class="flex flex-wrap gap-3">
              <span class="px-5 py-2.5 bg-white/10 text-white rounded-full text-sm font-semibold border border-white/20 hover:bg-white/20 transition-colors">Educação Infantil</span>
              <span class="px-5 py-2.5 bg-white/10 text-white rounded-full text-sm font-semibold border border-white/20 hover:bg-white/20 transition-colors">Ensino Fundamental</span>
              <span class="px-5 py-2.5 bg-white/10 text-white rounded-full text-sm font-semibold border border-white/20 hover:bg-white/20 transition-colors">Inglês e Espanhol</span>
              <span class="px-5 py-2.5 bg-white/10 text-white rounded-full text-sm font-semibold border border-white/20 hover:bg-white/20 transition-colors">Informática</span>
              <span class="px-5 py-2.5 bg-white/10 text-white rounded-full text-sm font-semibold border border-white/20 hover:bg-white/20 transition-colors">Karatê</span>
            </div>
          </div>
          <div class="relative animate-on-scroll">
            <div class="absolute inset-0 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-3xl transform rotate-6 opacity-50 blur-xl"></div>
            <div class="relative bg-white/5 backdrop-blur-sm rounded-3xl p-2 border border-white/10">
              <div class="rounded-2xl w-full h-96 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center">
                <div class="text-center p-8">
                  <i class="fas fa-image text-5xl text-white/30 mb-4"></i>
                  <p class="text-white/50">Coloque aqui a foto da escola</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="projects" class="py-24 bg-gray-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 animate-on-scroll">
          <span class="text-verde-complementar font-bold text-sm uppercase tracking-wider">Projetos</span>
          <h2 class="text-4xl md:text-5xl font-display font-bold text-white mt-3 mb-6">
            Aprendizagem em sala e além da sala
          </h2>
          <p class="text-gray-400 max-w-2xl mx-auto text-lg italic">
            "Tenha em mente que tudo que você aprende na escola é trabalho de muitas gerações."
            <span class="not-italic font-semibold gradient-text"> — Albert Einstein</span>
          </p>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="h-48 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center p-4">
              <div class="text-center">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a imagem</p>
              </div>
            </div>
            <div class="p-6">
              <h3 class="font-display font-bold text-white text-lg mb-2">Karatê e Muay Thai</h3>
              <p class="text-gray-400 text-sm">A arte é dividida em Kata e Kumite, desenvolvendo disciplina, foco e respeito.</p>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="h-48 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center p-4">
              <div class="text-center">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a imagem</p>
              </div>
            </div>
            <div class="p-6">
              <h3 class="font-display font-bold text-white text-lg mb-2">Preparatório 9º Ano</h3>
              <p class="text-gray-400 text-sm">Capacitação para concursos de admissão em instituições de ensino médio técnico.</p>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="h-48 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center p-4">
              <div class="text-center">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a imagem</p>
              </div>
            </div>
            <div class="p-6">
              <h3 class="font-display font-bold text-white text-lg mb-2">Informática</h3>
              <p class="text-gray-400 text-sm">História do computador, internet, Scratch, robótica e tecnologia educativa.</p>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="h-48 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center p-4">
              <div class="text-center">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a imagem</p>
              </div>
            </div>
            <div class="p-6">
              <h3 class="font-display font-bold text-white text-lg mb-2">Inglês e Espanhol</h3>
              <p class="text-gray-400 text-sm">Aprender outro idioma é ampliar o mundo e levar um tesouro para toda a vida.</p>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="h-48 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center p-4">
              <div class="text-center">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a imagem</p>
              </div>
            </div>
            <div class="p-6">
              <h3 class="font-display font-bold text-white text-lg mb-2">Feira Estudantil</h3>
              <p class="text-gray-400 text-sm">Mostra de trabalhos e projetos desenvolvidos pelos alunos ao longo do ano letivo.</p>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="h-48 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center p-4">
              <div class="text-center">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a imagem</p>
              </div>
            </div>
            <div class="p-6">
              <h3 class="font-display font-bold text-white text-lg mb-2">Semana do Folclore</h3>
              <p class="text-gray-400 text-sm">Celebração da cultura brasileira com danças, músicas e histórias tradicionais.</p>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="h-48 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center p-4">
              <div class="text-center">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a imagem</p>
              </div>
            </div>
            <div class="p-6">
              <h3 class="font-display font-bold text-white text-lg mb-2">Semana da Criança</h3>
              <p class="text-gray-400 text-sm">Momento especial com atividades lúdicas, brincadeiras e muita diversão.</p>
            </div>
          </div>
          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="h-48 bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center p-4">
              <div class="text-center">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a imagem</p>
              </div>
            </div>
            <div class="p-6">
              <h3 class="font-display font-bold text-white text-lg mb-2">Dia do Cinema</h3>
              <p class="text-gray-400 text-sm">Exibição de filmes educativos e sessões de cinema para os alunos.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="events" class="py-24 bg-gradient-to-b from-gray-800 to-gray-900">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 animate-on-scroll">
          <span class="text-verde-complementar font-bold text-sm uppercase tracking-wider">Eventos</span>
          <h2 class="text-4xl md:text-5xl font-display font-bold text-white mt-3 mb-6">
            Próximos Eventos da Escola
          </h2>
          <p class="text-gray-400 max-w-2xl mx-auto text-lg">
            Fique por dentro das atividades e eventos programados para nossa comunidade escolar.
          </p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="bg-gradient-to-r from-azul-principal to-azul-claro p-6">
              <div class="flex items-center gap-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center">
                  <span class="text-white font-bold text-3xl block">15</span>
                  <span class="text-white/80 text-sm uppercase">Mar</span>
                </div>
                <div>
                  <h3 class="font-display font-bold text-white text-lg">Reunião de Pais</h3>
                  <p class="text-white/80 text-sm">19:00 - Auditório</p>
                </div>
              </div>
            </div>
            <div class="p-6">
              <p class="text-gray-400 text-sm mb-4">Reunião pedagógica com os responsáveis para apresentação do planejamento anual.</p>
              <span class="inline-block px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-xs font-semibold border border-blue-500/30">Todos os anos</span>
            </div>
          </div>

          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="bg-gradient-to-r from-amarelo-destaque to-orange-500 p-6">
              <div class="flex items-center gap-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center">
                  <span class="text-white font-bold text-3xl block">22</span>
                  <span class="text-white/80 text-sm uppercase">Mar</span>
                </div>
                <div>
                  <h3 class="font-display font-bold text-white text-lg">Feira de Ciências</h3>
                  <p class="text-white/80 text-sm">08:00 - Pátio</p>
                </div>
              </div>
            </div>
            <div class="p-6">
              <p class="text-gray-400 text-sm mb-4">Exposição de trabalhos científicos desenvolvidos pelos alunos durante o semestre.</p>
              <span class="inline-block px-3 py-1 bg-orange-500/20 text-orange-400 rounded-full text-xs font-semibold border border-orange-500/30">Fundamental</span>
            </div>
          </div>

          <div class="bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 overflow-hidden card-hover animate-on-scroll">
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-6">
              <div class="flex items-center gap-4">
                <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4 text-center">
                  <span class="text-white font-bold text-3xl block">30</span>
                  <span class="text-white/80 text-sm uppercase">Mar</span>
                </div>
                <div>
                  <h3 class="font-display font-bold text-white text-lg">Festa Cultural</h3>
                  <p class="text-white/80 text-sm">14:00 - Ginásio</p>
                </div>
              </div>
            </div>
            <div class="p-6">
              <p class="text-gray-400 text-sm mb-4">Celebração da diversidade cultural com apresentações artísticas e gastronômicas.</p>
              <span class="inline-block px-3 py-1 bg-purple-500/20 text-purple-400 rounded-full text-xs font-semibold border border-purple-500/30">Toda a escola</span>
            </div>
          </div>
        </div>

        <div class="text-center mt-12">
          <a href="eventos/eventos.html" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-azul-principal to-azul-claro text-white rounded-full font-semibold hover:shadow-xl hover:shadow-blue-500/30 transition-all duration-300 transform hover:scale-105">
            <i class="fas fa-calendar-alt"></i>
            Ver Todos os Eventos
          </a>
        </div>
      </div>
    </section>

    <section id="gallery" class="py-24 bg-gray-900">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 animate-on-scroll">
          <span class="text-verde-complementar font-bold text-sm uppercase tracking-wider">Galeria</span>
          <h2 class="text-4xl md:text-5xl font-display font-bold text-white mt-3 mb-6">
            Momentos especiais da nossa escola
          </h2>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          <a href="#" class="relative group overflow-hidden rounded-2xl aspect-square animate-on-scroll">
            <div class="w-full h-full bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center">
              <div class="text-center p-4">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a foto</p>
              </div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
              <span class="text-white font-semibold text-sm">Exposição de Trabalhos</span>
            </div>
          </a>
          <a href="#" class="relative group overflow-hidden rounded-2xl aspect-square animate-on-scroll">
            <div class="w-full h-full bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center">
              <div class="text-center p-4">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a foto</p>
              </div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
              <span class="text-white font-semibold text-sm">Aula de Campo 6º Ano</span>
            </div>
          </a>
          <a href="#" class="relative group overflow-hidden rounded-2xl aspect-square animate-on-scroll">
            <div class="w-full h-full bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center">
              <div class="text-center p-4">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a foto</p>
              </div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
              <span class="text-white font-semibold text-sm">Aula de Campo 3º Ano</span>
            </div>
          </a>
          <a href="#" class="relative group overflow-hidden rounded-2xl aspect-square animate-on-scroll">
            <div class="w-full h-full bg-gradient-to-br from-gray-700 to-gray-800 flex items-center justify-center">
              <div class="text-center p-4">
                <i class="fas fa-image text-3xl text-white/30 mb-2"></i>
                <p class="text-white/50 text-xs">Coloque a foto</p>
              </div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-4">
              <span class="text-white font-semibold text-sm">Aula de Campo 5º Ano</span>
            </div>
          </a>
        </div>

        <div class="text-center mt-12">
          <a href="album/Album.html" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-azul-principal to-azul-claro text-white rounded-full font-semibold hover:shadow-xl hover:shadow-blue-500/30 transition-all duration-300 transform hover:scale-105">
            <i class="fas fa-images"></i>
            Ver Todos os Álbuns
          </a>
        </div>
      </div>
    </section>

    <!-- Gallery Modal -->
    <div id="gallery-modal" class="fixed inset-0 z-50 hidden">
      <div class="absolute inset-0 bg-black/90 backdrop-blur-sm" onclick="closeGallery()"></div>
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <button onclick="closeGallery()" class="absolute top-4 right-4 text-white text-4xl hover:text-gray-300 transition-colors z-10">
          <i class="fas fa-times"></i>
        </button>
        <button onclick="prevImage()" class="absolute left-4 text-white text-4xl hover:text-gray-300 transition-colors z-10">
          <i class="fas fa-chevron-left"></i>
        </button>
        <button onclick="nextImage()" class="absolute right-4 text-white text-4xl hover:text-gray-300 transition-colors z-10">
          <i class="fas fa-chevron-right"></i>
        </button>
        <div class="relative w-full h-full flex items-center justify-center">
          <img id="gallery-image" src="" alt="" class="max-w-full max-h-full object-contain">
        </div>
      </div>
    </div>

    <section class="py-24 bg-gradient-to-r from-azul-principal via-azul-escuro to-gray-900 relative overflow-hidden">
      <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 left-0 w-96 h-96 bg-amarelo-destaque rounded-full filter blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-verde-complementar rounded-full filter blur-3xl"></div>
      </div>
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center animate-on-scroll relative">
        <h2 class="text-4xl md:text-5xl font-display font-bold text-white mb-6">
          Estamos de braços abertos para recebê-los!
        </h2>
        <p class="text-white/90 mb-10 text-xl">
          Venha conhecer nossa escola e fazer parte da nossa história.
        </p>
        <a href="https://maps.app.goo.gl/fwYKoEHm2eieZ1zg6" target="_blank" class="inline-flex items-center gap-2 px-10 py-5 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-full font-bold hover:shadow-2xl hover:shadow-yellow-500/40 transition-all duration-300 transform hover:scale-105">
          <i class="fas fa-map-marker-alt"></i>
          Como Chegar
        </a>
      </div>
    </section>

  </main>

  <footer id="contact" class="bg-gray-900 text-white py-20 border-t border-white/10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid md:grid-cols-3 gap-12">
        <div>
          <div class="flex items-center gap-3 mb-6">
            <img src="img/logo.jpg" alt="Logo [Inserir nome da escola aqui]" class="h-14">
            <div>
              <span class="font-bold text-sm text-white">[Inserir nome da escola aqui]</span>
              <span class="block font-extrabold gradient-text">[Inserir nome da escola aqui]</span>
            </div>
          </div>
          <p class="text-gray-400 mb-4 leading-relaxed">
            Estrada do Amapá (Lote 17, Quadra 04)<br>
            Parque Barão do Amapá - Duque de Caxias - RJ 25235-475
          </p>
          <p class="text-gray-400 mb-2">
            <i class="fas fa-envelope mr-2 text-amarelo-destaque"></i>ima.instituto@gmail.com
          </p>
          <p class="text-gray-400">
            <i class="fas fa-phone mr-2 text-amarelo-destaque"></i>(21) 98855-0912 / (21) 3672-0169
          </p>
        </div>
        <div>
          <h3 class="font-display font-bold text-lg mb-6">Links Rápidos</h3>
          <ul class="space-y-3">
            <li><a href="#" class="text-gray-400 hover:text-amarelo-destaque transition-colors">Início</a></li>
            <li><a href="#about" class="text-gray-400 hover:text-amarelo-destaque transition-colors">Sobre Nós</a></li>
            <li><a href="#projects" class="text-gray-400 hover:text-amarelo-destaque transition-colors">Projetos</a></li>
            <li><a href="biblioteca_vrtual/biblioteca.html" target="_blank" class="text-gray-400 hover:text-amarelo-destaque transition-colors">Biblioteca</a></li>
            <li><a href="aviso2/passo1.html" target="_blank" class="text-gray-400 hover:text-amarelo-destaque transition-colors">Correções</a></li>
          </ul>
        </div>
        <div>
          <h3 class="font-display font-bold text-lg mb-6">Redes Sociais</h3>
          <div class="flex gap-4">
            <a href="https://www.facebook.com/ceaabrasil" target="_blank" class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center hover:bg-amarelo-destaque hover:text-azul-escuro transition-all duration-300 transform hover:scale-110">
              <i class="fab fa-facebook-f text-xl"></i>
            </a>
            <a href="https://www.instagram.com/ceaa.colegiobrasiloficial" target="_blank" class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center hover:bg-amarelo-destaque hover:text-azul-escuro transition-all duration-300 transform hover:scale-110">
              <i class="fab fa-instagram text-xl"></i>
            </a>
            <a href="#" class="w-12 h-12 bg-white/10 rounded-full flex items-center justify-center hover:bg-amarelo-destaque hover:text-azul-escuro transition-all duration-300 transform hover:scale-110">
              <i class="fab fa-whatsapp text-xl"></i>
            </a>
          </div>
        </div>
      </div>
      <div class="border-t border-white/10 mt-12 pt-8 text-center">
        <p class="text-gray-500 text-sm">
          © 2026 [Inserir nome da escola aqui]. Todos os direitos reservados.
        </p>
      </div>
    </div>
  </footer>

  <a href="https://wa.me/5521988550912" target="_blank" class="fixed bottom-6 right-6 w-14 h-14 bg-green-500 rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 z-50">
    <i class="fab fa-whatsapp text-white text-2xl"></i>
  </a>

  <button id="back-to-top" class="fixed bottom-6 left-6 w-12 h-12 bg-amarelo-destaque rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 z-50 opacity-0 invisible">
    <i class="fas fa-arrow-up text-azul-escuro"></i>
  </button>

  <script>
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const menuOverlay = document.getElementById('menu-overlay');
    const menuDrawer = document.getElementById('menu-drawer');
    const closeMenuBtn = document.getElementById('close-menu');

    function openMenu() {
      mobileMenu.classList.remove('hidden');
      setTimeout(() => {
        menuOverlay.classList.remove('opacity-0');
        menuDrawer.classList.remove('translate-x-full');
      }, 10);
      document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
      menuOverlay.classList.add('opacity-0');
      menuDrawer.classList.add('translate-x-full');
      setTimeout(() => {
        mobileMenu.classList.add('hidden');
        // Resetar opacidade para a próxima abertura
        menuOverlay.classList.remove('opacity-0');
      }, 300);
      document.body.style.overflow = '';
    }

    mobileMenuBtn.addEventListener('click', openMenu);
    closeMenuBtn.addEventListener('click', closeMenu);
    menuOverlay.addEventListener('click', closeMenu);
    
    // Fechar menu ao clicar em links
    const mobileMenuLinks = document.querySelectorAll('#mobile-menu a');
    mobileMenuLinks.forEach(link => {
      link.addEventListener('click', closeMenu);
    });

    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    let currentSlide = 0;
    let autoSlideInterval;

    function showSlide(index) {
      slides.forEach((slide, i) => {
        slide.classList.remove('active');
        dots[i].classList.remove('active');
      });
      slides[index].classList.add('active');
      dots[index].classList.add('active');
      currentSlide = index;
    }

    function nextSlide() {
      const next = (currentSlide + 1) % slides.length;
      showSlide(next);
    }

    function startAutoSlide() {
      autoSlideInterval = setInterval(nextSlide, 5000);
    }

    function stopAutoSlide() {
      clearInterval(autoSlideInterval);
    }

    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        stopAutoSlide();
        showSlide(index);
        startAutoSlide();
      });
    });

    startAutoSlide();

    const statsSection = document.getElementById('stats');
    const counters = document.querySelectorAll('[data-count]');
    let counted = false;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !counted) {
          counted = true;
          counters.forEach(counter => {
            const target = parseInt(counter.dataset.count);
            const duration = 2000;
            const step = target / (duration / 16);
            let current = 0;

            const updateCounter = () => {
              current += step;
              if (current < target) {
                counter.textContent = Math.floor(current);
                requestAnimationFrame(updateCounter);
              } else {
                counter.textContent = target;
              }
            };

            updateCounter();
          });
        }
      });
    }, { threshold: 0.5 });

    observer.observe(statsSection);

    const backToTop = document.getElementById('back-to-top');

    window.addEventListener('scroll', () => {
      if (window.scrollY > 500) {
        backToTop.classList.remove('opacity-0', 'invisible');
      } else {
        backToTop.classList.add('opacity-0', 'invisible');
      }
    });

    backToTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Header scroll effect
    const header = document.getElementById('main-header');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 50) {
        header.classList.add('glass-dark');
      } else {
        header.classList.remove('glass-dark');
      }
    });

    const animateElements = document.querySelectorAll('.animate-on-scroll');

    const scrollObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('animate-fade-in-up');
          scrollObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    animateElements.forEach(el => scrollObserver.observe(el));
  </script>

  <style>
    .animate-fade-in-up {
      animation: fade-in-up 0.6s ease-out forwards;
    }

    @keyframes fade-in-up {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>

  <!-- Modal de Acesso ao Sistema -->
  <div id="acesso-sistema-modal" class="fixed inset-0 z-[100] hidden">
    <div id="modal-overlay" class="absolute inset-0 bg-black/60 backdrop-blur-sm opacity-0 transition-opacity duration-300"></div>
    <div id="modal-content" class="absolute inset-0 flex items-center justify-center p-4">
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl transform scale-95 opacity-0 transition-all duration-300">
        <div class="p-6 border-b border-gray-100">
          <div class="flex items-center justify-between">
            <h2 class="text-2xl font-display font-bold text-azul-principal">Acesso ao Sistema</h2>
            <button id="close-modal" class="p-2 rounded-full hover:bg-gray-100 transition-colors">
              <i class="fas fa-times text-gray-500 text-xl"></i>
            </button>
          </div>
        </div>
        
        <div class="p-6">
          <!-- Tabs -->
          <div class="flex gap-2 mb-6 bg-gray-100 p-1 rounded-xl">
            <button class="login-tab flex-1 py-3 px-4 rounded-lg font-semibold transition-all bg-white text-azul-principal shadow-sm" data-tab="professor">
              <i class="fas fa-chalkboard-teacher mr-2"></i>Professor
            </button>
            <button class="login-tab flex-1 py-3 px-4 rounded-lg font-semibold transition-all text-gray-600 hover:text-azul-principal" data-tab="aluno">
              <i class="fas fa-user-graduate mr-2"></i>Aluno
            </button>
            <button class="login-tab flex-1 py-3 px-4 rounded-lg font-semibold transition-all text-gray-600 hover:text-azul-principal" data-tab="admin">
              <i class="fas fa-user-shield mr-2"></i>Admin
            </button>
          </div>

          <!-- Login Form Professor -->
          <div id="login-professor" class="login-form">
            <form id="form-professor" class="space-y-4">
              <input type="hidden" name="tipo_usuario" value="professor">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Número de Matrícula</label>
                <input type="text" name="login_field" required placeholder="Digite sua matrícula" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Senha</label>
                <input type="password" name="senha" required placeholder="Digite sua senha" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent outline-none transition-all">
              </div>
              <button type="submit" class="w-full py-3 bg-azul-principal text-white rounded-xl font-semibold hover:bg-azul-escuro transition-colors">
                <i class="fas fa-spinner fa-spin mr-2 hidden" id="loading-professor"></i>Entrar como Professor
              </button>
              <div id="error-professor" class="hidden text-red-600 text-sm text-center mt-2"></div>
            </form>
          </div>

          <!-- Login Form Aluno -->
          <div id="login-aluno" class="login-form hidden">
            <form id="form-aluno" class="space-y-4">
              <input type="hidden" name="tipo_usuario" value="aluno">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">CPF do Responsável</label>
                <input type="text" name="login_field" required placeholder="Digite o CPF (000.000.000-00)" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Senha</label>
                <input type="password" name="senha" required placeholder="Digite sua senha" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent outline-none transition-all">
              </div>
              <button type="submit" class="w-full py-3 bg-verde-complementar text-white rounded-xl font-semibold hover:bg-verde-claro transition-colors">
                <i class="fas fa-spinner fa-spin mr-2 hidden" id="loading-aluno"></i>Entrar como Aluno
              </button>
              <div id="error-aluno" class="hidden text-red-600 text-sm text-center mt-2"></div>
              <div class="mt-4 text-center">
                <a href="portal/register.php" class="text-sm text-verde-complementar hover:underline">Não tem conta? Cadastre-se</a>
              </div>
            </form>
          </div>

          <!-- Login Form Admin -->
          <div id="login-admin" class="login-form hidden">
            <form id="form-admin" class="space-y-4">
              <input type="hidden" name="tipo_usuario" value="admin">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Usuário</label>
                <input type="text" name="login_field" required placeholder="Digite seu usuário" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent outline-none transition-all">
              </div>
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Senha</label>
                <input type="password" name="senha" required placeholder="Digite sua senha" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent outline-none transition-all">
              </div>
              <button type="submit" class="w-full py-3 bg-amarelo-destaque text-azul-escuro rounded-xl font-semibold hover:bg-amarelo-claro transition-colors">
                <i class="fas fa-spinner fa-spin mr-2 hidden" id="loading-admin"></i>Entrar como Admin
              </button>
              <div id="error-admin" class="hidden text-red-600 text-sm text-center mt-2"></div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Modal de Acesso ao Sistema
      const acessoSistemaBtn = document.getElementById('acesso-sistema-btn');
      const acessoSistemaBtnMobile = document.getElementById('acesso-sistema-btn-mobile');
      const acessoSistemaModal = document.getElementById('acesso-sistema-modal');
      const modalOverlay = document.getElementById('modal-overlay');
      const modalContent = document.getElementById('modal-content');
      const closeModal = document.getElementById('close-modal');
      const loginTabs = document.querySelectorAll('.login-tab');
      const loginForms = document.querySelectorAll('.login-form');

    function openModal() {
      acessoSistemaModal.classList.remove('hidden');
      setTimeout(() => {
        modalOverlay.classList.remove('opacity-0');
        modalContent.querySelector('div').classList.remove('scale-95', 'opacity-0');
        modalContent.querySelector('div').classList.add('scale-100', 'opacity-100');
      }, 10);
    }

    function closeModalFn() {
      modalOverlay.classList.add('opacity-0');
      modalContent.querySelector('div').classList.add('scale-95', 'opacity-0');
      modalContent.querySelector('div').classList.remove('scale-100', 'opacity-100');
      setTimeout(() => {
        acessoSistemaModal.classList.add('hidden');
      }, 300);
    }

    if (acessoSistemaBtn) {
      acessoSistemaBtn.addEventListener('click', openModal);
    } else {
      console.error('Botão acesso-sistema-btn não encontrado');
    }
    
    if (acessoSistemaBtnMobile) {
      acessoSistemaBtnMobile.addEventListener('click', () => {
        closeMenu();
        openModal();
      });
    }
    closeModal.addEventListener('click', closeModalFn);
    modalOverlay.addEventListener('click', closeModalFn);

    // Tabs de Login
    loginTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const tabName = tab.dataset.tab;
        
        // Update tabs
        loginTabs.forEach(t => {
          t.classList.remove('bg-white', 'text-azul-principal', 'shadow-sm');
          t.classList.add('text-gray-600');
        });
        tab.classList.add('bg-white', 'text-azul-principal', 'shadow-sm');
        tab.classList.remove('text-gray-600');
        
        // Update forms
        loginForms.forEach(form => {
          form.classList.add('hidden');
        });
        document.getElementById(`login-${tabName}`).classList.remove('hidden');
      });
    });

    // Login via AJAX
    function handleLogin(formId, loadingId, errorId) {
      const form = document.getElementById(formId);
      const loading = document.getElementById(loadingId);
      const error = document.getElementById(errorId);
      
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Show loading
        loading.classList.remove('hidden');
        error.classList.add('hidden');
        
        const formData = new FormData(form);
        
        try {
          const response = await fetch('portal/api/login.php', {
            method: 'POST',
            body: formData
          });
          
          const data = await response.json();
          
          if (data.success) {
            // Redirect to dashboard
            window.location.href = data.redirect;
          } else {
            // Show error
            error.textContent = data.message;
            error.classList.remove('hidden');
          }
        } catch (err) {
          error.textContent = 'Erro ao fazer login. Tente novamente.';
          error.classList.remove('hidden');
        } finally {
          loading.classList.add('hidden');
        }
      });
    }
    
    // Initialize login handlers
    handleLogin('form-professor', 'loading-professor', 'error-professor');
    handleLogin('form-aluno', 'loading-aluno', 'error-aluno');
    handleLogin('form-admin', 'loading-admin', 'error-admin');

    // User menu dropdown toggle
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userMenuDropdown = document.getElementById('user-menu-dropdown');
    
    if (userMenuBtn && userMenuDropdown) {
      userMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        userMenuDropdown.classList.toggle('hidden');
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', () => {
        userMenuDropdown.classList.add('hidden');
      });
      
      // Prevent closing when clicking inside dropdown
      userMenuDropdown.addEventListener('click', (e) => {
        e.stopPropagation();
      });
    }

    // Gallery Modal Functions
    let currentImages = [];
    let currentIndex = 0;

    // Mapeamento de imagens disponíveis em cada álbum
    const albumImages = {
      'A1': Array.from({length: 27}, (_, i) => `album/img/A1/${i + 1}.jpg`),
      'A2': Array.from({length: 36}, (_, i) => `album/img/A2/${i + 1}.jpg`),
      'A3': Array.from({length: 27}, (_, i) => `album/img/A3/${i + 1}.jpg`),
      'A4': Array.from({length: 11}, (_, i) => `album/img/A4/${i + 1}.jpg`)
    };

    function openGallery(albumId) {
      currentImages = albumImages[albumId] || [];
      currentIndex = 0;
      
      if (currentImages.length > 0) {
        document.getElementById('gallery-modal').classList.remove('hidden');
        updateGalleryImage();
        document.body.style.overflow = 'hidden';
      }
    }

    function closeGallery() {
      document.getElementById('gallery-modal').classList.add('hidden');
      document.body.style.overflow = '';
    }

    function updateGalleryImage() {
      const img = document.getElementById('gallery-image');
      img.src = currentImages[currentIndex];
    }

    function nextImage() {
      if (currentIndex < currentImages.length - 1) {
        currentIndex++;
        updateGalleryImage();
      }
    }

    function prevImage() {
      if (currentIndex > 0) {
        currentIndex--;
        updateGalleryImage();
      }
    }

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (!document.getElementById('gallery-modal').classList.contains('hidden')) {
        if (e.key === 'Escape') closeGallery();
        if (e.key === 'ArrowRight') nextImage();
        if (e.key === 'ArrowLeft') prevImage();
      }
    });

    // Make functions global
    window.openGallery = openGallery;
    window.closeGallery = closeGallery;
    });
    window.nextImage = nextImage;
    window.prevImage = prevImage;
  </script>

</body>

</html>

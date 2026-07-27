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
  <title>Site Institucional Moderno e Sistema de Gestão Escolar</title>
  <meta name="description" content="Site institucional moderno e sistema de gestão escolar completo para instituições educacionais.">
  <link rel="stylesheet" href="css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Open Sans', system-ui, sans-serif;
      background: #f8f9fa;
      min-height: 100vh;
      overflow-x: hidden;
    }
    
    /* Header */
    .top-bar {
      background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 100%);
      padding: 8px 0;
    }
    
    .main-header {
      background: #ffffff;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    
    /* Navigation */
    .nav-link {
      color: #334155;
      font-weight: 500;
      padding: 0.5rem 1rem;
      transition: all 0.3s ease;
      position: relative;
    }
    .nav-link:hover {
      color: #1e3a8a;
    }
    .nav-link::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      width: 0;
      height: 2px;
      background: #1e3a8a;
      transition: all 0.3s ease;
      transform: translateX(-50%);
    }
    .nav-link:hover::after {
      width: 80%;
    }
    
    /* Dropdown */
    .dropdown {
      position: relative;
    }
    .dropdown-menu {
      position: absolute;
      top: 100%;
      left: 0;
      background: white;
      min-width: 220px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
      border-radius: 8px;
      padding: 0.5rem 0;
      opacity: 0;
      visibility: hidden;
      transform: translateY(10px);
      transition: all 0.3s ease;
    }
    .dropdown:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    .dropdown-item {
      display: block;
      padding: 0.5rem 1rem;
      color: #334155;
      transition: all 0.2s ease;
    }
    .dropdown-item:hover {
      background: #f1f5f9;
      color: #1e3a8a;
      padding-left: 1.5rem;
    }
    
    /* Hero Section */
    .hero-section {
      background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e3a8a 100%);
      position: relative;
      overflow: hidden;
    }
    .hero-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></svg>');
      background-size: 100px 100px;
      animation: patternMove 20s linear infinite;
    }
    @keyframes patternMove {
      0% { background-position: 0 0; }
      100% { background-position: 100px 100px; }
    }
    
    /* Cards */
    .feature-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
      transition: all 0.3s ease;
      border: 1px solid #e2e8f0;
    }
    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    
    /* Buttons */
    .btn-primary {
      background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
      color: white;
      padding: 0.75rem 2rem;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(30, 58, 138, 0.3);
    }
    
    .btn-secondary {
      background: white;
      color: #1e3a8a;
      padding: 0.75rem 2rem;
      border-radius: 8px;
      font-weight: 600;
      transition: all 0.3s ease;
      border: 2px solid #1e3a8a;
      cursor: pointer;
    }
    .btn-secondary:hover {
      background: #1e3a8a;
      color: white;
    }
    
    /* Modal */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(5px);
      z-index: 1000;
      display: none;
      align-items: center;
      justify-content: center;
    }
    .modal-overlay.active {
      display: flex;
    }
    .modal-content {
      background: white;
      border-radius: 16px;
      padding: 2rem;
      max-width: 400px;
      width: 90%;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }
    
    /* Tabs */
    .tab-btn {
      padding: 0.5rem 1rem;
      border: 2px solid #e2e8f0;
      background: white;
      color: #64748b;
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    .tab-btn.active {
      background: #1e3a8a;
      color: white;
      border-color: #1e3a8a;
    }
    
    /* Inputs */
    .form-input {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      font-size: 0.95rem;
      transition: all 0.3s ease;
    }
    .form-input:focus {
      outline: none;
      border-color: #1e3a8a;
      box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }
    
    /* Mobile Menu */
    .mobile-menu {
      position: fixed;
      top: 0;
      right: -100%;
      width: 280px;
      height: 100vh;
      background: white;
      z-index: 1001;
      transition: right 0.3s ease;
      box-shadow: -5px 0 20px rgba(0, 0, 0, 0.1);
    }
    .mobile-menu.active {
      right: 0;
    }
    .mobile-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      display: none;
    }
    .mobile-overlay.active {
      display: block;
    }
    
    /* Social Icons */
    .social-icon {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      transition: all 0.3s ease;
    }
    .social-icon:hover {
      transform: translateY(-3px);
    }
  </style>
</head>
<body class="text-gray-800">
  
  <!-- Top Bar -->
  <div class="top-bar">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between text-white text-sm">
        <div class="flex items-center gap-4">
          <span><i class="fas fa-phone mr-2"></i>(11) 1234-5678</span>
          <span class="hidden sm:inline"><i class="fas fa-envelope mr-2"></i>contato@escola.com.br</span>
        </div>
        <div class="flex items-center gap-3">
          <a href="#" class="social-icon bg-blue-600 hover:bg-blue-700"><i class="fab fa-facebook-f"></i></a>
          <a href="#" class="social-icon bg-pink-600 hover:bg-pink-700"><i class="fab fa-instagram"></i></a>
          <a href="#" class="social-icon bg-blue-400 hover:bg-blue-500"><i class="fab fa-twitter"></i></a>
          <a href="#" class="social-icon bg-red-600 hover:bg-red-700"><i class="fab fa-youtube"></i></a>
          <a href="https://wa.me/5511999999999" target="_blank" class="social-icon bg-green-500 hover:bg-green-600"><i class="fab fa-whatsapp"></i></a>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Main Header -->
  <header class="main-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">
        <a href="#" class="flex items-center gap-3">
          <img src="img/logo.jpg" alt="Logo" class="h-14 w-auto">
          <div class="hidden sm:block">
            <h1 class="text-xl font-bold text-gray-800" style="font-family: 'Playfair Display', serif;">Nome da Escola</h1>
            <p class="text-xs text-gray-500">Educação de Excelência</p>
          </div>
        </a>

        <nav class="hidden lg:flex items-center gap-1">
          <a href="#" class="nav-link">Início</a>
          
          <div class="dropdown">
            <a href="#" class="nav-link flex items-center gap-1">
              Instituição <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <div class="dropdown-menu">
              <a href="#" class="dropdown-item">Sobre o Colégio</a>
              <a href="historico.php" class="dropdown-item">História</a>
              <a href="#" class="dropdown-item">Missão, Visão e Valores</a>
              <a href="#" class="dropdown-item">Nossa Equipe</a>
              <a href="#" class="dropdown-item">Instalações</a>
            </div>
          </div>
          
          <div class="dropdown">
            <a href="#" class="nav-link flex items-center gap-1">
              Ensino <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <div class="dropdown-menu">
              <a href="#" class="dropdown-item">Educação Infantil</a>
              <a href="#" class="dropdown-item">Ensino Fundamental I</a>
              <a href="#" class="dropdown-item">Ensino Fundamental II</a>
              <a href="#" class="dropdown-item">Ensino Médio</a>
            </div>
          </div>
          
          <a href="noticias.php" class="nav-link">Notícias</a>
          <a href="eventos.php" class="nav-link">Eventos</a>
          <a href="contato.php" class="nav-link">Contato</a>
        </nav>

        <div class="hidden md:flex items-center gap-3">
          <a href="biblioteca.php" class="btn-secondary text-sm">
            <i class="fas fa-book mr-2"></i>Biblioteca
          </a>
          
          <?php if ($isLoggedIn): ?>
            <div class="relative">
              <button id="user-menu-btn" class="btn-primary text-sm flex items-center gap-2">
                <i class="fas fa-user"></i>
                <span><?php echo htmlspecialchars(substr($userName, 0, 10)); ?></span>
              </button>
              <div id="user-menu-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl py-2 hidden z-50 border">
                <div class="px-4 py-2 border-b border-gray-100">
                  <p class="text-xs text-gray-500">Logado como</p>
                  <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($userName); ?></p>
                  <p class="text-xs text-blue-600 font-medium capitalize"><?php echo htmlspecialchars($userType); ?></p>
                </div>
                <?php if ($userType === 'admin'): ?>
                  <a href="portal/admin/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Painel Admin</a>
                <?php elseif ($userType === 'professor'): ?>
                  <a href="portal/professor/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Painel Professor</a>
                <?php elseif ($userType === 'aluno'): ?>
                  <a href="portal/aluno/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Painel Aluno</a>
                <?php elseif ($userType === 'secretaria'): ?>
                  <a href="portal/secretaria/index.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Painel Secretaria</a>
                <?php endif; ?>
                <div class="border-t border-gray-100 mt-2 pt-2">
                  <a href="portal/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Sair</a>
                </div>
              </div>
            </div>
          <?php else: ?>
            <button id="acesso-sistema-btn" class="btn-primary text-sm">
              <i class="fas fa-sign-in-alt mr-2"></i>Acesso
            </button>
          <?php endif; ?>
        </div>

        <button id="mobile-menu-btn" class="lg:hidden p-2 text-gray-600 hover:text-gray-800">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>
    </div>
  </header>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="mobile-menu">
    <div class="p-6">
      <div class="flex items-center justify-between mb-8">
        <img src="img/logo.jpg" alt="Logo" class="h-12">
        <button id="close-menu" class="p-2 text-gray-600 hover:text-gray-800">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      <nav class="flex flex-col gap-4">
        <a href="#" class="text-gray-700 font-semibold hover:text-blue-600">Início</a>
        <a href="#" class="text-gray-700 font-semibold hover:text-blue-600">Instituição</a>
        <a href="#" class="text-gray-700 font-semibold hover:text-blue-600">Ensino</a>
        <a href="noticias.php" class="text-gray-700 font-semibold hover:text-blue-600">Notícias</a>
        <a href="eventos.php" class="text-gray-700 font-semibold hover:text-blue-600">Eventos</a>
        <a href="contato.php" class="text-gray-700 font-semibold hover:text-blue-600">Contato</a>
        <div class="border-t border-gray-200 pt-4 mt-4 space-y-3">
          <a href="biblioteca.php" class="block w-full btn-secondary text-center text-sm">Biblioteca</a>
          <?php if ($isLoggedIn): ?>
            <div class="bg-gray-50 rounded-lg p-3">
              <p class="text-xs text-gray-500 mb-1">Logado como</p>
              <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars(substr($userName, 0, 20)); ?></p>
              <p class="text-xs text-blue-600 font-medium capitalize"><?php echo htmlspecialchars($userType); ?></p>
            </div>
            <a href="portal/logout.php" class="block w-full text-center py-2 text-red-600 hover:bg-red-50 rounded-lg text-sm">Sair</a>
          <?php else: ?>
            <button id="acesso-sistema-btn-mobile" class="block w-full btn-primary text-sm">Acesso ao Sistema</button>
          <?php endif; ?>
        </div>
      </nav>
    </div>
  </div>
  <div id="mobile-overlay" class="mobile-overlay"></div>

  <!-- Hero Section -->
  <section class="hero-section py-24 md:py-32 relative">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      <div class="text-center text-white">
        <div class="inline-block mb-6 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
          <span class="text-sm font-semibold">
            <i class="fas fa-star mr-2"></i>Matrículas 2026 Abertas
          </span>
        </div>
        
        <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold mb-6" style="font-family: 'Playfair Display', serif;">
          Educação de Excelência<br>para o Futuro
        </h1>
        
        <p class="text-xl md:text-2xl text-white/90 mb-10 max-w-3xl mx-auto">
          Formando cidadãos conscientes, críticos e preparados para os desafios do mundo moderno.
        </p>
        
        <div class="flex flex-wrap justify-center gap-4">
          <a href="pre_matricula.php" class="btn-primary text-lg">
            <i class="fas fa-user-graduate mr-2"></i>Pré-Matrícula
          </a>
          <a href="agendar_visita.php" class="btn-secondary text-lg">
            <i class="fas fa-calendar-check mr-2"></i>Agendar Visita
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Features Section -->
  <section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4" style="font-family: 'Playfair Display', serif;">
          Nossos Serviços
        </h2>
        <p class="text-gray-600 max-w-2xl mx-auto">
          Conheça todas as funcionalidades e serviços disponíveis em nossa plataforma educacional.
        </p>
      </div>
      
      <div class="grid md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <a href="noticias.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-newspaper text-blue-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Notícias</h3>
          <p class="text-sm text-gray-600">Portal de notícias e blog</p>
        </a>
        
        <a href="eventos.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-calendar-alt text-purple-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Eventos</h3>
          <p class="text-sm text-gray-600">Sistema de inscrição</p>
        </a>
        
        <a href="galeria.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-pink-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-images text-pink-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Álbum de Fotos</h3>
          <p class="text-sm text-gray-600">Galeria de imagens</p>
        </a>
        
        <a href="galeria_videos.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-video text-red-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Vídeos</h3>
          <p class="text-sm text-gray-600">Galeria de vídeos</p>
        </a>
        
        <a href="chat.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-comments text-green-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Chat Online</h3>
          <p class="text-sm text-gray-600">Atendimento em tempo real</p>
        </a>
        
        <a href="aviso2/passo1.html" target="_blank" class="feature-card text-center">
          <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-edit text-yellow-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Avisos/Correções</h3>
          <p class="text-sm text-gray-600">Comunicados</p>
        </a>
        
        <a href="vagas.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-indigo-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-briefcase text-indigo-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Vagas</h3>
          <p class="text-sm text-gray-600">Currículos e empregos</p>
        </a>
        
        <a href="doacoes.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-teal-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-heart text-teal-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Doações</h3>
          <p class="text-sm text-gray-600">Sistema de contribuições</p>
        </a>
        
        <a href="portal_pais.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-cyan-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-users text-cyan-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Portal Pais</h3>
          <p class="text-sm text-gray-600">Área dos responsáveis</p>
        </a>
        
        <a href="faq.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-question-circle text-orange-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">FAQ</h3>
          <p class="text-sm text-gray-600">Suporte e perguntas</p>
        </a>
        
        <a href="avaliacoes.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-rose-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-star text-rose-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Avaliações</h3>
          <p class="text-sm text-gray-600">Depoimentos</p>
        </a>
        
        <a href="ex_alunos.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-amber-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user-graduate text-amber-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Ex-Alunos</h3>
          <p class="text-sm text-gray-600">Networking</p>
        </a>
        
        <a href="newsletter.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-lime-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-envelope text-lime-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Newsletter</h3>
          <p class="text-sm text-gray-600">Comunicações</p>
        </a>
        
        <a href="calendario_escolar.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-violet-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-calendar text-violet-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Calendário</h3>
          <p class="text-sm text-gray-600">Calendário escolar</p>
        </a>
        
        <a href="formularios.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-sky-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-file-download text-sky-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Formulários</h3>
          <p class="text-sm text-gray-600">Downloads</p>
        </a>
        
        <a href="projetos.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-emerald-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-project-diagram text-emerald-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Projetos</h3>
          <p class="text-sm text-gray-600">Iniciativas</p>
        </a>
        
        <a href="https://wa.me/5511999999999" target="_blank" class="feature-card text-center">
          <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fab fa-whatsapp text-green-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">WhatsApp</h3>
          <p class="text-sm text-gray-600">Chat direto</p>
        </a>
        
        <a href="contato.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-map-marker-alt text-slate-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Como Chegar</h3>
          <p class="text-sm text-gray-600">Endereço e contato</p>
        </a>
        
        <a href="redes_sociais.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-share-alt text-blue-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Redes Sociais</h3>
          <p class="text-sm text-gray-600">Integração</p>
        </a>
        
        <a href="parcerias.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-fuchsia-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-handshake text-fuchsia-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Parcerias</h3>
          <p class="text-sm text-gray-600">Convênios</p>
        </a>
        
        <a href="transporte.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-bus text-yellow-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Transporte</h3>
          <p class="text-sm text-gray-600">Informações</p>
        </a>
        
        <a href="recursos.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-teal-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-book-open text-teal-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Recursos</h3>
          <p class="text-sm text-gray-600">Educacionais</p>
        </a>
        
        <a href="contato_departamentos.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-building text-purple-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Departamentos</h3>
          <p class="text-sm text-gray-600">Contato avançado</p>
        </a>
        
        <a href="historico.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-amber-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-history text-amber-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Histórico</h3>
          <p class="text-sm text-gray-600">Da instituição</p>
        </a>
        
        <a href="tour_virtual.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-cyan-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-vr-cardboard text-cyan-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Tour Virtual</h3>
          <p class="text-sm text-gray-600">360°</p>
        </a>
        
        <a href="transparencia.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-slate-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-file-invoice-dollar text-slate-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Transparência</h3>
          <p class="text-sm text-gray-600">Prestação de contas</p>
        </a>
        
        <a href="acessibilidade.php" class="feature-card text-center">
          <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-universal-access text-blue-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2">Acessibilidade</h3>
          <p class="text-sm text-gray-600">Libras, alto contraste</p>
        </a>
      </div>
    </div>
  </section>

  <!-- Login Modal -->
  <div id="login-modal" class="modal-overlay">
    <div class="modal-content">
      <button id="close-modal" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
        <i class="fas fa-times text-xl"></i>
      </button>
      
      <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <i class="fas fa-sign-in-alt text-white text-2xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-gray-800 mb-1" style="font-family: 'Playfair Display', serif;">Acesso ao Sistema</h2>
        <p class="text-gray-600 text-sm">Entre com suas credenciais</p>
      </div>
      
      <!-- Tabs -->
      <div class="flex flex-wrap gap-2 mb-6">
        <button class="tab-btn flex-1 text-xs" data-tab="professor">
          <i class="fas fa-chalkboard-teacher mr-1"></i>Professor
        </button>
        <button class="tab-btn flex-1 text-xs" data-tab="aluno">
          <i class="fas fa-graduation-cap mr-1"></i>Aluno
        </button>
        <button class="tab-btn flex-1 text-xs" data-tab="secretaria">
          <i class="fas fa-building mr-1"></i>Secretaria
        </button>
        <button class="tab-btn flex-1 text-xs" data-tab="admin">
          <i class="fas fa-cog mr-1"></i>Admin
        </button>
      </div>
      
      <!-- Login Form -->
      <form id="login-form" class="space-y-4">
        <input type="hidden" id="login-tipo" name="tipo" value="professor">
        
        <div>
          <label id="usuario-label" class="block text-sm font-semibold text-gray-700 mb-2">Matrícula</label>
          <input type="text" id="login-usuario" name="usuario" required class="form-input" placeholder="Digite sua matrícula">
        </div>
        
        <div>
          <label class="block text-sm font-semibold text-gray-700 mb-2">Senha</label>
          <input type="password" id="login-senha" name="senha" required class="form-input" placeholder="Digite sua senha">
        </div>
        
        <div id="login-error" class="hidden text-red-600 text-sm text-center"></div>
        
        <button type="submit" class="btn-primary w-full">
          <i class="fas fa-sign-in-alt mr-2"></i>Entrar
        </button>
      </form>
      
      <p class="text-center text-gray-600 text-sm mt-6">
        Esqueceu sua senha? <a href="#" class="text-blue-600 hover:text-blue-700">Recuperar</a>
      </p>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid md:grid-cols-4 gap-8">
        <div>
          <h3 class="text-lg font-bold mb-4" style="font-family: 'Playfair Display', serif;">Nome da Escola</h3>
          <p class="text-gray-400 text-sm">Educação de excelência, acolhimento e tecnologia para formar grandes futuros.</p>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Links Rápidos</h4>
          <ul class="space-y-2 text-sm text-gray-400">
            <li><a href="#" class="hover:text-white">Sobre Nós</a></li>
            <li><a href="noticias.php" class="hover:text-white">Notícias</a></li>
            <li><a href="eventos.php" class="hover:text-white">Eventos</a></li>
            <li><a href="contato.php" class="hover:text-white">Contato</a></li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Contato</h4>
          <ul class="space-y-2 text-sm text-gray-400">
            <li><i class="fas fa-phone mr-2"></i>(11) 1234-5678</li>
            <li><i class="fas fa-envelope mr-2"></i>contato@escola.com.br</li>
            <li><i class="fas fa-map-marker-alt mr-2"></i>Rua da Escola, 123</li>
          </ul>
        </div>
        <div>
          <h4 class="font-semibold mb-4">Redes Sociais</h4>
          <div class="flex gap-3">
            <a href="#" class="social-icon bg-blue-600"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-icon bg-pink-600"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-icon bg-blue-400"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-icon bg-red-600"><i class="fab fa-youtube"></i></a>
          </div>
        </div>
      </div>
      <div class="border-t border-gray-800 mt-8 pt-8 text-center text-sm text-gray-400">
        <p>&copy; 2026 Nome da Escola. Todos os direitos reservados.</p>
      </div>
    </div>
  </footer>

  <script>
    // Mobile menu
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const closeMenuBtn = document.getElementById('close-menu');
    const mobileOverlay = document.getElementById('mobile-overlay');

    mobileMenuBtn.addEventListener('click', () => {
      mobileMenu.classList.add('active');
      mobileOverlay.classList.add('active');
    });

    closeMenuBtn.addEventListener('click', () => {
      mobileMenu.classList.remove('active');
      mobileOverlay.classList.remove('active');
    });

    mobileOverlay.addEventListener('click', () => {
      mobileMenu.classList.remove('active');
      mobileOverlay.classList.remove('active');
    });

    // User menu dropdown
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userMenuDropdown = document.getElementById('user-menu-dropdown');

    if (userMenuBtn) {
      userMenuBtn.addEventListener('click', () => {
        userMenuDropdown.classList.toggle('hidden');
      });
    }

    // Login modal
    const acessoSistemaBtn = document.getElementById('acesso-sistema-btn');
    const acessoSistemaBtnMobile = document.getElementById('acesso-sistema-btn-mobile');
    const loginModal = document.getElementById('login-modal');
    const closeModalBtn = document.getElementById('close-modal');
    const loginForm = document.getElementById('login-form');
    const tabBtns = document.querySelectorAll('.tab-btn');
    const loginTipo = document.getElementById('login-tipo');
    const usuarioLabel = document.getElementById('usuario-label');
    const loginUsuario = document.getElementById('login-usuario');
    const loginError = document.getElementById('login-error');

    function openLoginModal(tipo) {
      loginModal.classList.add('active');
      setLoginTipo(tipo);
    }

    function setLoginTipo(tipo) {
      loginTipo.value = tipo;
      tabBtns.forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.tab === tipo) {
          btn.classList.add('active');
        }
      });
      
      switch(tipo) {
        case 'professor':
          usuarioLabel.textContent = 'Matrícula';
          loginUsuario.placeholder = 'Digite sua matrícula';
          break;
        case 'aluno':
          usuarioLabel.textContent = 'CPF do Responsável';
          loginUsuario.placeholder = 'Digite o CPF do responsável';
          break;
        case 'secretaria':
          usuarioLabel.textContent = 'Usuário';
          loginUsuario.placeholder = 'Digite seu usuário';
          break;
        case 'admin':
          usuarioLabel.textContent = 'Usuário';
          loginUsuario.placeholder = 'Digite seu usuário';
          break;
      }
    }

    if (acessoSistemaBtn) {
      acessoSistemaBtn.addEventListener('click', () => openLoginModal('professor'));
    }

    if (acessoSistemaBtnMobile) {
      acessoSistemaBtnMobile.addEventListener('click', () => {
        mobileMenu.classList.remove('active');
        mobileOverlay.classList.remove('active');
        openLoginModal('professor');
      });
    }

    closeModalBtn.addEventListener('click', () => {
      loginModal.classList.remove('active');
    });

    loginModal.addEventListener('click', (e) => {
      if (e.target === loginModal) {
        loginModal.classList.remove('active');
      }
    });

    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => setLoginTipo(btn.dataset.tab));
    });

    // Login form submission
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      
      const formData = new FormData(loginForm);
      loginError.classList.add('hidden');
      
      try {
        const response = await fetch('login.php', {
          method: 'POST',
          body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
          window.location.href = result.redirect;
        } else {
          loginError.textContent = result.message;
          loginError.classList.remove('hidden');
        }
      } catch (error) {
        loginError.textContent = 'Erro ao processar login. Tente novamente.';
        loginError.classList.remove('hidden');
      }
    });
  </script>
</body>
</html>

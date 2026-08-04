<?php
$pageTitle = 'Início';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
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
    
    /* Modern Typography */
    h1, h2, h3, h4, h5, h6 {
      font-family: 'Poppins', sans-serif;
    }
    
    /* Hero Section Animation */
    .hero-section {
      background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #3b82f6 100%);
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
      background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></svg>');
      background-size: 100px 100px;
      animation: float 20s infinite linear;
    }
    
    @keyframes float {
      0% { transform: translateY(0) rotate(0deg); }
      100% { transform: translateY(-100px) rotate(360deg); }
    }
    
    /* Card Hover Effects */
    .feature-card, .bg-white/5 border border-white/10 backdrop-blur-sm.rounded-xl {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .feature-card:hover, .bg-white/5 border border-white/10 backdrop-blur-sm.rounded-xl:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }
    
    /* Gradient Text */
    .gradient-text {
      background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    /* Button Styles */
    .btn-primary {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 0.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
    }
    
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 12px rgba(59, 130, 246, 0.4);
    }
    
    .btn-secondary {
      background: white;
      color: #1e3a8a;
      padding: 0.75rem 1.5rem;
      border-radius: 0.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      border: 2px solid #e2e8f0;
    }
    
    .btn-secondary:hover {
      border-color: #3b82f6;
      color: #3b82f6;
    }
    
    /* Section Animations */
    section {
      opacity: 0;
      transform: translateY(30px);
      animation: fadeInUp 0.8s ease forwards;
    }
    
    @keyframes fadeInUp {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    section:nth-child(1) { animation-delay: 0.1s; }
    section:nth-child(2) { animation-delay: 0.2s; }
    section:nth-child(3) { animation-delay: 0.3s; }
    section:nth-child(4) { animation-delay: 0.4s; }
  </style>
</head>
<body class="text-white/90">
  
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
            <h1 class="text-xl font-bold text-white/90 font-poppins">Nome da Escola</h1>
            <p class="text-xs text-white/50">Educação de Excelência</p>
          </div>
        </a>

        <nav class="hidden lg:flex items-center gap-1">
          <a href="#" class="nav-link">Início</a>
          
          <div class="dropdown">
            <a href="#" class="nav-link flex items-center gap-1">
              Instituição <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <div class="dropdown-menu">
              <a href="historico.php" class="dropdown-item">História</a>
              <a href="tour_virtual.php" class="dropdown-item">Tour Virtual 360°</a>
              <a href="transparencia.php" class="dropdown-item">Transparência</a>
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
          
          <div class="dropdown">
            <a href="#" class="nav-link flex items-center gap-1">
              Serviços <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <div class="dropdown-menu">
              <a href="biblioteca.php" class="dropdown-item">Biblioteca</a>
              <a href="transporte.php" class="dropdown-item">Transporte</a>
              <a href="formularios.php" class="dropdown-item">Formulários</a>
              <a href="calendario_escolar.php" class="dropdown-item">Calendário Escolar</a>
            </div>
          </div>
          
          <div class="dropdown">
            <a href="#" class="nav-link flex items-center gap-1">
              Comunidade <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <div class="dropdown-menu">
              <a href="portal_pais.php" class="dropdown-item">Portal dos Pais</a>
              <a href="ex_alunos.php" class="dropdown-item">Ex-Alunos</a>
              <a href="parcerias.php" class="dropdown-item">Parcerias</a>
              <a href="trabalhe_conosco.php" class="dropdown-item">Trabalhe Conosco</a>
              <a href="doacoes.php" class="dropdown-item">Doações</a>
            </div>
          </div>
          
          <div class="dropdown">
            <a href="#" class="nav-link flex items-center gap-1">
              Recursos <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <div class="dropdown-menu">
              <a href="projetos.php" class="dropdown-item">Projetos</a>
              <a href="recursos_educacionais.php" class="dropdown-item">Recursos Educacionais</a>
              <a href="album/index.php" class="dropdown-item">Álbum de Fotos</a>
              <a href="galeria_videos.php" class="dropdown-item">Galeria de Vídeos</a>
            </div>
          </div>
          
          <a href="contato_departamentos.php" class="nav-link">Contato</a>
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
              <div id="user-menu-dropdown" class="absolute right-0 mt-2 w-48 bg-white/5 border border-white/10 backdrop-blur-sm rounded-lg shadow-xl py-2 hidden z-50 border">
                <div class="px-4 py-2 border-b border-white/5">
                  <p class="text-xs text-white/50">Logado como</p>
                  <p class="text-sm font-semibold text-white/90"><?php echo htmlspecialchars($userName); ?></p>
                  <p class="text-xs text-blue-400 font-medium capitalize"><?php echo htmlspecialchars($userType); ?></p>
                </div>
                <?php if ($userType === 'admin'): ?>
                  <a href="portal/admin/index.php" class="block px-4 py-2 text-sm text-white/70 hover:bg-[#030814]">Painel Admin</a>
                <?php elseif ($userType === 'professor'): ?>
                  <a href="portal/professor/index.php" class="block px-4 py-2 text-sm text-white/70 hover:bg-[#030814]">Painel Professor</a>
                <?php elseif ($userType === 'aluno'): ?>
                  <a href="portal/aluno/index.php" class="block px-4 py-2 text-sm text-white/70 hover:bg-[#030814]">Painel Aluno</a>
                <?php elseif ($userType === 'secretaria'): ?>
                  <a href="portal/secretaria/index.php" class="block px-4 py-2 text-sm text-white/70 hover:bg-[#030814]">Painel Secretaria</a>
                <?php endif; ?>
                <div class="border-t border-white/5 mt-2 pt-2">
                  <a href="portal/logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-red-50">Sair</a>
                </div>
              </div>
            </div>
          <?php else: ?>
            <button id="acesso-sistema-btn" class="btn-primary text-sm">
              <i class="fas fa-sign-in-alt mr-2"></i>Acesso
            </button>
          <?php endif; ?>
        </div>

        <button id="mobile-menu-btn" class="lg:hidden p-2 text-white/60 hover:text-white/90">
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
        <button id="close-menu" class="p-2 text-white/60 hover:text-white/90">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      <nav class="flex flex-col gap-4">
        <a href="#" class="text-white/70 font-semibold hover:text-blue-400">Início</a>
        <a href="historico.php" class="text-white/70 font-semibold hover:text-blue-400">História</a>
        <a href="tour_virtual.php" class="text-white/70 font-semibold hover:text-blue-400">Tour Virtual</a>
        <a href="biblioteca.php" class="text-white/70 font-semibold hover:text-blue-400">Biblioteca</a>
        <a href="portal_pais.php" class="text-white/70 font-semibold hover:text-blue-400">Portal dos Pais</a>
        <a href="calendario_escolar.php" class="text-white/70 font-semibold hover:text-blue-400">Calendário</a>
        <a href="contato_departamentos.php" class="text-white/70 font-semibold hover:text-blue-400">Contato</a>
        <div class="border-t border-white/10 pt-4 mt-4 space-y-3">
          <a href="biblioteca.php" class="block w-full btn-secondary text-center text-sm">Biblioteca</a>
          <?php if ($isLoggedIn): ?>
            <div class="bg-[#030814] rounded-lg p-3">
              <p class="text-xs text-white/50 mb-1">Logado como</p>
              <p class="text-sm font-semibold text-white/90"><?php echo htmlspecialchars(substr($userName, 0, 20)); ?></p>
              <p class="text-xs text-blue-400 font-medium capitalize"><?php echo htmlspecialchars($userType); ?></p>
            </div>
            <a href="portal/logout.php" class="block w-full text-center py-2 text-red-400 hover:bg-red-50 rounded-lg text-sm">Sair</a>
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
        <div class="inline-block mb-6 px-4 py-2 bg-white/5 border border-white/10 backdrop-blur-sm/20 backdrop-blur-sm rounded-full">
          <span class="text-sm font-semibold">
            <i class="fas fa-star mr-2"></i>Matrículas 2026 Abertas
          </span>
        </div>
        
        <h1 class="text-4xl md:text-6xl lg:text-7xl font-bold mb-6 font-poppins">
          Educação de Excelência<br>para o Futuro
        </h1>
        
        <p class="text-xl md:text-2xl text-white/90 mb-10 max-w-3xl mx-auto">
          Formando cidadãos conscientes, críticos e preparados para os desafios do mundo moderno.
        </p>
        
        <div class="flex flex-wrap justify-center gap-4">
          <a href="agendar_visita.php" class="btn-secondary text-lg bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm border-white/30 text-white hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 hover:border-white/50">
            <i class="fas fa-calendar-check mr-2"></i>Agendar Visita
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Notícias e Comunicados Section -->
  <section class="py-20 bg-white/5 border border-white/10 backdrop-blur-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between mb-12">
        <div>
          <h2 class="text-3xl md:text-4xl font-bold text-white mb-2 font-poppins">
            Notícias e Comunicados
          </h2>
          <p class="text-white/60">Fique por dentro das últimas novidades da escola</p>
        </div>
        <a href="noticias.php" class="hidden md:inline-flex items-center gap-2 text-blue-400 hover:text-blue-700 font-semibold">
          Ver todas <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
        <!-- Notícia em destaque -->
        <div class="lg:col-span-2 bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 rounded-2xl p-8 text-white shadow-xl hover:shadow-2xl transition-all">
          <div class="flex items-center gap-2 mb-4">
            <span class="px-3 py-1 bg-white/5 border border-white/10 backdrop-blur-sm/20 backdrop-blur-sm rounded-full text-sm font-medium">Destaque</span>
            <span class="text-sm opacity-80">Hoje</span>
          </div>
          <h3 class="text-2xl font-bold mb-3 font-poppins">Matrículas 2026 Abertas</h3>
          <p class="text-white/90 mb-6">As matrículas para o ano letivo de 2026 estão abertas. Garanta a vaga de seu filho em nossa instituição de excelência.</p>
          <a href="pre_matricula.php" class="inline-flex items-center gap-2 bg-white/5 border border-white/10 backdrop-blur-sm text-blue-400 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-all hover:scale-105">
            <i class="fas fa-user-graduate"></i> Fazer Pré-Matrícula
          </a>
        </div>
        
        <!-- Lista de notícias -->
        <div class="space-y-4">
          <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all cursor-pointer">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-[0_4px_20px_rgb(0,0,0,0.3)]">
                <i class="fas fa-newspaper text-white"></i>
              </div>
              <div>
                <h4 class="font-semibold text-white/90 mb-1 font-poppins">Calendário Escolar 2026</h4>
                <p class="text-sm text-white/60">Confira as datas importantes do ano letivo</p>
              </div>
            </div>
          </div>
          
          <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all cursor-pointer">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-[0_4px_20px_rgb(0,0,0,0.3)]">
                <i class="fas fa-calendar-check text-white"></i>
              </div>
              <div>
                <h4 class="font-semibold text-white/90 mb-1 font-poppins">Reunião de Pais</h4>
                <p class="text-sm text-white/60">15/02/2026 - Auditório Principal</p>
              </div>
            </div>
          </div>
          
          <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all cursor-pointer">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-[0_4px_20px_rgb(0,0,0,0.3)]">
                <i class="fas fa-trophy text-white"></i>
              </div>
              <div>
                <h4 class="font-semibold text-white/90 mb-1 font-poppins">Olimpíadas Escolares</h4>
                <p class="text-sm text-white/60">Inscrições abertas até 28/02</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Próximos Eventos Section -->
  <section class="py-20 bg-gradient-to-br from-gray-50 to-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between mb-12">
        <div>
          <h2 class="text-3xl md:text-4xl font-bold text-white mb-2 font-poppins">
            Próximos Eventos
          </h2>
          <p class="text-white/60">Participe das atividades da nossa comunidade</p>
        </div>
        <a href="eventos/eventos.html" class="hidden md:inline-flex items-center gap-2 text-blue-400 hover:text-blue-700 font-semibold">
          Ver todos <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.5)] hover:shadow-2xl transition-all hover:-translate-y-2">
          <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl flex items-center justify-center mb-4 shadow-[0_4px_20px_rgb(0,0,0,0.3)]">
            <i class="fas fa-graduation-cap text-white text-xl"></i>
          </div>
          <h4 class="font-semibold text-white/90 mb-2 font-poppins">Formatura Ensino Médio</h4>
          <p class="text-sm text-white/60 mb-3">15/12/2026</p>
          <a href="inscrever_evento.php" class="text-sm text-blue-400 hover:text-blue-700 font-semibold">Inscrever-se →</a>
        </div>
        
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.5)] hover:shadow-2xl transition-all hover:-translate-y-2">
          <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-700 rounded-xl flex items-center justify-center mb-4 shadow-[0_4px_20px_rgb(0,0,0,0.3)]">
            <i class="fas fa-running text-white text-xl"></i>
          </div>
          <h4 class="font-semibold text-white/90 mb-2 font-poppins">Gincana Escolar</h4>
          <p class="text-sm text-white/60 mb-3">20/03/2026</p>
          <a href="inscrever_evento.php" class="text-sm text-blue-400 hover:text-blue-700 font-semibold">Inscrever-se →</a>
        </div>
        
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.5)] hover:shadow-2xl transition-all hover:-translate-y-2">
          <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-700 rounded-xl flex items-center justify-center mb-4 shadow-[0_4px_20px_rgb(0,0,0,0.3)]">
            <i class="fas fa-palette text-white text-xl"></i>
          </div>
          <h4 class="font-semibold text-white/90 mb-2 font-poppins">Feira de Ciências</h4>
          <p class="text-sm text-white/60 mb-3">10/04/2026</p>
          <a href="inscrever_evento.php" class="text-sm text-blue-400 hover:text-blue-700 font-semibold">Inscrever-se →</a>
        </div>
        
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.5)] hover:shadow-2xl transition-all hover:-translate-y-2">
          <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-700 rounded-xl flex items-center justify-center mb-4 shadow-[0_4px_20px_rgb(0,0,0,0.3)]">
            <i class="fas fa-music text-white text-xl"></i>
          </div>
          <h4 class="font-semibold text-white/90 mb-2 font-poppins">Festival de Música</h4>
          <p class="text-sm text-white/60 mb-3">25/05/2026</p>
          <a href="inscrever_evento.php" class="text-sm text-blue-400 hover:text-blue-700 font-semibold">Inscrever-se →</a>
        </div>
      </div>
    </div>
  </section>

  <!-- Acesso Rápido Section -->
  <section class="py-20 bg-white/5 border border-white/10 backdrop-blur-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-4 font-poppins">
          Acesso Rápido
        </h2>
        <p class="text-white/60 max-w-2xl mx-auto">
          Acesse rapidamente os serviços mais utilizados
        </p>
      </div>
      
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <a href="biblioteca.php" class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all hover:-translate-y-2 group">
          <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-[0_8px_30px_rgb(0,0,0,0.5)] group-hover:scale-110 transition-transform">
            <i class="fas fa-book text-white text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-1 font-poppins">Biblioteca</h3>
          <p class="text-sm text-white/60">Acervo digital</p>
        </a>
        
        <a href="portal_pais.php" class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all hover:-translate-y-2 group">
          <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-700 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-[0_8px_30px_rgb(0,0,0,0.5)] group-hover:scale-110 transition-transform">
            <i class="fas fa-users text-white text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-1 font-poppins">Portal dos Pais</h3>
          <p class="text-sm text-white/60">Área responsáveis</p>
        </a>
        
        <a href="calendario_escolar.php" class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all hover:-translate-y-2 group">
          <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-[0_8px_30px_rgb(0,0,0,0.5)] group-hover:scale-110 transition-transform">
            <i class="fas fa-calendar text-white text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-1 font-poppins">Calendário</h3>
          <p class="text-sm text-white/60">Datas importantes</p>
        </a>
        
        <a href="formularios.php" class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all hover:-translate-y-2 group">
          <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-700 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-[0_8px_30px_rgb(0,0,0,0.5)] group-hover:scale-110 transition-transform">
            <i class="fas fa-file-download text-white text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-1 font-poppins">Formulários</h3>
          <p class="text-sm text-white/60">Downloads</p>
        </a>
      </div>
    </div>
  </section>

  <!-- Login Modal -->
  <div id="login-modal" class="modal-overlay">
    <div class="modal-content">
      <button id="close-modal" class="absolute top-4 right-4 text-gray-400 hover:text-white/60">
        <i class="fas fa-times text-xl"></i>
      </button>
      
      <div class="text-center mb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <i class="fas fa-sign-in-alt text-white text-2xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-white/90 mb-1" style="font-family: 'Playfair Display', serif;">Acesso ao Sistema</h2>
        <p class="text-white/60 text-sm">Entre com suas credenciais</p>
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
          <label id="usuario-label" class="block text-sm font-semibold text-white/70 mb-2">Matrícula</label>
          <input type="text" id="login-usuario" name="usuario" required class="form-input" placeholder="Digite sua matrícula">
        </div>
        
        <div>
          <label class="block text-sm font-semibold text-white/70 mb-2">Senha</label>
          <input type="password" id="login-senha" name="senha" required class="form-input" placeholder="Digite sua senha">
        </div>
        
        <div id="login-error" class="hidden text-red-400 text-sm text-center"></div>
        
        <button type="submit" class="btn-primary w-full">
          <i class="fas fa-sign-in-alt mr-2"></i>Entrar
        </button>
      </form>
      
      <p class="text-center text-white/60 text-sm mt-6">
        Esqueceu sua senha? <a href="#" class="text-blue-400 hover:text-blue-700">Recuperar</a>
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

  <!-- WhatsApp Floating Button -->
  <a href="https://wa.me/5511999999999" target="_blank" class="fixed bottom-6 right-6 w-14 h-14 bg-green-500 rounded-full flex items-center justify-center shadow-[0_8px_30px_rgb(0,0,0,0.5)] hover:bg-green-600 transition-all hover:scale-110 z-50 group">
    <i class="fab fa-whatsapp text-white text-2xl"></i>
    <div class="absolute right-16 bg-white/5 border border-white/10 backdrop-blur-sm px-4 py-2 rounded-lg shadow-[0_8px_30px_rgb(0,0,0,0.5)] opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
      <span class="text-sm font-medium text-white/90">Fale conosco!</span>
    </div>
  </a>
</body>
</html>


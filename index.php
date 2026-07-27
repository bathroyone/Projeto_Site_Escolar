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
    
    /* Animated Background */
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
    
    /* Glass Effect */
    .glass {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    /* Floating Particles */
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
    
    /* Header Animation */
    .header-animate {
      animation: slideDown 0.8s ease-out;
    }
    @keyframes slideDown {
      from { transform: translateY(-100%); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    
    /* Hero Section */
    .hero-animate {
      animation: fadeInUp 1s ease-out 0.3s both;
    }
    @keyframes fadeInUp {
      from { transform: translateY(50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    
    /* Button Animations */
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
    
    /* Card Hover Effect */
    .card-3d {
      transition: all 0.5s ease;
      transform-style: preserve-3d;
    }
    .card-3d:hover {
      transform: translateY(-10px) rotateX(5deg);
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
    }
    
    /* Modal Styles */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.8);
      backdrop-filter: blur(10px);
      z-index: 1000;
      display: none;
      animation: fadeIn 0.3s ease;
    }
    .modal-overlay.active {
      display: flex;
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    .modal-content {
      animation: modalSlide 0.4s ease;
    }
    @keyframes modalSlide {
      from { transform: scale(0.9) translateY(-50px); opacity: 0; }
      to { transform: scale(1) translateY(0); opacity: 1; }
    }
    
    /* Tab Styles */
    .tab-btn {
      transition: all 0.3s ease;
    }
    .tab-btn.active {
      background: linear-gradient(135deg, #ffd700, #ffed4a);
      color: #0a2463;
      transform: scale(1.05);
    }
    
    /* Input Styles */
    .input-animate {
      transition: all 0.3s ease;
    }
    .input-animate:focus {
      transform: scale(1.02);
      box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.3);
    }
    
    /* Gradient Text */
    .gradient-text {
      background: linear-gradient(135deg, #ffd700, #ffed4a, #fbbf24);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    /* Pulse Animation */
    .pulse {
      animation: pulse 2s infinite;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    /* Glow Effect */
    .glow {
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.5);
    }
    
    /* Mobile Menu */
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
  
  <!-- Animated Background -->
  <div class="bg-animation"></div>
  
  <!-- Floating Particles -->
  <div id="particles"></div>
  
  <!-- Header -->
  <header class="fixed top-0 left-0 right-0 z-50 glass header-animate">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">
        <a href="#" class="flex items-center gap-3 group">
          <img src="img/logo.jpg" alt="Logo" class="h-14 w-auto transition-transform duration-300 group-hover:scale-110">
        </a>

        <nav class="hidden lg:flex items-center gap-8">
          <a href="#" class="text-white/90 hover:text-white font-medium transition-colors relative group">
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
          <!-- Biblioteca Virtual Button -->
          <a href="biblioteca.php" class="btn-animate px-6 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-full font-semibold text-sm flex items-center gap-2 glow">
            <i class="fas fa-book"></i>
            <span>Biblioteca Virtual</span>
          </a>
          
          <?php if ($isLoggedIn): ?>
            <div class="relative ml-2">
              <button id="user-menu-btn" class="btn-animate px-4 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-full font-semibold text-sm flex items-center gap-2">
                <div class="w-8 h-8 bg-white/ rounded-full flex items-center justify-center">
                  <i class="fas fa-user"></i>
                </div>
                <span class="max-w-[80px] truncate"><?php echo htmlspecialchars(substr($userName, 0, 10)); ?></span>
                <i class="fas fa-chevron-down text-xs"></i>
              </button>
              <div id="user-menu-dropdown" class="absolute right-0 mt-2 w-56 bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl py-3 hidden z-50 border border-white/20">
                <div class="px-4 py-3 border-b border-white/10">
                  <p class="text-xs text-white/60">Logado como</p>
                  <p class="text-sm font-semibold text-white truncate"><?php echo htmlspecialchars($userName); ?></p>
                  <p class="text-xs text-yellow-400 font-medium capitalize"><?php echo htmlspecialchars($userType); ?></p>
                </div>
                <?php if ($userType === 'admin'): ?>
                  <a href="portal/admin/index.php" class="block px-4 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                    <i class="fas fa-cog mr-2"></i>Painel Admin
                  </a>
                <?php elseif ($userType === 'professor'): ?>
                  <a href="portal/professor/index.php" class="block px-4 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>Painel Professor
                  </a>
                <?php elseif ($userType === 'aluno'): ?>
                  <a href="portal/aluno/index.php" class="block px-4 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                    <i class="fas fa-graduation-cap mr-2"></i>Painel Aluno
                  </a>
                <?php elseif ($userType === 'secretaria'): ?>
                  <a href="portal/secretaria/index.php" class="block px-4 py-2 text-sm text-white/80 hover:bg-white/10 hover:text-white transition-colors">
                    <i class="fas fa-building mr-2"></i>Painel Secretaria
                  </a>
                <?php endif; ?>
                <div class="border-t border-white/10 mt-2 pt-2">
                  <a href="portal/logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-red-500/10 transition-colors">
                    <i class="fas fa-sign-out-alt mr-2"></i>Sair
                  </a>
                </div>
              </div>
            </div>
          <?php else: ?>
            <button id="acesso-sistema-btn" class="btn-animate ml-2 px-6 py-3 bg-gradient-to-r from-yellow-400 to-yellow-500 text-blue-900 rounded-full font-bold text-sm flex items-center gap-2 pulse">
              <i class="fas fa-sign-in-alt"></i>
              <span>Acesso</span>
            </button>
          <?php endif; ?>
        </div>

        <button id="mobile-menu-btn" class="lg:hidden p-3 rounded-xl text-white hover:bg-white/10 transition-colors">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>
    </div>
  </header>

  <!-- Mobile Menu -->
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
        <a href="#" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-xl transition-colors">Início</a>
        <a href="#about" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-xl transition-colors">Sobre</a>
        <a href="#projects" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-xl transition-colors">Projetos</a>
        <a href="#contact" class="px-4 py-3 text-white font-semibold hover:bg-white/10 rounded-xl transition-colors">Contato</a>
        <div class="border-t border-white/10 pt-4 mt-4 space-y-3">
          <a href="biblioteca.php" class="block px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl font-semibold text-center hover:shadow-lg transition-all flex items-center justify-center gap-2">
            <i class="fas fa-book"></i>Biblioteca Virtual
          </a>
          <?php if ($isLoggedIn): ?>
            <div class="px-4 py-3 bg-white/10 rounded-xl">
              <p class="text-white/60 text-xs mb-1">Logado como</p>
              <p class="text-white font-semibold"><?php echo htmlspecialchars(substr($userName, 0, 20)); ?></p>
              <p class="text-yellow-400 text-xs font-medium capitalize"><?php echo htmlspecialchars($userType); ?></p>
            </div>
            <a href="portal/logout.php" class="block px-4 py-3 bg-red-500/20 text-red-300 rounded-xl font-semibold text-center hover:bg-red-500/30 transition-all flex items-center justify-center gap-2">
              <i class="fas fa-sign-out-alt"></i>Sair
            </a>
          <?php else: ?>
            <button id="acesso-sistema-btn-mobile" class="block w-full px-4 py-3 bg-gradient-to-r from-yellow-400 to-yellow-500 text-blue-900 rounded-xl font-bold text-center hover:shadow-lg transition-all flex items-center justify-center gap-2">
              <i class="fas fa-sign-in-alt"></i>Acesso ao Sistema
            </button>
          <?php endif; ?>
        </div>
      </nav>
    </div>
  </div>

  <!-- Hero Section -->
  <section class="relative min-h-screen flex items-center justify-center pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
      <div class="text-center hero-animate">
        <div class="inline-block mb-6 px-6 py-3 glass rounded-full">
          <span class="text-yellow-400 font-semibold text-sm">
            <i class="fas fa-star mr-2"></i>Matrículas 2026 Abertas
          </span>
        </div>
        
        <h1 class="text-5xl md:text-7xl lg:text-8xl font-display font-bold mb-6 leading-tight">
          <span class="gradient-text">Inserir o Nome</span>
          <br>
          <span class="text-white">da Escola Aqui</span>
        </h1>
        
        <p class="text-xl md:text-2xl text-white/80 mb-10 max-w-3xl mx-auto leading-relaxed">
          Educação de excelência, acolhimento e tecnologia para formar grandes futuros.
        </p>
        
        <div class="flex flex-wrap justify-center gap-4 mb-16">
          <a href="#contact" class="btn-animate px-8 py-4 bg-gradient-to-r from-yellow-400 to-yellow-500 text-blue-900 rounded-full font-bold text-lg flex items-center gap-2 glow">
            <i class="fas fa-phone"></i>
            <span>Fale Conosco</span>
          </a>
          <a href="pre_matricula.php" class="btn-animate px-8 py-4 bg-gradient-to-r from-purple-600 to-purple-500 text-white rounded-full font-bold text-lg flex items-center gap-2">
            <i class="fas fa-user-graduate"></i>
            <span>Pré-Matrícula</span>
          </a>
          <a href="agendar_visita.php" class="btn-animate px-8 py-4 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-full font-bold text-lg flex items-center gap-2">
            <i class="fas fa-calendar-check"></i>
            <span>Agendar Visita</span>
          </a>
        </div>
        
        <!-- Access Cards -->
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 max-w-5xl mx-auto">
          <div class="card-3d glass rounded-2xl p-6 cursor-pointer" onclick="openLoginModal('professor')">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
            </div>
            <h3 class="text-white font-semibold text-lg mb-2">Professor</h3>
            <p class="text-white/60 text-sm">Acesso com matrícula</p>
          </div>
          
          <div class="card-3d glass rounded-2xl p-6 cursor-pointer" onclick="openLoginModal('aluno')">
            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-graduation-cap text-white text-2xl"></i>
            </div>
            <h3 class="text-white font-semibold text-lg mb-2">Aluno</h3>
            <p class="text-white/60 text-sm">Acesso com CPF do responsável</p>
          </div>
          
          <div class="card-3d glass rounded-2xl p-6 cursor-pointer" onclick="openLoginModal('secretaria')">
            <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-building text-white text-2xl"></i>
            </div>
            <h3 class="text-white font-semibold text-lg mb-2">Secretaria</h3>
            <p class="text-white/60 text-sm">Acesso com usuário</p>
          </div>
          
          <div class="card-3d glass rounded-2xl p-6 cursor-pointer" onclick="openLoginModal('admin')">
            <div class="w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-cog text-white text-2xl"></i>
            </div>
            <h3 class="text-white font-semibold text-lg mb-2">Admin</h3>
            <p class="text-white/60 text-sm">Acesso administrativo</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Login Modal -->
  <div id="login-modal" class="modal-overlay items-center justify-center p-4">
    <div class="modal-content glass rounded-3xl p-8 max-w-md w-full relative">
      <button id="close-modal" class="absolute top-4 right-4 text-white/60 hover:text-white transition-colors">
        <i class="fas fa-times text-xl"></i>
      </button>
      
      <div class="text-center mb-8">
        <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
          <i class="fas fa-sign-in-alt text-blue-900 text-3xl"></i>
        </div>
        <h2 class="text-2xl font-bold text-white mb-2">Acesso ao Sistema</h2>
        <p class="text-white/60 text-sm">Entre com suas credenciais</p>
      </div>
      
      <!-- Tabs -->
      <div class="flex flex-wrap gap-2 mb-6">
        <button class="tab-btn flex-1 px-4 py-2 rounded-xl text-sm font-semibold bg-white/10 text-white/80" data-tab="professor">
          <i class="fas fa-chalkboard-teacher mr-1"></i>Professor
        </button>
        <button class="tab-btn flex-1 px-4 py-2 rounded-xl text-sm font-semibold bg-white/10 text-white/80" data-tab="aluno">
          <i class="fas fa-graduation-cap mr-1"></i>Aluno
        </button>
        <button class="tab-btn flex-1 px-4 py-2 rounded-xl text-sm font-semibold bg-white/10 text-white/80" data-tab="secretaria">
          <i class="fas fa-building mr-1"></i>Secretaria
        </button>
        <button class="tab-btn flex-1 px-4 py-2 rounded-xl text-sm font-semibold bg-white/10 text-white/80" data-tab="admin">
          <i class="fas fa-cog mr-1"></i>Admin
        </button>
      </div>
      
      <!-- Login Form -->
      <form id="login-form" class="space-y-4">
        <input type="hidden" id="login-tipo" name="tipo" value="professor">
        
        <div>
          <label id="usuario-label" class="block text-sm font-semibold text-white mb-2">Matrícula</label>
          <input type="text" id="login-usuario" name="usuario" required class="input-animate w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-yellow-400" placeholder="Digite sua matrícula">
        </div>
        
        <div>
          <label class="block text-sm font-semibold text-white mb-2">Senha</label>
          <input type="password" id="login-senha" name="senha" required class="input-animate w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-white/40 focus:outline-none focus:border-yellow-400" placeholder="Digite sua senha">
        </div>
        
        <div id="login-error" class="hidden text-red-400 text-sm text-center"></div>
        
        <button type="submit" class="btn-animate w-full py-4 bg-gradient-to-r from-yellow-400 to-yellow-500 text-blue-900 rounded-xl font-bold text-lg flex items-center justify-center gap-2">
          <i class="fas fa-sign-in-alt"></i>
          <span>Entrar</span>
        </button>
      </form>
      
      <p class="text-center text-white/60 text-sm mt-6">
        Esqueceu sua senha? <a href="#" class="text-yellow-400 hover:text-yellow-300 transition-colors">Recuperar</a>
      </p>
    </div>
  </div>

  <script>
    // Create floating particles
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

    // Mobile menu
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
          loginForm.classList.add('error-message');
          setTimeout(() => loginForm.classList.remove('error-message'), 500);
        }
      } catch (error) {
        loginError.textContent = 'Erro ao processar login. Tente novamente.';
        loginError.classList.remove('hidden');
      }
    });

    // GSAP animations
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

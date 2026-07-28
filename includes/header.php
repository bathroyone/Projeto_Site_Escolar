<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
  <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' : ''; ?>Site Institucional Moderno e Sistema de Gestão Escolar</title>
  <meta name="description" content="Site institucional moderno e sistema de gestão escolar completo para instituições educacionais.">
  <link rel="stylesheet" href="css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    body {
      font-family: 'Inter', system-ui, sans-serif;
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
      color: #1e293b;
      font-weight: 500;
      padding: 0.5rem 1rem;
      transition: all 0.3s ease;
      position: relative;
    }
    .nav-link:hover {
      color: #2563eb;
    }
    .nav-link::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      width: 0;
      height: 2px;
      background: #2563eb;
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
      border: 1px solid #e2e8f0;
    }
    .dropdown:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    .dropdown-item {
      display: block;
      padding: 0.5rem 1rem;
      color: #1e293b;
      transition: all 0.2s ease;
    }
    .dropdown-item:hover {
      background: #f1f5f9;
      color: #2563eb;
    }
    
    /* Buttons */
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
  </style>
</head>
<body class="text-gray-800">
  
  <!-- Main Header -->
  <header class="main-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-auto min-h-[5rem] py-3 flex-wrap lg:flex-nowrap gap-4">
        <a href="index.php" class="flex items-center gap-3 shrink-0">
          <img src="img/logo.jpg" alt="Logo" class="h-12 sm:h-14 w-auto shrink-0">
          <div class="hidden sm:block shrink-0">
            <h1 class="text-lg sm:text-xl font-bold text-gray-800 font-poppins leading-tight whitespace-nowrap">Nome da Escola</h1>
            <p class="text-xs text-gray-500 whitespace-nowrap">Educação de Excelência</p>
          </div>
        </a>

        <nav class="hidden lg:flex items-center gap-1 shrink-1 overflow-x-auto">
          <a href="index.php" class="nav-link whitespace-nowrap">Início</a>
          
          <div class="dropdown">
            <a href="#" class="nav-link flex items-center gap-1 whitespace-nowrap">
              Instituição <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <div class="dropdown-menu">
              <a href="historico.php" class="dropdown-item">História</a>
              <a href="tour_virtual.php" class="dropdown-item">Tour Virtual 360°</a>
              <a href="transparencia.php" class="dropdown-item">Transparência</a>
            </div>
          </div>
          
          <div class="dropdown">
            <a href="#" class="nav-link flex items-center gap-1 whitespace-nowrap">
              Ensino <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <div class="dropdown-menu">
              <a href="educacao_infantil.php" class="dropdown-item">Educação Infantil</a>
              <a href="ensino_fundamental_i.php" class="dropdown-item">Ensino Fundamental I</a>
              <a href="ensino_fundamental_ii.php" class="dropdown-item">Ensino Fundamental II</a>
              <a href="ensino_medio.php" class="dropdown-item">Ensino Médio</a>
            </div>
          </div>
          
          <div class="dropdown">
            <a href="#" class="nav-link flex items-center gap-1 whitespace-nowrap">
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
            <a href="#" class="nav-link flex items-center gap-1 whitespace-nowrap">
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
            <a href="#" class="nav-link flex items-center gap-1 whitespace-nowrap">
              Recursos <i class="fas fa-chevron-down text-xs"></i>
            </a>
            <div class="dropdown-menu">
              <a href="projetos.php" class="dropdown-item">Projetos</a>
              <a href="recursos_educacionais.php" class="dropdown-item">Recursos Educacionais</a>
              <a href="album/index.php" class="dropdown-item">Álbum de Fotos</a>
              <a href="galeria_videos.php" class="dropdown-item">Galeria de Vídeos</a>
            </div>
          </div>
          
          <a href="contato_departamentos.php" class="nav-link whitespace-nowrap">Contato</a>
        </nav>

        <div class="hidden md:flex items-center gap-2 shrink-0">
          <a href="biblioteca.php" class="btn-secondary text-sm inline-flex items-center justify-center whitespace-nowrap">
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
            <button id="acesso-sistema-btn" class="btn-primary text-sm inline-flex items-center justify-center whitespace-nowrap">
              <i class="fas fa-sign-in-alt mr-2"></i>Acesso ao Sistema
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
        <a href="index.php" class="text-gray-700 font-semibold hover:text-blue-600">Início</a>
        <a href="historico.php" class="text-gray-700 font-semibold hover:text-blue-600">História</a>
        <a href="tour_virtual.php" class="text-gray-700 font-semibold hover:text-blue-600">Tour Virtual</a>
        <a href="biblioteca.php" class="text-gray-700 font-semibold hover:text-blue-600">Biblioteca</a>
        <a href="portal_pais.php" class="text-gray-700 font-semibold hover:text-blue-600">Portal dos Pais</a>
        <a href="calendario_escolar.php" class="text-gray-700 font-semibold hover:text-blue-600">Calendário</a>
        <a href="contato_departamentos.php" class="text-gray-700 font-semibold hover:text-blue-600">Contato</a>
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

  <!-- Login Modal -->
  <div id="login-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center opacity-0 transition-opacity duration-300" style="z-index: 9999;">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden transform scale-95 transition-transform duration-300">
      <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-6 text-white flex justify-between items-center">
        <h3 class="text-xl font-bold font-poppins">Acesso ao Sistema</h3>
        <button id="close-login" class="text-white/80 hover:text-white"><i class="fas fa-times text-xl"></i></button>
      </div>
      <div class="p-6">
        <form id="login-form" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Acesso</label>
            <select name="tipo" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none bg-white">
              <option value="aluno">Aluno / Responsável</option>
              <option value="professor">Professor</option>
              <option value="secretaria">Secretaria</option>
              <option value="admin">Administrador</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Usuário / CPF / Matrícula</label>
            <input type="text" name="usuario" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
            <input type="password" name="senha" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
          </div>
          <div id="login-error" class="text-red-500 text-sm hidden"></div>
          <button type="submit" class="w-full btn-primary flex justify-center items-center py-2 mt-2">
            <span>Entrar</span>
            <i class="fas fa-spinner fa-spin ml-2 hidden" id="login-loading"></i>
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Mobile menu functionality
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const closeMenuBtn = document.getElementById('close-menu');
    const mobileMenu = document.getElementById('mobile-menu');
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
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (userMenuBtn && !userMenuBtn.contains(e.target) && !userMenuDropdown.contains(e.target)) {
        userMenuDropdown.classList.add('hidden');
      }
    });

    document.addEventListener('DOMContentLoaded', () => {
      // Login Modal functionality
      const loginModal = document.getElementById('login-modal');
      const closeLoginBtn = document.getElementById('close-login');
      const loginForm = document.getElementById('login-form');
      const loginError = document.getElementById('login-error');
      const loginLoading = document.getElementById('login-loading');
      
      const acessoBtns = document.querySelectorAll('#acesso-sistema-btn, #acesso-sistema-btn-mobile');
      
      acessoBtns.forEach(btn => {
        if (btn) {
          btn.addEventListener('click', (e) => {
            e.preventDefault();
            if(mobileMenu) mobileMenu.classList.remove('active');
            if(mobileOverlay) mobileOverlay.classList.remove('active');
            
            loginModal.classList.remove('hidden');
            loginModal.classList.add('flex');
            setTimeout(() => {
              loginModal.classList.remove('opacity-0');
              loginModal.querySelector('div').classList.remove('scale-95');
            }, 10);
          });
        }
      });
      
      if (closeLoginBtn) {
        closeLoginBtn.addEventListener('click', () => {
          loginModal.classList.add('opacity-0');
          loginModal.querySelector('div').classList.add('scale-95');
          setTimeout(() => {
            loginModal.classList.add('hidden');
            loginModal.classList.remove('flex');
          }, 300);
        });
      }
      
      if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          loginError.classList.add('hidden');
          loginLoading.classList.remove('hidden');
          
          try {
            const formData = new FormData(loginForm);
            const response = await fetch('login.php', {
              method: 'POST',
              body: formData
            });
            const data = await response.json();
            
            if (data.success) {
              window.location.href = data.redirect;
            } else {
              loginError.textContent = data.message;
              loginError.classList.remove('hidden');
            }
          } catch (error) {
            loginError.textContent = 'Erro ao conectar. Tente novamente.';
            loginError.classList.remove('hidden');
          } finally {
            loginLoading.classList.add('hidden');
          }
        });
      }
    });
  </script>

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
  <title><?php echo isset($pageTitle) ? $pageTitle . ' | ' : ''; ?>Colégio de Excelência</title>
  <meta name="description" content="Site institucional moderno e sistema de gestão escolar completo para instituições educacionais.">
  <link rel="stylesheet" href="css/output.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Outfit', 'Inter', system-ui, sans-serif;
      background: #f8f9fa;
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* ── HEADER ─────────────────────────────────── */
    .main-header {
      background: rgba(5, 12, 26, 0.85);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      box-shadow: 0 1px 0 rgba(255,255,255,0.08), 0 8px 32px rgba(0,0,0,0.4);
      border-bottom: 1px solid rgba(255,255,255,0.05);
      position: sticky;
      top: 0;
      z-index: 200;
    }

    .header-inner {
      display: flex;
      align-items: center;
      height: 76px;
      gap: 0;
    }

    /* Logo */
    .logo-area {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      flex-shrink: 0;
      margin-right: 24px;
    }
    .logo-area img { height: 52px; width: auto; }
    .logo-text-wrap { display: none; }
    @media(min-width: 640px){ .logo-text-wrap { display: block; } }
    .logo-name {
      font-family: 'Outfit', sans-serif;
      font-size: 1.25rem;
      font-weight: 800;
      color: #ffffff;
      line-height: 1.2;
      white-space: nowrap;
      letter-spacing: -0.02em;
    }
    .logo-tag {
      font-size: 0.72rem;
      color: rgba(255,255,255,0.5);
      font-weight: 600;
      letter-spacing: 0.1em;
      white-space: nowrap;
      text-transform: uppercase;
    }

    /* Nav */
    .main-nav {
      display: none;
      align-items: center;
      gap: 2px;
      flex: 1;
    }
    @media(min-width: 1024px){ .main-nav { display: flex; } }

    .nav-link {
      color: rgba(255,255,255,0.75);
      font-weight: 600;
      font-size: 0.85rem;
      padding: 0.5rem 0.8rem;
      border-radius: 10px;
      transition: all 0.2s ease;
      white-space: nowrap;
      text-decoration: none;
      position: relative;
    }
    .nav-link:hover { color: #ffffff; background: rgba(255,255,255,0.08); }

    /* Dropdown */
    .nav-dropdown { position: relative; }
    .nav-dropdown-trigger {
      display: flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
    }
    .nav-dropdown-trigger .chev {
      font-size: 0.65rem;
      transition: transform 0.25s ease;
      color: #94a3b8;
    }
    .nav-dropdown:hover .chev { transform: rotate(180deg); }

    .nav-dropdown-menu {
      position: absolute;
      top: calc(100% + 8px);
      left: 50%;
      transform: translateX(-50%) translateY(6px);
      background: rgba(15, 23, 42, 0.95);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(255,255,255,0.1);
      min-width: 240px;
      border-radius: 16px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05);
      padding: 8px;
      opacity: 0;
      visibility: hidden;
      transition: all 0.2s cubic-bezier(0.4,0,0.2,1);
      pointer-events: none;
    }
    .nav-dropdown::after {
      content: '';
      position: absolute;
      top: 100%;
      left: 0;
      width: 100%;
      height: 15px; /* Invisible bridge to prevent hover loss */
    }
    .nav-dropdown:hover .nav-dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateX(-50%) translateY(0);
      pointer-events: all;
    }
    .nav-dropdown-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      border-radius: 10px;
      color: rgba(255,255,255,0.8);
      font-size: 0.85rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .nav-dropdown-item:hover { background: rgba(255,255,255,0.08); color: #ffffff; }
    .nav-dropdown-item i { width: 20px; text-align: center; color: rgba(255,255,255,0.4); font-size: 0.85rem; transition: color 0.2s; }
    .nav-dropdown-item:hover i { color: #8b5cf6; }

    /* Header right buttons */
    .header-actions {
      display: none;
      align-items: center;
      gap: 10px;
      margin-left: 20px;
      flex-shrink: 0;
    }
    @media(min-width: 768px){ .header-actions { display: flex; } }

    .btn-lib {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 18px;
      border-radius: 12px;
      font-size: 0.85rem;
      font-weight: 700;
      color: rgba(255,255,255,0.9);
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.15);
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .btn-lib:hover { background: rgba(255,255,255,0.15); color: #ffffff; border-color: rgba(255,255,255,0.3); }

    .btn-acesso {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 20px;
      border-radius: 10px;
      font-size: 0.85rem;
      font-weight: 600;
      color: white;
      background: linear-gradient(135deg, #1d4ed8, #4f46e5);
      border: none;
      cursor: pointer;
      transition: all 0.25s ease;
      white-space: nowrap;
      box-shadow: 0 4px 12px rgba(29,78,216,0.35);
    }
    .btn-acesso:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 20px rgba(29,78,216,0.45);
    }

    .mobile-toggle {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-left: auto;
      width: 44px; height: 44px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,0.1);
      background: rgba(255,255,255,0.05);
      cursor: pointer;
      color: rgba(255,255,255,0.9);
      font-size: 1.2rem;
      transition: all 0.2s;
    }
    .mobile-toggle:hover { background: rgba(255,255,255,0.15); color: #ffffff; }
    @media(min-width: 1024px){ .mobile-toggle { display: none; } }

    .mobile-drawer {
      position: fixed;
      top: 0; right: -100%;
      width: 320px; height: 100vh;
      background: #050c1a;
      border-left: 1px solid rgba(255,255,255,0.1);
      z-index: 1001;
      transition: right 0.3s cubic-bezier(0.4,0,0.2,1);
      box-shadow: -8px 0 40px rgba(0,0,0,0.5);
      overflow-y: auto;
    }
    .mobile-drawer.open { right: 0; }
    .mobile-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.65);
      z-index: 1000;
      backdrop-filter: blur(4px);
      display: none;
    }
    .mobile-overlay.open { display: block; }

    .mobile-nav-link {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 18px;
      border-radius: 12px;
      color: rgba(255,255,255,0.8);
      font-weight: 600;
      font-size: 0.95rem;
      text-decoration: none;
      transition: all 0.2s;
    }
    .mobile-nav-link:hover { background: rgba(255,255,255,0.08); color: #ffffff; }
    .mobile-nav-link i { color: rgba(255,255,255,0.4); }

    /* Social icon */
    .social-icon {
      width: 36px; height: 36px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      color: white;
      transition: all 0.3s ease;
    }
    .social-icon:hover { transform: translateY(-3px); }

    /* ── LOGIN MODAL ─────────────────────────────── */
    .login-overlay {
      position: fixed; inset: 0;
      background: rgba(15,23,42,0.65);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .login-overlay.show { display: flex; }
    .login-overlay.visible { opacity: 1; }

    .login-card {
      background: white;
      border-radius: 24px;
      width: 100%;
      max-width: 440px;
      overflow: hidden;
      box-shadow: 0 32px 80px rgba(0,0,0,0.25), 0 0 0 1px rgba(255,255,255,0.1);
      transform: translateY(20px) scale(0.97);
      transition: transform 0.35s cubic-bezier(0.34,1.56,0.64,1);
    }
    .login-overlay.visible .login-card {
      transform: translateY(0) scale(1);
    }

    .login-header-bar {
      background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #1d4ed8 100%);
      padding: 32px 32px 24px;
      position: relative;
      overflow: hidden;
    }
    .login-header-bar::before {
      content: '';
      position: absolute;
      top: -30px; right: -30px;
      width: 120px; height: 120px;
      border-radius: 50%;
      background: rgba(255,255,255,0.06);
    }
    .login-header-bar::after {
      content: '';
      position: absolute;
      bottom: -20px; left: 40px;
      width: 80px; height: 80px;
      border-radius: 50%;
      background: rgba(255,255,255,0.04);
    }
    .login-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.2);
      border-radius: 100px;
      padding: 4px 12px;
      color: rgba(255,255,255,0.85);
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 12px;
    }
    .login-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.65rem;
      font-weight: 700;
      color: white;
      margin-bottom: 4px;
    }
    .login-subtitle {
      color: rgba(255,255,255,0.6);
      font-size: 0.875rem;
    }
    .login-close-btn {
      position: absolute;
      top: 16px; right: 16px;
      width: 34px; height: 34px;
      border-radius: 50%;
      border: none;
      background: rgba(255,255,255,0.12);
      color: rgba(255,255,255,0.8);
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.9rem;
      transition: all 0.2s;
    }
    .login-close-btn:hover { background: rgba(255,255,255,0.22); color: white; }

    .login-body { padding: 28px 32px 32px; }

    .login-type-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-bottom: 24px;
    }
    .login-type-btn {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      padding: 12px 8px;
      border-radius: 12px;
      border: 2px solid #e2e8f0;
      background: white;
      cursor: pointer;
      font-size: 0.8rem;
      font-weight: 600;
      color: #64748b;
      transition: all 0.2s ease;
    }
    .login-type-btn i { font-size: 1.2rem; color: #94a3b8; }
    .login-type-btn:hover { border-color: #bfdbfe; background: #f0f9ff; color: #1d4ed8; }
    .login-type-btn:hover i { color: #3b82f6; }
    .login-type-btn.active {
      border-color: #3b82f6;
      background: linear-gradient(135deg, #eff6ff, #e0f2fe);
      color: #1d4ed8;
    }
    .login-type-btn.active i { color: #1d4ed8; }

    .login-field { margin-bottom: 16px; }
    .login-field label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: #374151;
      margin-bottom: 6px;
    }
    .login-input {
      width: 100%;
      padding: 11px 14px;
      border: 1.5px solid #e2e8f0;
      border-radius: 10px;
      font-size: 0.9rem;
      color: #1e293b;
      background: #f8fafc;
      transition: all 0.2s ease;
      outline: none;
    }
    .login-input:focus {
      border-color: #3b82f6;
      background: white;
      box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }
    .login-input-wrap { position: relative; }
    .login-input-wrap .login-input { padding-left: 40px; }
    .login-input-icon {
      position: absolute;
      left: 13px; top: 50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 0.9rem;
    }

    .login-submit-btn {
      width: 100%;
      padding: 13px;
      border-radius: 12px;
      border: none;
      background: linear-gradient(135deg, #1d4ed8 0%, #4f46e5 100%);
      color: white;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.25s ease;
      box-shadow: 0 4px 16px rgba(29,78,216,0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-top: 8px;
    }
    .login-submit-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(29,78,216,0.45);
    }
    .login-error-msg {
      background: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 10px;
      padding: 10px 14px;
      color: #dc2626;
      font-size: 0.825rem;
      font-weight: 500;
      display: none;
      align-items: center;
      gap: 8px;
      margin-bottom: 12px;
    }
    .login-error-msg.show { display: flex; }

    .login-footer-note {
      text-align: center;
      margin-top: 20px;
      font-size: 0.78rem;
      color: #94a3b8;
    }
    .login-footer-note a { color: #3b82f6; text-decoration: none; font-weight: 600; }

    /* Dropdown & btn resets (kept for compat) */
    .btn-primary {
      background: linear-gradient(135deg,#1d4ed8,#4f46e5);
      color: white; padding: .75rem 1.5rem;
      border-radius: .5rem; font-weight: 600;
      transition: all .3s ease;
      box-shadow: 0 4px 6px rgba(29,78,216,.3);
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 12px rgba(29,78,216,.4); }
    .btn-secondary {
      background: white; color: #1e3a8a;
      padding: .75rem 1.5rem; border-radius: .5rem; font-weight: 600;
      transition: all .3s ease; border: 2px solid #e2e8f0;
    }
    .btn-secondary:hover { border-color: #3b82f6; color: #3b82f6; }
    h1,h2,h3,h4,h5,h6 { font-family: 'Outfit', sans-serif; }
    .login-card { font-family: 'Outfit', sans-serif !important; }
    .login-card *:not(i) { font-family: 'Outfit', sans-serif !important; }
    .login-input { font-family: 'Outfit', sans-serif !important; }
  </style>
</head>
<body class="text-gray-800">

  <!-- ══════════ MAIN HEADER ══════════ -->
  <header class="main-header">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 header-inner">

      <!-- Logo -->
      <a href="index.php" class="logo-area">
        <img src="img/logo.jpg" alt="Logo da Escola">
        <div class="logo-text-wrap">
          <div class="logo-name">Nome da Escola</div>
          <div class="logo-tag">Educação de Excelência</div>
        </div>
      </a>

      <!-- Desktop Nav -->
      <nav class="main-nav">
        <a href="index.php" class="nav-link">Início</a>

        <div class="nav-dropdown">
          <a href="#" class="nav-link nav-dropdown-trigger">Instituição <i class="fas fa-chevron-down chev"></i></a>
          <div class="nav-dropdown-menu">
            <a href="historico.php" class="nav-dropdown-item"><i class="fas fa-landmark"></i>História</a>
            <a href="tour_virtual.php" class="nav-dropdown-item"><i class="fas fa-vr-cardboard"></i>Tour Virtual 360°</a>
            <a href="transparencia.php" class="nav-dropdown-item"><i class="fas fa-file-alt"></i>Transparência</a>
          </div>
        </div>

        <div class="nav-dropdown">
          <a href="#" class="nav-link nav-dropdown-trigger">Ensino <i class="fas fa-chevron-down chev"></i></a>
          <div class="nav-dropdown-menu">
            <a href="educacao_infantil.php" class="nav-dropdown-item"><i class="fas fa-child"></i>Educação Infantil</a>
            <a href="ensino_fundamental_i.php" class="nav-dropdown-item"><i class="fas fa-star"></i>Ensino Fundamental I</a>
            <a href="ensino_fundamental_ii.php" class="nav-dropdown-item"><i class="fas fa-atom"></i>Ensino Fundamental II</a>
            <a href="ensino_medio.php" class="nav-dropdown-item"><i class="fas fa-graduation-cap"></i>Ensino Médio</a>
          </div>
        </div>

        <div class="nav-dropdown">
          <a href="#" class="nav-link nav-dropdown-trigger">Serviços <i class="fas fa-chevron-down chev"></i></a>
          <div class="nav-dropdown-menu">
            <a href="biblioteca.php" class="nav-dropdown-item"><i class="fas fa-book"></i>Biblioteca</a>
            <a href="transporte.php" class="nav-dropdown-item"><i class="fas fa-bus"></i>Transporte</a>
            <a href="formularios.php" class="nav-dropdown-item"><i class="fas fa-file-download"></i>Formulários</a>
            <a href="calendario_escolar.php" class="nav-dropdown-item"><i class="fas fa-calendar"></i>Calendário Escolar</a>
          </div>
        </div>

        <div class="nav-dropdown">
          <a href="#" class="nav-link nav-dropdown-trigger">Comunidade <i class="fas fa-chevron-down chev"></i></a>
          <div class="nav-dropdown-menu">
            <a href="portal_pais.php" class="nav-dropdown-item"><i class="fas fa-users"></i>Portal dos Pais</a>
            <a href="ex_alunos.php" class="nav-dropdown-item"><i class="fas fa-user-tie"></i>Ex-Alunos</a>
            <a href="parcerias.php" class="nav-dropdown-item"><i class="fas fa-handshake"></i>Parcerias</a>
            <a href="trabalhe_conosco.php" class="nav-dropdown-item"><i class="fas fa-briefcase"></i>Trabalhe Conosco</a>
          </div>
        </div>

        <div class="nav-dropdown">
          <a href="#" class="nav-link nav-dropdown-trigger">Recursos <i class="fas fa-chevron-down chev"></i></a>
          <div class="nav-dropdown-menu">
            <a href="projetos.php" class="nav-dropdown-item"><i class="fas fa-lightbulb"></i>Projetos</a>
            <a href="recursos_educacionais.php" class="nav-dropdown-item"><i class="fas fa-laptop"></i>Recursos Educacionais</a>
            <a href="album/index.php" class="nav-dropdown-item"><i class="fas fa-images"></i>Álbum de Fotos</a>
            <a href="galeria_videos.php" class="nav-dropdown-item"><i class="fas fa-video"></i>Galeria de Vídeos</a>
          </div>
        </div>

        <a href="contato_departamentos.php" class="nav-link">Contato</a>
      </nav>

      <!-- Desktop Action Buttons -->
      <div class="header-actions">
        <a href="biblioteca.php" class="btn-lib">
          <i class="fas fa-book-open"></i> Biblioteca
        </a>

        <?php if ($isLoggedIn): ?>
          <div style="position:relative;">
            <button id="user-menu-btn" class="btn-acesso">
              <i class="fas fa-user-circle"></i>
              <span><?php echo htmlspecialchars(substr($userName, 0, 12)); ?></span>
              <i class="fas fa-chevron-down" style="font-size:0.65rem; opacity:0.7;"></i>
            </button>
            <div id="user-menu-dropdown" style="position:absolute; right:0; top:calc(100% + 8px); width:200px; background:white; border-radius:14px; box-shadow:0 12px 40px rgba(0,0,0,0.14), 0 0 0 1px rgba(0,0,0,0.05); padding:8px; display:none; z-index:300;">
              <div style="padding:10px 12px 8px; border-bottom:1px solid #f1f5f9; margin-bottom:4px;">
                <p style="font-size:0.7rem; color:#94a3b8; margin-bottom:2px;">Logado como</p>
                <p style="font-size:0.875rem; font-weight:600; color:#1e293b;"><?php echo htmlspecialchars($userName); ?></p>
                <p style="font-size:0.75rem; color:#3b82f6; font-weight:500; text-transform:capitalize;"><?php echo htmlspecialchars($userType); ?></p>
              </div>
              <?php if ($userType === 'admin'): ?>
                <a href="portal/admin/index.php" style="display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:8px; color:#374151; font-size:0.85rem; text-decoration:none; transition:background 0.15s;" onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='transparent'"><i class="fas fa-shield-alt" style="width:16px; color:#94a3b8;"></i>Painel Admin</a>
              <?php elseif ($userType === 'professor'): ?>
                <a href="portal/professor/index.php" style="display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:8px; color:#374151; font-size:0.85rem; text-decoration:none; transition:background 0.15s;" onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='transparent'"><i class="fas fa-chalkboard-teacher" style="width:16px; color:#94a3b8;"></i>Painel Professor</a>
              <?php elseif ($userType === 'aluno'): ?>
                <a href="portal/aluno/index.php" style="display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:8px; color:#374151; font-size:0.85rem; text-decoration:none; transition:background 0.15s;" onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='transparent'"><i class="fas fa-user-graduate" style="width:16px; color:#94a3b8;"></i>Painel Aluno</a>
              <?php elseif ($userType === 'secretaria'): ?>
                <a href="portal/secretaria/index.php" style="display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:8px; color:#374151; font-size:0.85rem; text-decoration:none; transition:background 0.15s;" onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='transparent'"><i class="fas fa-building" style="width:16px; color:#94a3b8;"></i>Painel Secretaria</a>
              <?php endif; ?>
              <div style="border-top:1px solid #f1f5f9; margin-top:4px; padding-top:4px;">
                <a href="portal/logout.php" style="display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:8px; color:#dc2626; font-size:0.85rem; text-decoration:none; transition:background 0.15s;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'"><i class="fas fa-sign-out-alt" style="width:16px;"></i>Sair</a>
              </div>
            </div>
          </div>
        <?php else: ?>
          <button id="acesso-sistema-btn" class="btn-acesso">
            <i class="fas fa-lock" style="font-size:0.8rem;"></i> Acesso ao Sistema
          </button>
        <?php endif; ?>
      </div>

      <!-- Mobile toggle -->
      <button id="mobile-toggle-btn" class="mobile-toggle">
        <i class="fas fa-bars"></i>
      </button>
    </div>
  </header>

  <!-- ══════════ MOBILE DRAWER ══════════ -->
  <div id="mobile-drawer" class="mobile-drawer">
    <div style="padding:24px;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:32px;">
        <a href="index.php" style="display:flex; align-items:center; gap:12px; text-decoration:none;">
          <img src="img/logo.jpg" alt="Logo" style="height:48px; border-radius:12px;">
          <span style="font-family:'Outfit',sans-serif; font-size:1.15rem; font-weight:800; color:white;">Nome da Escola</span>
        </a>
        <button id="close-drawer" style="width:40px; height:40px; border-radius:12px; border:1px solid rgba(255,255,255,0.1); background:rgba(255,255,255,0.05); cursor:pointer; font-size:1.1rem; color:rgba(255,255,255,0.8); transition:all 0.2s;">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <nav style="display:flex; flex-direction:column; gap:2px;">
        <a href="index.php" class="mobile-nav-link"><i class="fas fa-home" style="width:18px; color:#94a3b8;"></i>Início</a>
        <a href="historico.php" class="mobile-nav-link"><i class="fas fa-landmark" style="width:18px; color:#94a3b8;"></i>História</a>
        <a href="educacao_infantil.php" class="mobile-nav-link"><i class="fas fa-child" style="width:18px; color:#94a3b8;"></i>Educação Infantil</a>
        <a href="ensino_fundamental_i.php" class="mobile-nav-link"><i class="fas fa-star" style="width:18px; color:#94a3b8;"></i>Ensino Fundamental I</a>
        <a href="ensino_fundamental_ii.php" class="mobile-nav-link"><i class="fas fa-atom" style="width:18px; color:#94a3b8;"></i>Ensino Fundamental II</a>
        <a href="ensino_medio.php" class="mobile-nav-link"><i class="fas fa-graduation-cap" style="width:18px; color:#94a3b8;"></i>Ensino Médio</a>
        <a href="biblioteca.php" class="mobile-nav-link"><i class="fas fa-book" style="width:18px; color:#94a3b8;"></i>Biblioteca</a>
        <a href="portal_pais.php" class="mobile-nav-link"><i class="fas fa-users" style="width:18px; color:#94a3b8;"></i>Portal dos Pais</a>
        <a href="calendario_escolar.php" class="mobile-nav-link"><i class="fas fa-calendar" style="width:18px; color:#94a3b8;"></i>Calendário</a>
        <a href="contato_departamentos.php" class="mobile-nav-link"><i class="fas fa-envelope" style="width:18px; color:#94a3b8;"></i>Contato</a>
      </nav>
      <div style="margin-top:20px; padding-top:20px; border-top:1px solid #f1f5f9; display:flex; flex-direction:column; gap:10px;">
        <a href="biblioteca.php" class="btn-lib" style="justify-content:center;">
          <i class="fas fa-book-open"></i> Biblioteca
        </a>
        <?php if ($isLoggedIn): ?>
          <a href="portal/logout.php" style="display:flex; align-items:center; justify-content:center; gap:8px; padding:11px; border-radius:10px; background:#fef2f2; color:#dc2626; font-weight:600; font-size:0.875rem; text-decoration:none;">
            <i class="fas fa-sign-out-alt"></i> Sair
          </a>
        <?php else: ?>
          <button id="acesso-sistema-btn-mobile" class="btn-acesso" style="width:100%; justify-content:center; padding:12px;">
            <i class="fas fa-lock" style="font-size:0.8rem;"></i> Acesso ao Sistema
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div id="mobile-overlay" class="mobile-overlay"></div>

  <!-- ══════════ LOGIN MODAL ══════════ -->
  <div id="login-modal" class="login-overlay">
    <div class="login-card">

      <div class="login-header-bar">
        <button id="close-login" class="login-close-btn"><i class="fas fa-times"></i></button>
        <div class="login-badge"><i class="fas fa-shield-alt"></i> Área Restrita</div>
        <div class="login-title">Acesso ao Sistema</div>
        <div class="login-subtitle">Selecione seu perfil e entre com suas credenciais</div>
      </div>

      <div class="login-body">

        <!-- Type selector -->
        <div class="login-type-grid" id="tipo-grid">
          <button class="login-type-btn active" data-tipo="aluno">
            <i class="fas fa-user-graduate"></i>Aluno / Responsável
          </button>
          <button class="login-type-btn" data-tipo="professor">
            <i class="fas fa-chalkboard-teacher"></i>Professor
          </button>
          <button class="login-type-btn" data-tipo="secretaria">
            <i class="fas fa-building"></i>Secretaria
          </button>
          <button class="login-type-btn" data-tipo="admin">
            <i class="fas fa-shield-alt"></i>Administrador
          </button>
        </div>

        <input type="hidden" id="login-tipo-val" value="aluno">

        <!-- Error -->
        <div id="login-error-box" class="login-error-msg">
          <i class="fas fa-exclamation-circle"></i>
          <span id="login-error-text"></span>
        </div>

        <!-- Form -->
        <form id="login-form">
          <div class="login-field">
            <label id="login-user-label" for="login-user">CPF do Responsável</label>
            <div class="login-input-wrap">
              <i class="fas fa-user login-input-icon"></i>
              <input type="text" id="login-user" name="usuario" class="login-input" placeholder="Digite seu usuário...">
            </div>
          </div>
          <div class="login-field">
            <label for="login-pass">Senha</label>
            <div class="login-input-wrap">
              <i class="fas fa-lock login-input-icon"></i>
              <input type="password" id="login-pass" name="senha" class="login-input" placeholder="••••••••">
            </div>
          </div>

          <button type="submit" class="login-submit-btn" id="login-submit-btn">
            <span>Entrar na plataforma</span>
            <i class="fas fa-arrow-right" id="login-btn-icon"></i>
            <i class="fas fa-spinner fa-spin" id="login-spinner" style="display:none;"></i>
          </button>
        </form>

        <div class="login-footer-note">
          Problemas para acessar? <a href="contato_departamentos.php">Fale com a secretaria</a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // ── Mobile drawer ──
    const mobileToggle = document.getElementById('mobile-toggle-btn');
    const closeDrawer  = document.getElementById('close-drawer');
    const mobileDrawer = document.getElementById('mobile-drawer');
    const mobileOverlay = document.getElementById('mobile-overlay');

    function openDrawer(){
      mobileDrawer.classList.add('open');
      mobileOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function closeDrawerFn(){
      mobileDrawer.classList.remove('open');
      mobileOverlay.classList.remove('open');
      document.body.style.overflow = '';
    }
    mobileToggle?.addEventListener('click', openDrawer);
    closeDrawer?.addEventListener('click', closeDrawerFn);
    mobileOverlay?.addEventListener('click', closeDrawerFn);

    // ── User dropdown ──
    const userMenuBtn  = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-menu-dropdown');
    userMenuBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      const vis = userDropdown.style.display;
      userDropdown.style.display = vis === 'block' ? 'none' : 'block';
    });
    document.addEventListener('click', (e) => {
      if (userDropdown && !userMenuBtn?.contains(e.target)) {
        userDropdown.style.display = 'none';
      }
    });

    // ── Login modal ──
    const loginModal   = document.getElementById('login-modal');
    const closeBtnLgn  = document.getElementById('close-login');
    const loginForm    = document.getElementById('login-form');
    const tipoGrid     = document.getElementById('tipo-grid');
    const tipoVal      = document.getElementById('login-tipo-val');
    const errorBox     = document.getElementById('login-error-box');
    const errorText    = document.getElementById('login-error-text');
    const submitBtn    = document.getElementById('login-submit-btn');
    const btnIcon      = document.getElementById('login-btn-icon');
    const spinner      = document.getElementById('login-spinner');
    const userLabel    = document.getElementById('login-user-label');
    const userInput    = document.getElementById('login-user');

    const userLabels = {
      aluno:      'CPF do Responsável',
      professor:  'Matrícula Funcional',
      secretaria: 'Login de Usuário',
      admin:      'Login de Administrador'
    };
    const userPlaceholders = {
      aluno:      '000.000.000-00',
      professor:  'Ex: PROF2026',
      secretaria: 'Ex: sec.maria',
      admin:      'Ex: admin'
    };

    // Type buttons
    tipoGrid?.querySelectorAll('.login-type-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        tipoGrid.querySelectorAll('.login-type-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const tipo = btn.dataset.tipo;
        tipoVal.value = tipo;
        userLabel.textContent = userLabels[tipo];
        userInput.placeholder = userPlaceholders[tipo];
        errorBox.classList.remove('show');
      });
    });

    function showModal(){
      loginModal.classList.add('show');
      requestAnimationFrame(() => {
        requestAnimationFrame(() => loginModal.classList.add('visible'));
      });
      document.body.style.overflow = 'hidden';
    }
    function hideModal(){
      loginModal.classList.remove('visible');
      setTimeout(() => {
        loginModal.classList.remove('show');
        document.body.style.overflow = '';
        loginForm?.reset();
        errorBox.classList.remove('show');
        // reset tipo
        tipoGrid.querySelectorAll('.login-type-btn').forEach(b => b.classList.remove('active'));
        tipoGrid.querySelector('[data-tipo="aluno"]').classList.add('active');
        tipoVal.value = 'aluno';
        userLabel.textContent = userLabels['aluno'];
        userInput.placeholder = userPlaceholders['aluno'];
      }, 300);
    }

    loginModal?.addEventListener('click', (e) => {
      if (e.target === loginModal) hideModal();
    });
    closeBtnLgn?.addEventListener('click', hideModal);
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && loginModal.classList.contains('show')) hideModal();
    });

    document.querySelectorAll('#acesso-sistema-btn, #acesso-sistema-btn-mobile').forEach(btn => {
      btn?.addEventListener('click', (e) => {
        e.preventDefault();
        closeDrawerFn();
        showModal();
      });
    });

    // Form submit
    loginForm?.addEventListener('submit', async (e) => {
      e.preventDefault();
      errorBox.classList.remove('show');
      submitBtn.disabled = true;
      btnIcon.style.display = 'none';
      spinner.style.display = 'inline-block';

      try {
        const fd = new FormData();
        fd.append('tipo',    tipoVal.value);
        fd.append('usuario', document.getElementById('login-user').value);
        fd.append('senha',   document.getElementById('login-pass').value);

        const res  = await fetch('login.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          window.location.href = data.redirect;
        } else {
          errorText.textContent = data.message;
          errorBox.classList.add('show');
        }
      } catch(err) {
        errorText.textContent = 'Erro de conexão. Tente novamente.';
        errorBox.classList.add('show');
      } finally {
        submitBtn.disabled = false;
        btnIcon.style.display = 'inline-block';
        spinner.style.display = 'none';
      }
    });
  </script>


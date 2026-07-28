<?php
$pageTitle = 'Início';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<style>
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
  .feature-card, .bg-white.rounded-xl {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }
  
  .feature-card:hover, .bg-white.rounded-xl:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
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

<!-- Hero Section -->
<section class="hero-section py-24 md:py-32 relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
    <div class="text-center text-white">
      <div class="inline-block mb-6 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full">
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
        <a href="agendar_visita.php" class="btn-secondary text-lg bg-white/10 backdrop-blur-sm border-white/30 text-white hover:bg-white/20 hover:border-white/50">
          <i class="fas fa-calendar-check mr-2"></i>Agendar Visita
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Notícias e Comunicados Section -->
<section class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between mb-12">
      <div>
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2 font-poppins">
          Notícias e Comunicados
        </h2>
        <p class="text-gray-600">Fique por dentro das últimas novidades da escola</p>
      </div>
      <a href="noticias.php" class="hidden md:inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-semibold">
        Ver todas <i class="fas fa-arrow-right"></i>
      </a>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Notícia em destaque -->
      <div class="lg:col-span-2 bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 rounded-2xl p-8 text-white shadow-xl hover:shadow-2xl transition-all">
        <div class="flex items-center gap-2 mb-4">
          <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium">Destaque</span>
          <span class="text-sm opacity-80">Hoje</span>
        </div>
        <h3 class="text-2xl font-bold mb-3 font-poppins">Matrículas 2026 Abertas</h3>
        <p class="text-white/90 mb-6">As matrículas para o ano letivo de 2026 estão abertas. Garanta a vaga de seu filho em nossa instituição de excelência.</p>
        <a href="pre_matricula.php" class="inline-flex items-center gap-2 bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition-all hover:scale-105">
          <i class="fas fa-user-graduate"></i> Fazer Pré-Matrícula
        </a>
      </div>
      
      <!-- Lista de notícias -->
      <div class="space-y-4">
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 hover:shadow-lg transition-all cursor-pointer">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-md">
              <i class="fas fa-newspaper text-white"></i>
            </div>
            <div>
              <h4 class="font-semibold text-gray-800 mb-1 font-poppins">Calendário Escolar 2026</h4>
              <p class="text-sm text-gray-600">Confira as datas importantes do ano letivo</p>
            </div>
          </div>
        </div>
        
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 hover:shadow-lg transition-all cursor-pointer">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-md">
              <i class="fas fa-calendar-check text-white"></i>
            </div>
            <div>
              <h4 class="font-semibold text-gray-800 mb-1 font-poppins">Reunião de Pais</h4>
              <p class="text-sm text-gray-600">15/02/2026 - Auditório Principal</p>
            </div>
          </div>
        </div>
        
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 hover:shadow-lg transition-all cursor-pointer">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center flex-shrink-0 shadow-md">
              <i class="fas fa-trophy text-white"></i>
            </div>
            <div>
              <h4 class="font-semibold text-gray-800 mb-1 font-poppins">Olimpíadas Escolares</h4>
              <p class="text-sm text-gray-600">Inscrições abertas até 28/02</p>
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
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2 font-poppins">
          Próximos Eventos
        </h2>
        <p class="text-gray-600">Participe das atividades da nossa comunidade</p>
      </div>
      <a href="eventos/eventos.html" class="hidden md:inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-semibold">
        Ver todos <i class="fas fa-arrow-right"></i>
      </a>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all hover:-translate-y-2">
        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl flex items-center justify-center mb-4 shadow-md">
          <i class="fas fa-graduation-cap text-white text-xl"></i>
        </div>
        <h4 class="font-semibold text-gray-800 mb-2 font-poppins">Formatura Ensino Médio</h4>
        <p class="text-sm text-gray-600 mb-3">15/12/2026</p>
        <a href="inscrever_evento.php" class="text-sm text-blue-600 hover:text-blue-700 font-semibold">Inscrever-se →</a>
      </div>
      
      <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all hover:-translate-y-2">
        <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-green-700 rounded-xl flex items-center justify-center mb-4 shadow-md">
          <i class="fas fa-running text-white text-xl"></i>
        </div>
        <h4 class="font-semibold text-gray-800 mb-2 font-poppins">Gincana Escolar</h4>
        <p class="text-sm text-gray-600 mb-3">20/03/2026</p>
        <a href="inscrever_evento.php" class="text-sm text-blue-600 hover:text-blue-700 font-semibold">Inscrever-se →</a>
      </div>
      
      <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all hover:-translate-y-2">
        <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-700 rounded-xl flex items-center justify-center mb-4 shadow-md">
          <i class="fas fa-palette text-white text-xl"></i>
        </div>
        <h4 class="font-semibold text-gray-800 mb-2 font-poppins">Feira de Ciências</h4>
        <p class="text-sm text-gray-600 mb-3">10/04/2026</p>
        <a href="inscrever_evento.php" class="text-sm text-blue-600 hover:text-blue-700 font-semibold">Inscrever-se →</a>
      </div>
      
      <div class="bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all hover:-translate-y-2">
        <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-700 rounded-xl flex items-center justify-center mb-4 shadow-md">
          <i class="fas fa-music text-white text-xl"></i>
        </div>
        <h4 class="font-semibold text-gray-800 mb-2 font-poppins">Festival de Música</h4>
        <p class="text-sm text-gray-600 mb-3">25/05/2026</p>
        <a href="inscrever_evento.php" class="text-sm text-blue-600 hover:text-blue-700 font-semibold">Inscrever-se →</a>
      </div>
    </div>
  </div>
</section>

<!-- Acesso Rápido Section -->
<section class="py-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 font-poppins">
        Acesso Rápido
      </h2>
      <p class="text-gray-600 max-w-2xl mx-auto">
        Acesse rapidamente os serviços mais utilizados
      </p>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
      <a href="biblioteca.php" class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all hover:-translate-y-2 group">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 transition-transform">
          <i class="fas fa-book text-white text-2xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 mb-1 font-poppins">Biblioteca</h3>
        <p class="text-sm text-gray-600">Acervo digital</p>
      </a>
      
      <a href="portal_pais.php" class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all hover:-translate-y-2 group">
        <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-700 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 transition-transform">
          <i class="fas fa-users text-white text-2xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 mb-1 font-poppins">Portal dos Pais</h3>
        <p class="text-sm text-gray-600">Área responsáveis</p>
      </a>
      
      <a href="calendario_escolar.php" class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all hover:-translate-y-2 group">
        <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 transition-transform">
          <i class="fas fa-calendar text-white text-2xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 mb-1 font-poppins">Calendário</h3>
        <p class="text-sm text-gray-600">Datas importantes</p>
      </a>
      
      <a href="formularios.php" class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-6 text-center hover:shadow-xl transition-all hover:-translate-y-2 group">
        <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-700 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-lg group-hover:scale-110 transition-transform">
          <i class="fas fa-file-download text-white text-2xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 mb-1 font-poppins">Formulários</h3>
        <p class="text-sm text-gray-600">Downloads</p>
      </a>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

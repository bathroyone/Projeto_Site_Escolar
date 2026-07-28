<?php
$pageTitle = 'Acessibilidade';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-slate-500 via-slate-600 to-gray-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Acessibilidade</h1>
      <p class="text-xl text-white/90">Nossa escola é para todos</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Accessibility Features -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-12">
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Recursos de Acessibilidade</h2>
      
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-gray-50 rounded-xl p-6">
          <div class="w-12 h-12 bg-slate-100 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-wheelchair text-slate-600 text-xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Acesso Físico</h3>
          <p class="text-sm text-gray-600">Rampas, elevadores e banheiros adaptados para cadeirantes.</p>
        </div>
        
        <div class="bg-gray-50 rounded-xl p-6">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-universal-access text-blue-600 text-xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Digital</h3>
          <p class="text-sm text-gray-600">Site compatível com leitores de tela e tecnologias assistivas.</p>
        </div>
        
        <div class="bg-gray-50 rounded-xl p-6">
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-sign-language text-green-600 text-xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Libras</h3>
          <p class="text-sm text-gray-600">Profissionais de Libras disponíveis para atendimento.</p>
        </div>
        
        <div class="bg-gray-50 rounded-xl p-6">
          <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-font text-purple-600 text-xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Materiais Adaptados</h3>
          <p class="text-sm text-gray-600">Material didático adaptado para diferentes necessidades.</p>
        </div>
        
        <div class="bg-gray-50 rounded-xl p-6">
          <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-eye text-orange-600 text-xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Visual</h3>
          <p class="text-sm text-gray-600">Alto contraste e fontes ajustáveis para baixa visão.</p>
        </div>
        
        <div class="bg-gray-50 rounded-xl p-6">
          <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-hands-helping text-red-600 text-xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Apoio Individual</h3>
          <p class="text-sm text-gray-600">Acompanhamento especializado para cada necessidade.</p>
        </div>
      </div>
    </div>
    
    <!-- Contact Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-4 font-poppins">Precisa de Suporte Especializado?</h2>
      <p class="text-gray-600 mb-6">Entre em contato com nossa equipe de acessibilidade para discutir suas necessidades específicas.</p>
      <div class="flex flex-wrap gap-4">
        <a href="contato_departamentos.php" class="btn-primary">
          <i class="fas fa-envelope mr-2"></i>Contato
        </a>
        <a href="tel:+5511123456789" class="btn-secondary">
          <i class="fas fa-phone mr-2"></i>(11) 1234-5678
        </a>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

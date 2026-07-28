<?php
$pageTitle = 'Ensino Fundamental I';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Ensino Fundamental I</h1>
      <p class="text-xl text-white/90">Construindo bases sólidas para o aprendizado</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Hero Image Section -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-12">
      <div class="h-64 relative">
        <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?w=1200&h=400&fit=crop" alt="Sala de aula ensino fundamental" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/80 to-indigo-600/80 flex items-center justify-center">
          <div class="text-center text-white">
            <i class="fas fa-graduation-cap text-6xl mb-4"></i>
            <p class="text-2xl font-semibold font-poppins">1º ao 5º Ano</p>
          </div>
        </div>
      </div>
      <div class="p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 font-poppins">Nossa Proposta Pedagógica</h2>
        <p class="text-gray-600 mb-6">O Ensino Fundamental I é a fase de alfabetização e construção das bases do conhecimento. Nosso foco é desenvolver habilidades essenciais como leitura, escrita, matemática e ciências, sempre de forma contextualizada e significativa.</p>
        
        <div class="grid md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-book-reader text-blue-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Alfabetização</h3>
            <p class="text-sm text-gray-600">Leitura e escrita</p>
          </div>
          
          <div class="text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-calculator text-green-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Matemática</h3>
            <p class="text-sm text-gray-600">Lógica e raciocínio</p>
          </div>
          
          <div class="text-center">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-flask text-purple-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Ciências</h3>
            <p class="text-sm text-gray-600">Descoberta e experimentação</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Subjects Section -->
    <div class="mb-12">
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Disciplinas</h2>
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-red-400 to-pink-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-language text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Português</h3>
          <p class="text-sm text-gray-600">Língua e literatura</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-green-400 to-teal-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-calculator text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Matemática</h3>
          <p class="text-sm text-gray-600">Números e operações</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-globe text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Geografia</h3>
          <p class="text-sm text-gray-600">Espaço e lugar</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-amber-400 to-orange-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-landmark text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">História</h3>
          <p class="text-sm text-gray-600">Tempo e sociedade</p>
        </div>
      </div>
    </div>
    
    <!-- Methodology -->
    <div>
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Metodologia</h2>
      <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="text-center mb-4">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-users text-blue-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Trabalho em Grupo</h3>
          </div>
          <p class="text-sm text-gray-600 text-center">Atividades colaborativas que desenvolvem habilidades sociais</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="text-center mb-4">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-laptop text-green-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Tecnologia</h3>
          </div>
          <p class="text-sm text-gray-600 text-center">Uso de ferramentas digitais para enriquecer o aprendizado</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="text-center mb-4">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-hands-helping text-purple-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Projetos</h3>
          </div>
          <p class="text-sm text-gray-600 text-center">Desenvolvimento de projetos interdisciplinares</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

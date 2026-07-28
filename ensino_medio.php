<?php
$pageTitle = 'Ensino Médio';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-800 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Ensino Médio</h1>
      <p class="text-xl text-white/90">Preparando para o futuro e o sucesso acadêmico</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Hero Image Section -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-12">
      <div class="h-64 relative">
        <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1200&h=400&fit=crop" alt="Estudantes ensino médio" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-600/80 to-purple-600/80 flex items-center justify-center">
          <div class="text-center text-white">
            <i class="fas fa-university text-6xl mb-4"></i>
            <p class="text-2xl font-semibold font-poppins">1º ao 3º Ano</p>
          </div>
        </div>
      </div>
      <div class="p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 font-poppins">Nossa Proposta Pedagógica</h2>
        <p class="text-gray-600 mb-6">O Ensino Médio é a fase de preparação para o ensino superior e para o mercado de trabalho. Nosso foco é oferecer uma formação completa, com ênfase no desenvolvimento de competências essenciais para o século XXI, preparando os alunos para vestibulares e ENEM.</p>
        
        <div class="grid md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-graduation-cap text-indigo-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Vestibulares</h3>
            <p class="text-sm text-gray-600">Preparação completa</p>
          </div>
          
          <div class="text-center">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-laptop-code text-purple-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Tecnologia</h3>
            <p class="text-sm text-gray-600">Inovação digital</p>
          </div>
          
          <div class="text-center">
            <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-compass text-pink-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Orientação</h3>
            <p class="text-sm text-gray-600">Carreira e futuro</p>
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
            <i class="fas fa-book-open text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Português</h3>
          <p class="text-sm text-gray-600">Literatura e redação</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-green-400 to-teal-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-infinity text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Matemática</h3>
          <p class="text-sm text-gray-600">Cálculo avançado</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-atom text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Física</h3>
          <p class="text-sm text-gray-600">Mecânica e termodinâmica</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-amber-400 to-orange-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-dna text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Biologia</h3>
          <p class="text-sm text-gray-600">Genética e ecologia</p>
        </div>
      </div>
    </div>
    
    <!-- Preparation Programs -->
    <div>
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Programas de Preparação</h2>
      <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="text-center mb-4">
            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-file-alt text-indigo-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">ENEM</h3>
          </div>
          <p class="text-sm text-gray-600 text-center">Preparação intensiva para o Exame Nacional do Ensino Médio</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="text-center mb-4">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-university text-purple-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Vestibulares</h3>
          </div>
          <p class="text-sm text-gray-600 text-center">Simulados e preparação para principais universidades</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="text-center mb-4">
            <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-briefcase text-pink-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Carreira</h3>
          </div>
          <p class="text-sm text-gray-600 text-center">Orientação vocacional e profissional</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<?php
$pageTitle = 'Educação Infantil';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-pink-500 via-pink-600 to-rose-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Educação Infantil</h1>
      <p class="text-xl text-white/90">Primeiros passos com amor e cuidado</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Hero Image Section -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden mb-12">
      <div class="h-64 relative">
        <img src="https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?w=1200&h=400&fit=crop" alt="Crianças brincando" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
          <div class="text-center text-white drop-shadow-md">
            <i class="fas fa-child text-6xl mb-4"></i>
            <p class="text-2xl font-semibold font-poppins">Berçário ao Pré</p>
          </div>
        </div>
      </div>
      <div class="p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-4 font-poppins">Nossa Proposta Pedagógica</h2>
        <p class="text-gray-600 mb-6">Nossa educação infantil foca no desenvolvimento integral da criança, respeitando suas individualidades e estimulando suas capacidades cognitivas, sociais e emocionais através de atividades lúdicas e interativas.</p>
        
        <div class="grid md:grid-cols-3 gap-6">
          <div class="text-center">
            <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-heart text-pink-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Afetividade</h3>
            <p class="text-sm text-gray-600">Ambiente acolhedor e seguro</p>
          </div>
          
          <div class="text-center">
            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-puzzle-piece text-purple-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Ludicidade</h3>
            <p class="text-sm text-gray-600">Aprender brincando</p>
          </div>
          
          <div class="text-center">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-users text-blue-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Socialização</h3>
            <p class="text-sm text-gray-600">Interação e cooperação</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Activities Section -->
    <div class="mb-12">
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Atividades Diárias</h2>
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-sun text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Brincadeiras</h3>
          <p class="text-sm text-gray-600">Jogos e recreação</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-green-400 to-teal-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-palette text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Artes</h3>
          <p class="text-sm text-gray-600">Pintura e desenho</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-music text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Música</h3>
          <p class="text-sm text-gray-600">Cantos e instrumentos</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="h-32 bg-gradient-to-br from-red-400 to-pink-500 rounded-lg flex items-center justify-center mb-4">
            <i class="fas fa-book text-white text-3xl"></i>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Histórias</h3>
          <p class="text-sm text-gray-600">Contação de histórias</p>
        </div>
      </div>
    </div>
    
    <!-- Age Groups -->
    <div>
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Grupos por Idade</h2>
      <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="text-center mb-4">
            <span class="text-4xl font-bold text-pink-600 font-poppins">0-2</span>
            <p class="text-gray-600">anos</p>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins text-center">Berçário</h3>
          <p class="text-sm text-gray-600 text-center">Cuidados essenciais e estímulos sensoriais</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="text-center mb-4">
            <span class="text-4xl font-bold text-purple-600 font-poppins">3-4</span>
            <p class="text-gray-600">anos</p>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins text-center">Maternal I</h3>
          <p class="text-sm text-gray-600 text-center">Desenvolvimento motor e linguagem</p>
        </div>
        
        <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
          <div class="text-center mb-4">
            <span class="text-4xl font-bold text-blue-600 font-poppins">5-6</span>
            <p class="text-gray-600">anos</p>
          </div>
          <h3 class="font-semibold text-gray-800 mb-2 font-poppins text-center">Pré</h3>
          <p class="text-sm text-gray-600 text-center">Preparação para o ensino fundamental</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

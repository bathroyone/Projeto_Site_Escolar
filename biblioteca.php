<?php
$pageTitle = 'Biblioteca';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Biblioteca Virtual</h1>
      <p class="text-xl text-white/90">Explore nosso acervo digital de livros, revistas e recursos educacionais</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Search Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-12">
      <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
          <input type="text" placeholder="Buscar livros, autores ou temas..." class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <button class="btn-primary px-8 py-3">
          <i class="fas fa-search mr-2"></i>Buscar
        </button>
      </div>
    </div>

    <!-- Categories -->
    <div class="mb-12">
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Categorias</h2>
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <a href="#" class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 text-center hover:shadow-lg transition-all group">
          <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <i class="fas fa-book text-white"></i>
          </div>
          <span class="text-sm font-medium text-gray-700">Literatura</span>
        </a>
        <a href="#" class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-4 text-center hover:shadow-lg transition-all group">
          <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <i class="fas fa-flask text-white"></i>
          </div>
          <span class="text-sm font-medium text-gray-700">Ciências</span>
        </a>
        <a href="#" class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-4 text-center hover:shadow-lg transition-all group">
          <div class="w-12 h-12 bg-purple-600 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <i class="fas fa-calculator text-white"></i>
          </div>
          <span class="text-sm font-medium text-gray-700">Matemática</span>
        </a>
        <a href="#" class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl p-4 text-center hover:shadow-lg transition-all group">
          <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <i class="fas fa-globe text-white"></i>
          </div>
          <span class="text-sm font-medium text-gray-700">História</span>
        </a>
        <a href="#" class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl p-4 text-center hover:shadow-lg transition-all group">
          <div class="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <i class="fas fa-palette text-white"></i>
          </div>
          <span class="text-sm font-medium text-gray-700">Artes</span>
        </a>
        <a href="#" class="bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl p-4 text-center hover:shadow-lg transition-all group">
          <div class="w-12 h-12 bg-teal-600 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
            <i class="fas fa-laptop text-white"></i>
          </div>
          <span class="text-sm font-medium text-gray-700">Tecnologia</span>
        </a>
      </div>
    </div>

    <!-- Featured Books -->
    <div>
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Livros em Destaque</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all overflow-hidden">
          <div class="h-48 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
            <i class="fas fa-book-open text-white text-4xl"></i>
          </div>
          <div class="p-6">
            <h3 class="font-bold text-gray-800 mb-2 font-poppins">O Pequeno Príncipe</h3>
            <p class="text-sm text-gray-600 mb-3">Antoine de Saint-Exupéry</p>
            <div class="flex items-center justify-between">
              <span class="text-xs text-blue-600 font-medium">Literatura</span>
              <button class="text-sm text-blue-600 hover:text-blue-700 font-medium">Ler →</button>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all overflow-hidden">
          <div class="h-48 bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center">
            <i class="fas fa-atom text-white text-4xl"></i>
          </div>
          <div class="p-6">
            <h3 class="font-bold text-gray-800 mb-2 font-poppins">Uma Breve História do Tempo</h3>
            <p class="text-sm text-gray-600 mb-3">Stephen Hawking</p>
            <div class="flex items-center justify-between">
              <span class="text-xs text-green-600 font-medium">Ciências</span>
              <button class="text-sm text-blue-600 hover:text-blue-700 font-medium">Ler →</button>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all overflow-hidden">
          <div class="h-48 bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
            <i class="fas fa-infinity text-white text-4xl"></i>
          </div>
          <div class="p-6">
            <h3 class="font-bold text-gray-800 mb-2 font-poppins">O Homem que Calculava</h3>
            <p class="text-sm text-gray-600 mb-3">Malba Tahan</p>
            <div class="flex items-center justify-between">
              <span class="text-xs text-purple-600 font-medium">Matemática</span>
              <button class="text-sm text-blue-600 hover:text-blue-700 font-medium">Ler →</button>
            </div>
          </div>
        </div>
        
        <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all overflow-hidden">
          <div class="h-48 bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center">
            <i class="fas fa-landmark text-white text-4xl"></i>
          </div>
          <div class="p-6">
            <h3 class="font-bold text-gray-800 mb-2 font-poppins">As Crônicas de Nárnia</h3>
            <p class="text-sm text-gray-600 mb-3">C.S. Lewis</p>
            <div class="flex items-center justify-between">
              <span class="text-xs text-orange-600 font-medium">Fantasia</span>
              <button class="text-sm text-blue-600 hover:text-blue-700 font-medium">Ler →</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<?php
$pageTitle = 'História';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-amber-600 via-amber-700 to-orange-800 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">História da Instituição</h1>
      <p class="text-xl text-white/90">Conheça nossa trajetória e tradição educacional</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Timeline Section -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-12">
      <h2 class="text-2xl font-bold text-gray-900 mb-8 font-poppins">Nossa Trajetória</h2>
      
      <div class="space-y-8">
        <div class="flex gap-6">
          <div class="flex-shrink-0 w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center">
            <span class="text-amber-600 font-bold">1995</span>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Fundação</h3>
            <p class="text-gray-600">A instituição foi fundada com o objetivo de oferecer educação de qualidade para a comunidade local, começando com apenas 3 turmas de educação infantil.</p>
          </div>
        </div>
        
        <div class="flex gap-6">
          <div class="flex-shrink-0 w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
            <span class="text-blue-600 font-bold">2005</span>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Expansão</h3>
            <p class="text-gray-600">Ampliação das instalações e inclusão do ensino fundamental, atendendo a crescente demanda por vagas na região.</p>
          </div>
        </div>
        
        <div class="flex gap-6">
          <div class="flex-shrink-0 w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
            <span class="text-green-600 font-bold">2015</span>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Ensino Médio</h3>
            <p class="text-gray-600">Implementação do ensino médio com foco em preparação para vestibulares e desenvolvimento integral dos alunos.</p>
          </div>
        </div>
        
        <div class="flex gap-6">
          <div class="flex-shrink-0 w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center">
            <span class="text-purple-600 font-bold">2024</span>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Modernização</h3>
            <p class="text-gray-600">Investimento em tecnologia, laboratórios modernos e metodologias de ensino inovadoras para preparar os alunos para o futuro.</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Values Section -->
    <div class="grid md:grid-cols-3 gap-6">
      <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
        <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-heart text-amber-600 text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Excelência</h3>
        <p class="text-sm text-gray-600">Compromisso com a qualidade educacional e desenvolvimento integral dos alunos.</p>
      </div>
      
      <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-users text-blue-600 text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Comunidade</h3>
        <p class="text-sm text-gray-600">Parceria com famílias e comunidade para um ambiente educacional colaborativo.</p>
      </div>
      
      <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-lightbulb text-green-600 text-xl"></i>
        </div>
        <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Inovação</h3>
        <p class="text-sm text-gray-600">Metodologias modernas e tecnologia integrada ao processo de ensino-aprendizagem.</p>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

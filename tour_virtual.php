<?php
$pageTitle = 'Tour Virtual 360°';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Tour Virtual 360°',
  'sub'    => 'Explore nossas instalações de forma imersiva sem sair de casa.',
  'icon'   => 'fas fa-vr-cardboard',
  'accent' => '#7c3aed',
  'badge'  => 'Instituição',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Tour Preview -->
    <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] overflow-hidden mb-12">
      <div class="h-96 bg-gradient-to-br from-cyan-400 to-blue-500 flex items-center justify-center">
        <div class="text-center text-white">
          <i class="fas fa-vr-cardboard text-8xl mb-4"></i>
          <p class="text-2xl font-semibold font-poppins">Tour Virtual Interativo</p>
          <p class="text-white/80 mt-2">Em breve disponível</p>
        </div>
      </div>
      <div class="p-8">
        <h2 class="text-2xl font-bold text-white mb-4 font-poppins">Explore Nossas Instalações</h2>
        <p class="text-white/60 mb-6">Conheça nossa escola através de um tour virtual interativo. Navegue pelas salas de aula, laboratórios, biblioteca, quadra esportiva e muito mais, tudo no conforto da sua casa.</p>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="text-center">
            <div class="w-16 h-16 bg-cyan-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-chalkboard-teacher text-cyan-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-white/90 mb-2 font-poppins">Salas de Aula</h3>
            <p class="text-sm text-white/60">Ambientes modernos</p>
          </div>
          
          <div class="text-center">
            <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-flask text-blue-400 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-white/90 mb-2 font-poppins">Laboratórios</h3>
            <p class="text-sm text-white/60">Ciências e tecnologia</p>
          </div>
          
          <div class="text-center">
            <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-book text-green-400 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-white/90 mb-2 font-poppins">Biblioteca</h3>
            <p class="text-sm text-white/60">Acervo completo</p>
          </div>
          
          <div class="text-center">
            <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-3">
              <i class="fas fa-futbol text-orange-600 text-2xl"></i>
            </div>
            <h3 class="font-semibold text-white/90 mb-2 font-poppins">Quadra</h3>
            <p class="text-sm text-white/60">Esportes e lazer</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Features -->
    <div class="grid md:grid-cols-3 gap-6">
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="h-32 bg-gradient-to-br from-cyan-400 to-blue-500 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-eye text-white text-3xl"></i>
        </div>
        <h3 class="font-semibold text-white/90 mb-2 font-poppins">Visão Panorâmica</h3>
        <p class="text-sm text-white/60">Immersão total em 360 graus</p>
      </div>
      
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="h-32 bg-gradient-to-br from-green-400 to-teal-500 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-mobile-alt text-white text-3xl"></i>
        </div>
        <h3 class="font-semibold text-white/90 mb-2 font-poppins">Acesso Mobile</h3>
        <p class="text-sm text-white/60">Funciona em qualquer dispositivo</p>
      </div>
      
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="h-32 bg-gradient-to-br from-purple-400 to-pink-500 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-info-circle text-white text-3xl"></i>
        </div>
        <h3 class="font-semibold text-white/90 mb-2 font-poppins">Informações Detalhadas</h3>
        <p class="text-sm text-white/60">Descrições de cada ambiente</p>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



<?php
$pageTitle = 'História';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'História da Instituição',
  'sub'    => 'Conheça nossa trajetória e tradição educacional de mais de 30 anos.',
  'icon'   => 'fas fa-landmark',
  'accent' => '#f59e0b',
  'badge'  => 'Instituição',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Timeline Section -->
    <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-8 mb-12">
      <h2 class="text-2xl font-bold text-white mb-8 font-poppins">Nossa Trajetória</h2>
      
      <div class="space-y-8">
        <div class="flex gap-6">
          <div class="flex-shrink-0 w-16 h-16 bg-amber-500/20 rounded-full flex items-center justify-center">
            <span class="text-amber-400 font-bold">1995</span>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-white/90 mb-2 font-poppins">Fundação</h3>
            <p class="text-white/60">A instituição foi fundada com o objetivo de oferecer educação de qualidade para a comunidade local, começando com apenas 3 turmas de educação infantil.</p>
          </div>
        </div>
        
        <div class="flex gap-6">
          <div class="flex-shrink-0 w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center">
            <span class="text-blue-400 font-bold">2005</span>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-white/90 mb-2 font-poppins">Expansão</h3>
            <p class="text-white/60">Ampliação das instalações e inclusão do ensino fundamental, atendendo a crescente demanda por vagas na região.</p>
          </div>
        </div>
        
        <div class="flex gap-6">
          <div class="flex-shrink-0 w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center">
            <span class="text-green-400 font-bold">2015</span>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-white/90 mb-2 font-poppins">Ensino Médio</h3>
            <p class="text-white/60">Implementação do ensino médio com foco em preparação para vestibulares e desenvolvimento integral dos alunos.</p>
          </div>
        </div>
        
        <div class="flex gap-6">
          <div class="flex-shrink-0 w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center">
            <span class="text-purple-400 font-bold">2024</span>
          </div>
          <div class="flex-1">
            <h3 class="font-semibold text-white/90 mb-2 font-poppins">Modernização</h3>
            <p class="text-white/60">Investimento em tecnologia, laboratórios modernos e metodologias de ensino inovadoras para preparar os alunos para o futuro.</p>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Values Section -->
    <div class="grid md:grid-cols-3 gap-6">
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="w-12 h-12 bg-amber-500/20 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-heart text-amber-400 text-xl"></i>
        </div>
        <h3 class="font-semibold text-white/90 mb-2 font-poppins">Excelência</h3>
        <p class="text-sm text-white/60">Compromisso com a qualidade educacional e desenvolvimento integral dos alunos.</p>
      </div>
      
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-users text-blue-400 text-xl"></i>
        </div>
        <h3 class="font-semibold text-white/90 mb-2 font-poppins">Comunidade</h3>
        <p class="text-sm text-white/60">Parceria com famílias e comunidade para um ambiente educacional colaborativo.</p>
      </div>
      
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center mb-4">
          <i class="fas fa-lightbulb text-green-400 text-xl"></i>
        </div>
        <h3 class="font-semibold text-white/90 mb-2 font-poppins">Inovação</h3>
        <p class="text-sm text-white/60">Metodologias modernas e tecnologia integrada ao processo de ensino-aprendizagem.</p>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



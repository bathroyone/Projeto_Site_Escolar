<?php
$pageTitle = 'Transparência';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Transparência',
  'sub'    => 'Informações administrativas, financeiras e documentos públicos da instituição.',
  'icon'   => 'fas fa-file-alt',
  'accent' => '#16a34a',
  'badge'  => 'Instituição',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Transparency Info -->
    <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-8 mb-12">
      <h2 class="text-2xl font-bold text-white mb-4 font-poppins">Nossa Política de Transparência</h2>
      <p class="text-white/60 mb-6">A instituição mantém um compromisso permanente com a transparência em todas as suas ações, garantindo o acesso à informação e a prestação de contas à comunidade escolar.</p>
      
      <div class="grid md:grid-cols-3 gap-6">
        <div class="text-center">
          <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-file-invoice-dollar text-white/60 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-2 font-poppins">Financeiro</h3>
          <p class="text-sm text-white/60">Relatórios mensais</p>
        </div>
        
        <div class="text-center">
          <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-chart-bar text-blue-400 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-2 font-poppins">Estatísticas</h3>
          <p class="text-sm text-white/60">Dados educacionais</p>
        </div>
        
        <div class="text-center">
          <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-balance-scale text-green-400 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-2 font-poppins">Legal</h3>
          <p class="text-sm text-white/60">Conformidade com leis</p>
        </div>
      </div>
    </div>
    
    <!-- Documents Section -->
    <div class="mb-12">
      <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Documentos Disponíveis</h2>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
              <i class="fas fa-file-pdf text-red-400 text-xl"></i>
            </div>
            <div>
              <h3 class="font-semibold text-white/90 mb-2 font-poppins">Relatório Financeiro 2024</h3>
              <p class="text-sm text-white/60 mb-3">Balanço anual e demonstrações</p>
              <a href="#" class="text-sm text-blue-400 hover:text-blue-700 font-medium">Baixar PDF</a>
            </div>
          </div>
        </div>
        
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
              <i class="fas fa-file-pdf text-red-400 text-xl"></i>
            </div>
            <div>
              <h3 class="font-semibold text-white/90 mb-2 font-poppins">Plano de Gestão</h3>
              <p class="text-sm text-white/60 mb-3">Metas e objetivos institucionais</p>
              <a href="#" class="text-sm text-blue-400 hover:text-blue-700 font-medium">Baixar PDF</a>
            </div>
          </div>
        </div>
        
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-red-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
              <i class="fas fa-file-pdf text-red-400 text-xl"></i>
            </div>
            <div>
              <h3 class="font-semibold text-white/90 mb-2 font-poppins">Estatísticas Educacionais</h3>
              <p class="text-sm text-white/60 mb-3">Dados de matrícula e desempenho</p>
              <a href="#" class="text-sm text-blue-400 hover:text-blue-700 font-medium">Baixar PDF</a>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Contact Section -->
    <div>
      <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Canal de Denúncias e Sugestões</h2>
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)]">
        <p class="text-white/60 mb-4">Para denúncias, sugestões ou dúvidas sobre a transparência institucional, entre em contato conosco:</p>
        <div class="flex flex-wrap gap-4">
          <a href="contato_departamentos.php" class="btn-primary">
            <i class="fas fa-envelope mr-2"></i>Contatar
          </a>
          <a href="#" class="btn-secondary">
            <i class="fas fa-phone mr-2"></i>Telefone
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



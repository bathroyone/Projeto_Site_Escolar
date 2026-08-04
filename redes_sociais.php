<?php
$pageTitle = 'Redes Sociais';
require_once 'portal/config.php';
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Redes Sociais',
  'sub'    => 'Siga-nos e compartilhe os momentos especiais da vida escolar.',
  'icon'   => 'fab fa-instagram',
  'accent' => '#ec4899',
  'badge'  => 'Mídia',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Social Media Links -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-blue-600 rounded-lg flex items-center justify-center">
            <i class="fab fa-facebook-f text-white text-2xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-white/90 mb-1 font-poppins">Facebook</h3>
            <p class="text-sm text-white/60">@escola.oficial</p>
          </div>
        </div>
        <a href="#" class="mt-4 block text-center btn-secondary text-sm">
          <i class="fas fa-external-link-alt mr-2"></i>Visitar
        </a>
      </div>
      
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-gradient-to-br from-purple-500 via-pink-500 to-orange-500 rounded-lg flex items-center justify-center">
            <i class="fab fa-instagram text-white text-2xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-white/90 mb-1 font-poppins">Instagram</h3>
            <p class="text-sm text-white/60">@escola.oficial</p>
          </div>
        </div>
        <a href="#" class="mt-4 block text-center btn-secondary text-sm">
          <i class="fas fa-external-link-alt mr-2"></i>Visitar
        </a>
      </div>
      
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-blue-400 rounded-lg flex items-center justify-center">
            <i class="fab fa-twitter text-white text-2xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-white/90 mb-1 font-poppins">Twitter</h3>
            <p class="text-sm text-white/60">@escola_oficial</p>
          </div>
        </div>
        <a href="#" class="mt-4 block text-center btn-secondary text-sm">
          <i class="fas fa-external-link-alt mr-2"></i>Visitar
        </a>
      </div>
      
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-red-600 rounded-lg flex items-center justify-center">
            <i class="fab fa-youtube text-white text-2xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-white/90 mb-1 font-poppins">YouTube</h3>
            <p class="text-sm text-white/60">Escola Oficial</p>
          </div>
        </div>
        <a href="#" class="mt-4 block text-center btn-secondary text-sm">
          <i class="fas fa-external-link-alt mr-2"></i>Visitar
        </a>
      </div>
      
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-blue-700 rounded-lg flex items-center justify-center">
            <i class="fab fa-linkedin-in text-white text-2xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-white/90 mb-1 font-poppins">LinkedIn</h3>
            <p class="text-sm text-white/60">Escola Institucional</p>
          </div>
        </div>
        <a href="#" class="mt-4 block text-center btn-secondary text-sm">
          <i class="fas fa-external-link-alt mr-2"></i>Visitar
        </a>
      </div>
      
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-green-500 rounded-lg flex items-center justify-center">
            <i class="fab fa-whatsapp text-white text-2xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-white/90 mb-1 font-poppins">WhatsApp</h3>
            <p class="text-sm text-white/60">(11) 12345-6789</p>
          </div>
        </div>
        <a href="#" class="mt-4 block text-center btn-secondary text-sm">
          <i class="fas fa-external-link-alt mr-2"></i>Conversar
        </a>
      </div>
    </div>
    
    <!-- Social Media Info -->
    <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-8">
      <h2 class="text-2xl font-bold text-white mb-4 font-poppins">Por que nos seguir?</h2>
      <div class="grid md:grid-cols-3 gap-6">
        <div class="text-center">
          <div class="w-16 h-16 bg-pink-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-bell text-pink-600 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-2 font-poppins">Notícias</h3>
          <p class="text-sm text-white/60">Acompanhe as últimas notícias da escola</p>
        </div>
        
        <div class="text-center">
          <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-calendar text-purple-400 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-2 font-poppins">Eventos</h3>
          <p class="text-sm text-white/60">Fique por dentro dos eventos e atividades</p>
        </div>
        
        <div class="text-center">
          <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-users text-blue-400 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-2 font-poppins">Comunidade</h3>
          <p class="text-sm text-white/60">Conecte-se com nossa comunidade escolar</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



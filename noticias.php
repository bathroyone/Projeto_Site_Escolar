<?php
$pageTitle = 'Notícias';
require_once 'portal/config.php';

// Criar tabela de notícias se não existir
$conn = getDBConnection();
$conn->query("CREATE TABLE IF NOT EXISTS noticias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    imagem VARCHAR(255),
    categoria ENUM('geral', 'eventos', 'academico', 'esportes', 'cultura') DEFAULT 'geral',
    destaque TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter notícias
$noticias = [];
try {
    $stmt = $conn->query("SELECT * FROM noticias WHERE ativo = 1 ORDER BY data_publicacao DESC LIMIT 20");
    $noticias = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter notícias: " . $e->getMessage());
}

// Obter notícias em destaque
$noticias_destaque = [];
try {
    $stmt = $conn->query("SELECT * FROM noticias WHERE destaque = 1 AND ativo = 1 ORDER BY data_publicacao DESC LIMIT 3");
    $noticias_destaque = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter notícias em destaque: " . $e->getMessage());
}
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Notícias e Comunicados',
  'sub'    => 'Fique por dentro de tudo o que acontece na nossa comunidade escolar.',
  'icon'   => 'fas fa-newspaper',
  'accent' => '#0284c7',
  'badge'  => 'Novidades',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Notícias em Destaque -->
    <?php if (!empty($noticias_destaque)): ?>
      <div class="mb-12">
        <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Destaques</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($noticias_destaque as $noticia): ?>
            <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] hover:shadow-2xl transition-all overflow-hidden">
              <?php if ($noticia['imagem']): ?>
                <img src="<?php echo htmlspecialchars($noticia['imagem']); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>" class="w-full h-48 object-cover">
              <?php else: ?>
                <div class="w-full h-48 bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center">
                  <i class="fas fa-newspaper text-white text-4xl"></i>
                </div>
              <?php endif; ?>
              <div class="p-6">
                <div class="flex items-center gap-2 mb-3">
                  <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-full text-xs font-semibold">Destaque</span>
                  <span class="text-white/50 text-xs"><?php echo date('d/m/Y', strtotime($noticia['data_publicacao'])); ?></span>
                </div>
                <h3 class="font-bold text-white/90 text-lg mb-2 font-poppins"><?php echo htmlspecialchars($noticia['titulo']); ?></h3>
                <p class="text-white/60 text-sm line-clamp-3"><?php echo htmlspecialchars(substr(strip_tags($noticia['conteudo']), 0, 150)) . '...'; ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Todas as Notícias -->
    <div>
      <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Todas as Notícias</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($noticias as $noticia): ?>
          <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] hover:shadow-2xl transition-all overflow-hidden">
            <?php if ($noticia['imagem']): ?>
              <img src="<?php echo htmlspecialchars($noticia['imagem']); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>" class="w-full h-48 object-cover">
            <?php else: ?>
              <div class="w-full h-48 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                <i class="fas fa-newspaper text-gray-400 text-4xl"></i>
              </div>
            <?php endif; ?>
            <div class="p-6">
              <div class="flex items-center gap-2 mb-3">
                <span class="px-3 py-1 rounded-full text-xs font-semibold 
                  <?php 
                  $cor_categoria = match($noticia['categoria']) {
                    'geral' => 'bg-gray-100 text-white/60',
                    'eventos' => 'bg-blue-500/20 text-blue-400',
                    'academico' => 'bg-green-500/20 text-green-400',
                    'esportes' => 'bg-orange-100 text-orange-600',
                    'cultura' => 'bg-purple-500/20 text-purple-400',
                    default => 'bg-gray-100 text-white/60'
                  };
                  echo $cor_categoria;
                  ?>">
                  <?php echo ucfirst($noticia['categoria']); ?>
                </span>
                <span class="text-white/50 text-xs"><?php echo date('d/m/Y', strtotime($noticia['data_publicacao'])); ?></span>
              </div>
              <h3 class="font-bold text-white/90 text-lg mb-2 font-poppins"><?php echo htmlspecialchars($noticia['titulo']); ?></h3>
              <p class="text-white/60 text-sm line-clamp-3"><?php echo htmlspecialchars(substr(strip_tags($noticia['conteudo']), 0, 150)) . '...'; ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



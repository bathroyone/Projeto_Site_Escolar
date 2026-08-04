<?php
$pageTitle = 'Galeria de Vídeos';
require_once 'portal/config.php';

// Criar tabela de vídeos se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    url VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255),
    categoria ENUM('evento', 'aula', 'institucional', 'outro') DEFAULT 'outro',
    data_publicacao DATE,
    visualizacoes INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter vídeos
$videos = [];
try {
    $stmt = $pdo->query("SELECT * FROM videos WHERE ativo = 1 ORDER BY created_at DESC LIMIT 20");
    $videos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter vídeos: " . $e->getMessage());
}

// Agrupar por categoria
$videos_por_categoria = [];
foreach ($videos as $video) {
    $videos_por_categoria[$video['categoria']][] = $video;
}

$nomes_categorias = [
    'evento' => 'Eventos',
    'aula' => 'Aulas',
    'institucional' => 'Institucional',
    'outro' => 'Outros'
];

$cores_categorias = [
    'evento' => 'bg-red-500/20 text-red-400',
    'aula' => 'bg-blue-500/20 text-blue-400',
    'institucional' => 'bg-green-500/20 text-green-400',
    'outro' => 'bg-gray-100 text-white/60'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Galeria de Vídeos',
  'sub'    => 'Reviva os melhores momentos da vida escolar através dos nossos vídeos.',
  'icon'   => 'fas fa-video',
  'accent' => '#ec4899',
  'badge'  => 'Recursos',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <?php if (!empty($videos_por_categoria)): ?>
      <?php foreach ($nomes_categorias as $categoria_key => $categoria_nome): ?>
        <?php if (!empty($videos_por_categoria[$categoria_key])): ?>
          <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6 font-poppins flex items-center gap-2">
              <span class="w-3 h-3 rounded-full <?php echo $cores_categorias[$categoria_key]; ?>"></span>
              <?php echo $categoria_nome; ?>
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              <?php foreach ($videos_por_categoria[$categoria_key] as $video): ?>
                <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl overflow-hidden shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
                  <div class="h-48 bg-gradient-to-br <?php echo $cores_categorias[$categoria_key]; ?> flex items-center justify-center">
                    <i class="fas fa-play-circle text-white text-5xl"></i>
                  </div>
                  <div class="p-6">
                    <h3 class="font-semibold text-white/90 mb-2 font-poppins"><?php echo htmlspecialchars($video['titulo']); ?></h3>
                    <p class="text-sm text-white/60 mb-3"><?php echo htmlspecialchars(substr($video['descricao'], 0, 80)); ?>...</p>
                    <div class="flex items-center justify-between text-xs text-white/50">
                      <span><i class="fas fa-eye mr-1"></i><?php echo $video['visualizacoes']; ?> visualizações</span>
                      <?php if (!empty($video['data_publicacao'])): ?>
                        <span><?php echo date('d/m/Y', strtotime($video['data_publicacao'])); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-8 text-center">
        <i class="fas fa-video text-4xl text-gray-300 mb-4"></i>
        <p class="text-white/60">Nenhum vídeo cadastrado no momento.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



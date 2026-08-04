<?php
$pageTitle = 'Recursos Educacionais';
require_once 'portal/config.php';

// Criar tabela de recursos educacionais se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS recursos_educacionais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    link VARCHAR(255),
    arquivo VARCHAR(255),
    categoria ENUM('material', 'video', 'livro', 'aplicativo', 'outro') DEFAULT 'outro',
    serie VARCHAR(100),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter recursos educacionais
$recursos = [];
try {
    $stmt = $pdo->query("SELECT * FROM recursos_educacionais WHERE ativo = 1 ORDER BY categoria, titulo");
    $recursos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter recursos: " . $e->getMessage());
}

// Agrupar por categoria
$recursos_por_categoria = [];
foreach ($recursos as $recurso) {
    $recursos_por_categoria[$recurso['categoria']][] = $recurso;
}

$nomes_categorias = [
    'material' => 'Materiais Didáticos',
    'video' => 'Vídeos',
    'livro' => 'Livros',
    'aplicativo' => 'Aplicativos',
    'outro' => 'Outros'
];

$cores_categorias = [
    'material' => 'bg-blue-500/20 text-blue-400',
    'video' => 'bg-red-500/20 text-red-400',
    'livro' => 'bg-green-500/20 text-green-400',
    'aplicativo' => 'bg-purple-500/20 text-purple-400',
    'outro' => 'bg-gray-100 text-white/60'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Recursos Educacionais',
  'sub'    => 'Materiais didáticos, vídeos e ferramentas de apoio ao aprendizado.',
  'icon'   => 'fas fa-laptop-code',
  'accent' => '#0284c7',
  'badge'  => 'Recursos',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <?php if (!empty($recursos_por_categoria)): ?>
      <?php foreach ($nomes_categorias as $categoria_key => $categoria_nome): ?>
        <?php if (!empty($recursos_por_categoria[$categoria_key])): ?>
          <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6 font-poppins flex items-center gap-2">
              <span class="w-3 h-3 rounded-full <?php echo $cores_categorias[$categoria_key]; ?>"></span>
              <?php echo $categoria_nome; ?>
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              <?php foreach ($recursos_por_categoria[$categoria_key] as $recurso): ?>
                <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
                  <div class="flex items-start gap-4">
                    <div class="w-12 h-12 <?php echo $cores_categorias[$categoria_key]; ?> rounded-lg flex items-center justify-center flex-shrink-0">
                      <i class="fas fa-link text-xl"></i>
                    </div>
                    <div class="flex-1">
                      <h3 class="font-semibold text-white/90 mb-2 font-poppins"><?php echo htmlspecialchars($recurso['titulo']); ?></h3>
                      <p class="text-sm text-white/60 mb-3"><?php echo htmlspecialchars($recurso['descricao']); ?></p>
                      <?php if (!empty($recurso['link'])): ?>
                        <a href="<?php echo htmlspecialchars($recurso['link']); ?>" target="_blank" class="text-sm text-blue-400 hover:text-blue-700 font-medium">
                          Acessar <i class="fas fa-external-link-alt ml-1"></i>
                        </a>
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
        <i class="fas fa-book-open text-4xl text-gray-300 mb-4"></i>
        <p class="text-white/60">Nenhum recurso educacional cadastrado no momento.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



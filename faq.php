<?php
$pageTitle = 'FAQ';
require_once 'portal/config.php';

// Criar tabela de FAQ se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pergunta VARCHAR(255) NOT NULL,
    resposta TEXT NOT NULL,
    categoria ENUM('geral', 'matricula', 'financeiro', 'pedagogico', 'tecnico') DEFAULT 'geral',
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter FAQs
$faqs = [];
try {
    $stmt = $pdo->query("SELECT * FROM faq WHERE ativo = 1 ORDER BY ordem ASC, id ASC");
    $faqs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter FAQs: " . $e->getMessage());
}

// Agrupar por categoria
$faq_por_categoria = [];
foreach ($faqs as $faq) {
    $faq_por_categoria[$faq['categoria']][] = $faq;
}

$nomes_categorias = [
    'geral' => 'Geral',
    'matricula' => 'Matrícula',
    'financeiro' => 'Financeiro',
    'pedagogico' => 'Pedagógico',
    'tecnico' => 'Técnico'
];

$cores_categorias = [
    'geral' => 'bg-blue-500/20 text-blue-400',
    'matricula' => 'bg-green-500/20 text-green-400',
    'financeiro' => 'bg-yellow-500/20 text-yellow-400',
    'pedagogico' => 'bg-purple-500/20 text-purple-400',
    'tecnico' => 'bg-red-500/20 text-red-400'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Perguntas Frequentes',
  'sub'    => 'Encontre respostas rápidas para as dúvidas mais comuns sobre nossa instituição.',
  'icon'   => 'fas fa-question-circle',
  'accent' => '#f59e0b',
  'badge'  => 'Ajuda',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Search -->
    <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-6 mb-12">
      <div class="relative">
        <input type="text" placeholder="Digite sua pergunta..." class="w-full px-6 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent text-white/90">
        <button class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-violet-600">
          <i class="fas fa-search text-xl"></i>
        </button>
      </div>
    </div>
    
    <!-- FAQ Categories -->
    <?php if (!empty($faq_por_categoria)): ?>
      <?php foreach ($nomes_categorias as $categoria_key => $categoria_nome): ?>
        <?php if (!empty($faq_por_categoria[$categoria_key])): ?>
          <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6 font-poppins flex items-center gap-2">
              <span class="w-3 h-3 rounded-full <?php echo $cores_categorias[$categoria_key]; ?>"></span>
              <?php echo $categoria_nome; ?>
            </h2>
            <div class="space-y-4">
              <?php foreach ($faq_por_categoria[$categoria_key] as $faq): ?>
                <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
                  <div class="p-6">
                    <h3 class="font-semibold text-white/90 mb-3 font-poppins"><?php echo htmlspecialchars($faq['pergunta']); ?></h3>
                    <p class="text-white/60"><?php echo htmlspecialchars($faq['resposta']); ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-8 text-center">
        <i class="fas fa-question-circle text-4xl text-gray-300 mb-4"></i>
        <p class="text-white/60">Nenhuma pergunta cadastrada no momento.</p>
      </div>
    <?php endif; ?>
    
    <!-- Contact Support -->
    <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-8 mt-12">
      <h2 class="text-2xl font-bold text-white mb-4 font-poppins">Ainda precisa de ajuda?</h2>
      <p class="text-white/60 mb-6">Entre em contato com nossa equipe de suporte para obter assistência personalizada.</p>
      <div class="flex flex-wrap gap-4">
        <a href="contato_departamentos.php" class="btn-primary">
          <i class="fas fa-envelope mr-2"></i>Contato
        </a>
        <a href="portal_pais.php" class="btn-secondary">
          <i class="fas fa-users mr-2"></i>Portal dos Pais
        </a>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



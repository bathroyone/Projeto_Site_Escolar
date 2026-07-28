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
    'geral' => 'bg-blue-100 text-blue-600',
    'matricula' => 'bg-green-100 text-green-600',
    'financeiro' => 'bg-yellow-100 text-yellow-600',
    'pedagogico' => 'bg-purple-100 text-purple-600',
    'tecnico' => 'bg-red-100 text-red-600'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-violet-500 via-violet-600 to-purple-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">FAQ e Suporte</h1>
      <p class="text-xl text-white/90">Perguntas frequentes e suporte técnico</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Search -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-12">
      <div class="relative">
        <input type="text" placeholder="Digite sua pergunta..." class="w-full px-6 py-4 border border-gray-300 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-transparent text-gray-800">
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
            <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins flex items-center gap-2">
              <span class="w-3 h-3 rounded-full <?php echo $cores_categorias[$categoria_key]; ?>"></span>
              <?php echo $categoria_nome; ?>
            </h2>
            <div class="space-y-4">
              <?php foreach ($faq_por_categoria[$categoria_key] as $faq): ?>
                <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all">
                  <div class="p-6">
                    <h3 class="font-semibold text-gray-800 mb-3 font-poppins"><?php echo htmlspecialchars($faq['pergunta']); ?></h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($faq['resposta']); ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="bg-white rounded-xl p-8 text-center">
        <i class="fas fa-question-circle text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-600">Nenhuma pergunta cadastrada no momento.</p>
      </div>
    <?php endif; ?>
    
    <!-- Contact Support -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mt-12">
      <h2 class="text-2xl font-bold text-gray-900 mb-4 font-poppins">Ainda precisa de ajuda?</h2>
      <p class="text-gray-600 mb-6">Entre em contato com nossa equipe de suporte para obter assistência personalizada.</p>
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

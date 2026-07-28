<?php
$pageTitle = 'Parcerias';
require_once 'portal/config.php';

// Criar tabela de parcerias se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS parcerias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_empresa VARCHAR(255) NOT NULL,
    descricao TEXT,
    logo VARCHAR(255),
    tipo ENUM('educacional', 'cultural', 'tecnologico', 'social', 'outro') DEFAULT 'outro',
    website VARCHAR(255),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter parcerias
$parcerias = [];
try {
    $stmt = $pdo->query("SELECT * FROM parcerias WHERE ativo = 1 ORDER BY created_at DESC LIMIT 20");
    $parcerias = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter parcerias: " . $e->getMessage());
}

// Agrupar por categoria
$parcerias_por_categoria = [];
foreach ($parcerias as $parceria) {
    $parcerias_por_categoria[$parceria['tipo']][] = $parceria;
}

$nomes_categorias = [
    'educacional' => 'Educacional',
    'cultural' => 'Cultural',
    'tecnologico' => 'Tecnológico',
    'social' => 'Social',
    'outro' => 'Outros'
];

$cores_categorias = [
    'educacional' => 'bg-blue-100 text-blue-600',
    'cultural' => 'bg-purple-100 text-purple-600',
    'tecnologico' => 'bg-cyan-100 text-cyan-600',
    'social' => 'bg-green-100 text-green-600',
    'outro' => 'bg-gray-100 text-gray-600'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-sky-500 via-sky-600 to-blue-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Parcerias</h1>
      <p class="text-xl text-white/90">Colaborações que enriquecem nossa comunidade</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <?php if (!empty($parcerias_por_categoria)): ?>
      <?php foreach ($nomes_categorias as $categoria_key => $categoria_nome): ?>
        <?php if (!empty($parcerias_por_categoria[$categoria_key])): ?>
          <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins flex items-center gap-2">
              <span class="w-3 h-3 rounded-full <?php echo $cores_categorias[$categoria_key]; ?>"></span>
              <?php echo $categoria_nome; ?>
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              <?php foreach ($parcerias_por_categoria[$categoria_key] as $parceria): ?>
                <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
                  <div class="flex items-start gap-4">
                    <div class="w-16 h-16 <?php echo $cores_categorias[$categoria_key]; ?> rounded-lg flex items-center justify-center flex-shrink-0">
                      <i class="fas fa-handshake text-2xl"></i>
                    </div>
                    <div class="flex-1">
                      <h3 class="font-semibold text-gray-800 mb-2 font-poppins"><?php echo htmlspecialchars($parceria['nome_empresa']); ?></h3>
                      <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($parceria['descricao']); ?></p>
                      <?php if (!empty($parceria['website'])): ?>
                        <a href="<?php echo htmlspecialchars($parceria['website']); ?>" target="_blank" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                          Visitar site <i class="fas fa-external-link-alt ml-1"></i>
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
      <div class="bg-white rounded-xl p-8 text-center">
        <i class="fas fa-handshake text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-600">Nenhuma parceria cadastrada no momento.</p>
      </div>
    <?php endif; ?>
    
    <!-- Partnership Info -->
    <div class="bg-white rounded-2xl shadow-lg p-8 mt-12">
      <h2 class="text-2xl font-bold text-gray-900 mb-4 font-poppins">Seja Nosso Parceiro</h2>
      <p class="text-gray-600 mb-6">Interessado em estabelecer uma parceria com nossa instituição? Entre em contato conosco para discutir oportunidades de colaboração.</p>
      <a href="contato_departamentos.php" class="btn-primary">
        <i class="fas fa-envelope mr-2"></i>Entre em Contato
      </a>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

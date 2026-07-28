<?php
$pageTitle = 'Projetos';
require_once 'portal/config.php';

// Criar tabela de projetos se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS projetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    imagem VARCHAR(255),
    categoria ENUM('pedagogico', 'social', 'ambiental', 'cultural', 'tecnologico', 'outro') DEFAULT 'outro',
    status ENUM('em_andamento', 'concluido', 'planejado') DEFAULT 'em_andamento',
    data_inicio DATE,
    data_fim DATE,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter projetos
$projetos = [];
try {
    $stmt = $pdo->query("SELECT * FROM projetos WHERE ativo = 1 ORDER BY created_at DESC LIMIT 20");
    $projetos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter projetos: " . $e->getMessage());
}

// Agrupar por categoria
$projetos_por_categoria = [];
foreach ($projetos as $projeto) {
    $projetos_por_categoria[$projeto['categoria']][] = $projeto;
}

$nomes_categorias = [
    'pedagogico' => 'Pedagógico',
    'social' => 'Social',
    'ambiental' => 'Ambiental',
    'cultural' => 'Cultural',
    'tecnologico' => 'Tecnológico',
    'outro' => 'Outros'
];

$cores_categorias = [
    'pedagogico' => 'bg-blue-100 text-blue-600',
    'social' => 'bg-green-100 text-green-600',
    'ambiental' => 'bg-emerald-100 text-emerald-600',
    'cultural' => 'bg-purple-100 text-purple-600',
    'tecnologico' => 'bg-cyan-100 text-cyan-600',
    'outro' => 'bg-gray-100 text-gray-600'
];

$cores_status = [
    'em_andamento' => 'bg-yellow-100 text-yellow-600',
    'concluido' => 'bg-green-100 text-green-600',
    'planejado' => 'bg-blue-100 text-blue-600'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-fuchsia-500 via-fuchsia-600 to-pink-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Projetos</h1>
      <p class="text-xl text-white/90">Iniciativas que transformam a educação</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <?php if (!empty($projetos_por_categoria)): ?>
      <?php foreach ($nomes_categorias as $categoria_key => $categoria_nome): ?>
        <?php if (!empty($projetos_por_categoria[$categoria_key])): ?>
          <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins flex items-center gap-2">
              <span class="w-3 h-3 rounded-full <?php echo $cores_categorias[$categoria_key]; ?>"></span>
              <?php echo $categoria_nome; ?>
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              <?php foreach ($projetos_por_categoria[$categoria_key] as $projeto): ?>
                <div class="bg-white rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-all">
                  <?php if (!empty($projeto['imagem'])): ?>
                    <div class="h-48 bg-gradient-to-br <?php echo $cores_categorias[$categoria_key]; ?> flex items-center justify-center">
                      <i class="fas fa-image text-white text-4xl"></i>
                    </div>
                  <?php else: ?>
                    <div class="h-48 bg-gradient-to-br <?php echo $cores_categorias[$categoria_key]; ?> flex items-center justify-center">
                      <i class="fas fa-project-diagram text-white text-4xl"></i>
                    </div>
                  <?php endif; ?>
                  <div class="p-6">
                    <div class="flex items-center gap-2 mb-3">
                      <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $cores_status[$projeto['status']]; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $projeto['status'])); ?>
                      </span>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2 font-poppins"><?php echo htmlspecialchars($projeto['titulo']); ?></h3>
                    <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars(substr($projeto['descricao'], 0, 100)); ?>...</p>
                    <?php if (!empty($projeto['data_inicio'])): ?>
                      <div class="text-xs text-gray-500">
                        <i class="fas fa-calendar-alt mr-1"></i><?php echo date('d/m/Y', strtotime($projeto['data_inicio'])); ?>
                        <?php if (!empty($projeto['data_fim'])): ?>
                          - <?php echo date('d/m/Y', strtotime($projeto['data_fim'])); ?>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="bg-white rounded-xl p-8 text-center">
        <i class="fas fa-project-diagram text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-600">Nenhum projeto cadastrado no momento.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

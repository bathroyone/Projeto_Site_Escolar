<?php
$pageTitle = 'Formulários';
require_once 'portal/config.php';

// Criar tabela de formulários se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS formularios_publicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    arquivo VARCHAR(255) NOT NULL,
    categoria ENUM('matricula', 'financeiro', 'pedagogico', 'outro') DEFAULT 'outro',
    downloads INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter formulários
$formularios = [];
try {
    $stmt = $pdo->query("SELECT * FROM formularios_publicos WHERE ativo = 1 ORDER BY categoria, titulo");
    $formularios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter formulários: " . $e->getMessage());
}

// Agrupar por categoria
$formularios_por_categoria = [];
foreach ($formularios as $formulario) {
    $formularios_por_categoria[$formulario['categoria']][] = $formulario;
}

$categorias = [
    'matricula' => 'Matrícula',
    'financeiro' => 'Financeiro',
    'pedagogico' => 'Pedagógico',
    'outro' => 'Outros'
];

$cores_categorias = [
    'matricula' => 'bg-blue-500/20 text-blue-400',
    'financeiro' => 'bg-green-500/20 text-green-400',
    'pedagogico' => 'bg-purple-500/20 text-purple-400',
    'outro' => 'bg-gray-100 text-white/60'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Formulários e Documentos',
  'sub'    => 'Baixe os formulários, declarações e documentos necessários para a vida escolar.',
  'icon'   => 'fas fa-file-download',
  'accent' => '#7c3aed',
  'badge'  => 'Serviços',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <?php if (!empty($formularios_por_categoria)): ?>
      <?php foreach ($categorias as $categoria_key => $categoria_nome): ?>
        <?php if (!empty($formularios_por_categoria[$categoria_key])): ?>
          <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6 font-poppins flex items-center gap-2">
              <span class="w-3 h-3 rounded-full <?php echo $cores_categorias[$categoria_key]; ?>"></span>
              <?php echo $categoria_nome; ?>
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
              <?php foreach ($formularios_por_categoria[$categoria_key] as $formulario): ?>
                <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
                  <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center <?php echo $cores_categorias[$categoria_key]; ?> flex-shrink-0">
                      <i class="fas fa-file-pdf text-xl"></i>
                    </div>
                    <div class="flex-1">
                      <h3 class="font-semibold text-white/90 mb-2 font-poppins"><?php echo htmlspecialchars($formulario['titulo']); ?></h3>
                      <p class="text-sm text-white/60 mb-3"><?php echo htmlspecialchars($formulario['descricao']); ?></p>
                      <div class="flex items-center justify-between">
                        <span class="text-xs text-white/50">
                          <i class="fas fa-download mr-1"></i><?php echo $formulario['downloads']; ?> downloads
                        </span>
                        <a href="#" class="text-sm text-blue-400 hover:text-blue-700 font-medium">
                          Baixar <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                      </div>
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
        <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
        <p class="text-white/60">Nenhum formulário disponível no momento.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



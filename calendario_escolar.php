<?php
$pageTitle = 'Calendário Escolar';
require_once 'portal/config.php';

// Criar tabela de calendário escolar se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS calendario_escolar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    tipo ENUM('feriado', 'evento', 'prova', 'reuniao', 'outro') DEFAULT 'evento',
    turma VARCHAR(100),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter eventos do calendário
$eventos = [];
try {
    $stmt = $pdo->query("SELECT * FROM calendario_escolar WHERE ativo = 1 AND (data_inicio >= CURDATE() OR data_fim >= CURDATE()) ORDER BY data_inicio ASC LIMIT 50");
    $eventos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter calendário: " . $e->getMessage());
}

// Obter feriados
$feriados = [];
try {
    $stmt = $pdo->query("SELECT * FROM calendario_escolar WHERE tipo = 'feriado' AND ativo = 1 AND YEAR(data_inicio) = YEAR(CURDATE()) ORDER BY data_inicio ASC");
    $feriados = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter feriados: " . $e->getMessage());
}

$tipos_eventos = [
    'feriado' => 'Feriado',
    'evento' => 'Evento',
    'prova' => 'Prova',
    'reuniao' => 'Reunião',
    'outro' => 'Outro'
];

$cores_tipos = [
    'feriado' => 'bg-red-100 text-red-600 border-red-200',
    'evento' => 'bg-blue-100 text-blue-600 border-blue-200',
    'prova' => 'bg-purple-100 text-purple-600 border-purple-200',
    'reuniao' => 'bg-green-100 text-green-600 border-green-200',
    'outro' => 'bg-gray-100 text-gray-600 border-gray-200'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Calendário Escolar</h1>
      <p class="text-xl text-white/90">Acompanhe as datas importantes do ano letivo</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Filtros -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
      <div class="flex flex-wrap gap-4 items-center">
        <h2 class="text-lg font-semibold text-gray-800 font-poppins">Filtrar por tipo:</h2>
        <div class="flex flex-wrap gap-2">
          <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">Todos</button>
          <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Feriados</button>
          <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Eventos</button>
          <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Provas</button>
          <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200">Reuniões</button>
        </div>
      </div>
    </div>
    
    <!-- Eventos -->
    <div class="space-y-4">
      <?php if (!empty($eventos)): ?>
        <?php foreach ($eventos as $evento): ?>
          <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0">
                <div class="w-16 h-16 rounded-xl flex items-center justify-center <?php echo $cores_tipos[$evento['tipo']]; ?>">
                  <i class="fas fa-calendar text-2xl"></i>
                </div>
              </div>
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $cores_tipos[$evento['tipo']]; ?>">
                    <?php echo $tipos_eventos[$evento['tipo']]; ?>
                  </span>
                  <?php if (!empty($evento['turma'])): ?>
                    <span class="text-sm text-gray-500">• <?php echo htmlspecialchars($evento['turma']); ?></span>
                  <?php endif; ?>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2 font-poppins"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($evento['descricao']); ?></p>
                <div class="flex items-center gap-4 text-sm text-gray-500">
                  <span><i class="fas fa-calendar-alt mr-1"></i><?php echo date('d/m/Y', strtotime($evento['data_inicio'])); ?></span>
                  <?php if (!empty($evento['data_fim'])): ?>
                    <span><i class="fas fa-arrow-right mr-1"></i><?php echo date('d/m/Y', strtotime($evento['data_fim'])); ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="bg-white rounded-xl p-8 text-center">
          <i class="fas fa-calendar-times text-4xl text-gray-300 mb-4"></i>
          <p class="text-gray-600">Nenhum evento encontrado no calendário.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<?php
$pageTitle = 'Avaliações';
require_once 'portal/config.php';

$success = '';
$error = '';

// Criar tabela de avaliações se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('aluno', 'responsavel', 'ex_aluno', 'visitante') DEFAULT 'visitante',
    avaliacao INT NOT NULL CHECK (avaliacao >= 1 AND avaliacao <= 5),
    comentario TEXT,
    aprovado TINYINT(1) DEFAULT 0,
    data_avaliacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Processar envio de avaliação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? 'visitante');
    $avaliacao = intval($_POST['avaliacao'] ?? 0);
    $comentario = sanitizeInput($_POST['comentario'] ?? '');
    
    if (empty($nome) || $avaliacao < 1 || $avaliacao > 5) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO avaliacoes (nome, tipo, avaliacao, comentario) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $tipo, $avaliacao, $comentario]);
            $success = 'Avaliação enviada com sucesso! Após aprovação, será exibida no site.';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar avaliação. Tente novamente.';
            error_log("Erro ao enviar avaliação: " . $e->getMessage());
        }
    }
}

// Obter avaliações aprovadas
$avaliacoes = [];
try {
    $stmt = $pdo->query("SELECT * FROM avaliacoes WHERE aprovado = 1 ORDER BY data_avaliacao DESC LIMIT 20");
    $avaliacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter avaliações: " . $e->getMessage());
}

$tipos_avaliacao = [
    'aluno' => 'Aluno',
    'responsavel' => 'Responsável',
    'ex_aluno' => 'Ex-Aluno',
    'visitante' => 'Visitante'
];

$cores_tipos = [
    'aluno' => 'bg-blue-100 text-blue-600',
    'responsavel' => 'bg-green-100 text-green-600',
    'ex_aluno' => 'bg-purple-100 text-purple-600',
    'visitante' => 'bg-gray-100 text-gray-600'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-amber-500 via-amber-600 to-yellow-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Avaliações</h1>
      <p class="text-xl text-white/90">O que nossa comunidade diz sobre nós</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <?php if ($success): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <div class="grid lg:grid-cols-2 gap-12">
      <!-- Evaluation Form -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Deixe sua Avaliação</h2>
        
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Nome *</label>
            <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent text-gray-800" placeholder="Digite seu nome">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
            <select name="tipo" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent text-gray-800">
              <?php foreach ($tipos_avaliacao as $tipo_key => $tipo_nome): ?>
                <option value="<?php echo $tipo_key; ?>"><?php echo $tipo_nome; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Avaliação *</label>
            <div class="flex gap-2">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <label class="cursor-pointer">
                  <input type="radio" name="avaliacao" value="<?php echo $i; ?>" required class="sr-only peer">
                  <i class="fas fa-star text-2xl text-gray-300 peer-checked:text-amber-500"></i>
                </label>
              <?php endfor; ?>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Comentário</label>
            <textarea name="comentario" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent text-gray-800" placeholder="Conte sua experiência"></textarea>
          </div>
          
          <button type="submit" class="btn-primary w-full">
            <i class="fas fa-paper-plane mr-2"></i>Enviar Avaliação
          </button>
        </form>
      </div>
      
      <!-- Reviews List -->
      <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Avaliações Recentes</h2>
        <div class="space-y-4">
          <?php if (!empty($avaliacoes)): ?>
            <?php foreach ($avaliacoes as $avaliacao): ?>
              <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
                <div class="flex items-start gap-4">
                  <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user text-amber-600 text-xl"></i>
                  </div>
                  <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                      <h3 class="font-semibold text-gray-800 font-poppins"><?php echo htmlspecialchars($avaliacao['nome']); ?></h3>
                      <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $cores_tipos[$avaliacao['tipo']]; ?>">
                        <?php echo $tipos_avaliacao[$avaliacao['tipo']]; ?>
                      </span>
                    </div>
                    <div class="flex items-center gap-1 mb-2">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $avaliacao['avaliacao'] ? 'text-amber-500' : 'text-gray-300'; ?> text-sm"></i>
                      <?php endfor; ?>
                    </div>
                    <?php if (!empty($avaliacao['comentario'])): ?>
                      <p class="text-sm text-gray-600"><?php echo htmlspecialchars($avaliacao['comentario']); ?></p>
                    <?php endif; ?>
                    <div class="text-xs text-gray-500 mt-2">
                      <?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="bg-white rounded-xl p-8 text-center">
              <i class="fas fa-star text-4xl text-gray-300 mb-4"></i>
              <p class="text-gray-600">Nenhuma avaliação cadastrada ainda.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

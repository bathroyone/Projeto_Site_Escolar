<?php
$pageTitle = 'Trabalhe Conosco';
require_once 'portal/config.php';

$success = '';
$error = '';

// Criar tabela de vagas se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS vagas_emprego (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    requisitos TEXT,
    salario VARCHAR(100),
    tipo ENUM('clt', 'pj', 'estagio', 'voluntario') DEFAULT 'clt',
    carga_horaria VARCHAR(50),
    ativo TINYINT(1) DEFAULT 1,
    data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Criar tabela de currículos se não existir
$pdo->query("CREATE TABLE IF NOT EXISTS curriculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(50),
    vaga_id INT,
    arquivo VARCHAR(255),
    mensagem TEXT,
    status ENUM('pendente', 'analise', 'entrevista', 'aprovado', 'rejeitado') DEFAULT 'pendente',
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vaga_id) REFERENCES vagas_emprego(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Processar envio de currículo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $vaga_id = intval($_POST['vaga_id'] ?? 0);
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    
    if (empty($nome) || empty($email) || $vaga_id === 0) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO curriculos (nome, email, telefone, vaga_id, mensagem) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $telefone, $vaga_id, $mensagem]);
            
            $success = 'Currículo enviado com sucesso! Entraremos em contato se houver interesse.';
        } catch (PDOException $e) {
            error_log("Erro ao enviar currículo: " . $e->getMessage());
            $error = 'Erro ao enviar currículo. Tente novamente.';
        }
    }
}

// Obter vagas ativas
$vagas = [];
try {
    $stmt = $pdo->query("SELECT * FROM vagas_emprego WHERE ativo = 1 ORDER BY data_publicacao DESC");
    $vagas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter vagas: " . $e->getMessage());
}

$tipos_vaga = [
    'clt' => 'CLT',
    'pj' => 'PJ',
    'estagio' => 'Estágio',
    'voluntario' => 'Voluntário'
];

$cores_tipos = [
    'clt' => 'bg-blue-100 text-blue-600',
    'pj' => 'bg-purple-100 text-purple-600',
    'estagio' => 'bg-green-100 text-green-600',
    'voluntario' => 'bg-orange-100 text-orange-600'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Trabalhe Conosco</h1>
      <p class="text-xl text-white/90">Faça parte da nossa equipe educacional</p>
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
    
    <!-- Job Listings -->
    <div class="mb-12">
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Vagas Disponíveis</h2>
      <div class="grid md:grid-cols-2 gap-6">
        <?php if (!empty($vagas)): ?>
          <?php foreach ($vagas as $vaga): ?>
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
              <div class="flex items-start gap-4 mb-4">
                <div class="w-12 h-12 <?php echo $cores_tipos[$vaga['tipo']]; ?> rounded-lg flex items-center justify-center flex-shrink-0">
                  <i class="fas fa-briefcase text-xl"></i>
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $cores_tipos[$vaga['tipo']]; ?>">
                      <?php echo $tipos_vaga[$vaga['tipo']]; ?>
                    </span>
                  </div>
                  <h3 class="font-semibold text-gray-800 mb-2 font-poppins"><?php echo htmlspecialchars($vaga['titulo']); ?></h3>
                  <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars(substr($vaga['descricao'], 0, 150)); ?>...</p>
                  <div class="flex items-center gap-4 text-xs text-gray-500">
                    <?php if (!empty($vaga['salario'])): ?>
                      <span><i class="fas fa-dollar-sign mr-1"></i><?php echo htmlspecialchars($vaga['salario']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($vaga['carga_horaria'])): ?>
                      <span><i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars($vaga['carga_horaria']); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <button onclick="openApplicationForm(<?php echo $vaga['id']; ?>, '<?php echo htmlspecialchars($vaga['titulo']); ?>')" class="btn-secondary w-full text-sm">
                <i class="fas fa-paper-plane mr-2"></i>Candidatar-se
              </button>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-span-2 bg-white rounded-xl p-8 text-center">
            <i class="fas fa-briefcase text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-600">Nenhuma vaga disponível no momento.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Application Form Modal -->
    <div id="application-modal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-bold text-gray-900 font-poppins">Candidatar-se</h3>
          <button onclick="closeApplicationForm()" class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
        
        <form method="POST" class="space-y-4">
          <input type="hidden" name="vaga_id" id="vaga_id" value="">
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
            <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-800" placeholder="Digite seu nome">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-800" placeholder="seu@email.com">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Telefone</label>
            <input type="tel" name="telefone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-800" placeholder="(11) 12345-6789">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Mensagem</label>
            <textarea name="mensagem" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-gray-800" placeholder="Conte um pouco sobre você"></textarea>
          </div>
          
          <button type="submit" class="btn-primary w-full">
            <i class="fas fa-paper-plane mr-2"></i>Enviar Candidatura
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<script>
function openApplicationForm(vagaId, vagaTitulo) {
    document.getElementById('vaga_id').value = vagaId;
    document.getElementById('application-modal').classList.remove('hidden');
}

function closeApplicationForm() {
    document.getElementById('application-modal').classList.add('hidden');
}
</script>

<?php require_once 'includes/footer.php'; ?>

<?php
$pageTitle = 'Newsletter';
require_once 'portal/config.php';

$success = '';
$error = '';

// Processar inscrição na newsletter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($nome) || empty($email)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um e-mail válido.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Criar tabela se não existir
            $pdo->query("CREATE TABLE IF NOT EXISTS newsletter (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                status ENUM('ativo', 'inativo', 'cancelado') DEFAULT 'ativo',
                data_inscricao DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Verificar se e-mail já está cadastrado
            $stmt = $pdo->prepare("SELECT id FROM newsletter WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este e-mail já está cadastrado em nossa newsletter.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO newsletter (nome, email) VALUES (?, ?)");
                $stmt->execute([$nome, $email]);
                $success = 'Inscrição realizada com sucesso! Você receberá nossas novidades.';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao processar inscrição. Tente novamente.';
            error_log("Erro ao inscrever na newsletter: " . $e->getMessage());
        }
    }
}

// Obter newsletters recentes
$newsletters = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM comunicados WHERE tipo = 'newsletter' AND ativo = 1 ORDER BY data_envio DESC LIMIT 10");
    $newsletters = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter newsletters: " . $e->getMessage());
}
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-orange-500 via-orange-600 to-amber-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Newsletter</h1>
      <p class="text-xl text-white/90">Receba novidades da escola por email</p>
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
      <!-- Newsletter Form -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Inscreva-se na Newsletter</h2>
        
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
            <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-gray-800" placeholder="Digite seu nome">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent text-gray-800" placeholder="seu@email.com">
          </div>
          
          <button type="submit" class="btn-primary w-full">
            <i class="fas fa-envelope mr-2"></i>Inscrever-se
          </button>
        </form>
      </div>
      
      <!-- Recent Newsletters -->
      <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Comunicações Recentes</h2>
        <div class="space-y-4">
          <?php if (!empty($newsletters)): ?>
            <?php foreach ($newsletters as $newsletter): ?>
              <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
                <h3 class="font-semibold text-gray-800 mb-2 font-poppins"><?php echo htmlspecialchars($newsletter['titulo']); ?></h3>
                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars(substr($newsletter['conteudo'], 0, 100)); ?>...</p>
                <div class="text-xs text-gray-500">
                  <i class="fas fa-calendar-alt mr-1"></i><?php echo date('d/m/Y', strtotime($newsletter['data_envio'])); ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="bg-white rounded-xl p-8 text-center">
              <i class="fas fa-newspaper text-4xl text-gray-300 mb-4"></i>
              <p class="text-gray-600">Nenhuma comunicação recente.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

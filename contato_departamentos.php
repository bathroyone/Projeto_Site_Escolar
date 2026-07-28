<?php
$pageTitle = 'Contato';
require_once 'portal/config.php';

$success = '';
$error = '';

// Criar tabela de departamentos se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    telefone VARCHAR(50),
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Criar tabela de mensagens por departamento se não existir
$pdo->query("CREATE TABLE IF NOT EXISTS mensagens_departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departamento_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(50),
    assunto VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    status ENUM('pendente', 'respondida', 'arquivada') DEFAULT 'pendente',
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter departamentos
$departamentos = [];
try {
    $stmt = $pdo->query("SELECT * FROM departamentos WHERE ativo = 1 ORDER BY nome");
    $departamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter departamentos: " . $e->getMessage());
}

// Processar formulário de contato
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $departamento_id = $_POST['departamento_id'] ?? '';
    $assunto = $_POST['assunto'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';
    
    if (empty($nome) || empty($email) || empty($departamento_id) || empty($assunto) || empty($mensagem)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO mensagens_departamentos (departamento_id, nome, email, telefone, assunto, mensagem) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$departamento_id, $nome, $email, $telefone, $assunto, $mensagem]);
            $success = 'Mensagem enviada com sucesso! Entraremos em contato em breve.';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar mensagem. Tente novamente.';
            error_log("Erro ao enviar mensagem: " . $e->getMessage());
        }
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Contato</h1>
      <p class="text-xl text-white/90">Entre em contato conosco</p>
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
      <!-- Contact Form -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Envie uma Mensagem</h2>
        
        <form method="POST" class="space-y-6">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
            <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Digite seu nome">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="seu@email.com">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Telefone</label>
            <input type="tel" name="telefone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="(11) 12345-6789">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Departamento *</label>
            <select name="departamento_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Selecione um departamento</option>
              <?php foreach ($departamentos as $dept): ?>
                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['nome']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Assunto *</label>
            <input type="text" name="assunto" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Assunto da mensagem">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Mensagem *</label>
            <textarea name="mensagem" required rows="5" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Digite sua mensagem"></textarea>
          </div>
          
          <button type="submit" class="btn-primary w-full">
            <i class="fas fa-paper-plane mr-2"></i>Enviar Mensagem
          </button>
        </form>
      </div>
      
      <!-- Departments -->
      <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Departamentos</h2>
        <div class="space-y-4">
          <?php foreach ($departamentos as $dept): ?>
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
              <h3 class="font-semibold text-gray-800 mb-2 font-poppins"><?php echo htmlspecialchars($dept['nome']); ?></h3>
              <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($dept['descricao']); ?></p>
              <div class="space-y-2 text-sm">
                <?php if (!empty($dept['email'])): ?>
                  <div class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-envelope text-gray-400"></i>
                    <a href="mailto:<?php echo htmlspecialchars($dept['email']); ?>" class="text-blue-600 hover:text-blue-700"><?php echo htmlspecialchars($dept['email']); ?></a>
                  </div>
                <?php endif; ?>
                <?php if (!empty($dept['telefone'])): ?>
                  <div class="flex items-center gap-2 text-gray-600">
                    <i class="fas fa-phone text-gray-400"></i>
                    <a href="tel:<?php echo htmlspecialchars($dept['telefone']); ?>" class="text-blue-600 hover:text-blue-700"><?php echo htmlspecialchars($dept['telefone']); ?></a>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        
        <!-- General Contact Info -->
        <div class="bg-white rounded-xl p-6 shadow-md mt-6">
          <h3 class="font-semibold text-gray-800 mb-4 font-poppins">Informações Gerais</h3>
          <div class="space-y-3 text-sm">
            <div class="flex items-center gap-3 text-gray-600">
              <i class="fas fa-map-marker-alt text-gray-400"></i>
              <span>Rua da Escola, 123 - Bairro</span>
            </div>
            <div class="flex items-center gap-3 text-gray-600">
              <i class="fas fa-phone text-gray-400"></i>
              <span>(11) 1234-5678</span>
            </div>
            <div class="flex items-center gap-3 text-gray-600">
              <i class="fas fa-envelope text-gray-400"></i>
              <span>contato@escola.com.br</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

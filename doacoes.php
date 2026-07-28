<?php
$pageTitle = 'Doações';
require_once 'portal/config.php';

$success = '';
$error = '';

// Criar tabela de doações se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS doacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(50),
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('pix', 'cartao', 'boleto', 'transferencia') DEFAULT 'pix',
    mensagem TEXT,
    status ENUM('pendente', 'confirmada', 'cancelada') DEFAULT 'pendente',
    data_doacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Processar doação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $valor = floatval($_POST['valor'] ?? 0);
    $tipo = sanitizeInput($_POST['tipo'] ?? 'pix');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    
    if (empty($nome) || empty($email) || $valor <= 0) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO doacoes (nome, email, telefone, valor, tipo, mensagem) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $telefone, $valor, $tipo, $mensagem]);
            $success = 'Doação registrada com sucesso! Obrigado pelo seu apoio.';
        } catch (PDOException $e) {
            $error = 'Erro ao processar doação. Tente novamente.';
            error_log("Erro ao processar doação: " . $e->getMessage());
        }
    }
}

$tipos_doacao = [
    'pix' => 'PIX',
    'cartao' => 'Cartão de Crédito',
    'boleto' => 'Boleto',
    'transferencia' => 'Transferência'
];

$cores_tipos = [
    'pix' => 'bg-green-100 text-green-600',
    'cartao' => 'bg-blue-100 text-blue-600',
    'boleto' => 'bg-yellow-100 text-yellow-600',
    'transferencia' => 'bg-purple-100 text-purple-600'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-emerald-500 via-emerald-600 to-green-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Faça uma Doação</h1>
      <p class="text-xl text-white/90">Apoie nossa missão educacional</p>
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
      <!-- Donation Form -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Fazer Doação</h2>
        
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
            <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-gray-800" placeholder="Digite seu nome">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-gray-800" placeholder="seu@email.com">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Telefone</label>
            <input type="tel" name="telefone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-gray-800" placeholder="(11) 12345-6789">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Valor da Doação *</label>
            <input type="number" name="valor" required min="1" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-gray-800" placeholder="R$ 0,00">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Forma de Pagamento *</label>
            <select name="tipo" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-gray-800">
              <?php foreach ($tipos_doacao as $tipo_key => $tipo_nome): ?>
                <option value="<?php echo $tipo_key; ?>"><?php echo $tipo_nome; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Mensagem</label>
            <textarea name="mensagem" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-gray-800" placeholder="Deixe uma mensagem (opcional)"></textarea>
          </div>
          
          <button type="submit" class="btn-primary w-full">
            <i class="fas fa-heart mr-2"></i>Fazer Doação
          </button>
        </form>
      </div>
      
      <!-- Donation Info -->
      <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Como sua doação ajuda</h2>
        
        <div class="space-y-6">
          <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-book text-emerald-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Materiais Didáticos</h3>
                <p class="text-sm text-gray-600">Apoia a compra de livros, materiais e recursos para enriquecer o aprendizado dos alunos.</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-laptop text-blue-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Tecnologia</h3>
                <p class="text-sm text-gray-600">Investe em equipamentos e tecnologia para modernizar o ambiente educacional.</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-graduate text-purple-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Bolsas de Estudo</h3>
                <p class="text-sm text-gray-600">Oferece oportunidades de estudo para alunos que necessitam de apoio financeiro.</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-building text-orange-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Infraestrutura</h3>
                <p class="text-sm text-gray-600">Contribui para melhorias e manutenção das instalações da escola.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

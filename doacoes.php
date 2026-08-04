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
    'pix' => 'bg-green-500/20 text-green-400',
    'cartao' => 'bg-blue-500/20 text-blue-400',
    'boleto' => 'bg-yellow-500/20 text-yellow-400',
    'transferencia' => 'bg-purple-500/20 text-purple-400'
];
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Doações',
  'sub'    => 'Contribua com o desenvolvimento da nossa comunidade escolar.',
  'icon'   => 'fas fa-heart',
  'accent' => '#ec4899',
  'badge'  => 'Comunidade',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <?php if ($success): ?>
      <div class="bg-green-500/20 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
      <div class="bg-red-500/20 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>
    
    <div class="grid lg:grid-cols-2 gap-12">
      <!-- Donation Form -->
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-8">
        <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Fazer Doação</h2>
        
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Nome Completo *</label>
            <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-white/90" placeholder="Digite seu nome">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Email *</label>
            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-white/90" placeholder="seu@email.com">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Telefone</label>
            <input type="tel" name="telefone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-white/90" placeholder="(11) 12345-6789">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Valor da Doação *</label>
            <input type="number" name="valor" required min="1" step="0.01" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-white/90" placeholder="R$ 0,00">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Forma de Pagamento *</label>
            <select name="tipo" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-white/90">
              <?php foreach ($tipos_doacao as $tipo_key => $tipo_nome): ?>
                <option value="<?php echo $tipo_key; ?>"><?php echo $tipo_nome; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Mensagem</label>
            <textarea name="mensagem" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-white/90" placeholder="Deixe uma mensagem (opcional)"></textarea>
          </div>
          
          <button type="submit" class="btn-primary w-full">
            <i class="fas fa-heart mr-2"></i>Fazer Doação
          </button>
        </form>
      </div>
      
      <!-- Donation Info -->
      <div>
        <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Como sua doação ajuda</h2>
        
        <div class="space-y-6">
          <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-book text-emerald-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-white/90 mb-2 font-poppins">Materiais Didáticos</h3>
                <p class="text-sm text-white/60">Apoia a compra de livros, materiais e recursos para enriquecer o aprendizado dos alunos.</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-laptop text-blue-400 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-white/90 mb-2 font-poppins">Tecnologia</h3>
                <p class="text-sm text-white/60">Investe em equipamentos e tecnologia para modernizar o ambiente educacional.</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-graduate text-purple-400 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-white/90 mb-2 font-poppins">Bolsas de Estudo</h3>
                <p class="text-sm text-white/60">Oferece oportunidades de estudo para alunos que necessitam de apoio financeiro.</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-building text-orange-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-white/90 mb-2 font-poppins">Infraestrutura</h3>
                <p class="text-sm text-white/60">Contribui para melhorias e manutenção das instalações da escola.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



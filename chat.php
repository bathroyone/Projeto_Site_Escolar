<?php
$pageTitle = 'Chat';
require_once 'portal/config.php';

// Criar tabela de mensagens de chat se não existir
$conn = getDBConnection();
$conn->query("CREATE TABLE IF NOT EXISTS chat_mensagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    mensagem TEXT NOT NULL,
    status ENUM('pendente', 'respondido', 'fechado') DEFAULT 'pendente',
    ip VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$success = '';
$error = '';

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    if (empty($nome) || empty($mensagem)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO chat_mensagens (nome, email, mensagem, ip) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $mensagem, $ip]);
            $success = 'Mensagem enviada com sucesso! Respondemos em breve.';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar mensagem. Tente novamente.';
            error_log("Erro ao enviar mensagem: " . $e->getMessage());
        }
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-lime-500 via-lime-600 to-green-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Chat Online</h1>
      <p class="text-xl text-white/90">Fale conosco em tempo real</p>
    </div>
  </div>
</section>

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
      <!-- Chat Form -->
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-8">
        <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Envie uma Mensagem</h2>
        
        <form method="POST" class="space-y-4">
          <input type="hidden" name="action" value="enviar">
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Nome *</label>
            <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent text-white/90" placeholder="Digite seu nome">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Email</label>
            <input type="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent text-white/90" placeholder="seu@email.com">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Mensagem *</label>
            <textarea name="mensagem" required rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lime-500 focus:border-transparent text-white/90" placeholder="Digite sua mensagem"></textarea>
          </div>
          
          <button type="submit" class="btn-primary w-full">
            <i class="fas fa-paper-plane mr-2"></i>Enviar Mensagem
          </button>
        </form>
      </div>
      
      <!-- Chat Info -->
      <div>
        <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Informações do Chat</h2>
        
        <div class="space-y-6">
          <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-lime-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock text-lime-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-white/90 mb-2 font-poppins">Horário de Atendimento</h3>
                <p class="text-sm text-white/60">Segunda a sexta: 08:00 às 18:00</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-bolt text-green-400 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-white/90 mb-2 font-poppins">Resposta Rápida</h3>
                <p class="text-sm text-white/60">Respondemos às mensagens em até 24 horas úteis.</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-shield-alt text-blue-400 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-white/90 mb-2 font-poppins">Privacidade</h3>
                <p class="text-sm text-white/60">Suas informações são mantidas em sigilo absoluto.</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-headset text-purple-400 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-white/90 mb-2 font-poppins">Outros Canais</h3>
                <p class="text-sm text-white/60">Prefere telefone? Ligue para (11) 1234-5678.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>


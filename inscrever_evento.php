<?php
$pageTitle = 'Inscrever-se em Evento';
require_once 'portal/config.php';

$success = '';
$error = '';

// Processar inscrição em evento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $evento_id = intval($_POST['evento_id'] ?? 0);
    $nome_evento = sanitizeInput($_POST['nome_evento'] ?? '');
    $data_evento = sanitizeInput($_POST['data_evento'] ?? '');
    
    if (empty($nome) || empty($email) || empty($telefone) || empty($evento_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se tabela existe, se não, criar
            $pdo->query("CREATE TABLE IF NOT EXISTS inscricoes_eventos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                evento_id INT NOT NULL,
                nome_evento VARCHAR(255) NOT NULL,
                nome_participante VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                telefone VARCHAR(50) NOT NULL,
                data_evento DATE,
                status ENUM('pendente', 'confirmada', 'cancelada') DEFAULT 'pendente',
                data_inscricao DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $stmt = $pdo->prepare("INSERT INTO inscricoes_eventos (evento_id, nome_evento, nome_participante, email, telefone, data_evento) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$evento_id, $nome_evento, $nome, $email, $telefone, $data_evento]);
            
            $success = 'Inscrição realizada com sucesso! Você receberá confirmação por email.';
        } catch (PDOException $e) {
            $error = 'Erro ao processar inscrição. Tente novamente.';
            error_log("Erro ao inscrever em evento: " . $e->getMessage());
        }
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Inscrição em Eventos',
  'sub'    => 'Confira os próximos eventos e garanta sua participação.',
  'icon'   => 'fas fa-ticket-alt',
  'accent' => '#7c3aed',
  'badge'  => 'Eventos',
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
    
    <div class="max-w-2xl mx-auto">
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-8">
        <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Formulário de Inscrição</h2>
        
        <form method="POST" class="space-y-4">
          <input type="hidden" name="evento_id" value="<?php echo intval($_GET['evento_id'] ?? 0); ?>">
          <input type="hidden" name="nome_evento" value="<?php echo htmlspecialchars($_GET['nome_evento'] ?? ''); ?>">
          <input type="hidden" name="data_evento" value="<?php echo htmlspecialchars($_GET['data_evento'] ?? ''); ?>">
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Nome Completo *</label>
            <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent text-white/90" placeholder="Digite seu nome">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Email *</label>
            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent text-white/90" placeholder="seu@email.com">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-white/70 mb-2">Telefone *</label>
            <input type="tel" name="telefone" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-violet-500 focus:border-transparent text-white/90" placeholder="(11) 12345-6789">
          </div>
          
          <button type="submit" class="btn-primary w-full">
            <i class="fas fa-calendar-check mr-2"></i>Confirmar Inscrição
          </button>
        </form>
        
        <div class="mt-6 text-center">
          <a href="index.php" class="text-sm text-white/60 hover:text-white/90">
            <i class="fas fa-arrow-left mr-1"></i>Voltar à página inicial
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



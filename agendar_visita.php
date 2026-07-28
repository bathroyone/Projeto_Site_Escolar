<?php
$pageTitle = 'Agendar Visita';
require_once 'portal/config.php';

$success = '';
$error = '';

// Processar agendamento de visita
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $data_visita = sanitizeInput($_POST['data_visita'] ?? '');
    $horario = sanitizeInput($_POST['horario'] ?? '');
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    
    if (empty($nome) || empty($email) || empty($telefone) || empty($data_visita) || empty($horario)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO visitas (visitante_nome, visitante_telefone, tipo_visita, motivo, data_visita, hora_entrada, status, observacoes) VALUES (?, ?, 'pais', ?, ?, ?, 'agendada', ?)");
            $observacoes = "Email: " . $email;
            $stmt->execute([$nome, $telefone, $motivo, $data_visita, $horario, $observacoes]);
            
            $success = 'Visita agendada com sucesso! Entraremos em contato para confirmar.';
        } catch (PDOException $e) {
            error_log("Erro ao agendar visita: " . $e->getMessage());
            $error = 'Erro ao agendar visita. Tente novamente.';
        }
    }
}
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-teal-500 via-teal-600 to-cyan-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Agendar Visita</h1>
      <p class="text-xl text-white/90">Conheça nossa escola pessoalmente</p>
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
      <!-- Visit Form -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Agende sua Visita</h2>
        
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
            <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-gray-800" placeholder="Digite seu nome">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-gray-800" placeholder="seu@email.com">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Telefone *</label>
            <input type="tel" name="telefone" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-gray-800" placeholder="(11) 12345-6789">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Data da Visita *</label>
            <input type="date" name="data_visita" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-gray-800">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Horário *</label>
            <select name="horario" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-gray-800">
              <option value="">Selecione um horário</option>
              <option value="08:00">08:00</option>
              <option value="09:00">09:00</option>
              <option value="10:00">10:00</option>
              <option value="11:00">11:00</option>
              <option value="14:00">14:00</option>
              <option value="15:00">15:00</option>
              <option value="16:00">16:00</option>
              <option value="17:00">17:00</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Motivo da Visita</label>
            <textarea name="motivo" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-gray-800" placeholder="Conte o motivo da sua visita"></textarea>
          </div>
          
          <button type="submit" class="btn-primary w-full">
            <i class="fas fa-calendar-check mr-2"></i>Agendar Visita
          </button>
        </form>
      </div>
      
      <!-- Visit Info -->
      <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Informações sobre a Visita</h2>
        
        <div class="space-y-6">
          <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-teal-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock text-teal-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Duração</h3>
                <p class="text-sm text-gray-600">A visita dura aproximadamente 1 hora, incluindo passeio pelas instalações e apresentação.</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-users text-blue-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Quem pode visitar</h3>
                <p class="text-sm text-gray-600">Pais, responsáveis e interessados em conhecer nossa escola são bem-vindos.</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-map-marker-alt text-green-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800 mb-2 font-poppins">Localização</h3>
                <p class="text-sm text-gray-600">Rua da Escola, 123 - Bairro, Cidade - Estado</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
            <div class="flex items-start gap-4">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-info-circle text-purple-600 text-xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-800 mb-2 font-poppins">O que ver</h3>
                <p class="text-sm text-gray-600">Salas de aula, laboratórios, biblioteca, quadra esportiva e áreas de convivência.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

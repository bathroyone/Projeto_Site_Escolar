<?php
$pageTitle = 'Ex-Alunos';
require_once 'portal/config.php';

$success = '';
$error = '';

// Criar tabela de ex-alunos se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS ex_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    telefone VARCHAR(50),
    ano_conclusao INT NOT NULL,
    curso VARCHAR(255),
    profissao VARCHAR(255),
    linkedin VARCHAR(255),
    instagram VARCHAR(255),
    bio TEXT,
    aprovado TINYINT(1) DEFAULT 0,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Processar cadastro de ex-aluno
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $ano_conclusao = intval($_POST['ano_conclusao'] ?? 0);
    $curso = sanitizeInput($_POST['curso'] ?? '');
    $profissao = sanitizeInput($_POST['profissao'] ?? '');
    $linkedin = sanitizeInput($_POST['linkedin'] ?? '');
    $instagram = sanitizeInput($_POST['instagram'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');
    
    if (empty($nome) || empty($email) || $ano_conclusao === 0) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO ex_alunos (nome, email, telefone, ano_conclusao, curso, profissao, linkedin, instagram, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $telefone, $ano_conclusao, $curso, $profissao, $linkedin, $instagram, $bio]);
            $success = 'Cadastro realizado com sucesso! Seu perfil será aprovado em breve.';
        } catch (PDOException $e) {
            $error = 'Erro ao cadastrar. Este email já pode estar cadastrado.';
            error_log("Erro ao cadastrar ex-aluno: " . $e->getMessage());
        }
    }
}

// Obter ex-alunos aprovados
$ex_alunos = [];
try {
    $stmt = $pdo->query("SELECT * FROM ex_alunos WHERE aprovado = 1 ORDER BY ano_conclusao DESC LIMIT 20");
    $ex_alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter ex-alunos: " . $e->getMessage());
}
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="bg-gradient-to-br from-rose-500 via-rose-600 to-pink-700 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Portal de Ex-Alunos</h1>
      <p class="text-xl text-white/90">Conecte-se com nossa comunidade de formados</p>
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
      <!-- Registration Form -->
      <div class="bg-white rounded-2xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Cadastre-se</h2>
        
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
            <input type="text" name="nome" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent text-gray-800" placeholder="Digite seu nome">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
            <input type="email" name="email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent text-gray-800" placeholder="seu@email.com">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Telefone</label>
            <input type="tel" name="telefone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent text-gray-800" placeholder="(11) 12345-6789">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Ano de Conclusão *</label>
            <input type="number" name="ano_conclusao" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent text-gray-800" placeholder="2020">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Curso</label>
            <input type="text" name="curso" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent text-gray-800" placeholder="Ex: Ensino Médio">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Profissão</label>
            <input type="text" name="profissao" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent text-gray-800" placeholder="Sua profissão atual">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">LinkedIn</label>
            <input type="url" name="linkedin" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent text-gray-800" placeholder="https://linkedin.com/in/seu-perfil">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Instagram</label>
            <input type="text" name="instagram" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent text-gray-800" placeholder="@seu-instagram">
          </div>
          
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Biografia</label>
            <textarea name="bio" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-rose-500 focus:border-transparent text-gray-800" placeholder="Conte um pouco sobre sua trajetória"></textarea>
          </div>
          
          <button type="submit" class="btn-primary w-full">
            <i class="fas fa-user-plus mr-2"></i>Cadastrar
          </button>
        </form>
      </div>
      
      <!-- Alumni List -->
      <div>
        <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Nossos Ex-Alunos</h2>
        <div class="space-y-4">
          <?php if (!empty($ex_alunos)): ?>
            <?php foreach ($ex_alunos as $ex_aluno): ?>
              <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
                <div class="flex items-start gap-4">
                  <div class="w-12 h-12 bg-rose-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user-graduate text-rose-600 text-xl"></i>
                  </div>
                  <div class="flex-1">
                    <h3 class="font-semibold text-gray-800 mb-1 font-poppins"><?php echo htmlspecialchars($ex_aluno['nome']); ?></h3>
                    <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars($ex_aluno['profissao'] ?? 'Profissão não informada'); ?></p>
                    <div class="flex items-center gap-4 text-xs text-gray-500">
                      <span><i class="fas fa-calendar-alt mr-1"></i><?php echo $ex_aluno['ano_conclusao']; ?></span>
                      <?php if (!empty($ex_aluno['curso'])): ?>
                        <span><i class="fas fa-graduation-cap mr-1"></i><?php echo htmlspecialchars($ex_aluno['curso']); ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="bg-white rounded-xl p-8 text-center">
              <i class="fas fa-user-graduate text-4xl text-gray-300 mb-4"></i>
              <p class="text-gray-600">Nenhum ex-aluno cadastrado ainda.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

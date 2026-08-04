<?php
$pageTitle = 'Portal dos Pais';
require_once 'portal/config.php';

// Verificar se o usuário está logado
$isLoggedIn = isset($_SESSION['usuario_id']);
$userName = $_SESSION['nome'] ?? '';
$userType = $_SESSION['tipo_usuario'] ?? '';

// Obter informações do aluno se estiver logado
$aluno_info = null;
if ($isLoggedIn && $userType === 'aluno') {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
        $aluno_info = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Erro ao obter informações do aluno: " . $e->getMessage());
    }
}

// Obter comunicados recentes
$comunicados = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM comunicados WHERE ativo = 1 ORDER BY data_envio DESC LIMIT 5");
    $comunicados = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter comunicados: " . $e->getMessage());
}

// Obter eventos próximos
$eventos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM eventos WHERE data_evento >= CURDATE() ORDER BY data_evento ASC LIMIT 5");
    $eventos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter eventos: " . $e->getMessage());
}
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Portal dos Pais e Responsáveis',
  'sub'    => 'Acompanhe a vida escolar do seu filho, comunicados e informações importantes.',
  'icon'   => 'fas fa-users',
  'accent' => '#0284c7',
  'badge'  => 'Comunidade',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <?php if (!$isLoggedIn): ?>
      <!-- Login Section -->
      <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-8 mb-12">
        <div class="text-center mb-8">
          <h2 class="text-2xl font-bold text-white mb-2 font-poppins">Acesso ao Portal</h2>
          <p class="text-white/60">Faça login para acessar as informações do aluno</p>
        </div>
        <div class="max-w-md mx-auto">
          <a href="portal/login.php" class="btn-primary w-full text-center block">
            <i class="fas fa-sign-in-alt mr-2"></i>Fazer Login
          </a>
        </div>
      </div>
    <?php else: ?>
      <!-- Student Info Section -->
      <?php if ($aluno_info): ?>
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-8 mb-12">
          <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Informações do Aluno</h2>
          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <p class="text-sm text-white/60 mb-1">Nome</p>
              <p class="font-semibold text-white/90"><?php echo htmlspecialchars($aluno_info['nome']); ?></p>
            </div>
            <div>
              <p class="text-sm text-white/60 mb-1">Matrícula</p>
              <p class="font-semibold text-white/90"><?php echo htmlspecialchars($aluno_info['matricula']); ?></p>
            </div>
            <div>
              <p class="text-sm text-white/60 mb-1">Turma</p>
              <p class="font-semibold text-white/90"><?php echo htmlspecialchars($aluno_info['turma']); ?></p>
            </div>
            <div>
              <p class="text-sm text-white/60 mb-1">Série</p>
              <p class="font-semibold text-white/90"><?php echo htmlspecialchars($aluno_info['serie']); ?></p>
            </div>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    
    <!-- Comunicados Section -->
    <div class="mb-12">
      <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Comunicados Recentes</h2>
      <div class="space-y-4">
        <?php if (!empty($comunicados)): ?>
          <?php foreach ($comunicados as $comunicado): ?>
            <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                  <i class="fas fa-bullhorn text-blue-400"></i>
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-white/90 mb-2 font-poppins"><?php echo htmlspecialchars($comunicado['titulo']); ?></h3>
                  <p class="text-sm text-white/60 mb-2"><?php echo htmlspecialchars(substr($comunicado['conteudo'], 0, 150)); ?>...</p>
                  <p class="text-xs text-white/50"><?php echo date('d/m/Y H:i', strtotime($comunicado['data_envio'])); ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-white/60">Nenhum comunicado recente.</p>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Eventos Section -->
    <div>
      <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Próximos Eventos</h2>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($eventos)): ?>
          <?php foreach ($eventos as $evento): ?>
            <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
              <div class="flex items-center gap-2 mb-3">
                <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                  <i class="fas fa-calendar text-green-400"></i>
                </div>
                <span class="text-sm text-white/60"><?php echo date('d/m/Y', strtotime($evento['data_evento'])); ?></span>
              </div>
              <h3 class="font-semibold text-white/90 mb-2 font-poppins"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
              <p class="text-sm text-white/60"><?php echo htmlspecialchars(substr($evento['descricao'], 0, 100)); ?>...</p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-white/60 col-span-3">Nenhum evento próximo.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



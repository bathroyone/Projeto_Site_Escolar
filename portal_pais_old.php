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
<section class="bg-gradient-to-br from-green-600 via-green-700 to-teal-800 py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center text-white">
      <h1 class="text-4xl md:text-5xl font-bold mb-4 font-poppins">Portal dos Pais</h1>
      <p class="text-xl text-white/90">Acompanhe a vida escolar de seus filhos</p>
    </div>
  </div>
</section>

<!-- Main Content -->
<section class="py-16 bg-gray-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <?php if (!$isLoggedIn): ?>
      <!-- Login Section -->
      <div class="bg-white rounded-2xl shadow-lg p-8 mb-12">
        <div class="text-center mb-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-2 font-poppins">Acesso ao Portal</h2>
          <p class="text-gray-600">Faça login para acessar as informações do aluno</p>
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
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-12">
          <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Informações do Aluno</h2>
          <div class="grid md:grid-cols-2 gap-6">
            <div>
              <p class="text-sm text-gray-600 mb-1">Nome</p>
              <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($aluno_info['nome']); ?></p>
            </div>
            <div>
              <p class="text-sm text-gray-600 mb-1">Matrícula</p>
              <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($aluno_info['matricula']); ?></p>
            </div>
            <div>
              <p class="text-sm text-gray-600 mb-1">Turma</p>
              <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($aluno_info['turma']); ?></p>
            </div>
            <div>
              <p class="text-sm text-gray-600 mb-1">Série</p>
              <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($aluno_info['serie']); ?></p>
            </div>
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
    
    <!-- Comunicados Section -->
    <div class="mb-12">
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Comunicados Recentes</h2>
      <div class="space-y-4">
        <?php if (!empty($comunicados)): ?>
          <?php foreach ($comunicados as $comunicado): ?>
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
              <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                  <i class="fas fa-bullhorn text-blue-600"></i>
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-800 mb-2 font-poppins"><?php echo htmlspecialchars($comunicado['titulo']); ?></h3>
                  <p class="text-sm text-gray-600 mb-2"><?php echo htmlspecialchars(substr($comunicado['conteudo'], 0, 150)); ?>...</p>
                  <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($comunicado['data_envio'])); ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-gray-600">Nenhum comunicado recente.</p>
        <?php endif; ?>
      </div>
    </div>
    
    <!-- Eventos Section -->
    <div>
      <h2 class="text-2xl font-bold text-gray-900 mb-6 font-poppins">Próximos Eventos</h2>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($eventos)): ?>
          <?php foreach ($eventos as $evento): ?>
            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-all">
              <div class="flex items-center gap-2 mb-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                  <i class="fas fa-calendar text-green-600"></i>
                </div>
                <span class="text-sm text-gray-600"><?php echo date('d/m/Y', strtotime($evento['data_evento'])); ?></span>
              </div>
              <h3 class="font-semibold text-gray-800 mb-2 font-poppins"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
              <p class="text-sm text-gray-600"><?php echo htmlspecialchars(substr($evento['descricao'], 0, 100)); ?>...</p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-gray-600 col-span-3">Nenhum evento próximo.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
                        <i class="fas fa-book text-white text-xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Biblioteca</h3>
                    <p class="text-gray-400 text-sm">Biblioteca virtual</p>
                </a>

                <a href="agendar_visita.php" class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all group">
                    <div class="w-12 h-12 bg-gradient-to-br from-amarelo-destaque to-orange-500 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-calendar-check text-azul-escuro text-xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-1">Agendar Visita</h3>
                    <p class="text-gray-400 text-sm">Marque uma visita</p>
                </a>
            </div>
        </div>

        <!-- Comunicados Recentes -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-bullhorn mr-2 text-amarelo-destaque"></i>Comunicados Recentes
            </h2>
            <div class="bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20 overflow-hidden">
                <?php if (count($comunicados) > 0): ?>
                    <?php foreach ($comunicados as $comunicado): ?>
                        <div class="p-6 border-b border-white/10 last:border-b-0">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-white font-semibold mb-2"><?php echo htmlspecialchars($comunicado['titulo']); ?></h3>
                                    <p class="text-gray-400 text-sm"><?php echo htmlspecialchars(substr($comunicado['mensagem'], 0, 150)) . '...'; ?></p>
                                </div>
                                <span class="text-gray-500 text-xs whitespace-nowrap ml-4"><?php echo date('d/m/Y', strtotime($comunicado['data_envio'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-gray-400">
                        <i class="fas fa-bullhorn text-4xl mb-4"></i>
                        <p>Nenhum comunicado recente.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Próximos Eventos -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-calendar-alt mr-2 text-amarelo-destaque"></i>Próximos Eventos
            </h2>
            <div class="grid md:grid-cols-2 gap-4">
                <?php if (count($eventos) > 0): ?>
                    <?php foreach ($eventos as $evento): ?>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                            <div class="flex items-center gap-4">
                                <div class="bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl p-4 text-center min-w-[70px]">
                                    <span class="text-white font-bold text-2xl block"><?php echo date('d', strtotime($evento['data_evento'])); ?></span>
                                    <span class="text-white/80 text-xs uppercase"><?php echo date('M', strtotime($evento['data_evento'])); ?></span>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold mb-1"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                                    <p class="text-gray-400 text-sm"><?php echo date('H:i', strtotime($evento['data_evento'])); ?> - <?php echo htmlspecialchars($evento['local'] ?? 'Local não informado'); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-2 bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20 text-center text-gray-400">
                        <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                        <p>Nenhum evento próximo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Links Úteis -->
        <div>
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-link mr-2 text-amarelo-destaque"></i>Links Úteis
            </h2>
            <div class="grid md:grid-cols-3 gap-4">
                <a href="index.php#contact" class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                    <i class="fas fa-phone text-amarelo-destaque text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Contato</h3>
                    <p class="text-gray-400 text-sm">Entre em contato conosco</p>
                </a>

                <a href="pre_matricula.php" class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                    <i class="fas fa-user-graduate text-amarelo-destaque text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Pré-Matrícula</h3>
                    <p class="text-gray-400 text-sm">Faça a pré-matrícula online</p>
                </a>

                <a href="index.php" class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                    <i class="fas fa-home text-amarelo-destaque text-2xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Site Principal</h3>
                    <p class="text-gray-400 text-sm">Voltar ao site da escola</p>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-16 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-gray-400 text-sm">© <?php echo date('Y'); ?> [Inserir nome da escola aqui]. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = event.target.closest('button');
            if (!button && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

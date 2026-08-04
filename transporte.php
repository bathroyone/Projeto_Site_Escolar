<?php
$pageTitle = 'Transporte';
require_once 'portal/config.php';

// Criar tabela de rotas de transporte se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS rotas_transporte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    descricao TEXT,
    bairros_atendidos TEXT,
    horario_saida TIME NOT NULL,
    horario_chegada TIME,
    valor_mensal DECIMAL(10,2),
    telefone_contato VARCHAR(50),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter rotas de transporte
$rotas = [];
try {
    $stmt = $pdo->query("SELECT * FROM rotas_transporte WHERE ativo = 1 ORDER BY nome");
    $rotas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter rotas: " . $e->getMessage());
}
?>
<?php require_once 'includes/header.php'; ?>

<!-- Hero Section -->

<?php
$pageHero = [
  'title'  => 'Transporte Escolar',
  'sub'    => 'Rotas, horários e informações sobre nosso serviço de transporte seguro e confortável.',
  'icon'   => 'fas fa-bus',
  'accent' => '#16a34a',
  'badge'  => 'Serviços',
];
require_once 'includes/page_hero.php';
?>

<!-- Main Content -->
<section class="py-16 bg-[#030814]">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    
    <!-- Transport Info -->
    <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.5)] p-8 mb-12">
      <h2 class="text-2xl font-bold text-white mb-4 font-poppins">Nosso Serviço de Transporte</h2>
      <p class="text-white/60 mb-6">Oferecemos transporte escolar seguro e confortável para os alunos, com motoristas qualificados e veículos equipados com todos os requisitos de segurança exigidos.</p>
      
      <div class="grid md:grid-cols-3 gap-6">
        <div class="text-center">
          <div class="w-16 h-16 bg-yellow-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-shield-alt text-yellow-400 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-2 font-poppins">Segurança</h3>
          <p class="text-sm text-white/60">Veículos monitorados</p>
        </div>
        
        <div class="text-center">
          <div class="w-16 h-16 bg-blue-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-user-check text-blue-400 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-2 font-poppins">Motoristas</h3>
          <p class="text-sm text-white/60">Profissionais qualificados</p>
        </div>
        
        <div class="text-center">
          <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-clock text-green-400 text-2xl"></i>
          </div>
          <h3 class="font-semibold text-white/90 mb-2 font-poppins">Pontualidade</h3>
          <p class="text-sm text-white/60">Horários estabelecidos</p>
        </div>
      </div>
    </div>
    
    <!-- Routes Section -->
    <div>
      <h2 class="text-2xl font-bold text-white mb-6 font-poppins">Rotas Disponíveis</h2>
      <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (!empty($rotas)): ?>
          <?php foreach ($rotas as $rota): ?>
            <div class="bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-6 shadow-[0_4px_20px_rgb(0,0,0,0.3)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.5)] transition-all">
              <div class="flex items-start gap-4 mb-4">
                <div class="w-12 h-12 bg-yellow-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                  <i class="fas fa-bus text-yellow-400 text-xl"></i>
                </div>
                <div>
                  <h3 class="font-semibold text-white/90 mb-1 font-poppins"><?php echo htmlspecialchars($rota['nome']); ?></h3>
                  <p class="text-sm text-white/60"><?php echo htmlspecialchars($rota['descricao']); ?></p>
                </div>
              </div>
              
              <div class="space-y-2 text-sm">
                <div class="flex items-center gap-2 text-white/60">
                  <i class="fas fa-map-marker-alt text-gray-400"></i>
                  <span><?php echo htmlspecialchars($rota['bairros_atendidos']); ?></span>
                </div>
                <div class="flex items-center gap-2 text-white/60">
                  <i class="fas fa-clock text-gray-400"></i>
                  <span>Saída: <?php echo date('H:i', strtotime($rota['horario_saida'])); ?></span>
                </div>
                <?php if (!empty($rota['valor_mensal'])): ?>
                  <div class="flex items-center gap-2 text-white/60">
                    <i class="fas fa-dollar-sign text-gray-400"></i>
                    <span>R$ <?php echo number_format($rota['valor_mensal'], 2, ',', '.'); ?>/mês</span>
                  </div>
                <?php endif; ?>
              </div>
              
              <?php if (!empty($rota['telefone_contato'])): ?>
                <div class="mt-4 pt-4 border-t border-white/5">
                  <a href="tel:<?php echo htmlspecialchars($rota['telefone_contato']); ?>" class="text-sm text-blue-400 hover:text-blue-700 font-medium">
                    <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($rota['telefone_contato']); ?>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-span-3 bg-white/5 border border-white/10 backdrop-blur-sm rounded-xl p-8 text-center">
            <i class="fas fa-bus text-4xl text-gray-300 mb-4"></i>
            <p class="text-white/60">Nenhuma rota cadastrada no momento.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>



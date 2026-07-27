<?php
require_once 'portal/config.php';

// Criar tabela de calendário escolar se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS calendario_escolar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    tipo ENUM('feriado', 'evento', 'prova', 'reuniao', 'outro') DEFAULT 'evento',
    turma VARCHAR(100),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter eventos do calendário
$eventos = [];
try {
    $stmt = $pdo->query("SELECT * FROM calendario_escolar WHERE ativo = 1 AND (data_inicio >= CURDATE() OR data_fim >= CURDATE()) ORDER BY data_inicio ASC LIMIT 50");
    $eventos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter calendário: " . $e->getMessage());
}

// Obter feriados
$feriados = [];
try {
    $stmt = $pdo->query("SELECT * FROM calendario_escolar WHERE tipo = 'feriado' AND ativo = 1 AND YEAR(data_inicio) = YEAR(CURDATE()) ORDER BY data_inicio ASC");
    $feriados = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter feriados: " . $e->getMessage());
}

$tipos_eventos = [
    'feriado' => 'Feriado',
    'evento' => 'Evento',
    'prova' => 'Prova',
    'reuniao' => 'Reunião',
    'outro' => 'Outro'
];

$cores_tipos = [
    'feriado' => 'bg-red-100 text-red-600 border-red-200',
    'evento' => 'bg-blue-100 text-blue-600 border-blue-200',
    'prova' => 'bg-purple-100 text-purple-600 border-purple-200',
    'reuniao' => 'bg-green-100 text-green-600 border-green-200',
    'outro' => 'bg-gray-100 text-gray-600 border-gray-200'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário Escolar | Site da Escola</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-azul-principal to-verde-complementar shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 group">
                        <img src="img/logo.jpg" alt="Logo" class="h-12">
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs tracking-wide">CALENDÁRIO</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">ESCOLAR</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="index.php" class="px-6 py-2.5 bg-white/20 text-white rounded-full font-semibold hover:bg-white/30 transition-all">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Banner -->
        <div class="bg-gradient-to-r from-azul-principal to-verde-complementar rounded-3xl p-8 mb-12 text-center">
            <h1 class="text-3xl md:text-4xl font-display font-bold text-white mb-4">
                <i class="fas fa-calendar-alt mr-3"></i>Calendário Escolar
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Consulte o calendário escolar com feriados, eventos, provas e reuniões.
            </p>
        </div>

        <!-- Filtros -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 mb-8 border border-white/20">
            <div class="flex flex-wrap gap-4 items-center">
                <span class="text-white font-semibold">Filtrar por tipo:</span>
                <button onclick="filtrarTipo('todos')" class="px-4 py-2 bg-amarelo-destaque text-azul-escuro rounded-full font-semibold text-sm hover:bg-amarelo-claro transition-colors">
                    Todos
                </button>
                <?php foreach ($tipos_eventos as $tipo => $label): ?>
                    <button onclick="filtrarTipo('<?php echo $tipo; ?>')" class="px-4 py-2 bg-white/10 text-white rounded-full font-semibold text-sm hover:bg-white/20 transition-colors">
                        <?php echo $label; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Feriados do Ano -->
        <?php if (count($feriados) > 0): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas fa-umbrella-beach mr-2 text-amarelo-destaque"></i>Feriados de <?php echo date('Y'); ?>
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <?php foreach ($feriados as $feriado): ?>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                            <div class="flex items-center gap-4">
                                <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-4 text-center min-w-[70px]">
                                    <span class="text-white font-bold text-2xl block"><?php echo date('d', strtotime($feriado['data_inicio'])); ?></span>
                                    <span class="text-white/80 text-xs uppercase"><?php echo date('M', strtotime($feriado['data_inicio'])); ?></span>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold mb-1"><?php echo htmlspecialchars($feriado['titulo']); ?></h3>
                                    <p class="text-gray-400 text-sm"><?php echo htmlspecialchars(substr($feriado['descricao'] ?? '-', 0, 50)); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Lista de Eventos -->
        <div>
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-calendar-check mr-2 text-amarelo-destaque"></i>Próximos Eventos
            </h2>
            
            <div class="space-y-4" id="eventos-lista">
                <?php if (count($eventos) > 0): ?>
                    <?php foreach ($eventos as $evento): ?>
                        <div class="evento-item bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20" data-tipo="<?php echo $evento['tipo']; ?>">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl p-4 text-center min-w-[70px] flex-shrink-0">
                                        <span class="text-white font-bold text-2xl block"><?php echo date('d', strtotime($evento['data_inicio'])); ?></span>
                                        <span class="text-white/80 text-xs uppercase"><?php echo date('M', strtotime($evento['data_inicio'])); ?></span>
                                    </div>
                                    <div>
                                        <h3 class="text-white font-semibold mb-2"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                                        <?php if ($evento['descricao']): ?>
                                            <p class="text-gray-400 text-sm mb-2"><?php echo htmlspecialchars($evento['descricao']); ?></p>
                                        <?php endif; ?>
                                        <div class="flex flex-wrap gap-2 items-center">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold border <?php echo $cores_tipos[$evento['tipo']]; ?>">
                                                <?php echo $tipos_eventos[$evento['tipo']]; ?>
                                            </span>
                                            <?php if ($evento['turma']): ?>
                                                <span class="text-gray-400 text-xs">
                                                    <i class="fas fa-users mr-1"></i><?php echo htmlspecialchars($evento['turma']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($evento['data_fim'] && $evento['data_fim'] != $evento['data_inicio']): ?>
                                                <span class="text-gray-400 text-xs">
                                                    <i class="fas fa-clock mr-1"></i><?php echo date('d/m', strtotime($evento['data_inicio'])); ?> a <?php echo date('d/m/Y', strtotime($evento['data_fim'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-12 text-gray-400">
                        <i class="fas fa-calendar-alt text-4xl mb-4"></i>
                        <p class="text-lg">Nenhum evento cadastrado para os próximos dias.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Informações -->
        <div class="mt-16 bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-info-circle mr-2 text-amarelo-destaque"></i>Informações Importantes
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <i class="fas fa-bell text-amarelo-destaque text-3xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-2">Avisos</h3>
                    <p class="text-gray-400 text-sm">Fique atento aos comunicados enviados pela secretaria.</p>
                </div>
                <div class="text-center">
                    <i class="fas fa-phone text-amarelo-destaque text-3xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-2">Dúvidas</h3>
                    <p class="text-gray-400 text-sm">Entre em contato com a secretaria para esclarecimentos.</p>
                </div>
                <div class="text-center">
                    <i class="fas fa-sync text-amarelo-destaque text-3xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-2">Atualizações</h3>
                    <p class="text-gray-400 text-sm">O calendário é atualizado regularmente.</p>
                </div>
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
        function filtrarTipo(tipo) {
            const itens = document.querySelectorAll('.evento-item');
            
            itens.forEach(item => {
                if (tipo === 'todos' || item.dataset.tipo === tipo) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

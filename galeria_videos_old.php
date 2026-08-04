<?php
require_once 'portal/config.php';

// Criar tabela de vídeos se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS videos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    url VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255),
    categoria ENUM('evento', 'aula', 'institucional', 'outro') DEFAULT 'outro',
    data_publicacao DATE,
    visualizacoes INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter vídeos
$videos = [];
try {
    $stmt = $pdo->query("SELECT * FROM videos WHERE ativo = 1 ORDER BY created_at DESC LIMIT 20");
    $videos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter vídeos: " . $e->getMessage());
}

// Agrupar por categoria
$videos_por_categoria = [];
foreach ($videos as $video) {
    $videos_por_categoria[$video['categoria']][] = $video;
}

$nomes_categorias = [
    'evento' => 'Eventos',
    'aula' => 'Aulas',
    'institucional' => 'Institucional',
    'outro' => 'Outros'
];

$icones_categorias = [
    'evento' => 'fa-calendar-alt',
    'aula' => 'fa-chalkboard-teacher',
    'institucional' => 'fa-building',
    'outro' => 'fa-video'
];

$cores_categorias = [
    'evento' => 'from-blue-500 to-blue-600',
    'aula' => 'from-purple-500 to-purple-600',
    'institucional' => 'from-green-500 to-green-600',
    'outro' => 'from-gray-500 to-gray-600'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeria de Vídeos | Site da Escola</title>
    <link rel="stylesheet" href="css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-azul-principal to-verde-complementar shadow-[0_8px_30px_rgb(0,0,0,0.5)] sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 group">
                        <img src="img/logo.jpg" alt="Logo" class="h-12">
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs tracking-wide">GALERIA DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">VÍDEOS</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <a href="index.php" class="px-6 py-2.5 bg-white/5 border border-white/10 backdrop-blur-sm/20 text-white rounded-full font-semibold hover:bg-white/5 border border-white/10 backdrop-blur-sm/30 transition-all">
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
                <i class="fas fa-video mr-3"></i>Galeria de Vídeos
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Assista a vídeos de eventos, aulas e institucionais da escola.
            </p>
        </div>

        <!-- Vídeos por Categoria -->
        <?php foreach ($videos_por_categoria as $categoria => $items): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas <?php echo $icones_categorias[$categoria]; ?> mr-2 text-amarelo-destaque"></i><?php echo $nomes_categorias[$categoria] ?? $categoria; ?>
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($items as $video): ?>
                        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl overflow-hidden border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                            <div class="relative aspect-video bg-gray-800">
                                <?php if ($video['thumbnail']): ?>
                                    <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="<?php echo htmlspecialchars($video['titulo']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-azul-principal to-verde-complementar">
                                        <i class="fas fa-play-circle text-white text-6xl opacity-50"></i>
                                    </div>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars($video['url']); ?>" target="_blank" class="absolute inset-0 flex items-center justify-center bg-black/40 hover:bg-black/30 transition-colors">
                                    <div class="w-16 h-16 bg-white/5 border border-white/10 backdrop-blur-sm/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                                        <i class="fas fa-play text-white text-2xl ml-1"></i>
                                    </div>
                                </a>
                            </div>
                            <div class="p-4">
                                <h3 class="text-white font-semibold mb-2"><?php echo htmlspecialchars($video['titulo']); ?></h3>
                                <?php if ($video['descricao']): ?>
                                    <p class="text-gray-400 text-sm mb-3"><?php echo htmlspecialchars(substr($video['descricao'], 0, 80)); ?>...</p>
                                <?php endif; ?>
                                <div class="flex items-center justify-between">
                                    <span class="text-white/50 text-xs">
                                        <i class="fas fa-eye mr-1"></i><?php echo $video['visualizacoes']; ?> visualizações
                                    </span>
                                    <?php if ($video['data_publicacao']): ?>
                                        <span class="text-white/50 text-xs"><?php echo date('d/m/Y', strtotime($video['data_publicacao'])); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($videos)): ?>
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-video text-4xl mb-4"></i>
                <p class="text-lg">Nenhum vídeo disponível ainda.</p>
            </div>
        <?php endif; ?>

        <!-- Informações -->
        <div class="mt-16 bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-info-circle mr-2 text-amarelo-destaque"></i>Informações
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-calendar-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Eventos</h3>
                    <p class="text-gray-400 text-sm">Vídeos de eventos e celebrações da escola.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Aulas</h3>
                    <p class="text-gray-400 text-sm">Conteúdo educacional e aulas gravadas.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-building text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Institucional</h3>
                    <p class="text-gray-400 text-sm">Vídeos institucionais e apresentações.</p>
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
</body>
</html>


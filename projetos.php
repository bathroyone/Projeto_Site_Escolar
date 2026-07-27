<?php
require_once 'portal/config.php';

// Criar tabela de projetos se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS projetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    imagem VARCHAR(255),
    categoria ENUM('pedagogico', 'social', 'ambiental', 'cultural', 'tecnologico', 'outro') DEFAULT 'outro',
    status ENUM('em_andamento', 'concluido', 'planejado') DEFAULT 'em_andamento',
    data_inicio DATE,
    data_fim DATE,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter projetos
$projetos = [];
try {
    $stmt = $pdo->query("SELECT * FROM projetos WHERE ativo = 1 ORDER BY created_at DESC LIMIT 20");
    $projetos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter projetos: " . $e->getMessage());
}

// Agrupar por categoria
$projetos_por_categoria = [];
foreach ($projetos as $projeto) {
    $projetos_por_categoria[$projeto['categoria']][] = $projeto;
}

$nomes_categorias = [
    'pedagogico' => 'Pedagógico',
    'social' => 'Social',
    'ambiental' => 'Ambiental',
    'cultural' => 'Cultural',
    'tecnologico' => 'Tecnológico',
    'outro' => 'Outros'
];

$icones_categorias = [
    'pedagogico' => 'fa-graduation-cap',
    'social' => 'fa-users',
    'ambiental' => 'fa-leaf',
    'cultural' => 'fa-palette',
    'tecnologico' => 'fa-laptop',
    'outro' => 'fa-project-diagram'
];

$cores_categorias = [
    'pedagogico' => 'from-blue-500 to-blue-600',
    'social' => 'from-purple-500 to-purple-600',
    'ambiental' => 'from-green-500 to-green-600',
    'cultural' => 'from-pink-500 to-pink-600',
    'tecnologico' => 'from-cyan-500 to-cyan-600',
    'outro' => 'from-gray-500 to-gray-600'
];

$status_projeto = [
    'em_andamento' => 'Em Andamento',
    'concluido' => 'Concluído',
    'planejado' => 'Planejado'
];

$cores_status = [
    'em_andamento' => 'bg-blue-100 text-blue-600',
    'concluido' => 'bg-green-100 text-green-600',
    'planejado' => 'bg-yellow-100 text-yellow-600'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projetos e Iniciativas | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">PROJETOS E</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">INICIATIVAS</span>
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
                <i class="fas fa-project-diagram mr-3"></i>Projetos e Iniciativas
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
            Conheça os projetos e iniciativas que transformam a educação e a comunidade.
            </p>
        </div>

        <!-- Projetos por Categoria -->
        <?php foreach ($projetos_por_categoria as $categoria => $items): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas <?php echo $icones_categorias[$categoria]; ?> mr-2 text-amarelo-destaque"></i><?php echo $nomes_categorias[$categoria] ?? $categoria; ?>
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($items as $projeto): ?>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl overflow-hidden border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                            <?php if ($projeto['imagem']): ?>
                                <div class="h-48 bg-gray-800">
                                    <img src="<?php echo htmlspecialchars($projeto['imagem']); ?>" alt="<?php echo htmlspecialchars($projeto['titulo']); ?>" class="w-full h-full object-cover">
                                </div>
                            <?php else: ?>
                                <div class="h-48 bg-gradient-to-br from-azul-principal to-verde-complementar flex items-center justify-center">
                                    <i class="fas <?php echo $icones_categorias[$categoria]; ?> text-white text-5xl opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $cores_status[$projeto['status']]; ?>">
                                        <?php echo $status_projeto[$projeto['status']]; ?>
                                    </span>
                                </div>
                                <h3 class="text-white font-semibold text-lg mb-2"><?php echo htmlspecialchars($projeto['titulo']); ?></h3>
                                <p class="text-gray-400 text-sm mb-4"><?php echo htmlspecialchars(substr($projeto['descricao'], 0, 100)); ?>...</p>
                                <?php if ($projeto['data_inicio'] && $projeto['data_fim']): ?>
                                    <div class="flex items-center gap-2 text-gray-500 text-xs">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('d/m/Y', strtotime($projeto['data_inicio'])); ?> a <?php echo date('d/m/Y', strtotime($projeto['data_fim'])); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($projetos)): ?>
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-project-diagram text-4xl mb-4"></i>
                <p class="text-lg">Nenhum projeto cadastrado ainda.</p>
            </div>
        <?php endif; ?>

        <!-- Informações -->
        <div class="mt-16 bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-lightbulb mr-2 text-amarelo-destaque"></i>Nossos Pilares
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-graduation-cap text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Pedagógico</h3>
                    <p class="text-gray-400 text-sm">Projetos focados no desenvolvimento educacional.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Social</h3>
                    <p class="text-gray-400 text-sm">Iniciativas para impacto na comunidade.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-leaf text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Ambiental</h3>
                    <p class="text-gray-400 text-sm">Projetos de sustentabilidade e meio ambiente.</p>
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

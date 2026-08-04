<?php
require_once 'portal/config.php';

// Criar tabela de recursos educacionais se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS recursos_educacionais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    link VARCHAR(255),
    arquivo VARCHAR(255),
    categoria ENUM('material', 'video', 'livro', 'aplicativo', 'outro') DEFAULT 'outro',
    serie VARCHAR(100),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter recursos educacionais
$recursos = [];
try {
    $stmt = $pdo->query("SELECT * FROM recursos_educacionais WHERE ativo = 1 ORDER BY categoria, titulo");
    $recursos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter recursos: " . $e->getMessage());
}

// Agrupar por categoria
$recursos_por_categoria = [];
foreach ($recursos as $recurso) {
    $recursos_por_categoria[$recurso['categoria']][] = $recurso;
}

$nomes_categorias = [
    'material' => 'Materiais Didáticos',
    'video' => 'Vídeos Educacionais',
    'livro' => 'Livros e Leitura',
    'aplicativo' => 'Aplicativos',
    'outro' => 'Outros Recursos'
];

$icones_categorias = [
    'material' => 'fa-file-alt',
    'video' => 'fa-video',
    'livro' => 'fa-book',
    'aplicativo' => 'fa-mobile-alt',
    'outro' => 'fa-folder'
];

$cores_categorias = [
    'material' => 'from-blue-500 to-blue-600',
    'video' => 'from-red-500 to-red-600',
    'livro' => 'from-green-500 to-green-600',
    'aplicativo' => 'from-purple-500 to-purple-600',
    'outro' => 'from-gray-500 to-gray-600'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recursos Educacionais | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">RECURSOS</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">EDUCACIONAIS</span>
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
                <i class="fas fa-graduation-cap mr-3"></i>Recursos Educacionais
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Acesse materiais didáticos, vídeos, livros e outros recursos para complementar o aprendizado.
            </p>
        </div>

        <!-- Recursos por Categoria -->
        <?php foreach ($recursos_por_categoria as $categoria => $items): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas <?php echo $icones_categorias[$categoria]; ?> mr-2 text-amarelo-destaque"></i><?php echo $nomes_categorias[$categoria] ?? $categoria; ?>
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($items as $recurso): ?>
                        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br <?php echo $cores_categorias[$categoria]; ?> rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fas <?php echo $icones_categorias[$categoria]; ?> text-white text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-white font-semibold mb-2"><?php echo htmlspecialchars($recurso['titulo']); ?></h3>
                                    <?php if ($recurso['descricao']): ?>
                                        <p class="text-gray-400 text-sm mb-3"><?php echo htmlspecialchars(substr($recurso['descricao'], 0, 80)); ?></p>
                                    <?php endif; ?>
                                    <?php if ($recurso['serie']): ?>
                                        <span class="inline-block px-2 py-1 bg-white/5 border border-white/10 backdrop-blur-sm/10 rounded-full text-xs text-gray-300 mb-3">
                                            <?php echo htmlspecialchars($recurso['serie']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <div class="flex items-center gap-2">
                                        <?php if ($recurso['link']): ?>
                                            <a href="<?php echo htmlspecialchars($recurso['link']); ?>" target="_blank" class="px-4 py-2 bg-amarelo-destaque text-azul-escuro rounded-full font-semibold text-sm hover:bg-amarelo-claro transition-colors">
                                                <i class="fas fa-external-link-alt mr-1"></i>Acessar
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($recurso['arquivo']): ?>
                                            <a href="<?php echo htmlspecialchars($recurso['arquivo']); ?>" download class="px-4 py-2 bg-white/5 border border-white/10 backdrop-blur-sm/10 text-white rounded-full font-semibold text-sm hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 transition-colors">
                                                <i class="fas fa-download mr-1"></i>Baixar
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($recursos)): ?>
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-folder-open text-4xl mb-4"></i>
                <p class="text-lg">Nenhum recurso educacional disponível ainda.</p>
            </div>
        <?php endif; ?>

        <!-- Dicas de Estudo -->
        <div class="mt-16 bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-lightbulb mr-2 text-amarelo-destaque"></i>Dicas de Estudo
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Organize seu Tempo</h3>
                    <p class="text-gray-400 text-sm">Crie um cronograma de estudos e mantenha-se disciplinado.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-book-open text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Leia Regularmente</h3>
                    <p class="text-gray-400 text-sm">A leitura constante melhora a compreensão e o vocabulário.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-question-circle text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Tire Dúvidas</h3>
                    <p class="text-gray-400 text-sm">Não hesite em perguntar aos professores quando tiver dúvidas.</p>
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


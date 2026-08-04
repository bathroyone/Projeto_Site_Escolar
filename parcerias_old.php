<?php
require_once 'portal/config.php';

// Criar tabela de parcerias se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS parcerias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_empresa VARCHAR(255) NOT NULL,
    descricao TEXT,
    logo VARCHAR(255),
    tipo ENUM('educacional', 'cultural', 'tecnologico', 'social', 'outro') DEFAULT 'outro',
    website VARCHAR(255),
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter parcerias
$parcerias = [];
try {
    $stmt = $pdo->query("SELECT * FROM parcerias WHERE ativo = 1 ORDER BY created_at DESC LIMIT 20");
    $parcerias = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter parcerias: " . $e->getMessage());
}

// Agrupar por categoria
$parcerias_por_categoria = [];
foreach ($parcerias as $parceria) {
    $parcerias_por_categoria[$parceria['tipo']][] = $parceria;
}

$nomes_categorias = [
    'educacional' => 'Educacional',
    'cultural' => 'Cultural',
    'tecnologico' => 'Tecnológico',
    'social' => 'Social',
    'outro' => 'Outros'
];

$icones_categorias = [
    'educacional' => 'fa-graduation-cap',
    'cultural' => 'fa-palette',
    'tecnologico' => 'fa-laptop',
    'social' => 'fa-users',
    'outro' => 'fa-handshake'
];

$cores_categorias = [
    'educacional' => 'from-blue-500 to-blue-600',
    'cultural' => 'from-pink-500 to-pink-600',
    'tecnologico' => 'from-cyan-500 to-cyan-600',
    'social' => 'from-purple-500 to-purple-600',
    'outro' => 'from-gray-500 to-gray-600'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parcerias e Convênios | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">PARCERIAS E</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">CONVÊNIOS</span>
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
                <i class="fas fa-handshake mr-3"></i>Parcerias e Convênios
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Conheça as empresas e instituições que contribuem para o desenvolvimento da nossa escola.
            </p>
        </div>

        <!-- Parcerias por Categoria -->
        <?php foreach ($parcerias_por_categoria as $categoria => $items): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas <?php echo $icones_categorias[$categoria]; ?> mr-2 text-amarelo-destaque"></i><?php echo $nomes_categorias[$categoria] ?? $categoria; ?>
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($items as $parceria): ?>
                        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                            <div class="flex items-start gap-4 mb-4">
                                <div class="w-16 h-16 bg-gradient-to-br <?php echo $cores_categorias[$categoria]; ?> rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fas <?php echo $icones_categorias[$categoria]; ?> text-white text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold text-lg"><?php echo htmlspecialchars($parceria['nome_empresa']); ?></h3>
                                </div>
                            </div>
                            <?php if ($parceria['descricao']): ?>
                                <p class="text-gray-400 text-sm mb-4"><?php echo htmlspecialchars(substr($parceria['descricao'], 0, 100)); ?>...</p>
                            <?php endif; ?>
                            <?php if ($parceria['website']): ?>
                                <a href="<?php echo htmlspecialchars($parceria['website']); ?>" target="_blank" class="inline-block px-4 py-2 bg-white/5 border border-white/10 backdrop-blur-sm/10 text-white rounded-full text-sm hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 transition-colors">
                                    <i class="fas fa-external-link-alt mr-1"></i>Visitar Site
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($parcerias)): ?>
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-handshake text-4xl mb-4"></i>
                <p class="text-lg">Nenhuma parceria cadastrada ainda.</p>
            </div>
        <?php endif; ?>

        <!-- Informações -->
        <div class="mt-16 bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 border border-white-20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-info-circle mr-2 text-amarelo-destaque"></i>Seja um Parceiro
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lightbulb text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Inovação</h3>
                    <p class="text-gray-400 text-sm">Trazemos soluções inovadoras para a educação.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Comunidade</h3>
                    <p class="text-gray-400 text-sm">Fortalecemos laços com a comunidade local.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Crescimento</h3>
                    <p class="text-gray-400 text-sm">Crescemos juntos com parcerias estratégicas.</p>
                </div>
            </div>
            <div class="text-center mt-8">
                <a href="index.php#contact" class="inline-block px-8 py-3 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-full font-semibold hover:shadow-xl hover:shadow-yellow-500/30 transition-all">
                    <i class="fas fa-envelope mr-2"></i>Entre em Contato
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
</body>
</html>


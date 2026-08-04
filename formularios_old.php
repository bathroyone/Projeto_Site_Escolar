<?php
require_once 'portal/config.php';

// Criar tabela de formulários se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS formularios_publicos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    arquivo VARCHAR(255) NOT NULL,
    categoria ENUM('matricula', 'financeiro', 'pedagogico', 'outro') DEFAULT 'outro',
    downloads INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter formulários
$formularios = [];
try {
    $stmt = $pdo->query("SELECT * FROM formularios_publicos WHERE ativo = 1 ORDER BY categoria, titulo");
    $formularios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter formulários: " . $e->getMessage());
}

// Agrupar por categoria
$formularios_por_categoria = [];
foreach ($formularios as $formulario) {
    $formularios_por_categoria[$formulario['categoria']][] = $formulario;
}

$nomes_categorias = [
    'matricula' => 'Matrícula',
    'financeiro' => 'Financeiro',
    'pedagogico' => 'Pedagógico',
    'outro' => 'Outros'
];

$icones_categorias = [
    'matricula' => 'fa-user-graduate',
    'financeiro' => 'fa-file-invoice-dollar',
    'pedagogico' => 'fa-book',
    'outro' => 'fa-file-alt'
];

$cores_categorias = [
    'matricula' => 'from-blue-500 to-blue-600',
    'financeiro' => 'from-green-500 to-green-600',
    'pedagogico' => 'from-purple-500 to-purple-600',
    'outro' => 'from-gray-500 to-gray-600'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulários | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">DOWNLOAD DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">FORMULÁRIOS</span>
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
                <i class="fas fa-download mr-3"></i>Download de Formulários
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Baixe os formulários necessários para matrícula, financeiro e outros processos da escola.
            </p>
        </div>

        <!-- Formulários por Categoria -->
        <?php foreach ($formularios_por_categoria as $categoria => $items): ?>
            <div class="mb-12">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas <?php echo $icones_categorias[$categoria]; ?> mr-2 text-amarelo-destaque"></i><?php echo $nomes_categorias[$categoria] ?? $categoria; ?>
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($items as $formulario): ?>
                        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 hover:border-amarelo-destaque/50 transition-all">
                            <div class="flex items-start gap-4">
                                <div class="w-12 h-12 bg-gradient-to-br <?php echo $cores_categorias[$categoria]; ?> rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-file-pdf text-white text-xl"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-white font-semibold mb-2"><?php echo htmlspecialchars($formulario['titulo']); ?></h3>
                                    <?php if ($formulario['descricao']): ?>
                                        <p class="text-gray-400 text-sm mb-3"><?php echo htmlspecialchars(substr($formulario['descricao'], 0, 80)); ?></p>
                                    <?php endif; ?>
                                    <div class="flex items-center justify-between">
                                        <span class="text-white/50 text-xs">
                                            <i class="fas fa-download mr-1"></i><?php echo $formulario['downloads']; ?> downloads
                                        </span>
                                        <a href="<?php echo htmlspecialchars($formulario['arquivo']); ?>" download class="px-4 py-2 bg-amarelo-destaque text-azul-escuro rounded-full font-semibold text-sm hover:bg-amarelo-claro transition-colors">
                                            Baixar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($formularios)): ?>
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-file-alt text-4xl mb-4"></i>
                <p class="text-lg">Nenhum formulário disponível para download.</p>
            </div>
        <?php endif; ?>

        <!-- Informações -->
        <div class="mt-16 bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-info-circle mr-2 text-amarelo-destaque"></i>Instruções
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-download text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">1. Baixar</h3>
                    <p class="text-gray-400 text-sm">Clique no botão "Baixar" para obter o formulário.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-edit text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">2. Preencher</h3>
                    <p class="text-gray-400 text-sm">Preencha o formulário com as informações necessárias.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-paper-plane text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">3. Entregar</h3>
                    <p class="text-gray-400 text-sm">Entregue o formulário preenchido na secretaria.</p>
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


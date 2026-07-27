<?php
require_once 'portal/config.php';

// Criar tabela de FAQ se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pergunta VARCHAR(255) NOT NULL,
    resposta TEXT NOT NULL,
    categoria ENUM('geral', 'matricula', 'financeiro', 'pedagogico', 'tecnico') DEFAULT 'geral',
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Obter FAQs
$faqs = [];
try {
    $stmt = $pdo->query("SELECT * FROM faq WHERE ativo = 1 ORDER BY ordem ASC, id ASC");
    $faqs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter FAQs: " . $e->getMessage());
}

// Agrupar por categoria
$faq_por_categoria = [];
foreach ($faqs as $faq) {
    $faq_por_categoria[$faq['categoria']][] = $faq;
}

$nomes_categorias = [
    'geral' => 'Geral',
    'matricula' => 'Matrícula',
    'financeiro' => 'Financeiro',
    'pedagogico' => 'Pedagógico',
    'tecnico' => 'Técnico'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ e Suporte | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">FAQ E</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">SUPORTE</span>
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
                <i class="fas fa-question-circle mr-3"></i>Perguntas Frequentes
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Encontre respostas para as dúvidas mais comuns sobre matrículas, pagamentos e mais.
            </p>
        </div>

        <!-- Busca -->
        <div class="mb-12">
            <div class="relative">
                <input type="text" id="search-faq" placeholder="Digite sua dúvida..." class="w-full px-6 py-4 bg-white/10 border border-white/20 rounded-2xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent">
                <i class="fas fa-search absolute right-6 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>

        <!-- FAQs por Categoria -->
        <?php foreach ($faq_por_categoria as $categoria => $items): ?>
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas fa-folder mr-2 text-amarelo-destaque"></i><?php echo $nomes_categorias[$categoria] ?? $categoria; ?>
                </h2>
                <div class="space-y-4">
                    <?php foreach ($items as $faq): ?>
                        <div class="faq-item bg-white/10 backdrop-blur-sm rounded-2xl border border-white/20 overflow-hidden">
                            <button onclick="toggleFaq(<?php echo $faq['id']; ?>)" class="w-full px-6 py-4 flex items-center justify-between text-left hover:bg-white/5 transition-colors">
                                <span class="text-white font-semibold"><?php echo htmlspecialchars($faq['pergunta']); ?></span>
                                <i class="fas fa-chevron-down text-amarelo-destaque transition-transform" id="icon-<?php echo $faq['id']; ?>"></i>
                            </button>
                            <div id="faq-<?php echo $faq['id']; ?>" class="hidden px-6 pb-4">
                                <p class="text-gray-300"><?php echo nl2br(htmlspecialchars($faq['resposta'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($faqs)): ?>
            <div class="text-center py-12 text-gray-400">
                <i class="fas fa-question-circle text-4xl mb-4"></i>
                <p class="text-lg">Nenhuma pergunta cadastrada ainda.</p>
            </div>
        <?php endif; ?>

        <!-- Contato para Suporte -->
        <div class="mt-16 bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-headset mr-2 text-amarelo-destaque"></i>Não encontrou sua resposta?
            </h2>
            <p class="text-gray-400 text-center mb-8 max-w-2xl mx-auto">
                Entre em contato com nossa equipe de suporte para obter ajuda personalizada.
            </p>
            <div class="grid md:grid-cols-3 gap-6">
                <a href="index.php#contact" class="bg-white/5 rounded-xl p-6 text-center hover:bg-white/10 transition-colors">
                    <i class="fas fa-phone text-amarelo-destaque text-3xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Telefone</h3>
                    <p class="text-gray-400 text-sm">(00) 0000-0000</p>
                </a>
                <a href="mailto:contato@escola.com" class="bg-white/5 rounded-xl p-6 text-center hover:bg-white/10 transition-colors">
                    <i class="fas fa-envelope text-amarelo-destaque text-3xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">E-mail</h3>
                    <p class="text-gray-400 text-sm">contato@escola.com</p>
                </a>
                <a href="agendar_visita.php" class="bg-white/5 rounded-xl p-6 text-center hover:bg-white/10 transition-colors">
                    <i class="fas fa-calendar-check text-amarelo-destaque text-3xl mb-3"></i>
                    <h3 class="text-white font-semibold mb-1">Agendar Visita</h3>
                    <p class="text-gray-400 text-sm">Visite a escola</p>
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
        function toggleFaq(id) {
            const content = document.getElementById('faq-' + id);
            const icon = document.getElementById('icon-' + id);
            
            content.classList.toggle('hidden');
            icon.classList.toggle('rotate-180');
        }

        // Busca em tempo real
        document.getElementById('search-faq').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('button span').textContent.toLowerCase();
                const answer = item.querySelector('p') ? item.querySelector('p').textContent.toLowerCase() : '';
                
                if (question.includes(searchTerm) || answer.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>

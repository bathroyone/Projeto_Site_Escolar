<?php
require_once 'portal/config.php';

$success = '';
$error = '';

// Criar tabela de avaliações se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    tipo ENUM('aluno', 'responsavel', 'ex_aluno', 'visitante') DEFAULT 'visitante',
    avaliacao INT NOT NULL CHECK (avaliacao >= 1 AND avaliacao <= 5),
    comentario TEXT,
    aprovado TINYINT(1) DEFAULT 0,
    data_avaliacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Processar envio de avaliação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? 'visitante');
    $avaliacao = intval($_POST['avaliacao'] ?? 0);
    $comentario = sanitizeInput($_POST['comentario'] ?? '');
    
    if (empty($nome) || $avaliacao < 1 || $avaliacao > 5) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO avaliacoes (nome, tipo, avaliacao, comentario) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $tipo, $avaliacao, $comentario]);
            
            $success = 'Avaliação enviada com sucesso! Após aprovação, ela será exibida no site.';
        } catch (PDOException $e) {
            error_log("Erro ao enviar avaliação: " . $e->getMessage());
            $error = 'Erro ao enviar avaliação. Tente novamente.';
        }
    }
}

// Obter avaliações aprovadas
$avaliacoes = [];
try {
    $stmt = $pdo->query("SELECT * FROM avaliacoes WHERE aprovado = 1 ORDER BY data_avaliacao DESC LIMIT 20");
    $avaliacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter avaliações: " . $e->getMessage());
}

// Calcular média geral
$media_geral = 0;
try {
    $stmt = $pdo->query("SELECT AVG(avaliacao) as media FROM avaliacoes WHERE aprovado = 1");
    $result = $stmt->fetch();
    $media_geral = $result['media'] ? round($result['media'], 1) : 0;
} catch (PDOException $e) {
    error_log("Erro ao calcular média: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliações e Depoimentos | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">AVALIAÇÕES E</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">DEPOIMENTOS</span>
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
                <i class="fas fa-star mr-3"></i>Avaliações e Depoimentos
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Veja o que nossa comunidade diz sobre a escola e compartilhe sua experiência.
            </p>
        </div>

        <!-- Estatísticas -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 mb-12 border border-white/20">
            <div class="grid md:grid-cols-3 gap-6 text-center">
                <div>
                    <div class="text-5xl font-bold text-amarelo-destaque mb-2"><?php echo $media_geral; ?></div>
                    <p class="text-white font-semibold">Média Geral</p>
                    <div class="flex justify-center mt-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= round($media_geral) ? 'text-amarelo-destaque' : 'text-gray-600'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <div>
                    <div class="text-5xl font-bold text-amarelo-destaque mb-2"><?php echo count($avaliacoes); ?></div>
                    <p class="text-white font-semibold">Avaliações</p>
                    <p class="text-gray-400 text-sm mt-2">Depoimentos publicados</p>
                </div>
                <div>
                    <div class="text-5xl font-bold text-amarelo-destaque mb-2">5.0</div>
                    <p class="text-white font-semibold">Qualidade</p>
                    <p class="text-gray-400 text-sm mt-2">Compromisso com a educação</p>
                </div>
            </div>
        </div>

        <!-- Formulário de Avaliação -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 mb-12 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-pen mr-2 text-amarelo-destaque"></i>Deixe sua Avaliação
            </h2>
            
            <?php if ($success): ?>
                <div class="bg-green-500/20 border border-green-500/30 text-green-300 px-4 py-3 rounded-xl mb-6 text-center">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-500/20 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl mb-6 text-center">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="max-w-2xl mx-auto">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Nome</label>
                        <input type="text" name="nome" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Seu nome">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Você é</label>
                        <select name="tipo" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent">
                            <option value="visitante">Visitante</option>
                            <option value="responsavel">Responsável de Aluno</option>
                            <option value="aluno">Aluno</option>
                            <option value="ex_aluno">Ex-Aluno</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Avaliação</label>
                        <div class="flex gap-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <label class="cursor-pointer">
                                    <input type="radio" name="avaliacao" value="<?php echo $i; ?>" required class="hidden peer">
                                    <i class="fas fa-star text-3xl text-gray-600 peer-checked:text-amarelo-destaque transition-colors"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Comentário (opcional)</label>
                        <textarea name="comentario" rows="4" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Conte-nos sobre sua experiência"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-xl font-bold hover:shadow-xl hover:shadow-yellow-500/30 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-paper-plane mr-2"></i>Enviar Avaliação
                    </button>
                </div>
            </form>
        </div>

        <!-- Avaliações -->
        <div>
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-comments mr-2 text-amarelo-destaque"></i>Depoimentos
            </h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <?php if (count($avaliacoes) > 0): ?>
                    <?php foreach ($avaliacoes as $avaliacao): ?>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                            <div class="flex items-start gap-4 mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user text-white text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold"><?php echo htmlspecialchars($avaliacao['nome']); ?></h3>
                                    <span class="text-gray-400 text-sm capitalize"><?php echo str_replace('_', ' ', $avaliacao['tipo']); ?></span>
                                </div>
                            </div>
                            <div class="flex gap-1 mb-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $avaliacao['avaliacao'] ? 'text-amarelo-destaque' : 'text-gray-600'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <?php if ($avaliacao['comentario']): ?>
                                <p class="text-gray-300 text-sm">"<?php echo htmlspecialchars($avaliacao['comentario']); ?>"</p>
                            <?php endif; ?>
                            <span class="text-gray-500 text-xs mt-3 block"><?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-2 text-center py-12 text-gray-400">
                        <i class="fas fa-comments text-4xl mb-4"></i>
                        <p class="text-lg">Nenhuma avaliação publicada ainda.</p>
                    </div>
                <?php endif; ?>
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

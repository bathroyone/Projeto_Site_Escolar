<?php
require_once 'portal/config.php';

$success = '';
$error = '';

// Criar tabela de doações se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS doacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(50),
    valor DECIMAL(10,2) NOT NULL,
    tipo ENUM('pix', 'cartao', 'boleto', 'transferencia') DEFAULT 'pix',
    mensagem TEXT,
    status ENUM('pendente', 'confirmada', 'cancelada') DEFAULT 'pendente',
    data_doacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Processar doação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $valor = floatval($_POST['valor'] ?? 0);
    $tipo = sanitizeInput($_POST['tipo'] ?? 'pix');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    
    if (empty($nome) || empty($email) || $valor <= 0) {
        $error = 'Por favor, preencha todos os campos obrigatórios com valores válidos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um e-mail válido.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO doacoes (nome, email, telefone, valor, tipo, mensagem) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $telefone, $valor, $tipo, $mensagem]);
            
            $success = 'Doação registrada com sucesso! Você receberá instruções para o pagamento.';
        } catch (PDOException $e) {
            error_log("Erro ao registrar doação: " . $e->getMessage());
            $error = 'Erro ao registrar doação. Tente novamente.';
        }
    }
}

// Obter estatísticas de doações
$total_doacoes = 0;
$total_valor = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(valor) as soma FROM doacoes WHERE status = 'confirmada'");
    $result = $stmt->fetch();
    $total_doacoes = $result['total'] ?? 0;
    $total_valor = $result['soma'] ?? 0;
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doações e Contribuições | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">DOAÇÕES E</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">CONTRIBUIÇÕES</span>
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
                <i class="fas fa-heart mr-3"></i>Doações e Contribuições
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Contribua para o desenvolvimento educacional e ajude a transformar vidas.
            </p>
        </div>

        <!-- Estatísticas -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 mb-12 border border-white/20">
            <div class="grid md:grid-cols-2 gap-6 text-center">
                <div>
                    <div class="text-5xl font-bold text-amarelo-destaque mb-2"><?php echo $total_doacoes; ?></div>
                    <p class="text-white font-semibold">Doações Realizadas</p>
                    <p class="text-gray-400 text-sm mt-2">Pessoas que contribuíram</p>
                </div>
                <div>
                    <div class="text-5xl font-bold text-amarelo-destaque mb-2">R$ <?php echo number_format($total_valor, 2, ',', '.'); ?></div>
                    <p class="text-white font-semibold">Total Arrecadado</p>
                    <p class="text-gray-400 text-sm mt-2">Valor total das doações</p>
                </div>
            </div>
        </div>

        <!-- Formulário de Doação -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 mb-12 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-hand-holding-heart mr-2 text-amarelo-destaque"></i>Faça sua Doação
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
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Nome Completo</label>
                            <input type="text" name="nome" required class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Seu nome">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">E-mail</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="seu@email.com">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Telefone</label>
                            <input type="tel" name="telefone" class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="(00) 00000-0000">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Valor da Doação</label>
                            <input type="number" name="valor" step="0.01" min="1" required class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="0,00">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Forma de Pagamento</label>
                        <select name="tipo" required class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent">
                            <option value="pix">PIX</option>
                            <option value="cartao">Cartão de Crédito</option>
                            <option value="boleto">Boleto</option>
                            <option value="transferencia">Transferência Bancária</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Mensagem (opcional)</label>
                        <textarea name="mensagem" rows="3" class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Deixe uma mensagem para a escola"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-xl font-bold hover:shadow-xl hover:shadow-yellow-500/30 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-heart mr-2"></i>Doar Agora
                    </button>
                </div>
            </form>
        </div>

        <!-- Como as Doações São Utilizadas -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-chart-line mr-2 text-amarelo-destaque"></i>Como as Doações São Utilizadas
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-book text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Materiais Didáticos</h3>
                    <p class="text-gray-400 text-sm">Aquisição de livros e materiais para os alunos.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-laptop text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Tecnologia</h3>
                    <p class="text-gray-400 text-sm">Investimento em equipamentos e infraestrutura tecnológica.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-graduation-cap text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Bolsas de Estudo</h3>
                    <p class="text-gray-400 text-sm">Apoio a alunos com dificuldades financeiras.</p>
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


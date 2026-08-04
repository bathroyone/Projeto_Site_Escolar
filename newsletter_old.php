<?php
require_once 'portal/config.php';

$success = '';
$error = '';

// Processar inscrição na newsletter
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    
    if (empty($nome) || empty($email)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um e-mail válido.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Criar tabela se não existir
            $pdo->query("CREATE TABLE IF NOT EXISTS newsletter (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                status ENUM('ativo', 'inativo', 'cancelado') DEFAULT 'ativo',
                data_inscricao DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Verificar se e-mail já está cadastrado
            $stmt = $pdo->prepare("SELECT id FROM newsletter WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este e-mail já está cadastrado em nossa newsletter.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO newsletter (nome, email) VALUES (?, ?)");
                $stmt->execute([$nome, $email]);
                
                $success = 'Inscrição realizada com sucesso! Você receberá nossas novidades em breve.';
            }
        } catch (PDOException $e) {
            error_log("Erro ao inscrever na newsletter: " . $e->getMessage());
            $error = 'Erro ao realizar inscrição. Tente novamente.';
        }
    }
}

// Obter newsletters recentes (comunicados enviados)
$newsletters = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM comunicados WHERE tipo = 'newsletter' AND ativo = 1 ORDER BY data_envio DESC LIMIT 10");
    $newsletters = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter newsletters: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">NEWSLETTER E</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">COMUNICAÇÕES</span>
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
                <i class="fas fa-envelope mr-3"></i>Newsletter e Comunicações
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Receba as últimas novidades, eventos e comunicados da escola diretamente no seu e-mail.
            </p>
        </div>

        <!-- Formulário de Inscrição -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 mb-12 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-paper-plane mr-2 text-amarelo-destaque"></i>Inscreva-se na Newsletter
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

            <form method="POST" action="" class="max-w-md mx-auto">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Nome Completo</label>
                        <input type="text" name="nome" required class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Seu nome">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">E-mail</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="seu@email.com">
                    </div>
                    
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-xl font-bold hover:shadow-xl hover:shadow-yellow-500/30 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-envelope mr-2"></i>Inscrever-se
                    </button>
                </div>
            </form>
            
            <p class="text-gray-400 text-sm text-center mt-4">
                Ao se inscrever, você concorda em receber comunicações da escola. Você pode cancelar a qualquer momento.
            </p>
        </div>

        <!-- Newsletters Anteriores -->
        <div>
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-history mr-2 text-amarelo-destaque"></i>Comunicações Anteriores
            </h2>
            
            <div class="space-y-4">
                <?php if (count($newsletters) > 0): ?>
                    <?php foreach ($newsletters as $newsletter): ?>
                        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="text-white font-semibold mb-2"><?php echo htmlspecialchars($newsletter['titulo']); ?></h3>
                                    <p class="text-gray-400 text-sm mb-3"><?php echo htmlspecialchars(substr($newsletter['mensagem'], 0, 200)) . '...'; ?></p>
                                    <span class="text-white/50 text-xs"><?php echo date('d/m/Y H:i', strtotime($newsletter['data_envio'])); ?></span>
                                </div>
                                <i class="fas fa-envelope text-amarelo-destaque text-2xl ml-4"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-12 text-gray-400">
                        <i class="fas fa-envelope text-4xl mb-4"></i>
                        <p class="text-lg">Nenhuma comunicação enviada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Benefícios -->
        <div class="mt-16">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-star mr-2 text-amarelo-destaque"></i>Por que se inscrever?
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-bell text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Avisos Imediatos</h3>
                    <p class="text-gray-400 text-sm">Receba avisos importantes sobre a escola em tempo real.</p>
                </div>

                <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-calendar-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Eventos</h3>
                    <p class="text-gray-400 text-sm">Fique por dentro de todos os eventos e atividades.</p>
                </div>

                <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20 text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-newspaper text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Notícias</h3>
                    <p class="text-gray-400 text-sm">Receba as últimas notícias e atualizações da escola.</p>
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


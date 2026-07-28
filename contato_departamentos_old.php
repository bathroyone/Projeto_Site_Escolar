<?php
require_once 'portal/config.php';

$success = '';
$error = '';

// Criar tabela de departamentos se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    telefone VARCHAR(50),
    descricao TEXT,
    ativo TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Criar tabela de mensagens por departamento se não existir
$pdo->query("CREATE TABLE IF NOT EXISTS mensagens_departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    departamento_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(50),
    assunto VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    status ENUM('pendente', 'respondida', 'arquivada') DEFAULT 'pendente',
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Processar envio de mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $departamento_id = intval($_POST['departamento_id'] ?? 0);
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $assunto = sanitizeInput($_POST['assunto'] ?? '');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    
    if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem) || empty($departamento_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um e-mail válido.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO mensagens_departamentos (departamento_id, nome, email, telefone, assunto, mensagem) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$departamento_id, $nome, $email, $telefone, $assunto, $mensagem]);
            
            $success = 'Mensagem enviada com sucesso! Entraremos em contato em breve.';
        } catch (PDOException $e) {
            error_log("Erro ao enviar mensagem: " . $e->getMessage());
            $error = 'Erro ao enviar mensagem. Tente novamente.';
        }
    }
}

// Obter departamentos
$departamentos = [];
try {
    $stmt = $pdo->query("SELECT * FROM departamentos WHERE ativo = 1 ORDER BY nome");
    $departamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter departamentos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contato por Departamento | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">CONTATO POR</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">DEPARTAMENTO</span>
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
                <i class="fas fa-building mr-3"></i>Contato por Departamento
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Entre em contato diretamente com o departamento específico para sua solicitação.
            </p>
        </div>

        <!-- Departamentos -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-sitemap mr-2 text-amarelo-destaque"></i>Departamentos
            </h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (count($departamentos) > 0): ?>
                    <?php foreach ($departamentos as $dept): ?>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                            <div class="flex items-start gap-4 mb-4">
                                <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-building text-white text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold text-lg"><?php echo htmlspecialchars($dept['nome']); ?></h3>
                                    <?php if ($dept['descricao']): ?>
                                        <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($dept['descricao']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <?php if ($dept['email']): ?>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-envelope text-amarelo-destaque"></i>
                                        <span class="text-gray-400 text-sm"><?php echo htmlspecialchars($dept['email']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($dept['telefone']): ?>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-phone text-amarelo-destaque"></i>
                                        <span class="text-gray-400 text-sm"><?php echo htmlspecialchars($dept['telefone']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-3 text-center py-12 text-gray-400">
                        <i class="fas fa-building text-4xl mb-4"></i>
                        <p class="text-lg">Nenhum departamento cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulário de Contato -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-paper-plane mr-2 text-amarelo-destaque"></i>Enviar Mensagem
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
                        <label class="block text-sm font-semibold text-white mb-2">Departamento</label>
                        <select name="departamento_id" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent">
                            <option value="">Selecione o departamento</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Nome Completo</label>
                        <input type="text" name="nome" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Seu nome">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">E-mail</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="seu@email.com">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Telefone</label>
                        <input type="tel" name="telefone" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="(00) 00000-0000">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Assunto</label>
                        <input type="text" name="assunto" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Assunto da mensagem">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Mensagem</label>
                        <textarea name="mensagem" rows="5" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Escreva sua mensagem"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-xl font-bold hover:shadow-xl hover:shadow-yellow-500/30 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-paper-plane mr-2"></i>Enviar Mensagem
                    </button>
                </div>
            </form>
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

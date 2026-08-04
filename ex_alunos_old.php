<?php
require_once 'portal/config.php';

$success = '';
$error = '';

// Criar tabela de ex-alunos se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS ex_alunos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    telefone VARCHAR(50),
    ano_conclusao INT NOT NULL,
    curso VARCHAR(255),
    profissao VARCHAR(255),
    linkedin VARCHAR(255),
    instagram VARCHAR(255),
    bio TEXT,
    aprovado TINYINT(1) DEFAULT 0,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Processar cadastro de ex-aluno
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $ano_conclusao = intval($_POST['ano_conclusao'] ?? 0);
    $curso = sanitizeInput($_POST['curso'] ?? '');
    $profissao = sanitizeInput($_POST['profissao'] ?? '');
    $linkedin = sanitizeInput($_POST['linkedin'] ?? '');
    $instagram = sanitizeInput($_POST['instagram'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');
    
    if (empty($nome) || empty($email) || empty($ano_conclusao)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um e-mail válido.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO ex_alunos (nome, email, telefone, ano_conclusao, curso, profissao, linkedin, instagram, bio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $telefone, $ano_conclusao, $curso, $profissao, $linkedin, $instagram, $bio]);
            
            $success = 'Cadastro realizado com sucesso! Após aprovação, seu perfil será exibido no portal.';
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Este e-mail já está cadastrado.';
            } else {
                error_log("Erro ao cadastrar ex-aluno: " . $e->getMessage());
                $error = 'Erro ao realizar cadastro. Tente novamente.';
            }
        }
    }
}

// Obter ex-alunos aprovados
$ex_alunos = [];
try {
    $stmt = $pdo->query("SELECT * FROM ex_alunos WHERE aprovado = 1 ORDER BY ano_conclusao DESC LIMIT 20");
    $ex_alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter ex-alunos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Ex-Alunos | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">PORTAL DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">EX-ALUNOS</span>
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
                <i class="fas fa-graduation-cap mr-3"></i>Portal de Ex-Alunos
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Conecte-se com ex-alunos, compartilhe experiências e amplie sua rede de networking.
            </p>
        </div>

        <!-- Formulário de Cadastro -->
        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-8 mb-12 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-user-plus mr-2 text-amarelo-destaque"></i>Cadastre-se
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
                            <label class="block text-sm font-semibold text-white mb-2">Ano de Conclusão</label>
                            <input type="number" name="ano_conclusao" required class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="2020">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Curso</label>
                            <input type="text" name="curso" class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Curso concluído">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Profissão Atual</label>
                            <input type="text" name="profissao" class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Sua profissão">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">LinkedIn</label>
                            <input type="text" name="linkedin" class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="linkedin.com/in/...">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Instagram</label>
                            <input type="text" name="instagram" class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="@usuario">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Biografia</label>
                        <textarea name="bio" rows="3" class="w-full px-4 py-3 bg-white/5 border border-white/10 backdrop-blur-sm/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Conte um pouco sobre sua trajetória"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full py-4 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-xl font-bold hover:shadow-xl hover:shadow-yellow-500/30 transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-user-plus mr-2"></i>Cadastrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Ex-Alunos -->
        <div>
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-users mr-2 text-amarelo-destaque"></i>Nossa Comunidade
            </h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if (count($ex_alunos) > 0): ?>
                    <?php foreach ($ex_alunos as $ex_aluno): ?>
                        <div class="bg-white/5 border border-white/10 backdrop-blur-sm/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                            <div class="flex items-start gap-4 mb-4">
                                <div class="w-16 h-16 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user-graduate text-white text-2xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-white font-semibold text-lg"><?php echo htmlspecialchars($ex_aluno['nome']); ?></h3>
                                    <span class="text-amarelo-destaque text-sm">Turma <?php echo $ex_aluno['ano_conclusao']; ?></span>
                                </div>
                            </div>
                            <?php if ($ex_aluno['curso']): ?>
                                <p class="text-gray-400 text-sm mb-2"><i class="fas fa-book mr-2"></i><?php echo htmlspecialchars($ex_aluno['curso']); ?></p>
                            <?php endif; ?>
                            <?php if ($ex_aluno['profissao']): ?>
                                <p class="text-gray-400 text-sm mb-2"><i class="fas fa-briefcase mr-2"></i><?php echo htmlspecialchars($ex_aluno['profissao']); ?></p>
                            <?php endif; ?>
                            <?php if ($ex_aluno['bio']): ?>
                                <p class="text-gray-300 text-sm mb-4"><?php echo htmlspecialchars(substr($ex_aluno['bio'], 0, 100)); ?></p>
                            <?php endif; ?>
                            <div class="flex gap-2">
                                <?php if ($ex_aluno['linkedin']): ?>
                                    <a href="<?php echo htmlspecialchars($ex_aluno['linkedin']); ?>" target="_blank" class="px-3 py-2 bg-white/5 border border-white/10 backdrop-blur-sm/10 rounded-lg text-white text-sm hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 transition-colors">
                                        <i class="fab fa-linkedin"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if ($ex_aluno['instagram']): ?>
                                    <a href="<?php echo htmlspecialchars($ex_aluno['instagram']); ?>" target="_blank" class="px-3 py-2 bg-white/5 border border-white/10 backdrop-blur-sm/10 rounded-lg text-white text-sm hover:bg-white/5 border border-white/10 backdrop-blur-sm/20 transition-colors">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-3 text-center py-12 text-gray-400">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p class="text-lg">Nenhum ex-aluno cadastrado ainda.</p>
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


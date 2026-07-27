<?php
require_once 'portal/config.php';

$success = '';
$error = '';

// Criar tabela de vagas se não existir
$pdo = getDBConnection();
$pdo->query("CREATE TABLE IF NOT EXISTS vagas_emprego (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT NOT NULL,
    requisitos TEXT,
    salario VARCHAR(100),
    tipo ENUM('clt', 'pj', 'estagio', 'voluntario') DEFAULT 'clt',
    carga_horaria VARCHAR(50),
    ativo TINYINT(1) DEFAULT 1,
    data_publicacao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Criar tabela de currículos se não existir
$pdo->query("CREATE TABLE IF NOT EXISTS curriculos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(50),
    vaga_id INT,
    arquivo VARCHAR(255),
    mensagem TEXT,
    status ENUM('pendente', 'analise', 'entrevista', 'aprovado', 'rejeitado') DEFAULT 'pendente',
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vaga_id) REFERENCES vagas_emprego(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Processar envio de currículo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $vaga_id = intval($_POST['vaga_id'] ?? 0);
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    
    if (empty($nome) || empty($email)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um e-mail válido.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO curriculos (nome, email, telefone, vaga_id, mensagem) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $telefone, $vaga_id, $mensagem]);
            
            $success = 'Currículo enviado com sucesso! Entraremos em contato se houver interesse.';
        } catch (PDOException $e) {
            error_log("Erro ao enviar currículo: " . $e->getMessage());
            $error = 'Erro ao enviar currículo. Tente novamente.';
        }
    }
}

// Obter vagas ativas
$vagas = [];
try {
    $stmt = $pdo->query("SELECT * FROM vagas_emprego WHERE ativo = 1 ORDER BY data_publicacao DESC");
    $vagas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter vagas: " . $e->getMessage());
}

$tipos_vaga = [
    'clt' => 'CLT',
    'pj' => 'PJ',
    'estagio' => 'Estágio',
    'voluntario' => 'Voluntário'
];

$cores_tipos = [
    'clt' => 'from-blue-500 to-blue-600',
    'pj' => 'from-purple-500 to-purple-600',
    'estagio' => 'from-green-500 to-green-600',
    'voluntario' => 'from-orange-500 to-orange-600'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trabalhe Conosco | Site da Escola</title>
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
                            <span class="text-white font-bold text-xs tracking-wide">TRABALHE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">CONOSCO</span>
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
                <i class="fas fa-briefcase mr-3"></i>Trabalhe Conosco
            </h1>
            <p class="text-white/90 text-lg max-w-2xl mx-auto">
                Junte-se à nossa equipe e faça parte da transformação educacional.
            </p>
        </div>

        <!-- Vagas Disponíveis -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-briefcase mr-2 text-amarelo-destaque"></i>Vagas Disponíveis
            </h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <?php if (count($vagas) > 0): ?>
                    <?php foreach ($vagas as $vaga): ?>
                        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-6 border border-white/20">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h3 class="text-white font-semibold text-lg"><?php echo htmlspecialchars($vaga['titulo']); ?></h3>
                                    <span class="inline-block px-3 py-1 bg-gradient-to-r <?php echo $cores_tipos[$vaga['tipo']]; ?> rounded-full text-xs text-white font-semibold mt-2">
                                        <?php echo $tipos_vaga[$vaga['tipo']]; ?>
                                    </span>
                                </div>
                            </div>
                            
                            <p class="text-gray-300 text-sm mb-4"><?php echo htmlspecialchars(substr($vaga['descricao'], 0, 150)); ?>...</p>
                            
                            <div class="space-y-2 mb-4">
                                <?php if ($vaga['salario']): ?>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-dollar-sign text-amarelo-destaque"></i>
                                        <span class="text-gray-400 text-sm">Salário: <?php echo htmlspecialchars($vaga['salario']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($vaga['carga_horaria']): ?>
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-clock text-amarelo-destaque"></i>
                                        <span class="text-gray-400 text-sm">Carga Horária: <?php echo htmlspecialchars($vaga['carga_horaria']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <button onclick="mostrarFormulario(<?php echo $vaga['id']; ?>, '<?php echo htmlspecialchars($vaga['titulo']); ?>')" class="w-full py-3 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-xl font-semibold hover:shadow-xl hover:shadow-yellow-500/30 transition-all">
                                <i class="fas fa-paper-plane mr-2"></i>Candidatar-se
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-2 text-center py-12 text-gray-400">
                        <i class="fas fa-briefcase text-4xl mb-4"></i>
                        <p class="text-lg">Nenhuma vaga disponível no momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulário de Candidatura -->
        <div id="formulario-candidatura" class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white-20 hidden">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-user-plus mr-2 text-amarelo-destaque"></i>Enviar Currículo
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
                    <input type="hidden" name="vaga_id" id="vaga_id" value="">
                    
                    <div class="bg-white/5 rounded-xl p-4 mb-4">
                        <p class="text-sm text-gray-400">Vaga selecionada</p>
                        <p class="text-white font-semibold" id="vaga_selecionada"></p>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">Nome Completo</label>
                            <input type="text" name="nome" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Seu nome">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-white mb-2">E-mail</label>
                            <input type="email" name="email" required class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="seu@email.com">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Telefone</label>
                        <input type="tel" name="telefone" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="(00) 00000-0000">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-white mb-2">Mensagem (opcional)</label>
                        <textarea name="mensagem" rows="3" class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:ring-2 focus:ring-amarelo-destaque focus:border-transparent" placeholder="Conte-nos sobre você"></textarea>
                    </div>
                    
                    <div class="flex gap-4">
                        <button type="submit" class="flex-1 py-4 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro text-azul-escuro rounded-xl font-bold hover:shadow-xl hover:shadow-yellow-500/30 transition-all duration-300 transform hover:scale-105">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Currículo
                        </button>
                        <button type="button" onclick="esconderFormulario()" class="px-6 py-4 bg-white/10 text-white rounded-xl font-semibold hover:bg-white/20 transition-all">
                            Cancelar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Benefícios -->
        <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-8 border border-white/20">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">
                <i class="fas fa-star mr-2 text-amarelo-destaque"></i>Benefícios
            </h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-heartbeat text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Plano de Saúde</h3>
                    <p class="text-gray-400 text-sm">Cobertura médica completa para você e sua família.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-graduation-cap text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Desenvolvimento</h3>
                    <p class="text-gray-400 text-sm">Cursos e treinamentos contínuos.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-umbrella-beach text-white text-2xl"></i>
                    </div>
                    <h3 class="text-white font-semibold mb-2">Férias Remuneradas</h3>
                    <p class="text-gray-400 text-sm">30 dias de férias remuneradas anuais.</p>
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

    <script>
        function mostrarFormulario(vagaId, vagaTitulo) {
            document.getElementById('vaga_id').value = vagaId;
            document.getElementById('vaga_selecionada').textContent = vagaTitulo;
            document.getElementById('formulario-candidatura').classList.remove('hidden');
            document.getElementById('formulario-candidatura').scrollIntoView({ behavior: 'smooth' });
        }

        function esconderFormulario() {
            document.getElementById('formulario-candidatura').classList.add('hidden');
        }
    </script>
</body>
</html>

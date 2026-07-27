<?php
require_once 'config.php';

// Se já estiver logado, redirecionar para o dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Obter turmas disponíveis
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT DISTINCT nome, serie FROM turmas WHERE ano_letivo = 2026 ORDER BY serie, nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $turma = sanitizeInput($_POST['turma'] ?? '');
    $serie = sanitizeInput($_POST['serie'] ?? '');
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha) || empty($turma) || empty($serie)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este email já está cadastrado.';
            } else {
                // Inserir novo usuário
                $senha_hash = hashPassword($senha);
                $matricula = 'ALU' . date('Y') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome_completo, email, senha, tipo_usuario, turma, serie, matricula) VALUES (?, ?, ?, 'aluno', ?, ?, ?)");
                $stmt->execute([$nome, $email, $senha_hash, $turma, $serie, $matricula]);
                
                $success = 'Cadastro realizado com sucesso! Você já pode fazer login.';
                
                // Limpar formulário
                $_POST = [];
            }
        } catch (PDOException $e) {
            error_log("Erro no cadastro: " . $e->getMessage());
            $error = 'Erro ao realizar cadastro. Por favor, tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | Portal CEAA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        azul: {
                            principal: '#063b7a',
                            escuro: '#082b54',
                            claro: '#0b4a8c'
                        },
                        amarelo: {
                            destaque: '#ffd000',
                            claro: '#ffe033'
                        },
                        verde: {
                            complementar: '#13843b',
                            claro: '#15a048'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Poppins', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-white min-h-screen flex items-center justify-center p-4 py-8">
    <div class="w-full max-w-lg">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="h-32 bg-gradient-to-br from-verde-complementar to-teal-600 flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-white/10"></div>
                <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-10 right-10 w-24 h-24 bg-amarelo-destaque/20 rounded-full blur-2xl"></div>
                <div class="relative z-10 text-center">
                    <i class="fas fa-user-plus text-white text-4xl mb-2"></i>
                    <h1 class="font-display font-bold text-white text-2xl">Cadastro de Aluno</h1>
                </div>
            </div>
            
            <div class="p-8">
                <div class="text-center mb-6">
                    <h2 class="font-display font-bold text-azul-principal text-2xl mb-2">Crie sua conta</h2>
                    <p class="text-gray-600 text-sm">Preencha os dados para se cadastrar no portal</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo $success; ?>
                        <div class="mt-3">
                            <a href="login.php" class="text-green-700 font-semibold hover:underline">Fazer login agora</a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="text" id="nome" name="nome" required
                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent transition-all"
                                    placeholder="Seu nome completo" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                            <div class="relative">
                                <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="email" id="email" name="email" required
                                    class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent transition-all"
                                    placeholder="seu@email.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="serie" class="block text-sm font-semibold text-gray-700 mb-2">Série</label>
                                <div class="relative">
                                    <i class="fas fa-graduation-cap absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <select id="serie" name="serie" required onchange="atualizarTurmas()"
                                        class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent transition-all appearance-none bg-white">
                                        <option value="">Selecione</option>
                                        <?php
                                        $series = array_unique(array_column($turmas, 'serie'));
                                        foreach ($series as $s): ?>
                                            <option value="<?php echo $s; ?>" <?php echo (isset($_POST['serie']) && $_POST['serie'] === $s) ? 'selected' : ''; ?>>
                                                <?php echo $s; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                </div>
                            </div>
                            
                            <div>
                                <label for="turma" class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                                <div class="relative">
                                    <i class="fas fa-users absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                    <select id="turma" name="turma" required
                                        class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent transition-all appearance-none bg-white">
                                        <option value="">Selecione a série primeiro</option>
                                        <?php
                                        if (isset($_POST['serie'])): ?>
                                            <?php
                                            $turmas_filtradas = array_filter($turmas, function($t) use ($_POST) {
                                                return $t['serie'] === $_POST['serie'];
                                            });
                                            foreach ($turmas_filtradas as $t): ?>
                                                <option value="<?php echo $t['nome']; ?>" <?php echo (isset($_POST['turma']) && $_POST['turma'] === $t['nome']) ? 'selected' : ''; ?>>
                                                    <?php echo $t['nome']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <i class="fas fa-chevron-down absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="senha" class="block text-sm font-semibold text-gray-700 mb-2">Senha</label>
                            <div class="relative">
                                <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="password" id="senha" name="senha" required minlength="6"
                                    class="w-full pl-12 pr-12 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent transition-all"
                                    placeholder="Mínimo 6 caracteres">
                                <button type="button" onclick="togglePassword('senha', 'eye-icon-1')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="eye-icon-1"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="confirmar_senha" class="block text-sm font-semibold text-gray-700 mb-2">Confirmar Senha</label>
                            <div class="relative">
                                <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                                <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6"
                                    class="w-full pl-12 pr-12 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent transition-all"
                                    placeholder="Confirme sua senha">
                                <button type="button" onclick="togglePassword('confirmar_senha', 'eye-icon-2')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-eye" id="eye-icon-2"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-verde-complementar to-teal-600 text-white font-bold py-3 rounded-xl hover:from-verde-claro hover:to-cyan-600 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-[1.02]">
                            <i class="fas fa-user-plus mr-2"></i>
                            Cadastrar
                        </button>
                    </form>
                    
                    <div class="mt-6 text-center">
                        <p class="text-gray-600 text-sm">
                            Já tem conta?
                            <a href="login.php" class="text-verde-complementar font-semibold hover:underline">Faça login aqui</a>
                        </p>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4 text-center">
                    <a href="../index.html" class="text-gray-500 text-sm hover:text-azul-principal transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Voltar ao site principal
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-6 text-gray-500 text-sm">
            <p>© 2026 Centro Educacional Alameda Argentina</p>
        </div>
    </div>
    
    <script>
        const turmasData = <?php echo json_encode($turmas); ?>;
        
        function atualizarTurmas() {
            const serie = document.getElementById('serie').value;
            const turmaSelect = document.getElementById('turma');
            
            turmaSelect.innerHTML = '<option value="">Selecione</option>';
            
            if (serie) {
                const turmasFiltradas = turmasData.filter(t => t.serie === serie);
                turmasFiltradas.forEach(t => {
                    const option = document.createElement('option');
                    option.value = t.nome;
                    option.textContent = t.nome;
                    turmaSelect.appendChild(option);
                });
            }
        }
        
        function togglePassword(inputId, iconId) {
            const senhaInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>

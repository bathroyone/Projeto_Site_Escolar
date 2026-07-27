<?php
require_once 'config.php';

// Se já estiver logado, redirecionar para o dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_field = sanitizeInput($_POST['login_field'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo_usuario = sanitizeInput($_POST['tipo_usuario'] ?? '');
    
    if (empty($login_field) || empty($senha) || empty($tipo_usuario)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar baseado no tipo de usuário
            if ($tipo_usuario === 'professor') {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE matricula = ? AND tipo_usuario = 'professor' AND ativo = TRUE");
                $stmt->execute([$login_field]);
            } elseif ($tipo_usuario === 'aluno') {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE cpf = ? AND tipo_usuario = 'aluno' AND ativo = TRUE");
                $stmt->execute([$login_field]);
            } elseif ($tipo_usuario === 'secretaria') {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE matricula = ? AND tipo_usuario = 'secretaria' AND ativo = TRUE");
                $stmt->execute([$login_field]);
            } elseif ($tipo_usuario === 'admin') {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario_login = ? AND tipo_usuario = 'admin' AND ativo = TRUE");
                $stmt->execute([$login_field]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE (email = ? OR matricula = ? OR cpf = ? OR usuario_login = ?) AND ativo = TRUE");
                $stmt->execute([$login_field, $login_field, $login_field, $login_field]);
            }
            
            $usuario = $stmt->fetch();
            
            if ($usuario && verifyPassword($senha, $usuario['senha'])) {
                // Login bem-sucedido
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nome'] = $usuario['nome_completo'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];
                $_SESSION['turma'] = $usuario['turma'];
                $_SESSION['serie'] = $usuario['serie'];
                
                // Redirecionar para o dashboard apropriado
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Credenciais incorretas.';
            }
        } catch (PDOException $e) {
            error_log("Erro no login: " . $e->getMessage());
            $error = 'Erro ao fazer login. Por favor, tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Portal CEAA</title>
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
<body class="bg-gradient-to-br from-blue-50 to-white min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="h-28 sm:h-32 bg-gradient-to-br from-azul-principal to-verde-complementar flex items-center justify-center relative overflow-hidden">
                <div class="absolute inset-0 bg-white/10"></div>
                <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-10 right-10 w-24 h-24 bg-amarelo-destaque/20 rounded-full blur-2xl"></div>
                <div class="relative z-10 text-center">
                    <i class="fas fa-graduation-cap text-white text-3xl sm:text-4xl mb-2"></i>
                    <h1 class="font-display font-bold text-white text-xl sm:text-2xl">Portal CEAA</h1>
                </div>
            </div>
            
            <div class="p-6 sm:p-8">
                <div class="text-center mb-6">
                    <h2 class="font-display font-bold text-azul-principal text-xl sm:text-2xl mb-2">Bem-vindo de volta!</h2>
                    <p class="text-gray-600 text-sm">Faça login para acessar o portal</p>
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
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="tipo_usuario" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Usuário</label>
                        <select id="tipo_usuario" name="tipo_usuario" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="aluno">Aluno</option>
                            <option value="professor">Professor</option>
                            <option value="secretaria">Secretaria</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="login_field" class="block text-sm font-semibold text-gray-700 mb-2" id="login_label">Email/CPF/Matrícula</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="login_field" name="login_field" required
                                class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                placeholder="Digite seu login">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="senha" class="block text-sm font-semibold text-gray-700 mb-2">Senha</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="password" id="senha" name="senha" required
                                class="w-full pl-12 pr-12 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                placeholder="••••••••">
                            <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-[1.02]">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Entrar
                    </button>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-gray-600 text-sm">
                        É aluno e ainda não tem conta?
                        <a href="register.php" class="text-azul-principal font-semibold hover:underline">Cadastre-se aqui</a>
                    </p>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="../index.php" class="text-gray-500 text-sm hover:text-azul-principal transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>
                        Voltar ao site principal
                    </a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-6 text-gray-500 text-sm">
            <p>© 2026 Centro Educacional</p>
        </div>
    </div>
    
    <script>
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const eyeIcon = document.getElementById('eye-icon');
            
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

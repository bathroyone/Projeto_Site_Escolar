<?php
require_once 'config.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (strlen($nova_senha) < 6) {
        $error = 'A nova senha deve ter no mínimo 6 caracteres.';
    } elseif ($nova_senha !== $confirmar_senha) {
        $error = 'A nova senha e a confirmação não coincidem.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            $usuario = $stmt->fetch();
            
            if ($usuario && verifyPassword($senha_atual, $usuario['senha'])) {
                $nova_senha_hash = hashPassword($nova_senha);
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ?, trocar_senha_proximo_login = FALSE WHERE id = ?");
                $stmt->execute([$nova_senha_hash, $_SESSION['usuario_id']]);
                
                $success = 'Senha alterada com sucesso! Redirecionando...';
                
                // Redirecionar para o painel apropriado após 2 segundos
                $redirect_url = 'dashboard.php';
                if ($_SESSION['tipo_usuario'] === 'admin') {
                    $redirect_url = 'admin/index.php';
                } elseif ($_SESSION['tipo_usuario'] === 'professor') {
                    $redirect_url = 'professor/index.php';
                } elseif ($_SESSION['tipo_usuario'] === 'aluno') {
                    $redirect_url = 'aluno/index.php';
                }
                
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '$redirect_url';
                    }, 2000);
                </script>";
            } else {
                $error = 'A senha atual está incorreta.';
            }
        } catch (PDOException $e) {
            error_log("Erro ao alterar senha: " . $e->getMessage());
            $error = 'Erro ao alterar senha. Por favor, tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Senha | Portal CEAA</title>
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
<body class="bg-gradient-to-br from-azul-principal to-azul-escuro min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-key text-white text-3xl"></i>
            </div>
            <h1 class="text-2xl font-display font-bold text-azul-principal">Alterar Senha</h1>
            <p class="text-gray-600 mt-2">Por segurança, você precisa alterar sua senha no primeiro acesso.</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label for="senha_atual" class="block text-sm font-semibold text-gray-700 mb-2">Senha Atual</label>
                <div class="relative">
                    <input type="password" id="senha_atual" name="senha_atual" required
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                        placeholder="Digite sua senha atual">
                    <button type="button" onclick="togglePassword('senha_atual')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="nova_senha" class="block text-sm font-semibold text-gray-700 mb-2">Nova Senha</label>
                <div class="relative">
                    <input type="password" id="nova_senha" name="nova_senha" required minlength="6"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                        placeholder="Mínimo 6 caracteres">
                    <button type="button" onclick="togglePassword('nova_senha')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-6">
                <label for="confirmar_senha" class="block text-sm font-semibold text-gray-700 mb-2">Confirmar Nova Senha</label>
                <div class="relative">
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                        placeholder="Digite a nova senha novamente">
                    <button type="button" onclick="togglePassword('confirmar_senha')" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit"
                class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-save mr-2"></i>Alterar Senha
            </button>
        </form>
        
        <div class="mt-6 text-center">
            <a href="logout.php" class="text-sm text-gray-500 hover:text-azul-principal transition-colors">
                <i class="fas fa-sign-out-alt mr-1"></i>Sair
            </a>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>

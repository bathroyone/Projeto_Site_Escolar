<?php
require_once '../config.php';

requireLogin();

if (!isAluno()) {
    header('Location: ../dashboard.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$turma = $_SESSION['turma'];
$serie = $_SESSION['serie'];

$success = '';
$error = '';

// Atualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'atualizar_perfil') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $telefone = sanitizeInput($_POST['telefone'] ?? '');
    $endereco = sanitizeInput($_POST['endereco'] ?? '');
    
    if (empty($nome) || empty($email)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo = ?, email = ?, telefone = ?, endereco = ? WHERE id = ?");
            $stmt->execute([$nome, $email, $telefone, $endereco, $aluno_id]);
            
            $_SESSION['nome'] = $nome;
            
            logAudit('PERFIL_ATUALIZAR', 'usuarios', $aluno_id, null, ['nome' => $nome, 'email' => $email]);
            
            $success = 'Perfil atualizado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao atualizar perfil.';
        }
    }
}

// Alterar senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'alterar_senha') {
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif ($nova_senha !== $confirmar_senha) {
        $error = 'A nova senha e a confirmação não coincidem.';
    } elseif (strlen($nova_senha) < 6) {
        $error = 'A nova senha deve ter no mínimo 6 caracteres.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
            $stmt->execute([$aluno_id]);
            $usuario = $stmt->fetch();
            
            if (verifyPassword($senha_atual, $usuario['senha'])) {
                $nova_senha_hash = hashPassword($nova_senha);
                $stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
                $stmt->execute([$nova_senha_hash, $aluno_id]);
                
                logAudit('SENHA_ALTERAR', 'usuarios', $aluno_id, null);
                
                $success = 'Senha alterada com sucesso!';
            } else {
                $error = 'A senha atual está incorreta.';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao alterar senha.';
        }
    }
}

// Conectar ao banco de dados
$pdo = getDBConnection();

// Obter dados do aluno
$aluno = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$aluno_id]);
    $aluno = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter dados do aluno: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil | Portal de Gestão Escolar</title>
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
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2">
                        <img src="../img/logo.jpg" alt="Logo" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">[Inserir nome da escola aqui]</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">[Inserir nome da escola aqui]</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-azul-principal transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar
                    </a>
                    
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-2 p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <span class="hidden md:block text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-sm text-gray-500">Aluno</p>
                            </div>
                            <div class="p-2">
                                <a href="../logout.php" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Sair
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-display font-bold text-azul-principal">Meu Perfil</h1>
                <p class="text-gray-600 mt-2">Gerencie seus dados pessoais</p>
            </div>
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Informações do Perfil -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-display font-bold text-azul-principal">Informações Pessoais</h2>
                    </div>
                    <form method="POST" action="" class="p-6">
                        <input type="hidden" name="action" value="atualizar_perfil">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
                                <input type="text" id="nome" name="nome" required
                                    value="<?php echo htmlspecialchars($aluno['nome_completo'] ?? ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                                <input type="email" id="email" name="email" required
                                    value="<?php echo htmlspecialchars($aluno['email'] ?? ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            </div>
                            
                            <div>
                                <label for="cpf" class="block text-sm font-semibold text-gray-700 mb-2">CPF</label>
                                <input type="text" id="cpf" name="cpf" readonly
                                    value="<?php echo htmlspecialchars($aluno['cpf'] ?? ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-600">
                            </div>
                            
                            <div>
                                <label for="telefone" class="block text-sm font-semibold text-gray-700 mb-2">Telefone</label>
                                <input type="tel" id="telefone" name="telefone"
                                    value="<?php echo htmlspecialchars($aluno['telefone'] ?? ''); ?>"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                    placeholder="(00) 00000-0000">
                            </div>
                            
                            <div>
                                <label for="turma" class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                                <input type="text" id="turma" name="turma" readonly
                                    value="<?php echo htmlspecialchars($turma); ?>"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-600">
                            </div>
                            
                            <div>
                                <label for="serie" class="block text-sm font-semibold text-gray-700 mb-2">Série</label>
                                <input type="text" id="serie" name="serie" readonly
                                    value="<?php echo htmlspecialchars($serie); ?>"
                                    class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50 text-gray-600">
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <label for="endereco" class="block text-sm font-semibold text-gray-700 mb-2">Endereço</label>
                            <textarea id="endereco" name="endereco" rows="3"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Endereço completo"><?php echo htmlspecialchars($aluno['endereco'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                            <i class="fas fa-save mr-2"></i>
                            Salvar Alterações
                        </button>
                    </form>
                </div>
            </div>

            <!-- Alterar Senha -->
            <div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-display font-bold text-azul-principal">Alterar Senha</h2>
                    </div>
                    <form method="POST" action="" class="p-6">
                        <input type="hidden" name="action" value="alterar_senha">
                        
                        <div class="mb-4">
                            <label for="senha_atual" class="block text-sm font-semibold text-gray-700 mb-2">Senha Atual *</label>
                            <input type="password" id="senha_atual" name="senha_atual" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div class="mb-4">
                            <label for="nova_senha" class="block text-sm font-semibold text-gray-700 mb-2">Nova Senha *</label>
                            <input type="password" id="nova_senha" name="nova_senha" required minlength="6"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Mínimo 6 caracteres">
                        </div>
                        
                        <div class="mb-6">
                            <label for="confirmar_senha" class="block text-sm font-semibold text-gray-700 mb-2">Confirmar Nova Senha *</label>
                            <input type="password" id="confirmar_senha" name="confirmar_senha" required minlength="6"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                            <i class="fas fa-key mr-2"></i>
                            Alterar Senha
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('user-menu');
            if (!e.target.closest('[onclick="toggleMenu()"]') && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

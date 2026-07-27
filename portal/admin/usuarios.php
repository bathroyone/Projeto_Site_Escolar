<?php
require_once '../config.php';

requireAdmin();

$error = '';
$success = '';

// Obter todos os usuários
$usuarios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY data_criacao DESC");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter usuários: " . $e->getMessage());
}

// Adicionar novo usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo_usuario = sanitizeInput($_POST['tipo_usuario'] ?? '');
    $turma = sanitizeInput($_POST['turma'] ?? '');
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $trocar_senha_proximo_login = isset($_POST['trocar_senha_proximo_login']) ? 1 : 0;
    
    if (empty($nome) || empty($email) || empty($senha) || empty($tipo_usuario)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter no mínimo 6 caracteres.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = 'Este email já está cadastrado.';
            } else {
                $senha_hash = hashPassword($senha);
                
                // Gerar matrícula apenas para professor e secretaria
                $matricula = null;
                if ($tipo_usuario === 'professor' || $tipo_usuario === 'secretaria') {
                    $matricula = strtoupper(substr($tipo_usuario, 0, 3)) . date('Y') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                }
                
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome_completo, email, senha, tipo_usuario, turma, serie, matricula, trocar_senha_proximo_login) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $email, $senha_hash, $tipo_usuario, $turma, $serie, $matricula, $trocar_senha_proximo_login]);
                
                $success = 'Usuário adicionado com sucesso!';
                
                // Recarregar usuários
                $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY data_criacao DESC");
                $usuarios = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            error_log("Erro ao adicionar usuário: " . $e->getMessage());
            $error = 'Erro ao adicionar usuário.';
        }
    }
}

// Editar usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $tipo_usuario = sanitizeInput($_POST['tipo_usuario'] ?? '');
    $turma = sanitizeInput($_POST['turma'] ?? '');
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $nova_senha = $_POST['nova_senha'] ?? '';
    
    if (empty($nome) || empty($email) || empty($tipo_usuario)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } elseif (!isValidEmail($email)) {
        $error = 'Email inválido.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Verificar se email já existe (exceto para o mesmo usuário)
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $usuario_id]);
            
            if ($stmt->fetch()) {
                $error = 'Este email já está cadastrado para outro usuário.';
            } else {
                // Atualizar matrícula se necessário
                $matricula = null;
                if ($tipo_usuario === 'professor' || $tipo_usuario === 'secretaria') {
                    // Verificar se jáTem matrícula
                    $stmt = $pdo->prepare("SELECT matricula FROM usuarios WHERE id = ?");
                    $stmt->execute([$usuario_id]);
                    $usuario_atual = $stmt->fetch();
                    
                    if (!$usuario_atual['matricula']) {
                        $matricula = strtoupper(substr($tipo_usuario, 0, 3)) . date('Y') . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                    }
                }
                
                // Atualizar usuário
                if (!empty($nova_senha)) {
                    if (strlen($nova_senha) < 6) {
                        $error = 'A nova senha deve ter no mínimo 6 caracteres.';
                    } else {
                        $senha_hash = hashPassword($nova_senha);
                        $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo = ?, email = ?, senha = ?, tipo_usuario = ?, turma = ?, serie = ?, matricula = COALESCE(?, matricula) WHERE id = ?");
                        $stmt->execute([$nome, $email, $senha_hash, $tipo_usuario, $turma, $serie, $matricula, $usuario_id]);
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome_completo = ?, email = ?, tipo_usuario = ?, turma = ?, serie = ?, matricula = COALESCE(?, matricula) WHERE id = ?");
                    $stmt->execute([$nome, $email, $tipo_usuario, $turma, $serie, $matricula, $usuario_id]);
                }
                
                $success = 'Usuário atualizado com sucesso!';
                
                // Recarregar usuários
                $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY data_criacao DESC");
                $usuarios = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            error_log("Erro ao editar usuário: " . $e->getMessage());
            $error = 'Erro ao editar usuário.';
        }
    }
}

// Atualizar status do usuário
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: usuarios.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao atualizar status: " . $e->getMessage());
    }
}

// Excluir usuário
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND id != ?");
        $stmt->execute([intval($_GET['id']), $_SESSION['usuario_id']]);
        header('Location: usuarios.php');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir usuário: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários | Portal CEAA</title>
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
                        <img src="../img/logo1.png" alt="Logo CEAA" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">CENTRO EDUCACIONAL</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">NOME DA ESCOLA</span>
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
                                <p class="text-sm text-gray-500">Administrador</p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gerenciar Usuários</h1>
                <p class="text-gray-600 mt-2">Adicionar e gerenciar alunos, professores e administradores</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>
                Adicionar Usuário
            </button>
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

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Nome</th>
                            <th class="px-4 sm:px-6 py-4 hidden sm:table-cell">Email</th>
                            <th class="px-4 sm:px-6 py-4">Tipo</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Turma/Série</th>
                            <th class="px-4 sm:px-6 py-4 hidden lg:table-cell">Matrícula</th>
                            <th class="px-4 sm:px-6 py-4 hidden lg:table-cell">Data Cadastro</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                            <th class="px-4 sm:px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                                            <?php echo strtoupper(substr($usuario['nome_completo'], 0, 1)); ?>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm truncate max-w-[150px] sm:max-w-none"><?php echo htmlspecialchars($usuario['nome_completo']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 hidden sm:table-cell text-sm"><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                                        <?php 
                                        $tipo_class = match($usuario['tipo_usuario']) {
                                            'aluno' => 'bg-blue-100 text-blue-600',
                                            'professor' => 'bg-green-100 text-green-600',
                                            'secretaria' => 'bg-orange-100 text-orange-600',
                                            'admin' => 'bg-purple-100 text-purple-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $tipo_class;
                                        ?>">
                                        <?php echo ucfirst($usuario['tipo_usuario']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 hidden md:table-cell text-sm">
                                    <?php echo htmlspecialchars($usuario['turma'] ?? '-'); ?> / <?php echo htmlspecialchars($usuario['serie'] ?? '-'); ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 hidden lg:table-cell text-sm"><?php echo htmlspecialchars($usuario['matricula'] ?? '-'); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden lg:table-cell"><?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $usuario['ativo'] ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                        <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-1 sm:gap-2">
                                        <button onclick="editUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nome_completo'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($usuario['email'], ENT_QUOTES); ?>', '<?php echo $usuario['tipo_usuario']; ?>', '<?php echo htmlspecialchars($usuario['turma'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($usuario['serie'] ?? '', ENT_QUOTES); ?>')" 
                                            class="p-1.5 sm:p-2 rounded-lg hover:bg-blue-100 text-blue-600 transition-colors" title="Editar">
                                            <i class="fas fa-edit text-sm"></i>
                                        </button>
                                        <a href="?action=toggle_status&id=<?php echo $usuario['id']; ?>" class="p-1.5 sm:p-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors" title="<?php echo $usuario['ativo'] ? 'Desativar' : 'Ativar'; ?>">
                                            <i class="fas fa-<?php echo $usuario['ativo'] ? 'ban' : 'check'; ?> text-sm"></i>
                                        </a>
                                        <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                            <a href="?action=excluir&id=<?php echo $usuario['id']; ?>" class="p-1.5 sm:p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                                                <i class="fas fa-trash text-sm"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Adicionar Usuário -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Adicionar Novo Usuário</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="adicionar">
                    
                    <div class="mb-4">
                        <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
                        <input type="text" id="nome" name="nome" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Nome completo do usuário">
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="email@exemplo.com">
                    </div>
                    
                    <div class="mb-4">
                        <label for="senha" class="block text-sm font-semibold text-gray-700 mb-2">Senha *</label>
                        <input type="password" id="senha" name="senha" required minlength="6"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Mínimo 6 caracteres">
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="trocar_senha_proximo_login" id="trocar_senha_proximo_login"
                                class="w-5 h-5 text-azul-principal border-gray-300 rounded focus:ring-azul-principal">
                            <span class="text-sm text-gray-700">Usuário deve alterar senha no primeiro acesso</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-7">O usuário será notificado para criar uma nova senha após o primeiro login.</p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo_usuario" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Usuário *</label>
                        <select id="tipo_usuario" name="tipo_usuario" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="aluno">Aluno</option>
                            <option value="professor">Professor</option>
                            <option value="secretaria">Secretaria</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="turma" class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                            <input type="text" id="turma" name="turma"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                placeholder="Ex: Turma A">
                        </div>
                        
                        <div>
                            <label for="serie" class="block text-sm font-semibold text-gray-700 mb-2">Série</label>
                            <input type="text" id="serie" name="serie"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                placeholder="Ex: 1º Ano">
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Usuário
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Usuário -->
    <div id="modal-editar" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModalEditar()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Editar Usuário</h2>
                    <button onclick="toggleModalEditar()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="editar">
                    <input type="hidden" name="usuario_id" id="edit_usuario_id">
                    
                    <div class="mb-4">
                        <label for="edit_nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome Completo *</label>
                        <input type="text" id="edit_nome" name="nome" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Nome completo do usuário">
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_email" class="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                        <input type="email" id="edit_email" name="email" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="email@exemplo.com">
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_nova_senha" class="block text-sm font-semibold text-gray-700 mb-2">Nova Senha (deixe em branco para manter atual)</label>
                        <input type="password" id="edit_nova_senha" name="nova_senha" minlength="6"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                            placeholder="Mínimo 6 caracteres">
                    </div>
                    
                    <div class="mb-4">
                        <label for="edit_tipo_usuario" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Usuário *</label>
                        <select id="edit_tipo_usuario" name="tipo_usuario" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="aluno">Aluno</option>
                            <option value="professor">Professor</option>
                            <option value="secretaria">Secretaria</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="edit_turma" class="block text-sm font-semibold text-gray-700 mb-2">Turma</label>
                            <input type="text" id="edit_turma" name="turma"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                placeholder="Ex: Turma A">
                        </div>
                        
                        <div>
                            <label for="edit_serie" class="block text-sm font-semibold text-gray-700 mb-2">Série</label>
                            <input type="text" id="edit_serie" name="serie"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent transition-all"
                                placeholder="Ex: 1º Ano">
                        </div>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Atualizar Usuário
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function toggleModal() {
            const modal = document.getElementById('modal');
            modal.classList.toggle('hidden');
        }

        function toggleModalEditar() {
            const modal = document.getElementById('modal-editar');
            modal.classList.toggle('hidden');
        }

        function editUsuario(id, nome, email, tipo, turma, serie) {
            document.getElementById('edit_usuario_id').value = id;
            document.getElementById('edit_nome').value = nome;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_tipo_usuario').value = tipo;
            document.getElementById('edit_turma').value = turma;
            document.getElementById('edit_serie').value = serie;
            document.getElementById('edit_nova_senha').value = '';
            toggleModalEditar();
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

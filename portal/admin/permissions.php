<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Atribuir role ao usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_role') {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $role_id = intval($_POST['role_id'] ?? 0);
    
    if (empty($usuario_id) || empty($role_id)) {
        $error = 'Por favor, selecione o usuário e a role.';
    } else {
        if (assignRole($usuario_id, $role_id)) {
            $success = 'Role atribuída com sucesso!';
        } else {
            $error = 'Erro ao atribuir role.';
        }
    }
}

// Remover role do usuário
if (isset($_GET['action']) && $_GET['action'] === 'remove_role' && isset($_GET['usuario_id']) && isset($_GET['role_id'])) {
    if (removeRole(intval($_GET['usuario_id']), intval($_GET['role_id']))) {
        header('Location: permissions.php');
        exit();
    } else {
        $error = 'Erro ao remover role.';
    }
}

// Obter todos os usuários
$usuarios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo, email, tipo_usuario FROM usuarios WHERE ativo = 1 ORDER BY nome_completo");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter usuários: " . $e->getMessage());
}

// Obter todas as roles
$roles = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM roles WHERE ativo = 1 ORDER BY nivel DESC");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter roles: " . $e->getMessage());
}

// Obter todas as permissões
$permissoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM permissoes WHERE ativo = 1 ORDER BY modulo, nome");
    $permissoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter permissões: " . $e->getMessage());
}

// Obter permissões por role
$permissoes_por_role = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT rp.role_id, p.* 
        FROM role_permissoes rp 
        JOIN permissoes p ON rp.permissao_id = p.id 
        WHERE p.ativo = 1
    ");
    $result = $stmt->fetchAll();
    
    foreach ($result as $row) {
        $permissoes_por_role[$row['role_id']][] = $row;
    }
} catch (PDOException $e) {
    error_log("Erro ao obter permissões por role: " . $e->getMessage());
}

// Obter roles por usuário
$roles_por_usuario = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT ur.usuario_id, r.* 
        FROM usuario_roles ur 
        JOIN roles r ON ur.role_id = r.id 
        WHERE r.ativo = 1
    ");
    $result = $stmt->fetchAll();
    
    foreach ($result as $row) {
        $roles_por_usuario[$row['usuario_id']][] = $row;
    }
} catch (PDOException $e) {
    error_log("Erro ao obter roles por usuário: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Permissões | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gestão de Permissões</h1>
                <p class="text-gray-600 mt-2">Roles e permissões do sistema</p>
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

        <!-- Tabs -->
        <div class="flex gap-2 mb-6 border-b border-gray-200">
            <button onclick="showTab('roles')" id="tab-roles" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Roles</button>
            <button onclick="showTab('permissoes')" id="tab-permissoes" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Permissões</button>
            <button onclick="showTab('usuarios')" id="tab-usuarios" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Usuários</button>
        </div>

        <!-- Tab Roles -->
        <div id="content-roles" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Roles do Sistema</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Nome</th>
                                <th class="px-4 sm:px-6 py-4">Descrição</th>
                                <th class="px-4 sm:px-6 py-4">Nível</th>
                                <th class="px-4 sm:px-6 py-4">Permissões</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="font-bold text-gray-800"><?php echo htmlspecialchars($role['nome']); ?></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($role['descricao']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-azul-principal/10 text-azul-principal">
                                            <?php echo $role['nivel']; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="text-gray-600 text-sm"><?php echo count($permissoes_por_role[$role['id']] ?? []); ?> permissões</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Permissões -->
        <div id="content-permissoes" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Permissões do Sistema</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Módulo</th>
                                <th class="px-4 sm:px-6 py-4">Nome</th>
                                <th class="px-4 sm:px-6 py-4">Código</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($permissoes as $permissao): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-600">
                                            <?php echo ucfirst($permissao['modulo']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($permissao['nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm font-mono"><?php echo htmlspecialchars($permissao['codigo']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($permissao['descricao']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Usuários -->
        <div id="content-usuarios" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Atribuir Role ao Usuário</h3>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="assign_role">
                    
                    <div>
                        <label for="usuario_id" class="block text-sm font-semibold text-gray-700 mb-2">Usuário</label>
                        <select id="usuario_id" name="usuario_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nome_completo']); ?> (<?php echo ucfirst($usuario['tipo_usuario']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="role_id" class="block text-sm font-semibold text-gray-700 mb-2">Role</label>
                        <select id="role_id" name="role_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-plus mr-2"></i>Atribuir
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Roles por Usuário</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Usuário</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4">Roles</th>
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
                                            <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($usuario['nome_completo']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                            <?php echo ucfirst($usuario['tipo_usuario']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <?php if (isset($roles_por_usuario[$usuario['id']])): ?>
                                            <div class="flex flex-wrap gap-1">
                                                <?php foreach ($roles_por_usuario[$usuario['id']] as $role): ?>
                                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-azul-principal/10 text-azul-principal">
                                                        <?php echo htmlspecialchars($role['nome']); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">Nenhuma role</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <?php if (isset($roles_por_usuario[$usuario['id']])): ?>
                                            <button onclick="removerRole(<?php echo $usuario['id']; ?>)" class="px-3 py-1 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors text-sm">
                                                <i class="fas fa-trash mr-1"></i>Remover
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function showTab(tab) {
            // Esconder todos os conteúdos
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            
            // Remover estilo ativo de todas as tabs
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('text-azul-principal', 'border-b-2', 'border-azul-principal');
                el.classList.add('text-gray-500');
            });
            
            // Mostrar conteúdo selecionado
            document.getElementById('content-' + tab).classList.remove('hidden');
            
            // Adicionar estilo ativo à tab selecionada
            const tabElement = document.getElementById('tab-' + tab);
            tabElement.classList.add('text-azul-principal', 'border-b-2', 'border-azul-principal');
            tabElement.classList.remove('text-gray-500');
        }

        function removerRole(usuarioId) {
            if (confirm('Tem certeza que deseja remover todas as roles deste usuário?')) {
                // Obter todas as roles do usuário
                const roles = document.querySelectorAll(`[data-usuario="${usuarioId}"] .role-badge`);
                roles.forEach(role => {
                    const roleId = role.dataset.roleId;
                    window.location.href = `?action=remove_role&usuario_id=${usuarioId}&role_id=${roleId}`;
                });
            }
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

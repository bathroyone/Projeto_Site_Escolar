<?php
require_once '../config.php';

requireAdmin();

// Obter estatísticas
$stats = [];
try {
    $pdo = getDBConnection();
    
    // Total de usuários por tipo
    $stmt = $pdo->query("SELECT tipo_usuario, COUNT(*) as total FROM usuarios GROUP BY tipo_usuario");
    $stats['usuarios'] = $stmt->fetchAll();
    
    // Total de arquivos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM arquivos WHERE ativo = TRUE");
    $stats['arquivos'] = $stmt->fetch();
    
    // Total de turmas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM turmas WHERE ano_letivo = 2026");
    $stats['turmas'] = $stmt->fetch();
    
    // Total de eventos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos_calendario WHERE data_inicio >= CURDATE()");
    $stats['eventos'] = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}

// Obter usuários recentes
$usuarios_recentes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY data_criacao DESC LIMIT 10");
    $usuarios_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter usuários: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | Portal CEAA</title>
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
                    <a href="../index.html" class="flex items-center gap-2">
                        <img src="../img/logo1.png" alt="Logo CEAA" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">CENTRO EDUCACIONAL</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">ALAMEDA ARGENTINA</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-2 p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <span class="hidden md:block text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
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
        <!-- Bem-vindo -->
        <div class="mb-8">
            <h1 class="text-3xl font-display font-bold text-azul-principal">
                Painel Administrativo
            </h1>
            <p class="text-gray-600 mt-2">Gerenciamento do sistema escolar</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total de Usuários</p>
                        <p class="text-3xl font-bold text-azul-principal">
                            <?php echo array_sum(array_column($stats['usuarios'], 'total')); ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-azul-principal text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 space-y-2">
                    <?php foreach ($stats['usuarios'] as $u): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500"><?php echo ucfirst($u['tipo_usuario']); ?>s:</span>
                            <span class="font-semibold"><?php echo $u['total']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Arquivos</p>
                        <p class="text-3xl font-bold text-verde-complementar"><?php echo $stats['arquivos']['total'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-alt text-verde-complementar text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Turmas Ativas</p>
                        <p class="text-3xl font-bold text-amarelo-destaque"><?php echo $stats['turmas']['total'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chalkboard text-amarelo-destaque text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Eventos Futuros</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $stats['eventos']['total'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-calendar text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <a href="usuarios.php" class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-user-plus text-azul-principal text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Gerenciar Usuários</h3>
                        <p class="text-sm text-gray-500">Adicionar e gerenciar alunos e professores</p>
                    </div>
                </div>
            </a>
            
            <a href="turmas.php" class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-chalkboard-teacher text-verde-complementar text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Gerenciar Turmas</h3>
                        <p class="text-sm text-gray-500">Criar e gerenciar turmas e séries</p>
                    </div>
                </div>
            </a>
            
            <a href="arquivos.php" class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100 hover:shadow-md transition-shadow group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-folder-open text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Gerenciar Arquivos</h3>
                        <p class="text-sm text-gray-500">Visualizar e gerenciar todos os arquivos</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Usuários Recentes -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-xl font-display font-bold text-azul-principal">Usuários Recentes</h2>
                <a href="usuarios.php" class="text-sm text-azul-principal hover:underline">Ver todos</a>
            </div>
            <div class="p-6">
                <?php if (count($usuarios_recentes) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-sm text-gray-500 border-b border-gray-100">
                                    <th class="pb-3">Nome</th>
                                    <th class="pb-3">Email</th>
                                    <th class="pb-3">Tipo</th>
                                    <th class="pb-3">Turma/Série</th>
                                    <th class="pb-3">Data Cadastro</th>
                                    <th class="pb-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios_recentes as $usuario): ?>
                                    <tr class="border-b border-gray-50">
                                        <td class="py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold">
                                                    <?php echo strtoupper(substr($usuario['nome_completo'], 0, 1)); ?>
                                                </div>
                                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($usuario['nome_completo']); ?></span>
                                            </div>
                                        </td>
                                        <td class="py-4 text-gray-600"><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td class="py-4">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                                <?php echo $usuario['tipo_usuario'] === 'aluno' ? 'bg-blue-100 text-blue-600' : ($usuario['tipo_usuario'] === 'professor' ? 'bg-green-100 text-green-600' : 'bg-purple-100 text-purple-600'); ?>">
                                                <?php echo ucfirst($usuario['tipo_usuario']); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 text-gray-600">
                                            <?php echo htmlspecialchars($usuario['turma'] ?? '-'); ?> / <?php echo htmlspecialchars($usuario['serie'] ?? '-'); ?>
                                        </td>
                                        <td class="py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></td>
                                        <td class="py-4">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $usuario['ativo'] ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                                <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-users text-4xl mb-4"></i>
                        <p>Nenhum usuário cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
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

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
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #063b7a 0%, #0b4a8c 50%, #13843b 100%);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .action-card:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg shadow-lg sticky top-0 z-40">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <a href="../index.php" class="flex items-center gap-2 sm:gap-3 group">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:bg-white/30 transition-all">
                            <i class="fas fa-graduation-cap text-white text-lg sm:text-xl"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">CENTRO EDUCACIONAL</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">NOME DA ESCOLA</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-2 p-2 rounded-xl hover:bg-white/10 transition-all">
                            <div class="w-9 h-9 sm:w-11 sm:h-11 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-xl flex items-center justify-center text-azul-escuro font-bold shadow-lg">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <div class="hidden sm:block text-left">
                                <span class="text-white text-xs sm:text-sm font-medium block"><?php echo htmlspecialchars(substr($_SESSION['nome'], 0, 15)); ?></span>
                                <span class="text-white/70 text-xs">Administrador</span>
                            </div>
                            <i class="fas fa-chevron-down text-white/70 text-xs sm:text-sm"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 sm:mt-3 w-48 sm:w-56 glass-card rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-4 sm:p-5 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                                <p class="font-semibold text-white text-sm"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-xs sm:text-sm text-white/80">Administrador</p>
                            </div>
                            <div class="p-2">
                                <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Sair</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="px-4 sm:px-6 lg:px-8 py-6 sm:py-10">
        <!-- Bem-vindo -->
        <div class="mb-8 sm:mb-10">
            <div class="flex items-center gap-3 sm:gap-4 mb-2">
                <div class="w-2 h-10 sm:h-12 bg-gradient-to-b from-amarelo-destaque to-amarelo-claro rounded-full"></div>
                <div>
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-display font-bold text-azul-principal">
                        Painel Administrativo
                    </h1>
                    <p class="text-gray-600 mt-1 text-sm sm:text-base md:text-lg">Gerenciamento completo do sistema escolar</p>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8 sm:mb-10">
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-azul-principal to-azul-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-users text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Total de Usuários</p>
                        <p class="text-4xl font-bold text-azul-principal">
                            <?php echo array_sum(array_column($stats['usuarios'], 'total')); ?>
                        </p>
                    </div>
                </div>
                <div class="space-y-2 pt-4 border-t border-gray-100">
                    <?php foreach ($stats['usuarios'] as $u): ?>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600"><?php echo ucfirst($u['tipo_usuario']); ?>s</span>
                            <span class="font-bold text-azul-principal bg-blue-50 px-3 py-1 rounded-full"><?php echo $u['total']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-file-alt text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Arquivos</p>
                        <p class="text-4xl font-bold text-verde-complementar"><?php echo $stats['arquivos']['total'] ?? 0; ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-arrow-up text-green-500"></i>
                        <span>Arquivos disponíveis</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-chalkboard text-azul-escuro text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Turmas Ativas</p>
                        <p class="text-4xl font-bold text-amarelo-destaque"><?php echo $stats['turmas']['total'] ?? 0; ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span>Turmas ativas 2026</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card stat-card rounded-3xl p-6 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-600 to-purple-400 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-calendar text-white text-2xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm font-medium">Eventos Futuros</p>
                        <p class="text-4xl font-bold text-purple-600"><?php echo $stats['eventos']['total'] ?? 0; ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-clock text-purple-500"></i>
                        <span>Próximos eventos</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 sm:gap-6 mb-8 sm:mb-10">
            <a href="usuarios.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-azul-principal to-azul-claro rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-user-plus text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Gerenciar Usuários</h3>
                    <p class="text-sm text-gray-500">Adicionar e gerenciar alunos e professores</p>
                </div>
            </a>
            
            <a href="turmas.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Gerenciar Turmas</h3>
                    <p class="text-sm text-gray-500">Criar e gerenciar turmas e séries</p>
                </div>
            </a>
            
            <a href="arquivos.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-folder-open text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Gerenciar Arquivos</h3>
                    <p class="text-sm text-gray-500">Visualizar e gerenciar todos os arquivos</p>
                </div>
            </a>
            
            <a href="biblioteca.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-amarelo-destaque to-orange-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-book text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Biblioteca Virtual</h3>
                    <p class="text-sm text-gray-500">Gerenciar livros e categorias</p>
                </div>
            </a>
            
            <a href="imagens.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-images text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Gerenciar Imagens</h3>
                    <p class="text-sm text-gray-500">Logo, álbuns e fotos do site</p>
                </div>
            </a>
            
            <a href="backup.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-gray-700 to-gray-600 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-database text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Backup & Restore</h3>
                    <p class="text-sm text-gray-500">Gerenciar backups do sistema</p>
                </div>
            </a>
            
            <a href="comunicados.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-teal-500 to-teal-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-bullhorn text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Comunicados</h3>
                    <p class="text-sm text-gray-500">Gerenciar comunicados oficiais</p>
                </div>
            </a>
            
            <a href="visitas.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-indigo-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-walking text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Visitas</h3>
                    <p class="text-sm text-gray-500">Agendamento de visitas</p>
                </div>
            </a>
            
            <a href="analytics.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-pink-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-pie text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Analytics</h3>
                    <p class="text-sm text-gray-500">Estatísticas do sistema</p>
                </div>
            </a>
            
            <a href="suporte.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-cyan-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-headset text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Suporte</h3>
                    <p class="text-sm text-gray-500">Gerenciar tickets de suporte</p>
                </div>
            </a>
            
            <a href="contratos.php" class="action-card glass-card rounded-3xl p-6 transition-all duration-300 hover:shadow-xl group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-rose-500 to-rose-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-contract text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Contratos</h3>
                    <p class="text-sm text-gray-500">Gestão de contratos</p>
                </div>
            </a>
        </div>

        <!-- Usuários Recentes -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-4 sm:p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro flex items-center justify-between">
                <h2 class="text-lg sm:text-xl font-display font-bold text-white">
                    <i class="fas fa-users mr-2"></i>Usuários Recentes
                </h2>
                <a href="usuarios.php" class="text-xs sm:text-sm text-white/90 hover:text-white transition-colors">
                    Ver todos <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="p-4 sm:p-6">
                <?php if (count($usuarios_recentes) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[600px]">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100">
                                    <th class="pb-3 sm:pb-4 font-semibold">Nome</th>
                                    <th class="pb-3 sm:pb-4 font-semibold hidden sm:table-cell">Email</th>
                                    <th class="pb-3 sm:pb-4 font-semibold">Tipo</th>
                                    <th class="pb-3 sm:pb-4 font-semibold">Turma/Série</th>
                                    <th class="pb-3 sm:pb-4 font-semibold hidden sm:table-cell">Data Cadastro</th>
                                    <th class="pb-3 sm:pb-4 font-semibold">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios_recentes as $usuario): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                        <td class="py-3 sm:py-4">
                                            <div class="flex items-center gap-2 sm:gap-3">
                                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl flex items-center justify-center text-white font-bold shadow-md text-xs sm:text-sm">
                                                    <?php echo strtoupper(substr($usuario['nome_completo'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <span class="font-semibold text-gray-800 text-xs sm:text-sm"><?php echo htmlspecialchars(substr($usuario['nome_completo'], 0, 20)); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 sm:py-4 text-gray-600 text-xs sm:text-sm hidden sm:table-cell"><?php echo htmlspecialchars($usuario['email']); ?></td>
                                        <td class="py-3 sm:py-4">
                                            <span class="px-2 py-1 sm:px-4 sm:py-2 rounded-full text-xs font-bold
                                                <?php echo $usuario['tipo_usuario'] === 'aluno' ? 'bg-blue-100 text-blue-700' : ($usuario['tipo_usuario'] === 'professor' ? 'bg-green-100 text-green-700' : 'bg-purple-100 text-purple-700'); ?>">
                                                <?php echo ucfirst($usuario['tipo_usuario']); ?>
                                            </span>
                                        </td>
                                        <td class="py-3 sm:py-4 text-gray-600 text-xs sm:text-sm">
                                            <span class="text-xs sm:text-sm"><?php echo htmlspecialchars($usuario['turma'] ?? '-'); ?> / <?php echo htmlspecialchars($usuario['serie'] ?? '-'); ?></span>
                                        </td>
                                        <td class="py-3 sm:py-4 text-gray-600 text-xs sm:text-sm hidden sm:table-cell"><?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></td>
                                        <td class="py-3 sm:py-4">
                                            <span class="px-2 py-1 sm:px-4 sm:py-2 rounded-full text-xs font-bold <?php echo $usuario['ativo'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                                <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 text-gray-500">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-gray-300 text-3xl"></i>
                        </div>
                        <p class="text-lg font-medium">Nenhum usuário cadastrado ainda.</p>
                        <p class="text-sm mt-2">Comece adicionando novos usuários ao sistema.</p>
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

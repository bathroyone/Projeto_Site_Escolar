<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Obter estatísticas do sistema
$stats = [];

try {
    $pdo = getDBConnection();
    
    // Total de usuários por tipo
    $stmt = $pdo->query("SELECT tipo_usuario, COUNT(*) as total FROM usuarios WHERE ativo = 1 GROUP BY tipo_usuario");
    $stats['usuarios'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Total de turmas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM turmas WHERE ano_letivo = YEAR(CURDATE())");
    $stats['turmas'] = $stmt->fetch()['total'];
    
    // Total de disciplinas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disciplinas");
    $stats['disciplinas'] = $stmt->fetch()['total'];
    
    // Eventos este mês
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos WHERE MONTH(data_inicio) = MONTH(CURDATE()) AND YEAR(data_inicio) = YEAR(CURDATE())");
    $stats['eventos_mes'] = $stmt->fetch()['total'];
    
    // Comunicados ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM comunicados WHERE ativo = 1");
    $stats['comunicados'] = $stmt->fetch()['total'];
    
    // Visitas hoje
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM visitas WHERE data_visita = CURDATE()");
    $stats['visitas_hoje'] = $stmt->fetch()['total'];
    
    // Logs de auditoria hoje
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM audit_logs WHERE DATE(created_at) = CURDATE()");
    $stats['logs_hoje'] = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}

// Obter relatórios gerados
$relatorios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT ar.*, u.nome_completo as gerador_nome 
        FROM analytics_relatorios ar 
        JOIN usuarios u ON ar.gerado_por = u.id 
        ORDER BY ar.created_at DESC LIMIT 20
    ");
    $relatorios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter relatórios: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics e Estatísticas | Portal de Gestão Escolar</title>
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
        <div class="mb-8">
            <h1 class="text-3xl font-display font-bold text-azul-principal">Analytics e Estatísticas</h1>
            <p class="text-gray-600 mt-2">Visão geral do sistema</p>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total de Alunos</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $stats['usuarios']['aluno'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-graduate text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total de Professores</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $stats['usuarios']['professor'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chalkboard-teacher text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total de Turmas</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $stats['turmas'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Disciplinas</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $stats['disciplinas'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-book text-orange-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Segunda linha de estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Eventos este Mês</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $stats['eventos_mes'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-pink-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Comunicados Ativos</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $stats['comunicados'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-bullhorn text-indigo-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Visitas Hoje</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $stats['visitas_hoje'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-user-clock text-teal-600 text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Logs de Auditoria Hoje</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $stats['logs_hoje'] ?? 0; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-history text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribuição de Usuários -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Distribuição de Usuários</h3>
                <div class="space-y-4">
                    <?php 
                    $tipos_usuario = ['aluno', 'professor', 'secretaria', 'admin'];
                    $cores = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-orange-500'];
                    $total_usuarios = array_sum($stats['usuarios'] ?? []);
                    foreach ($tipos_usuario as $index => $tipo): 
                        $quantidade = $stats['usuarios'][$tipo] ?? 0;
                        $percentual = $total_usuarios > 0 ? round(($quantidade / $total_usuarios) * 100, 1) : 0;
                    ?>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700"><?php echo ucfirst($tipo); ?></span>
                                <span class="text-sm text-gray-500"><?php echo $quantidade; ?> (<?php echo $percentual; ?>%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="<?php echo $cores[$index]; ?> h-2 rounded-full" style="width: <?php echo $percentual; ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Ações Recentes</h3>
                <div class="space-y-3">
                    <?php 
                    try {
                        $pdo = getDBConnection();
                        $stmt = $pdo->query("SELECT al.*, u.nome_completo as usuario_nome FROM audit_logs al JOIN usuarios u ON al.usuario_id = u.id ORDER BY al.created_at DESC LIMIT 5");
                        $logs_recentes = $stmt->fetchAll();
                        
                        foreach ($logs_recentes as $log): 
                    ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-10 h-10 bg-azul-principal rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($log['usuario_nome'], 0, 1)); ?>
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($log['usuario_nome']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($log['acao']); ?></p>
                            </div>
                            <span class="text-xs text-gray-400"><?php echo date('H:i', strtotime($log['created_at'])); ?></span>
                        </div>
                    <?php 
                        endforeach;
                    } catch (PDOException $e) {
                        echo '<p class="text-gray-500">Erro ao carregar ações recentes.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Relatórios Gerados -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h3 class="text-lg font-bold text-azul-principal">Relatórios Gerados</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Título</th>
                            <th class="px-4 sm:px-6 py-4">Tipo</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Período</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Gerado por</th>
                            <th class="px-4 sm:px-6 py-4">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($relatorios as $relatorio): ?>
                            <tr cla ss="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($relatorio['titulo']); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                        <?php echo ucfirst($relatorio['tipo']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell">
                                    <?php echo date('d/m/Y', strtotime($relatorio['data_inicio'])); ?> a <?php echo date('d/m/Y', strtotime($relatorio['data_fim'])); ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($relatorio['gerador_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y H:i', strtotime($relatorio['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($relatorios)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-chart-bar text-4xl mb-2"></i>
                    <p>Nenhum relatório gerado ainda.</p>
                </div>
            <?php endif; ?>
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

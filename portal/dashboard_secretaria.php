<?php
session_start();
require_once 'config.php';

// Verificar se o usuário está logado e é secretaria ou admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] !== 'secretaria' && $_SESSION['tipo'] !== 'admin')) {
    header('Location: login.php');
    exit();
}

// Conectar ao banco de dados
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

// Obter estatísticas de mensalidades
$alunos_em_dia = 0;
$alunos_devendo = 0;
$total_alunos = 0;

$query_total = "SELECT COUNT(*) as total FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = 1";
$result_total = $conn->query($query_total);
$total_alunos = $result_total->fetch_assoc()['total'];

$query_em_dia = "SELECT COUNT(*) as total FROM aluno_mensalidades WHERE status = 'pago' AND mes_referencia = MONTH(CURRENT_DATE()) AND ano_referencia = YEAR(CURRENT_DATE())";
$result_em_dia = $conn->query($query_em_dia);
$alunos_em_dia = $result_em_dia->fetch_assoc()['total'];

$query_devendo = "SELECT COUNT(*) as total FROM aluno_mensalidades WHERE status IN ('pendente', 'atrasado') AND mes_referencia = MONTH(CURRENT_DATE()) AND ano_referencia = YEAR(CURRENT_DATE())";
$result_devendo = $conn->query($query_devendo);
$alunos_devendo = $result_devendo->fetch_assoc()['total'];

// Obter lista de alunos com status de mensalidade
$alunos_status = [];
$query_alunos = "SELECT u.id, u.nome_completo, u.serie, u.turma, 
                 (SELECT COUNT(*) FROM aluno_mensalidades WHERE aluno_id = u.id AND status = 'pago' AND mes_referencia = MONTH(CURRENT_DATE()) AND ano_referencia = YEAR(CURRENT_DATE())) as pago,
                 (SELECT COUNT(*) FROM aluno_mensalidades WHERE aluno_id = u.id AND status IN ('pendente', 'atrasado') AND mes_referencia = MONTH(CURRENT_DATE()) AND ano_referencia = YEAR(CURRENT_DATE())) as devendo
                 FROM usuarios u 
                 WHERE u.tipo_usuario = 'aluno' AND u.ativo = 1
                 ORDER BY u.nome_completo";
$result_alunos = $conn->query($query_alunos);

while ($row = $result_alunos->fetch_assoc()) {
    $alunos_status[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Secretaria | Portal de Gestão Escolar</title>
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
                    },
                    animation: {
                        'pulse-blue': 'pulse-blue 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'pulse-red': 'pulse-red 2s cubic-bezier(0.4, 0, 0.6, 1) infinite'
                    },
                    keyframes: {
                        'pulse-blue': {
                            '0%, 100%': { opacity: '1', boxShadow: '0 0 0 0 rgba(59, 130, 246, 0.7)' },
                            '50%': { opacity: '0.7', boxShadow: '0 0 0 10px rgba(59, 130, 246, 0)' }
                        },
                        'pulse-red': {
                            '0%, 100%': { opacity: '1', boxShadow: '0 0 0 0 rgba(239, 68, 68, 0.7)' },
                            '50%': { opacity: '0.7', boxShadow: '0 0 0 10px rgba(239, 68, 68, 0)' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .status-em-dia {
            animation: pulse-blue 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .status-devendo {
            animation: pulse-red 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <a href="../index.php" class="flex items-center gap-2">
                        <img src="../img/logo.jpg" alt="Logo" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">[Inserir nome da escola aqui]</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">[Inserir nome da escola aqui]</span>
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
                                <p class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($_SESSION['tipo']); ?></p>
                            </div>
                            <div class="p-2">
                                <a href="logout.php" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
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
        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total de Alunos</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $total_alunos; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-azul-principal/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-users text-azul-principal text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Alunos em Dia</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $alunos_em_dia; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Alunos Devendo</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $alunos_devendo; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas -->
        <h2 class="text-xl font-display font-bold text-azul-principal mb-4">Ações Rápidas</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <a href="secretaria/pre_matriculas.php" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-user-graduate text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Pré-Matrículas</h3>
                    <p class="text-sm text-gray-500">Aprovar e gerenciar solicitações</p>
                </div>
            </a>
            
            <a href="admin/mensalidades.php" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-azul-principal to-azul-claro rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-money-bill-wave text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Mensalidades</h3>
                    <p class="text-sm text-gray-500">Gerenciar mensalidades</p>
                </div>
            </a>
            
            <a href="admin/financeiro.php" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-line text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Financeiro</h3>
                    <p class="text-sm text-gray-500">Gestão financeira</p>
                </div>
            </a>
            
            <a href="admin/documentos.php" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-amarelo-destaque to-orange-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-folder-open text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Documentos</h3>
                    <p class="text-sm text-gray-500">Documentação de alunos</p>
                </div>
            </a>
            
            <a href="admin/relatorios.php" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-16 h-16 bg-gradient-to-br from-pink-500 to-pink-400 rounded-2xl flex items-center justify-center shadow-lg mb-4 group-hover:scale-110 transition-transform">
                        <i class="fas fa-file-alt text-white text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Relatórios</h3>
                    <p class="text-sm text-gray-500">Relatórios e estatísticas</p>
                </div>
            </a>
        </div>

        <!-- Lista de Alunos com Status -->
        <h2 class="text-xl font-display font-bold text-azul-principal mb-4">Status de Mensalidades</h2>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px]">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Aluno</th>
                            <th class="px-4 sm:px-6 py-4 hidden sm:table-cell">Série</th>
                            <th class="px-4 sm:px-6 py-4 hidden sm:table-cell">Turma</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alunos_status as $aluno): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold flex-shrink-0">
                                            <?php echo strtoupper(substr($aluno['nome_completo'], 0, 1)); ?>
                                        </div>
                                        <span class="font-medium text-gray-800 text-sm truncate max-w-[150px] sm:max-w-none"><?php echo htmlspecialchars($aluno['nome_completo']); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 hidden sm:table-cell text-sm"><?php echo htmlspecialchars($aluno['serie'] ?? '-'); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 hidden sm:table-cell text-sm"><?php echo htmlspecialchars($aluno['turma'] ?? '-'); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <?php if ($aluno['pago'] > 0): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600 status-em-dia">
                                            <span class="w-2 h-2 bg-blue-600 rounded-full mr-2 animate-pulse"></span>
                                            Em Dia
                                        </span>
                                    <?php elseif ($aluno['devendo'] > 0): ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-600 status-devendo">
                                            <span class="w-2 h-2 bg-red-600 rounded-full mr-2 animate-pulse"></span>
                                            Devendo
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                            Pendente
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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

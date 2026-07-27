<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é secretaria
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'secretaria') {
    header('Location: ../login.php');
    exit();
}

// Obter estatísticas gerais
$estatisticas_gerais = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT CASE WHEN YEAR(m.data_matricula) = YEAR(CURDATE()) THEN m.id END) as matriculas_ano,
            COUNT(DISTINCT CASE WHEN m.status = 'ativa' THEN m.id END) as matriculas_ativas,
            COUNT(DISTINCT CASE WHEN m.status = 'cancelada' THEN m.id END) as matriculas_canceladas,
            COUNT(DISTINCT CASE WHEN m.status = 'trancada' THEN m.id END) as matriculas_trancadas
        FROM matriculas m
    ");
    $estatisticas_gerais = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas gerais: " . $e->getMessage());
}

// Obter matrículas por turma
$matriculas_por_turma = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT t.nome as turma_nome, t.serie, COUNT(m.id) as total_alunos
        FROM turmas t
        LEFT JOIN matriculas m ON t.id = m.turma_id AND m.status = 'ativa'
        GROUP BY t.id, t.nome, t.serie
        ORDER BY t.serie, t.nome
    ");
    $matriculas_por_turma = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter matrículas por turma: " . $e->getMessage());
}

// Obter matrículas por mês (último ano)
$matriculas_por_mes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            MONTH(data_matricula) as mes,
            YEAR(data_matricula) as ano,
            COUNT(*) as total
        FROM matriculas
        WHERE data_matricula >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY MONTH(data_matricula), YEAR(data_matricula)
        ORDER BY ano DESC, mes DESC
    ");
    $matriculas_por_mes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter matrículas por mês: " . $e->getMessage());
}

// Obter pré-matrículas
$pre_matriculas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
            COUNT(CASE WHEN status = 'aprovada' THEN 1 END) as aprovadas,
            COUNT(CASE WHEN status = 'rejeitada' THEN 1 END) as rejeitadas
        FROM pre_matriculas
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    ");
    $pre_matriculas = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter pré-matrículas: " . $e->getMessage());
}

// Obter taxa de retenção
$taxa_retensao = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            (COUNT(CASE WHEN m.status = 'ativa' THEN 1 END) * 100.0 / NULLIF(COUNT(*), 0)) as taxa_retensao
        FROM matriculas m
        WHERE YEAR(m.data_matricula) = YEAR(CURDATE()) - 1
    ");
    $taxa_retensao = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter taxa de retenção: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estatísticas de Matrícula | Portal da Secretaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg shadow-lg sticky top-0 z-40">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 sm:gap-3 group">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:bg-white/30 transition-all">
                            <i class="fas fa-arrow-left text-white text-lg sm:text-xl"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">ESTATÍSTICAS DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">MATRÍCULA</span>
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
                                <span class="text-white/70 text-xs">Secretaria</span>
                            </div>
                            <i class="fas fa-chevron-down text-white/70 text-xs sm:text-sm"></i>
                        </button>

                        <div id="user-menu" class="hidden absolute right-0 mt-2 sm:mt-3 w-48 sm:w-56 glass-card rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-4 sm:p-5 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                                <p class="font-semibold text-white text-sm"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-xs sm:text-sm text-white/80">Secretaria</p>
                            </div>
                            <div class="p-2">
                                <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-home"></i>
                                    <span>Painel Secretaria</span>
                                </a>
                                <a href="../dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
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

    <main class="px-4 sm:px-6 lg:px-8 py-8">
        <h2 class="text-2xl font-bold text-azul-principal mb-8">Estatísticas de Matrícula</h2>

        <!-- Estatísticas Gerais -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-azul-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-user-plus text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Este Ano</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo $estatisticas_gerais['matriculas_ano'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Ativas</p>
                        <p class="text-3xl font-bold text-verde-complementar"><?php echo $estatisticas_gerais['matriculas_ativas'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-times-circle text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Canceladas</p>
                        <p class="text-3xl font-bold text-red-500"><?php echo $estatisticas_gerais['matriculas_canceladas'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-pause-circle text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Trancadas</p>
                        <p class="text-3xl font-bold text-yellow-500"><?php echo $estatisticas_gerais['matriculas_trancadas'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Matrículas por Mês -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-chart-line mr-2"></i>Matrículas por Mês
                </h3>
            </div>
            <div class="p-6">
                <canvas id="chartMatriculasMes" height="100"></canvas>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 mb-8">
            <!-- Matrículas por Turma -->
            <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-amarelo-destaque to-amarelo-claro">
                    <h3 class="text-xl font-display font-bold text-azul-escuro">
                        <i class="fas fa-users mr-2"></i>Matrículas por Turma
                    </h3>
                </div>
                <div class="p-6">
                    <canvas id="chartMatriculasTurma" height="200"></canvas>
                </div>
            </div>

            <!-- Pré-Matrículas -->
            <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-verde-complementar to-verde-claro">
                    <h3 class="text-xl font-display font-bold text-white">
                        <i class="fas fa-clipboard-list mr-2"></i>Pré-Matrículas
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div class="text-center p-4 bg-yellow-50 rounded-xl">
                            <p class="text-3xl font-bold text-yellow-500"><?php echo $pre_matriculas['pendentes'] ?? 0; ?></p>
                            <p class="text-sm text-gray-600">Pendentes</p>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-xl">
                            <p class="text-3xl font-bold text-green-500"><?php echo $pre_matriculas['aprovadas'] ?? 0; ?></p>
                            <p class="text-sm text-gray-600">Aprovadas</p>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-xl">
                            <p class="text-3xl font-bold text-red-500"><?php echo $pre_matriculas['rejeitadas'] ?? 0; ?></p>
                            <p class="text-sm text-gray-600">Rejeitadas</p>
                        </div>
                    </div>
                    <canvas id="chartPreMatriculas" height="150"></canvas>
                </div>
            </div>
        </div>

        <!-- Taxa de Retenção -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-percentage mr-2"></i>Taxa de Retenção
                </h3>
            </div>
            <div class="p-6">
                <div class="flex items-center justify-center">
                    <div class="text-center">
                        <p class="text-6xl font-bold text-azul-principal mb-2"><?php echo number_format($taxa_retensao['taxa_retensao'] ?? 0, 1); ?>%</p>
                        <p class="text-gray-600">Taxa de retenção do ano anterior</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = event.target.closest('button');
            if (!button && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        // Gráfico de Matrículas por Mês
        const ctxMes = document.getElementById('chartMatriculasMes').getContext('2d');
        new Chart(ctxMes, {
            type: 'line',
            data: {
                labels: <?php 
                    $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
                    echo json_encode($meses);
                ?>,
                datasets: [{
                    label: 'Matrículas',
                    data: <?php 
                        $dados = array_fill(0, 12, 0);
                        foreach ($matriculas_por_mes as $item) {
                            $dados[$item['mes'] - 1] = $item['total'];
                        }
                        echo json_encode($dados);
                    ?>,
                    borderColor: '#063b7a',
                    backgroundColor: 'rgba(6, 59, 122, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Gráfico de Matrículas por Turma
        const ctxTurma = document.getElementById('chartMatriculasTurma').getContext('2d');
        new Chart(ctxTurma, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($matriculas_por_turma, 'turma_nome')); ?>,
                datasets: [{
                    label: 'Alunos',
                    data: <?php echo json_encode(array_column($matriculas_por_turma, 'total_alunos')); ?>,
                    backgroundColor: '#13843b'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Gráfico de Pré-Matrículas
        const ctxPre = document.getElementById('chartPreMatriculas').getContext('2d');
        new Chart(ctxPre, {
            type: 'doughnut',
            data: {
                labels: ['Pendentes', 'Aprovadas', 'Rejeitadas'],
                datasets: [{
                    data: [<?php echo ($pre_matriculas['pendentes'] ?? 0) . ', ' . ($pre_matriculas['aprovadas'] ?? 0) . ', ' . ($pre_matriculas['rejeitadas'] ?? 0); ?>],
                    backgroundColor: ['#ffd000', '#13843b', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>

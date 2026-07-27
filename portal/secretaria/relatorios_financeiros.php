<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é secretaria
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'secretaria') {
    header('Location: ../login.php');
    exit();
}

$ano_filtro = $_GET['ano'] ?? date('Y');
$mes_filtro = $_GET['mes'] ?? '';

// Obter estatísticas financeiras
$estatisticas = [];
try {
    $pdo = getDBConnection();
    
    $where_clause = "WHERE YEAR(m.data_vencimento) = ?";
    $params = [$ano_filtro];
    
    if ($mes_filtro) {
        $where_clause .= " AND MONTH(m.data_vencimento) = ?";
        $params[] = $mes_filtro;
    }
    
    $sql = "
        SELECT 
            COUNT(CASE WHEN m.status = 'pago' THEN 1 END) as total_pagas,
            COUNT(CASE WHEN m.status = 'pendente' THEN 1 END) as total_pendentes,
            COUNT(CASE WHEN m.status = 'pendente' AND m.data_vencimento < CURDATE() THEN 1 END) as total_atraso,
            SUM(CASE WHEN m.status = 'pago' THEN m.valor_pago ELSE 0 END) as total_arrecadado,
            SUM(CASE WHEN m.status = 'pendente' THEN m.valor ELSE 0 END) as total_pendente,
            SUM(CASE WHEN m.status = 'pendente' AND m.data_vencimento < CURDATE() THEN m.valor ELSE 0 END) as total_atraso_valor,
            AVG(CASE WHEN m.status = 'pago' THEN DATEDIFF(m.data_pagamento, m.data_vencimento) END) as media_dias_pagamento
        FROM mensalidades m
        $where_clause
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $estatisticas = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}

// Obter detalhamento por mês
$detalhamento_mensal = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(m.data_vencimento) as mes,
            SUM(CASE WHEN m.status = 'pago' THEN m.valor_pago ELSE 0 END) as arrecadado,
            SUM(CASE WHEN m.status = 'pendente' THEN m.valor ELSE 0 END) as pendente,
            COUNT(CASE WHEN m.status = 'pago' THEN 1 END) as pagas,
            COUNT(CASE WHEN m.status = 'pendente' THEN 1 END) as pendentes
        FROM mensalidades m
        WHERE YEAR(m.data_vencimento) = ?
        GROUP BY MONTH(m.data_vencimento)
        ORDER BY mes
    ");
    $stmt->execute([$ano_filtro]);
    $detalhamento_mensal = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter detalhamento mensal: " . $e->getMessage());
}

// Obter inadimplência por turma
$inadimplencia_turma = [];
try {
    $pdo = getDBConnection();
    
    $where_clause = "WHERE YEAR(m.data_vencimento) = ?";
    $params = [$ano_filtro];
    
    if ($mes_filtro) {
        $where_clause .= " AND MONTH(m.data_vencimento) = ?";
        $params[] = $mes_filtro;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            t.nome as turma,
            t.serie,
            COUNT(DISTINCT mat.aluno_id) as total_alunos,
            COUNT(CASE WHEN m.status = 'pendente' AND m.data_vencimento < CURDATE() THEN 1 END) as alunos_atraso,
            SUM(CASE WHEN m.status = 'pendente' AND m.data_vencimento < CURDATE() THEN m.valor ELSE 0 END) as valor_atraso
        FROM mensalidades m
        JOIN matriculas mat ON m.matricula_id = mat.id
        JOIN turmas t ON mat.turma_id = t.id
        $where_clause
        GROUP BY t.id, t.nome, t.serie
        ORDER BY valor_atraso DESC
    ");
    $stmt->execute($params);
    $inadimplencia_turma = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter inadimplência por turma: " . $e->getMessage());
}

// Obter forma de pagamento
$formas_pagamento = [];
try {
    $pdo = getDBConnection();
    
    $where_clause = "WHERE YEAR(m.data_pagamento) = ?";
    $params = [$ano_filtro];
    
    if ($mes_filtro) {
        $where_clause .= " AND MONTH(m.data_pagamento) = ?";
        $params[] = $mes_filtro;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            forma_pagamento,
            COUNT(*) as quantidade,
            SUM(valor_pago) as total
        FROM mensalidades m
        $where_clause AND m.status = 'pago'
        GROUP BY forma_pagamento
        ORDER BY total DESC
    ");
    $stmt->execute($params);
    $formas_pagamento = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter formas de pagamento: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Financeiros | Portal da Secretaria</title>
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
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">RELATÓRIOS</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">FINANCEIROS</span>
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
        <!-- Filtros -->
        <div class="glass-card rounded-3xl p-6 mb-8">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Ano</label>
                    <select name="ano" onchange="window.location.href='?ano='+this.value+'&mes=<?php echo $mes_filtro; ?>'" class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                            <option value="<?php echo $i; ?>" <?php echo $ano_filtro == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Mês</label>
                    <select name="mes" onchange="window.location.href='?ano=<?php echo $ano_filtro; ?>&mes='+this.value" class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <option value="">Todos</option>
                        <?php 
                        $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                        foreach ($meses as $index => $mes): 
                            $mes_num = $index + 1;
                        ?>
                            <option value="<?php echo $mes_num; ?>" <?php echo $mes_filtro == $mes_num ? 'selected' : ''; ?>><?php echo $mes; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button onclick="window.location.href='relatorios_financeiros.php'" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Limpar Filtros
                </button>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-azul-principal mb-8">Relatórios Financeiros Detalhados</h2>

        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-dollar-sign text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Arrecadado</p>
                        <p class="text-3xl font-bold text-verde-complementar">R$ <?php echo number_format($estatisticas['total_arrecadado'] ?? 0, 2, ',', '.'); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span><?php echo $estatisticas['total_pagas'] ?? 0; ?> mensalidades pagas</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Pendente</p>
                        <p class="text-3xl font-bold text-yellow-500">R$ <?php echo number_format($estatisticas['total_pendente'] ?? 0, 2, ',', '.'); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-hourglass-half text-yellow-500"></i>
                        <span><?php echo $estatisticas['total_pendentes'] ?? 0; ?> mensalidades pendentes</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Em Atraso</p>
                        <p class="text-3xl font-bold text-red-500">R$ <?php echo number_format($estatisticas['total_atraso_valor'] ?? 0, 2, ',', '.'); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-calendar-times text-red-500"></i>
                        <span><?php echo $estatisticas['total_atraso'] ?? 0; ?> mensalidades em atraso</span>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-azul-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-calendar-day text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Média Dias</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo number_format($estatisticas['media_dias_pagamento'] ?? 0, 1); ?></p>
                    </div>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-chart-line text-azul-principal"></i>
                        <span>Dias para pagamento</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Arrecadação Mensal -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-chart-area mr-2"></i>Arrecadação Mensal
                </h3>
            </div>
            <div class="p-6">
                <canvas id="graficoArrecadacao" height="100"></canvas>
            </div>
        </div>

        <!-- Inadimplência por Turma -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-red-500 to-red-400">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-chart-bar mr-2"></i>Inadimplência por Turma
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($inadimplencia_turma) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Turma</th>
                                    <th class="px-4 sm:px-6 py-4">Série</th>
                                    <th class="px-4 sm:px-6 py-4">Total Alunos</th>
                                    <th class="px-4 sm:px-6 py-4">Alunos em Atraso</th>
                                    <th class="px-4 sm:px-6 py-4">Valor em Atraso</th>
                                    <th class="px-4 sm:px-6 py-4">% Inadimplência</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inadimplencia_turma as $turma): ?>
                                    <?php 
                                    $percentual_inadimplencia = $turma['total_alunos'] > 0 ? round(($turma['alunos_atraso'] / $turma['total_alunos']) * 100) : 0;
                                    ?>
                                    <tr class="border-b border-gray-50 hover:bg-red-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($turma['turma']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($turma['serie']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $turma['total_alunos']; ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $turma['alunos_atraso']; ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600">R$ <?php echo number_format($turma['valor_atraso'], 2, ',', '.'); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                                    <div class="h-full <?php echo $percentual_inadimplencia > 30 ? 'bg-red-500' : ($percentual_inadimplencia > 15 ? 'bg-yellow-500' : 'bg-green-500'); ?>" style="width: <?php echo $percentual_inadimplencia; ?>%"></div>
                                                </div>
                                                <span class="text-sm text-gray-600"><?php echo $percentual_inadimplencia; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-check-circle text-4xl mb-4 text-green-500"></i>
                        <p>Nenhuma inadimplência registrada.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formas de Pagamento -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-credit-card mr-2"></i>Formas de Pagamento
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($formas_pagamento) > 0): ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($formas_pagamento as $forma): ?>
                            <div class="bg-gray-50 rounded-xl p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-800"><?php echo ucfirst($forma['forma_pagamento']); ?></span>
                                    <span class="text-sm text-gray-500"><?php echo $forma['quantidade']; ?> pagamentos</span>
                                </div>
                                <div class="text-2xl font-bold text-azul-principal">R$ <?php echo number_format($forma['total'], 2, ',', '.'); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-credit-card text-4xl mb-4"></i>
                        <p>Nenhum pagamento registrado no período.</p>
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

        // Gráfico de Arrecadação Mensal
        const ctx = document.getElementById('graficoArrecadacao').getContext('2d');
        const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        const dadosArrecadacao = new Array(12).fill(0);
        const dadosPendente = new Array(12).fill(0);
        
        <?php foreach ($detalhamento_mensal as $dado): ?>
            dadosArrecadacao[<?php echo $dado['mes'] - 1; ?>] = <?php echo $dado['arrecadado']; ?>;
            dadosPendente[<?php echo $dado['mes'] - 1; ?>] = <?php echo $dado['pendente']; ?>;
        <?php endforeach; ?>

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: meses,
                datasets: [
                    {
                        label: 'Arrecadado',
                        data: dadosArrecadacao,
                        backgroundColor: 'rgba(19, 132, 59, 0.8)',
                        borderColor: 'rgba(19, 132, 59, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Pendente',
                        data: dadosPendente,
                        backgroundColor: 'rgba(255, 208, 0, 0.8)',
                        borderColor: 'rgba(255, 208, 0, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = event.target.closest('button');
            if (!button && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

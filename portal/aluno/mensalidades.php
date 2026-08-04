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

// Conectar ao banco de dados
$pdo = getDBConnection();

// Obter mensalidades do aluno
$mensalidades = [];
try {
    $stmt = $pdo->query("
        SELECT 
            fm.*,
            CASE 
                WHEN fm.status = 'pago' THEN 'Pago'
                WHEN fm.status = 'pendente' AND fm.data_vencimento < CURDATE() THEN 'Atrasado'
                WHEN fm.status = 'pendente' THEN 'Pendente'
                ELSE fm.status
            END as status_formatado
        FROM financeiro_mensalidades fm
        WHERE fm.aluno_id = ?
        ORDER BY fm.ano_letivo DESC, fm.mes_referencia DESC
    ");
    $stmt->execute([$aluno_id]);
    $mensalidades = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter mensalidades: " . $e->getMessage());
}

// Calcular estatísticas
$estatisticas = [
    'total' => count($mensalidades),
    'pagas' => 0,
    'pendentes' => 0,
    'atrasadas' => 0,
    'valor_total' => 0,
    'valor_pago' => 0,
    'valor_pendente' => 0
];

foreach ($mensalidades as $mensalidade) {
    $estatisticas['valor_total'] += $mensalidade['valor'];
    
    if ($mensalidade['status'] === 'pago') {
        $estatisticas['pagas']++;
        $estatisticas['valor_pago'] += $mensalidade['valor'];
    } elseif ($mensalidade['status'] === 'pendente') {
        if ($mensalidade['data_vencimento'] && strtotime($mensalidade['data_vencimento']) < time()) {
            $estatisticas['atrasadas']++;
            $estatisticas['valor_pendente'] += $mensalidade['valor'];
        } else {
            $estatisticas['pendentes']++;
            $estatisticas['valor_pendente'] += $mensalidade['valor'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensalidades e Pagamentos | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Mensalidades e Pagamentos</h1>
                <p class="text-gray-600 mt-2">Visualize suas mensalidades e histórico de pagamentos</p>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total a Pagar</p>
                        <p class="text-2xl font-bold text-azul-principal">R$ <?php echo number_format($estatisticas['valor_pendente'], 2, ',', '.'); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-azul-principal/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-azul-principal text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total Pago</p>
                        <p class="text-2xl font-bold text-green-600">R$ <?php echo number_format($estatisticas['valor_pago'], 2, ',', '.'); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Pendentes</p>
                        <p class="text-2xl font-bold text-yellow-600"><?php echo $estatisticas['pendentes']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-yellow-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Atrasadas</p>
                        <p class="text-2xl font-bold text-red-600"><?php echo $estatisticas['atrasadas']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Mensalidades -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Histórico de Mensalidades</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Referência</th>
                            <th class="px-4 sm:px-6 py-4">Valor</th>
                            <th class="px-4 sm:px-6 py-4">Vencimento</th>
                            <th class="px-4 sm:px-6 py-4">Data Pagamento</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mensalidades as $mensalidade): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($mensalidade['mes_referencia']); ?>/<?php echo $mensalidade['ano_letivo']; ?></div>
                                    <div class="text-sm text-gray-500">Ano Letivo: <?php echo $mensalidade['ano_letivo']; ?></div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 font-semibold text-gray-800">
                                    R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm">
                                    <?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm">
                                    <?php echo $mensalidade['data_pagamento'] ? date('d/m/Y', strtotime($mensalidade['data_pagamento'])) : '-'; ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php 
                                        $cor_status = match($mensalidade['status']) {
                                            'pago' => 'bg-green-100 text-green-600',
                                            'pendente' => ($mensalidade['data_vencimento'] && strtotime($mensalidade['data_vencimento']) < time()) ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-600',
                                            'cancelado' => 'bg-gray-100 text-gray-600',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                        echo $cor_status;
                                        ?>">
                                        <?php echo $mensalidade['status_formatado']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($mensalidades)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-receipt text-4xl mb-2"></i>
                    <p>Nenhuma mensalidade encontrada.</p>
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

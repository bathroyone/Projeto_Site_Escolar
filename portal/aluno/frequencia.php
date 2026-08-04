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

// Obter dados de frequência
$frequencia_detalhada = [];
try {
    $stmt = $pdo->query("
        SELECT 
            cd.data_aula,
            cd.conteudo,
            d.nome as disciplina_nome,
            u.nome_completo as professor_nome,
            cp.status,
            cp.observacoes
        FROM chamadas_digitais cd
        JOIN disciplinas d ON cd.disciplina_id = d.id
        JOIN usuarios u ON cd.professor_id = u.id
        LEFT JOIN chamada_presenca cp ON cd.id = cp.chamada_id AND cp.aluno_id = ?
        WHERE cd.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        ORDER BY cd.data_aula DESC
        LIMIT 50
    ");
    $stmt->execute([$aluno_id, $turma, $serie]);
    $frequencia_detalhada = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter frequência: " . $e->getMessage());
}

// Calcular estatísticas
$estatisticas = [
    'total_aulas' => count($frequencia_detalhada),
    'presente' => 0,
    'ausente' => 0,
    'atrasado' => 0,
    'justificado' => 0,
    'nao_registrado' => 0
];

foreach ($frequencia_detalhada as $registro) {
    if ($registro['status']) {
        $status = $registro['status'];
        if (isset($estatisticas[$status])) {
            $estatisticas[$status]++;
        }
    } else {
        $estatisticas['nao_registrado']++;
    }
}

$percentual_presenca = $estatisticas['total_aulas'] > 0 
    ? round(($estatisticas['presente'] / $estatisticas['total_aulas']) * 100, 1) 
    : 0;

// Agrupar por disciplina
$frequencia_por_disciplina = [];
foreach ($frequencia_detalhada as $registro) {
    $disciplina = $registro['disciplina_nome'];
    if (!isset($frequencia_por_disciplina[$disciplina])) {
        $frequencia_por_disciplina[$disciplina] = [
            'total' => 0,
            'presente' => 0,
            'ausente' => 0
        ];
    }
    $frequencia_por_disciplina[$disciplina]['total']++;
    if ($registro['status'] === 'presente') {
        $frequencia_por_disciplina[$disciplina]['presente']++;
    } elseif ($registro['status'] === 'ausente') {
        $frequencia_por_disciplina[$disciplina]['ausente']++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frequência e Presença | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Frequência e Presença</h1>
                <p class="text-gray-600 mt-2">Registro de suas presenças nas aulas</p>
            </div>
            <button onclick="window.print()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-print mr-2"></i>Imprimir
            </button>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total de Aulas</p>
                        <p class="text-4xl font-bold text-azul-principal"><?php echo $estatisticas['total_aulas']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-azul-principal/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-calendar-check text-azul-principal text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">% Presença</p>
                        <p class="text-4xl font-bold <?php echo $percentual_presenca >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $percentual_presenca; ?>%
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-chart-pie text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Presentes</p>
                        <p class="text-4xl font-bold text-green-600"><?php echo $estatisticas['presente']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Ausentes</p>
                        <p class="text-4xl font-bold text-red-600"><?php echo $estatisticas['ausente']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-times-circle text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Atrasados</p>
                        <p class="text-4xl font-bold text-yellow-600"><?php echo $estatisticas['atrasado']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-yellow-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Frequência por Disciplina -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Frequência por Disciplina</h2>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <?php foreach ($frequencia_por_disciplina as $disciplina => $dados): ?>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($disciplina); ?></span>
                                    <span class="text-sm text-gray-500">
                                        <?php echo $dados['presente']; ?>/<?php echo $dados['total']; ?> aulas
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <?php 
                                    $percentual = $dados['total'] > 0 ? ($dados['presente'] / $dados['total']) * 100 : 0;
                                    ?>
                                    <div class="h-3 rounded-full 
                                        <?php echo $percentual >= 75 ? 'bg-green-500' : ($percentual >= 50 ? 'bg-yellow-500' : 'bg-red-500'); ?>"
                                        style="width: <?php echo $percentual; ?>%">
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-gray-700 mt-1"><?php echo round($percentual, 1); ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($frequencia_por_disciplina)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-chart-bar text-4xl mb-2"></i>
                        <p>Nenhum registro de frequência encontrado.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Histórico Detalhado -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Histórico Detalhado</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Data</th>
                            <th class="px-4 sm:px-6 py-4">Disciplina</th>
                            <th class="px-4 sm:px-6 py-4">Professor</th>
                            <th class="px-4 sm:px-6 py-4">Conteúdo</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($frequencia_detalhada as $registro): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="font-medium text-gray-800"><?php echo date('d/m/Y', strtotime($registro['data_aula'])); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('H:i', strtotime($registro['data_aula'])); ?></div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($registro['disciplina_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($registro['professor_nome']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm max-w-xs"><?php echo htmlspecialchars(substr($registro['conteudo'] ?? '', 0, 50)) . '...'; ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <?php if ($registro['status']): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($registro['status']) {
                                                'presente' => 'bg-green-100 text-green-600',
                                                'ausente' => 'bg-red-100 text-red-600',
                                                'atrasado' => 'bg-yellow-100 text-yellow-600',
                                                'justificado' => 'bg-blue-100 text-blue-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst($registro['status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">
                                            Não registrado
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (empty($frequencia_detalhada)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-clipboard-list text-4xl mb-2"></i>
                    <p>Nenhum registro de frequência encontrado.</p>
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

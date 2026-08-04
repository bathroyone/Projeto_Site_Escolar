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

// Obter notas por bimestre
$notas_por_bimestre = [];
try {
    $stmt = $pdo->query("
        SELECT 
            bimestre,
            AVG(nota) as media_geral,
            COUNT(*) as total_notas
        FROM notas
        WHERE aluno_id = ?
        GROUP BY bimestre
        ORDER BY bimestre
    ");
    $stmt->execute([$aluno_id]);
    $notas_por_bimestre = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter notas por bimestre: " . $e->getMessage());
}

// Obter frequência por bimestre
$frequencia_por_bimestre = [];
try {
    $stmt = $pdo->query("
        SELECT 
            QUARTER(cd.data_aula) as bimestre,
            COUNT(*) as total_aulas,
            SUM(CASE WHEN cp.status = 'presente' THEN 1 ELSE 0 END) as aulas_presentes
        FROM chamadas_digitais cd
        LEFT JOIN chamada_presenca cp ON cd.id = cp.chamada_id AND cp.aluno_id = ?
        WHERE cd.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        GROUP BY QUARTER(cd.data_aula)
        ORDER BY QUARTER(cd.data_aula)
    ");
    $stmt->execute([$aluno_id, $turma, $serie]);
    $frequencia_por_bimestre = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter frequência por bimestre: " . $e->getMessage());
}

// Obter estatísticas gerais
$estatisticas = [
    'media_geral' => 0,
    'frequencia_geral' => 0,
    'melhor_disciplina' => '',
    'pior_disciplina' => ''
];

try {
    // Média geral
    $stmt = $pdo->prepare("SELECT AVG(nota) as media FROM notas WHERE aluno_id = ?");
    $stmt->execute([$aluno_id]);
    $media = $stmt->fetch();
    $estatisticas['media_geral'] = $media['media'] ? round($media['media'], 1) : 0;
    
    // Melhor e pior disciplina
    $stmt = $pdo->prepare("
        SELECT 
            disciplina,
            AVG(nota) as media
        FROM notas
        WHERE aluno_id = ?
        GROUP BY disciplina
        ORDER BY media DESC
        LIMIT 1
    ");
    $stmt->execute([$aluno_id]);
    $melhor = $stmt->fetch();
    $estatisticas['melhor_disciplina'] = $melhor['disciplina'] ?? '-';
    
    $stmt = $pdo->prepare("
        SELECT 
            disciplina,
            AVG(nota) as media
        FROM notas
        WHERE aluno_id = ?
        GROUP BY disciplina
        ORDER BY media ASC
        LIMIT 1
    ");
    $stmt->execute([$aluno_id]);
    $pior = $stmt->fetch();
    $estatisticas['pior_disciplina'] = $pior['disciplina'] ?? '-';
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progresso Anual | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Progresso Anual</h1>
                <p class="text-gray-600 mt-2">Acompanhe sua evolução durante o ano letivo</p>
            </div>
            <button onclick="window.print()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-print mr-2"></i>Imprimir
            </button>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Média Geral</p>
                        <p class="text-4xl font-bold <?php echo $estatisticas['media_geral'] >= 7 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $estatisticas['media_geral']; ?>
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-azul-principal/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-azul-principal text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Melhor Disciplina</p>
                        <p class="text-lg font-bold text-green-600"><?php echo htmlspecialchars($estatisticas['melhor_disciplina']); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-trophy text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Pior Disciplina</p>
                        <p class="text-lg font-bold text-orange-600"><?php echo htmlspecialchars($estatisticas['pior_disciplina']); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-arrow-down text-orange-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Série</p>
                        <p class="text-2xl font-bold text-azul-principal"><?php echo htmlspecialchars($serie); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-azul-principal/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-azul-principal text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Progresso por Bimestre -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Progresso por Bimestre</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Médias por Bimestre -->
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-4">Média de Notas</h3>
                        <div class="space-y-4">
                            <?php for($i = 1; $i <= 4; $i++): ?>
                                <?php 
                                $bimestre_data = array_filter($notas_por_bimestre, fn($b) => $b['bimestre'] == $i);
                                $bimestre_data = reset($bimestre_data);
                                $media = $bimestre_data['media_geral'] ?? 0;
                                ?>
                                <div class="flex items-center gap-4">
                                    <span class="w-20 text-sm font-semibold text-gray-700"><?php echo $i; ?>º Bim</span>
                                    <div class="flex-1 bg-gray-200 rounded-full h-4">
                                        <div class="h-4 rounded-full 
                                            <?php echo $media >= 7 ? 'bg-green-500' : ($media >= 5 ? 'bg-yellow-500' : 'bg-red-500'); ?>"
                                            style="width: <?php echo min($media * 10, 100); ?>%">
                                        </div>
                                    </div>
                                    <span class="w-12 text-sm font-bold text-gray-800"><?php echo round($media, 1); ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <!-- Frequência por Bimestre -->
                    <div>
                        <h3 class="font-semibold text-gray-800 mb-4">Frequência</h3>
                        <div class="space-y-4">
                            <?php for($i = 1; $i <= 4; $i++): ?>
                                <?php 
                                $bimestre_data = array_filter($frequencia_por_bimestre, fn($b) => $b['bimestre'] == $i);
                                $bimestre_data = reset($bimestre_data);
                                $percentual = $bimestre_data && $bimestre_data['total_aulas'] > 0 
                                    ? round(($bimestre_data['aulas_presentes'] / $bimestre_data['total_aulas']) * 100, 1) 
                                    : 0;
                                ?>
                                <div class="flex items-center gap-4">
                                    <span class="w-20 text-sm font-semibold text-gray-700"><?php echo $i; ?>º Bim</span>
                                    <div class="flex-1 bg-gray-200 rounded-full h-4">
                                        <div class="h-4 rounded-full 
                                            <?php echo $percentual >= 75 ? 'bg-green-500' : ($percentual >= 50 ? 'bg-yellow-500' : 'bg-red-500'); ?>"
                                            style="width: <?php echo $percentual; ?>%">
                                        </div>
                                    </div>
                                    <span class="w-12 text-sm font-bold text-gray-800"><?php echo $percentual; ?>%</span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resumo Detalhado -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Resumo Detalhado</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Bimestre</th>
                            <th class="px-4 sm:px-6 py-4">Média</th>
                            <th class="px-4 sm:px-6 py-4">Total Notas</th>
                            <th class="px-4 sm:px-6 py-4">Frequência</th>
                            <th class="px-4 sm:px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for($i = 1; $i <= 4; $i++): ?>
                            <?php 
                            $bimestre_notas = array_filter($notas_por_bimestre, fn($b) => $b['bimestre'] == $i);
                            $bimestre_notas = reset($bimestre_notas);
                            $media = $bimestre_notas['media_geral'] ?? 0;
                            $total_notas = $bimestre_notas['total_notas'] ?? 0;
                            
                            $bimestre_freq = array_filter($frequencia_por_bimestre, fn($b) => $b['bimestre'] == $i);
                            $bimestre_freq = reset($bimestre_freq);
                            $percentual = $bimestre_freq && $bimestre_freq['total_aulas'] > 0 
                                ? round(($bimestre_freq['aulas_presentes'] / $bimestre_freq['total_aulas']) * 100, 1) 
                                : 0;
                            
                            $status = ($media >= 7 && $percentual >= 75) ? 'Aprovado' : 'Atenção';
                            ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 font-semibold text-gray-800"><?php echo $i; ?>º Bimestre</td>
                                <td class="px-4 sm:px-6 py-4 font-semibold <?php echo $media >= 7 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo round($media, 1); ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo $total_notas; ?></td>
                                <td class="px-4 sm:px-6 py-4 font-semibold <?php echo $percentual >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $percentual; ?>%
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php echo $status === 'Aprovado' ? 'bg-green-100 text-green-600' : 'bg-orange-100 text-orange-600'; ?>">
                                        <?php echo $status; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endfor; ?>
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

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

// Obter histórico de notas por ano letivo
$historico = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            n.bimestre,
            n.disciplina,
            n.nota,
            n.tipo_avaliacao,
            n.data_lancamento,
            u.nome_completo as professor_nome,
            YEAR(n.data_lancamento) as ano
        FROM notas n 
        JOIN usuarios u ON n.professor_id = u.id 
        WHERE n.aluno_id = ?
        ORDER BY YEAR(n.data_lancamento) DESC, n.bimestre, n.disciplina
    ");
    $stmt->execute([$aluno_id]);
    $notas = $stmt->fetchAll();
    
    // Agrupar por ano e bimestre
    foreach ($notas as $nota) {
        $ano = $nota['ano'];
        $bimestre = $nota['bimestre'];
        if (!isset($historico[$ano])) {
            $historico[$ano] = [];
        }
        if (!isset($historico[$ano][$bimestre])) {
            $historico[$ano][$bimestre] = [];
        }
        $historico[$ano][$bimestre][] = $nota;
    }
} catch (PDOException $e) {
    error_log("Erro ao obter histórico: " . $e->getMessage());
}

// Calcular evolução por disciplina
$evolucao = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            disciplina,
            bimestre,
            AVG(nota) as media,
            YEAR(data_lancamento) as ano
        FROM notas 
        WHERE aluno_id = ?
        GROUP BY disciplina, bimestre, YEAR(data_lancamento)
        ORDER BY disciplina, YEAR(data_lancamento), bimestre
    ");
    $stmt->execute([$aluno_id]);
    $evolucao = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter evolução: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Notas | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Histórico de Notas</h1>
                <p class="text-gray-600 mt-2">Evolução do seu desempenho ao longo do tempo</p>
            </div>
        </div>

        <!-- Histórico por Ano -->
        <div class="space-y-8">
            <?php foreach ($historico as $ano => $bimestres): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                        <h2 class="text-xl font-display font-bold text-white">
                            <i class="fas fa-calendar-alt mr-2"></i>Ano <?php echo $anoi; ?>
                        </h2>
                    </div>
                    
                    <?php foreach ($bimestres as $bimestre => $notas): ?>
                        <div class="border-b border-gray-100 last:border-b-0">
                            <div class="p-4 bg-gray-50 flex items-center justify-between">
                                <h3 class="font-bold text-gray-800"><?php echo $bimestre; ?>º Bimestre</h3>
                                <?php 
                                $media = array_sum(array_column($notas, 'nota')) / count($notas);
                                ?>
                                <span class="px-3 py-1 rounded-full text-sm font-semibold 
                                    <?php echo $media >= 7 ? 'bg-green-100 text-green-600' : ($media >= 5 ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600'); ?>">
                                    Média: <?php echo round($media, 1); ?>
                                </span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                            <th class="px-4 sm:px-6 py-4">Disciplina</th>
                                            <th class="px-4 sm:px-6 py-4">Professor</th>
                                            <th class="px-4 sm:px-6 py-4">Tipo</th>
                                            <th class="px-4 sm:px-6 py-4">Nota</th>
                                            <th class="px-4 sm:px-6 py-4">Data</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($notas as $nota): ?>
                                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                                <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($nota['disciplina']); ?></td>
                                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($nota['professor_nome']); ?></td>
                                                <td class="px-4 sm:px-6 py-4">
                                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                                        <?php echo ucfirst($nota['tipo_avaliacao']); ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 sm:px-6 py-4">
                                                    <span class="text-2xl font-bold 
                                                        <?php echo $nota['nota'] >= 7 ? 'text-green-600' : ($nota['nota'] >= 5 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                                        <?php echo $nota['nota']; ?>
                                                    </span>
                                                </td>
                                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm">
                                                    <?php echo date('d/m/Y', strtotime($nota['data_lancamento'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($historico)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                    <i class="fas fa-history text-4xl mb-2"></i>
                    <p>Nenhum histórico de notas encontrado.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Evolução por Disciplina -->
        <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Evolução por Disciplina</h2>
            </div>
            <div class="p-6">
                <?php if (!empty($evolucao)): ?>
                    <div class="space-y-4">
                        <?php foreach ($evolucao as $item): ?>
                            <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($item['disciplina']); ?></span>
                                        <span class="text-sm text-gray-500"><?php echo $item['ano']; ?> - <?php echo $item['bimestre']; ?>º Bimestre</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-3">
                                        <div class="h-3 rounded-full 
                                            <?php echo $item['media'] >= 7 ? 'bg-green-500' : ($item['media'] >= 5 ? 'bg-yellow-500' : 'bg-red-500'); ?>"
                                            style="width: <?php echo min($item['media'] * 10, 100); ?>%">
                                        </div>
                                    </div>
                                    <span class="text-sm font-bold text-gray-700 mt-1"><?php echo round($item['media'], 1); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-gray-500">
                        <i class="fas fa-chart-line text-4xl mb-2"></i>
                        <p>Nenhuma evolução registrada.</p>
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

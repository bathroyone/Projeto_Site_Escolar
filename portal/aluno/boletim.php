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
$conn = getDBConnection();

// Obter notas do aluno agrupadas por bimestre
$notas_por_bimestre = [];
try {
    $stmt = $conn->prepare("
        SELECT n.*, u.nome_completo as professor_nome 
        FROM notas n 
        JOIN usuarios u ON n.professor_id = u.id 
        WHERE n.aluno_id = ?
        ORDER BY n.bimestre, n.disciplina
    ");
    $stmt->execute([$aluno_id]);
    $notas = $stmt->fetchAll();
    
    foreach ($notas as $nota) {
        $bimestre = $nota['bimestre'];
        if (!isset($notas_por_bimestre[$bimestre])) {
            $notas_por_bimestre[$bimestre] = [];
        }
        $notas_por_bimestre[$bimestre][] = $nota;
    }
} catch (PDOException $e) {
    error_log("Erro ao obter notas: " . $e->getMessage());
}

// Calcular médias por bimestre
$medias_bimestre = [];
foreach ($notas_por_bimestre as $bimestre => $notas) {
    $soma = 0;
    $count = 0;
    foreach ($notas as $nota) {
        $soma += $nota['nota'];
        $count++;
    }
    $medias_bimestre[$bimestre] = $count > 0 ? round($soma / $count, 1) : 0;
}

// Calcular média geral
$media_geral = count($medias_bimestre) > 0 ? round(array_sum($medias_bimestre) / count($medias_bimestre), 1) : 0;

// Obter frequência do aluno
$frequencia = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_aulas,
            SUM(CASE WHEN status = 'presente' THEN 1 ELSE 0 END) as presentes,
            SUM(CASE WHEN status = 'ausente' THEN 1 ELSE 0 END) as ausentes,
            SUM(CASE WHEN status = 'atrasado' THEN 1 ELSE 0 END) as atrasados
        FROM chamada 
        WHERE aluno_id = ?
    ");
    $stmt->execute([$aluno_id]);
    $frequencia = $stmt->fetch();
    
    $frequencia['percentual'] = $frequencia['total_aulas'] > 0 
        ? round(($frequencia['presentes'] / $frequencia['total_aulas']) * 100, 1) 
        : 0;
} catch (PDOException $e) {
    error_log("Erro ao obter frequência: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boletim Escolar | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Boletim Escolar</h1>
                <p class="text-gray-600 mt-2">Seu desempenho acadêmico</p>
            </div>
            <button onclick="window.print()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-print mr-2"></i>Imprimir
            </button>
        </div>

        <!-- Cards de Resumo -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Média Geral</p>
                        <p class="text-4xl font-bold <?php echo $media_geral >= 7 ? 'text-green-600' : ($media_geral >= 5 ? 'text-yellow-600' : 'text-red-600'); ?>">
                            <?php echo $media_geral; ?>
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
                        <p class="text-gray-500 text-sm mb-1">Frequência</p>
                        <p class="text-4xl font-bold <?php echo $frequencia['percentual'] >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $frequencia['percentual']; ?>%
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-clipboard-check text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Faltas</p>
                        <p class="text-4xl font-bold text-red-600">
                            <?php echo $frequencia['ausentes'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-user-times text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Boletim por Bimestre -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Notas por Bimestre</h2>
            </div>
            
            <?php foreach ($notas_por_bimestre as $bimestre => $notas): ?>
                <div class="border-b border-gray-100 last:border-b-0">
                    <div class="p-4 bg-gray-50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-800"><?php echo $bimestre; ?>º Bimestre</h3>
                        <span class="px-3 py-1 rounded-full text-sm font-semibold 
                            <?php echo ($medias_bimestre[$bimestre] ?? 0) >= 7 ? 'bg-green-100 text-green-600' : (($medias_bimestre[$bimestre] ?? 0) >= 5 ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600'); ?>">
                            Média: <?php echo $medias_bimestre[$bimestre] ?? 0; ?>
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Disciplina</th>
                                    <th class="px-4 sm:px-6 py-4">Professor</th>
                                    <th class="px-4 sm:px-6 py-4">Nota</th>
                                    <th class="px-4 sm:px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notas as $nota): ?>
                                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($nota['disciplina']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($nota['professor_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="text-2xl font-bold 
                                                <?php echo $nota['nota'] >= 7 ? 'text-green-600' : ($nota['nota'] >= 5 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                                <?php echo $nota['nota']; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php echo $nota['nota'] >= 7 ? 'bg-green-100 text-green-600' : ($nota['nota'] >= 5 ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600'); ?>">
                                                <?php echo $nota['nota'] >= 7 ? 'Aprovado' : ($nota['nota'] >= 5 ? 'Recuperação' : 'Reprovado'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($notas_por_bimestre)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-clipboard-list text-4xl mb-2"></i>
                    <p>Nenhuma nota registrada ainda.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Informações do Aluno -->
        <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-xl font-display font-bold text-azul-principal mb-4">Informações do Aluno</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-gray-500 text-sm">Nome</p>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Turma</p>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($turma); ?></p>
                </div>
                <div>
                    <p class="text-gray-500 text-sm">Série</p>
                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($serie); ?></p>
                </div>
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

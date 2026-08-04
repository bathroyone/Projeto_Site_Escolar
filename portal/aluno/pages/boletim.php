<?php
require_once '../../config.php';

requireLogin();

if (!isAluno()) {
    header('Location: ../../dashboard.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$turma = $_SESSION['turma'];
$serie = $_SESSION['serie'];

// Obter notas do aluno agrupadas por bimestre
$notas_por_bimestre = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
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
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
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

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Boletim Escolar</h2>
        <button onclick="window.print()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-print mr-2"></i>Imprimir
        </button>
    </div>
</div>

<!-- Cards de Resumo -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Média Geral</p>
                <p class="text-4xl font-bold <?php echo $media_geral >= 7 ? 'text-green-600' : ($media_geral >= 5 ? 'text-yellow-600' : 'text-red-600'); ?>">
                    <?php echo $media_geral; ?>
                </p>
            </div>
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-chart-line text-primary-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Frequência</p>
                <p class="text-4xl font-bold <?php echo $frequencia['percentual'] >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $frequencia['percentual']; ?>%
                </p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-clipboard-check text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Faltas</p>
                <p class="text-4xl font-bold text-red-600">
                    <?php echo $frequencia['ausentes'] ?? 0; ?>
                </p>
            </div>
            <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-times text-red-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Boletim por Bimestre -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Notas por Bimestre</h3>
    </div>
    
    <?php foreach ($notas_por_bimestre as $bimestre => $notas): ?>
        <div class="border-b border-gray-200 last:border-b-0">
            <div class="p-4 bg-gray-50 flex items-center justify-between">
                <h4 class="font-bold text-gray-800"><?php echo $bimestre; ?>º Bimestre</h4>
                <span class="px-3 py-1 rounded-full text-sm font-medium 
                    <?php echo ($medias_bimestre[$bimestre] ?? 0) >= 7 ? 'bg-green-100 text-green-600' : (($medias_bimestre[$bimestre] ?? 0) >= 5 ? 'bg-yellow-100 text-yellow-600' : 'bg-red-100 text-red-600'); ?>">
                    Média: <?php echo $medias_bimestre[$bimestre] ?? 0; ?>
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nota</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($notas as $nota): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($nota['disciplina']); ?></td>
                                <td class="px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($nota['professor_nome']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="text-2xl font-bold 
                                        <?php echo $nota['nota'] >= 7 ? 'text-green-600' : ($nota['nota'] >= 5 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo $nota['nota']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium 
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
<div class="mt-8 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
    <h3 class="font-semibold text-gray-800 mb-4">Informações do Aluno</h3>
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

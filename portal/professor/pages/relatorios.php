<?php
require_once '../config.php';

requireLogin();

if (!isProfessor()) {
    header('Location: ../../dashboard.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Obter turmas do professor
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT DISTINCT t.* 
        FROM turmas t 
        JOIN grade_aulas ga ON t.id = ga.turma_id 
        WHERE ga.professor_id = ?
    ");
    $stmt->execute([$professor_id]);
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter dados de desempenho
$desempenho = [];
$turma_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : 0;

if ($turma_id) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.nome_completo, 
                   AVG(n.nota) as media_geral,
                   COUNT(n.id) as total_notas,
                   (SELECT COUNT(*) FROM chamada c JOIN matriculas m ON c.aluno_id = m.aluno_id WHERE m.turma_id = ? AND c.aluno_id = u.id AND c.status = 'presente') as presencas,
                   (SELECT COUNT(*) FROM chamada c JOIN matriculas m ON c.aluno_id = m.aluno_id WHERE m.turma_id = ? AND c.aluno_id = u.id) as total_aulas
            FROM usuarios u
            JOIN matriculas m ON u.id = m.aluno_id
            LEFT JOIN notas n ON u.id = n.aluno_id
            WHERE m.turma_id = ? AND m.status = 'ativa' AND u.tipo_usuario = 'aluno'
            GROUP BY u.id
            ORDER BY media_geral DESC
        ");
        $stmt->execute([$turma_id, $turma_id, $turma_id]);
        $desempenho = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao obter desempenho: " . $e->getMessage());
    }
}
?>

<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Relatórios de Desempenho</h2>
</div>

<!-- Seleção de Turma -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
    <form method="GET" action="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                <select name="turma_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Selecione</option>
                    <?php foreach ($turmas as $turma): ?>
                        <option value="<?php echo $turma['id']; ?>" <?php echo $turma_id == $turma['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-chart-bar mr-2"></i>Gerar Relatório
                </button>
            </div>
        </div>
    </form>
</div>

<?php if ($turma_id && !empty($desempenho)): ?>
    <!-- Estatísticas Gerais -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm mb-1">Média da Turma</p>
                    <p class="text-3xl font-bold text-primary-600">
                        <?php 
                        $media_turma = array_sum(array_column($desempenho, 'media_geral')) / count($desempenho);
                        echo number_format($media_turma, 1, ',', '.');
                        ?>
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
                    <p class="text-gray-500 text-sm mb-1">Total Alunos</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo count($desempenho); ?></p>
                </div>
                <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm mb-1">Frequência Média</p>
                    <p class="text-3xl font-bold text-blue-600">
                        <?php 
                        $frequencia_media = array_sum(array_map(function($d) {
                            return $d['total_aulas'] > 0 ? ($d['presencas'] / $d['total_aulas']) * 100 : 0;
                        }, $desempenho)) / count($desempenho);
                        echo number_format($frequencia_media, 1, ',', '.'); ?>%
                    </p>
                </div>
                <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-clipboard-check text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm mb-1">Acima da Média</p>
                    <p class="text-3xl font-bold text-purple-600">
                        <?php 
                        $acima_media = count(array_filter($desempenho, function($d) use ($media_turma) {
                            return $d['media_geral'] >= $media_turma;
                        }));
                        echo $acima_media;
                        ?>
                    </p>
                </div>
                <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-star text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Desempenho -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Desempenho por Aluno</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Média Geral</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Notas</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Frequência</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($desempenho as $aluno): ?>
                        <?php 
                        $frequencia = $aluno['total_aulas'] > 0 ? ($aluno['presencas'] / $aluno['total_aulas']) * 100 : 0;
                        $status = $aluno['media_geral'] >= 7 && $frequencia >= 75 ? 'Aprovado' : 'Atenção';
                        $status_cor = $status === 'Aprovado' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600';
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($aluno['nome_completo']); ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo number_format($aluno['media_geral'], 1, ',', '.'); ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo $aluno['total_notas']; ?></td>
                            <td class="px-6 py-4 text-gray-600"><?php echo number_format($frequencia, 1, ',', '.'); ?>%</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_cor; ?>"><?php echo $status; ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php
require_once '../../config.php';

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

// Obter grade de aulas do professor
$grade_aulas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT ga.*, t.nome as turma_nome, t.serie 
        FROM grade_aulas ga 
        JOIN turmas t ON ga.turma_id = t.id 
        WHERE ga.professor_id = ?
        ORDER BY FIELD(ga.dia_semana, 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'), ga.horario_inicio
    ");
    $stmt->execute([$professor_id]);
    $grade_aulas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter grade de aulas: " . $e->getMessage());
}

// Obter trabalhos do professor
$trabalhos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT tc.*, t.nome as turma_nome 
        FROM trabalhos_correcoes tc 
        LEFT JOIN turmas t ON tc.turma_id = t.id 
        WHERE tc.professor_id = ?
        ORDER BY tc.data_upload DESC
        LIMIT 5
    ");
    $stmt->execute([$professor_id]);
    $trabalhos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter trabalhos: " . $e->getMessage());
}

// Obter notas lançadas
$notas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT n.*, u.nome_completo as aluno_nome, t.nome as turma_nome 
        FROM notas n 
        JOIN usuarios u ON n.aluno_id = u.id 
        LEFT JOIN matriculas m ON u.id = m.aluno_id
        LEFT JOIN turmas t ON m.turma_id = t.id
        WHERE n.professor_id = ?
        ORDER BY n.data_lancamento DESC
        LIMIT 5
    ");
    $stmt->execute([$professor_id]);
    $notas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter notas: " . $e->getMessage());
}
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-primary-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Turmas</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo count($turmas); ?></p>
        <p class="text-sm text-gray-500 mt-1">Turmas atribuídas</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-calendar text-green-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Aulas</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo count($grade_aulas); ?></p>
        <p class="text-sm text-gray-500 mt-1">Aulas na grade</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-star text-yellow-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Notas</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo count($notas); ?></p>
        <p class="text-sm text-gray-500 mt-1">Notas lançadas</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-tasks text-purple-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Trabalhos</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo count($trabalhos); ?></p>
        <p class="text-sm text-gray-500 mt-1">Trabalhos criados</p>
    </div>
</div>

<!-- Quick Actions -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Ações Rápidas</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <a href="#" onclick="loadContent('diario')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-primary-200 transition-colors">
                <i class="fas fa-book text-primary-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Diário</p>
        </a>
        
        <a href="#" onclick="loadContent('lancar_notas')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-green-200 transition-colors">
                <i class="fas fa-star text-green-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Lançar Notas</p>
        </a>
        
        <a href="#" onclick="loadContent('chamada')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-yellow-200 transition-colors">
                <i class="fas fa-clipboard-check text-yellow-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Chamada</p>
        </a>
        
        <a href="#" onclick="loadContent('planejamento')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-red-200 transition-colors">
                <i class="fas fa-calendar-alt text-red-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Planejamento</p>
        </a>
        
        <a href="#" onclick="loadContent('materiais')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-purple-200 transition-colors">
                <i class="fas fa-folder-open text-purple-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Materiais</p>
        </a>
        
        <a href="#" onclick="loadContent('provas')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-indigo-200 transition-colors">
                <i class="fas fa-file-alt text-indigo-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Provas</p>
        </a>
    </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Notas Recentes -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Notas Recentes</h3>
        </div>
        <div class="p-4">
            <?php if (count($notas) > 0): ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($notas, 0, 5) as $nota): ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold text-sm">
                                <?php echo strtoupper(substr($nota['aluno_nome'], 0, 1)); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo htmlspecialchars($nota['aluno_nome']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($nota['disciplina']); ?></p>
                            </div>
                            <p class="text-sm font-semibold text-primary-600"><?php echo number_format($nota['nota'], 1); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">Nenhuma nota lançada</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Trabalhos Recentes -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Trabalhos Recentes</h3>
        </div>
        <div class="p-4">
            <?php if (count($trabalhos) > 0): ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($trabalhos, 0, 5) as $trabalho): ?>
                        <div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 font-semibold text-sm">
                                <i class="fas fa-tasks"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo htmlspecialchars($trabalho['titulo']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($trabalho['turma_nome'] ?? 'Geral'); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">Nenhum trabalho criado</p>
            <?php endif; ?>
        </div>
    </div>
</div>

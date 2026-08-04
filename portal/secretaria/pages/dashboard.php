<?php
require_once '../../config.php';

requireLogin();

if (!isSecretaria()) {
    header('Location: ../../dashboard.php');
    exit();
}

$secretaria_id = $_SESSION['usuario_id'];

// Obter estatísticas de matrículas
$estatisticas_matriculas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT u.id) as total_alunos,
            COUNT(DISTINCT CASE WHEN u.ativo = 1 THEN u.id END) as alunos_ativos,
            COUNT(DISTINCT CASE WHEN u.ativo = 0 THEN u.id END) as alunos_inativos,
            COUNT(DISTINCT t.id) as total_turmas
        FROM usuarios u
        LEFT JOIN matriculas m ON u.id = m.aluno_id
        LEFT JOIN turmas t ON m.turma_id = t.id
        WHERE u.tipo_usuario = 'aluno'
    ");
    $estatisticas_matriculas = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}

// Obter matrículas recentes
$matriculas_recentes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT m.*, u.nome_completo as aluno_nome, t.nome as turma_nome, t.serie
        FROM matriculas m
        JOIN usuarios u ON m.aluno_id = u.id
        LEFT JOIN turmas t ON m.turma_id = t.id
        ORDER BY m.data_matricula DESC
        LIMIT 10
    ");
    $matriculas_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter matrículas recentes: " . $e->getMessage());
}

// Obter pré-matrículas pendentes
$pre_matriculas_pendentes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT * FROM pre_matriculas
        WHERE status = 'pendente'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $pre_matriculas_pendentes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter pré-matrículas: " . $e->getMessage());
}

// Obter mensalidades em atraso
$mensalidades_atraso = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT m.*, u.nome_completo as aluno_nome, t.nome as turma_nome
        FROM mensalidades m
        JOIN matriculas mat ON m.matricula_id = mat.id
        JOIN usuarios u ON mat.aluno_id = u.id
        LEFT JOIN turmas t ON mat.turma_id = t.id
        WHERE m.status = 'pendente' AND m.data_vencimento < CURDATE()
        ORDER BY m.data_vencimento ASC
        LIMIT 10
    ");
    $mensalidades_atraso = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter mensalidades em atraso: " . $e->getMessage());
}
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-graduate text-primary-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Total</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo $estatisticas_matriculas['total_alunos'] ?? 0; ?></p>
        <p class="text-sm text-gray-500 mt-1">Alunos matriculados</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-green-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Ativos</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo $estatisticas_matriculas['alunos_ativos'] ?? 0; ?></p>
        <p class="text-sm text-gray-500 mt-1">Alunos ativos</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clipboard-list text-yellow-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Pendentes</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo count($pre_matriculas_pendentes); ?></p>
        <p class="text-sm text-gray-500 mt-1">Pré-matrículas</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Atraso</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo count($mensalidades_atraso); ?></p>
        <p class="text-sm text-gray-500 mt-1">Mensalidades</p>
    </div>
</div>

<!-- Quick Actions -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Ações Rápidas</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <a href="#" onclick="loadContent('matriculas')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-primary-200 transition-colors">
                <i class="fas fa-user-plus text-primary-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Matrículas</p>
        </a>
        
        <a href="#" onclick="loadContent('renovacoes')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-green-200 transition-colors">
                <i class="fas fa-sync-alt text-green-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Renovações</p>
        </a>
        
        <a href="#" onclick="loadContent('pre_matriculas')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-yellow-200 transition-colors">
                <i class="fas fa-clipboard-list text-yellow-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Pré-Matrículas</p>
        </a>
        
        <a href="#" onclick="loadContent('mensalidades')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-red-200 transition-colors">
                <i class="fas fa-dollar-sign text-red-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Mensalidades</p>
        </a>
        
        <a href="#" onclick="loadContent('declaracoes')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-purple-200 transition-colors">
                <i class="fas fa-file-alt text-purple-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Declarações</p>
        </a>
        
        <a href="#" onclick="loadContent('atestados')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-indigo-200 transition-colors">
                <i class="fas fa-certificate text-indigo-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Atestados</p>
        </a>
    </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Matrículas Recentes -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Matrículas Recentes</h3>
        </div>
        <div class="p-4">
            <?php if (count($matriculas_recentes) > 0): ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($matriculas_recentes, 0, 5) as $matricula): ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold text-sm">
                                <?php echo strtoupper(substr($matricula['aluno_nome'], 0, 1)); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo htmlspecialchars($matricula['aluno_nome']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($matricula['turma_nome'] ?? 'Sem turma'); ?></p>
                            </div>
                            <p class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">Nenhuma matrícula recente</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mensalidades em Atraso -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Mensalidades em Atraso</h3>
        </div>
        <div class="p-4">
            <?php if (count($mensalidades_atraso) > 0): ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($mensalidades_atraso, 0, 5) as $mensalidade): ?>
                        <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
                            <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center text-red-600 font-semibold text-sm">
                                <?php echo strtoupper(substr($mensalidade['aluno_nome'], 0, 1)); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?></p>
                            </div>
                            <span class="text-xs font-medium text-red-600">R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">Nenhuma mensalidade em atraso</p>
            <?php endif; ?>
        </div>
    </div>
</div>

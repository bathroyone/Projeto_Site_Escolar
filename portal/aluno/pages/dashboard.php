<?php
require_once '../../config.php';

requireLogin();

if (!isAluno()) {
    header('Location: ../../dashboard.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];

// Obter notas do aluno
$notas = [];
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
} catch (PDOException $e) {
    error_log("Erro ao obter notas: " . $e->getMessage());
}

// Calcular média geral
$media_geral = 0;
if (count($notas) > 0) {
    $soma_notas = array_sum(array_column($notas, 'nota'));
    $media_geral = $soma_notas / count($notas);
}

// Obter trabalhos
$trabalhos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT tc.*, u.nome_completo as professor_nome 
        FROM trabalhos_correcoes tc 
        JOIN usuarios u ON tc.professor_id = u.id 
        WHERE tc.ativo = TRUE
        ORDER BY tc.data_upload DESC
        LIMIT 5
    ");
    $stmt->execute();
    $trabalhos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter trabalhos: " . $e->getMessage());
}

// Obter avisos
$avisos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT a.*, u.nome_completo as professor_nome 
        FROM avisos a 
        JOIN usuarios u ON a.professor_id = u.id 
        ORDER BY a.data_criacao DESC
        LIMIT 5
    ");
    $avisos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter avisos: " . $e->getMessage());
}
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-star text-primary-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Média</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($media_geral, 1); ?></p>
        <p class="text-sm text-gray-500 mt-1">Média geral</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-book text-green-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Notas</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo count($notas); ?></p>
        <p class="text-sm text-gray-500 mt-1">Notas lançadas</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-tasks text-yellow-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Trabalhos</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo count($trabalhos); ?></p>
        <p class="text-sm text-gray-500 mt-1">Trabalhos disponíveis</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-bell text-purple-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Avisos</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo count($avisos); ?></p>
        <p class="text-sm text-gray-500 mt-1">Avisos recentes</p>
    </div>
</div>

<!-- Quick Actions -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Ações Rápidas</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <a href="#" onclick="loadContent('boletim')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-primary-200 transition-colors">
                <i class="fas fa-file-alt text-primary-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Boletim</p>
        </a>
        
        <a href="#" onclick="loadContent('horarios')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-green-200 transition-colors">
                <i class="fas fa-calendar text-green-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Horários</p>
        </a>
        
        <a href="#" onclick="loadContent('materiais')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-yellow-200 transition-colors">
                <i class="fas fa-book text-yellow-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Materiais</p>
        </a>
        
        <a href="#" onclick="loadContent('trabalhos')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-red-200 transition-colors">
                <i class="fas fa-tasks text-red-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Trabalhos</p>
        </a>
        
        <a href="#" onclick="loadContent('frequencia')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-purple-200 transition-colors">
                <i class="fas fa-clipboard-check text-purple-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Frequência</p>
        </a>
        
        <a href="#" onclick="loadContent('mensalidades')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-indigo-200 transition-colors">
                <i class="fas fa-dollar-sign text-indigo-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Mensalidades</p>
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
                                <?php echo strtoupper(substr($nota['disciplina'], 0, 1)); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo htmlspecialchars($nota['disciplina']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($nota['bimestre']); ?>º Bimestre</p>
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

    <!-- Avisos recentes -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Avisos Recentes</h3>
        </div>
        <div class="p-4">
            <?php if (count($avisos) > 0): ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($avisos, 0, 5) as $aviso): ?>
                        <div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 font-semibold text-sm">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo htmlspecialchars($aviso['titulo']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($aviso['data_criacao'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">Nenhum aviso recente</p>
            <?php endif; ?>
        </div>
    </div>
</div>

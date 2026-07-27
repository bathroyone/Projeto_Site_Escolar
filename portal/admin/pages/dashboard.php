<?php
require_once '../../config.php';

requireAdmin();

// Obter estatísticas
$stats = [];
try {
    $pdo = getDBConnection();
    
    // Total de usuários por tipo
    $stmt = $pdo->query("SELECT tipo_usuario, COUNT(*) as total FROM usuarios GROUP BY tipo_usuario");
    $stats['usuarios'] = $stmt->fetchAll();
    
    // Total de arquivos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM arquivos WHERE ativo = TRUE");
    $stats['arquivos'] = $stmt->fetch();
    
    // Total de turmas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM turmas WHERE ano_letivo = 2026");
    $stats['turmas'] = $stmt->fetch();
    
    // Total de eventos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM eventos_calendario WHERE data_inicio >= CURDATE()");
    $stats['eventos'] = $stmt->fetch();
    
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}

// Obter usuários recentes
$usuarios_recentes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY data_criacao DESC LIMIT 10");
    $usuarios_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter usuários: " . $e->getMessage());
}

// Obter atividades recentes
$atividades_recentes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT 10");
    $atividades_recentes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter atividades: " . $e->getMessage());
}
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-primary-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Total</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo array_sum(array_column($stats['usuarios'], 'total')); ?></p>
        <p class="text-sm text-gray-500 mt-1">Usuários cadastrados</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-chalkboard text-green-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Turmas</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo $stats['turmas']['total'] ?? 0; ?></p>
        <p class="text-sm text-gray-500 mt-1">Turmas ativas</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-folder text-yellow-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Arquivos</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo $stats['arquivos']['total'] ?? 0; ?></p>
        <p class="text-sm text-gray-500 mt-1">Arquivos no sistema</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-calendar text-purple-600 text-xl"></i>
            </div>
            <span class="text-sm text-gray-500">Eventos</span>
        </div>
        <p class="text-3xl font-bold text-gray-800"><?php echo $stats['eventos']['total'] ?? 0; ?></p>
        <p class="text-sm text-gray-500 mt-1">Eventos futuros</p>
    </div>
</div>

<!-- Quick Actions -->
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Ações Rápidas</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <a href="#" onclick="loadContent('usuarios')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-primary-200 transition-colors">
                <i class="fas fa-users text-primary-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Usuários</p>
        </a>
        
        <a href="#" onclick="loadContent('turmas')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-green-200 transition-colors">
                <i class="fas fa-chalkboard text-green-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Turmas</p>
        </a>
        
        <a href="#" onclick="loadContent('disciplinas')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-yellow-200 transition-colors">
                <i class="fas fa-book text-yellow-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Disciplinas</p>
        </a>
        
        <a href="#" onclick="loadContent('matriculas')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-red-200 transition-colors">
                <i class="fas fa-user-plus text-red-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Matrículas</p>
        </a>
        
        <a href="#" onclick="loadContent('financeiro')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-purple-200 transition-colors">
                <i class="fas fa-dollar-sign text-purple-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Financeiro</p>
        </a>
        
        <a href="#" onclick="loadContent('relatorios')" class="bg-white rounded-xl border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md transition-all text-center group">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-indigo-200 transition-colors">
                <i class="fas fa-chart-bar text-indigo-600"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">Relatórios</p>
        </a>
    </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Usuários Recentes -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Usuários Recentes</h3>
        </div>
        <div class="p-4">
            <?php if (count($usuarios_recentes) > 0): ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($usuarios_recentes, 0, 5) as $usuario): ?>
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold text-sm">
                                <?php echo strtoupper(substr($usuario['nome_completo'], 0, 1)); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo htmlspecialchars($usuario['nome_completo']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($usuario['tipo_usuario']); ?></p>
                            </div>
                            <p class="text-xs text-gray-500"><?php echo date('d/m/Y', strtotime($usuario['data_criacao'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">Nenhum usuário recente</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Atividades Recentes -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Atividades Recentes</h3>
        </div>
        <div class="p-4">
            <?php if (count($atividades_recentes) > 0): ?>
                <div class="space-y-3">
                    <?php foreach (array_slice($atividades_recentes, 0, 5) as $atividade): ?>
                        <div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 font-semibold text-sm">
                                <i class="fas fa-history"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo htmlspecialchars($atividade['action']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($atividade['timestamp'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">Nenhuma atividade recente</p>
            <?php endif; ?>
        </div>
    </div>
</div>

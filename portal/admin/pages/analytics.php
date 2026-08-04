<?php
require_once '../config.php';

requireAdmin();

// Obter estatísticas
$stats = [];
try {
    $pdo = getDBConnection();
    
    // Total de usuários por tipo
    $stmt = $pdo->query("SELECT tipo_usuario, COUNT(*) as total FROM usuarios GROUP BY tipo_usuario");
    $stats['usuarios_por_tipo'] = $stmt->fetchAll();
    
    // Total de turmas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM turmas");
    $stats['total_turmas'] = $stmt->fetch()['total'];
    
    // Total de alunos ativos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM matriculas WHERE status = 'ativa'");
    $stats['alunos_ativos'] = $stmt->fetch()['total'];
    
    // Acessos por mês
    $stmt = $pdo->query("SELECT MONTH(data_login) as mes, COUNT(*) as total FROM logs_acesso WHERE YEAR(data_login) = YEAR(CURDATE()) GROUP BY MONTH(data_login) ORDER BY mes");
    $stats['acessos_mes'] = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}
?>

<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Analytics</h2>
</div>

<!-- Cards de Estatísticas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Usuários</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo array_sum(array_column($stats['usuarios_por_tipo'] ?? [], 'total')); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-blue-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Turmas</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_turmas'] ?? 0; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chalkboard text-green-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Alunos Ativos</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['alunos_ativos'] ?? 0; ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-graduate text-yellow-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Acessos Mês</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo array_sum(array_column($stats['acessos_mes'] ?? [], 'total')); ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-purple-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos e Tabelas -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Usuários por Tipo -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Usuários por Tipo</h3>
        <div class="space-y-4">
            <?php foreach ($stats['usuarios_por_tipo'] ?? [] as $tipo): ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600"><?php echo ucfirst($tipo['tipo_usuario']); ?></span>
                    <div class="flex items-center gap-2">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-primary-600 h-2 rounded-full" style="width: <?php echo ($tipo['total'] / array_sum(array_column($stats['usuarios_por_tipo'], 'total'))) * 100; ?>%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-800"><?php echo $tipo['total']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Acessos por Mês -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Acessos por Mês</h3>
        <div class="space-y-4">
            <?php 
            $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
            foreach ($stats['acessos_mes'] ?? [] as $acesso): 
            ?>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600"><?php echo $meses[$acesso['mes'] - 1]; ?></span>
                    <div class="flex items-center gap-2">
                        <div class="w-32 bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($acesso['total'] / max(array_column($stats['acessos_mes'], 'total'))) * 100; ?>%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-800"><?php echo $acesso['total']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Tabela Detalhada -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mt-6">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Resumo Detalhado</h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Professores</p>
                <p class="text-xl font-bold text-gray-800"><?php echo ($stats['usuarios_por_tipo']['professor']['total'] ?? 0); ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Alunos</p>
                <p class="text-xl font-bold text-gray-800"><?php echo ($stats['usuarios_por_tipo']['aluno']['total'] ?? 0); ?></p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-500">Secretaria</p>
                <p class="text-xl font-bold text-gray-800"><?php echo ($stats['usuarios_por_tipo']['secretaria']['total'] ?? 0); ?></p>
            </div>
        </div>
    </div>
</div>

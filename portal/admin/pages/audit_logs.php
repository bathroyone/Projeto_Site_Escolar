<?php
require_once '../config.php';
requireAdmin();

// Exportar logs
if (isset($_GET['action']) && $_GET['action'] === 'exportar') {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY timestamp DESC");
        $logs = $stmt->fetchAll();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="logs_auditoria_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Data/Hora', 'Usuário', 'Ação', 'Detalhes', 'IP']);
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['timestamp'],
                $log['usuario'] ?? 'Sistema',
                $log['action'],
                $log['detalhes'] ?? '',
                $log['ip'] ?? ''
            ]);
        }
        fclose($output);
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao exportar logs: " . $e->getMessage());
    }
}

// Obter logs de auditoria
$logs = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT 50");
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter logs: " . $e->getMessage());
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Logs de Auditoria</h2>
        <a href="?action=exportar" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-download mr-2"></i>Exportar
        </a>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center gap-4">
            <input type="text" placeholder="Buscar logs..." class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            <input type="date" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data/Hora</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ação</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detalhes</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($logs as $log): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('d/m/Y H:i:s', strtotime($log['timestamp'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                            <?php echo htmlspecialchars($log['usuario'] ?? 'Sistema'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <?php echo htmlspecialchars($log['action']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($log['detalhes'] ?? '-'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

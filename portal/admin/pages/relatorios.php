<?php
require_once '../config.php';

requireAdmin();

// Exportar relatório
if (isset($_GET['action']) && $_GET['action'] === 'exportar' && isset($_GET['tipo'])) {
    $tipo = sanitizeInput($_GET['tipo']);
    
    try {
        $pdo = getDBConnection();
        
        if ($tipo === 'usuarios') {
            $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nome_completo");
            $data = $stmt->fetchAll();
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="relatorio_usuarios_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Nome', 'Email', 'Tipo', 'Matricula', 'Data Criação']);
            foreach ($data as $row) {
                fputcsv($output, [$row['id'], $row['nome_completo'], $row['email'], $row['tipo_usuario'], $row['matricula'] ?? '', $row['data_criacao']]);
            }
            fclose($output);
            exit();
        } elseif ($tipo === 'turmas') {
            $stmt = $pdo->query("SELECT t.*, u.nome_completo as professor_nome FROM turmas t LEFT JOIN usuarios u ON t.professor_id = u.id ORDER BY t.serie, t.nome");
            $data = $stmt->fetchAll();
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="relatorio_turmas_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Nome', 'Série', 'Ano Letivo', 'Professor']);
            foreach ($data as $row) {
                fputcsv($output, [$row['id'], $row['nome'], $row['serie'], $row['ano_letivo'], $row['professor_nome'] ?? '']);
            }
            fclose($output);
            exit();
        } elseif ($tipo === 'geral') {
            $stmt = $pdo->query("SELECT tipo_usuario, COUNT(*) as total FROM usuarios GROUP BY tipo_usuario");
            $usuarios = $stmt->fetchAll();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM turmas");
            $turmas = $stmt->fetch()['total'];
            
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM matriculas WHERE status = 'ativa'");
            $matriculas = $stmt->fetch()['total'];
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="relatorio_geral_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Categoria', 'Total']);
            foreach ($usuarios as $row) {
                fputcsv($output, ['Usuários ' . ucfirst($row['tipo_usuario']), $row['total']]);
            }
            fputcsv($output, ['Total Turmas', $turmas]);
            fputcsv($output, ['Matrículas Ativas', $matriculas]);
            fclose($output);
            exit();
        }
    } catch (PDOException $e) {
        error_log("Erro ao exportar relatório: " . $e->getMessage());
    }
}

// Obter estatísticas para relatórios
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
    
    // Total de disciplinas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM disciplinas");
    $stats['total_disciplinas'] = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Relatórios</h2>
        <button onclick="exportarRelatorio()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-download mr-2"></i>Exportar Relatório
        </button>
    </div>
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
                <p class="text-sm text-gray-500">Disciplinas</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total_disciplinas'] ?? 0; ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-book text-purple-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tipos de Relatórios -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hover:shadow-md transition-shadow cursor-pointer" onclick="gerarRelatorio('usuarios')">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-blue-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800">Relatório de Usuários</h3>
        </div>
        <p class="text-gray-600 text-sm">Listagem completa de todos os usuários do sistema com detalhes.</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hover:shadow-md transition-shadow cursor-pointer" onclick="gerarRelatorio('turmas')">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chalkboard text-green-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800">Relatório de Turmas</h3>
        </div>
        <p class="text-gray-600 text-sm">Informações detalhadas sobre todas as turmas e matrículas.</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hover:shadow-md transition-shadow cursor-pointer" onclick="gerarRelatorio('financeiro')">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-dollar-sign text-yellow-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800">Relatório Financeiro</h3>
        </div>
        <p class="text-gray-600 text-sm">Resumo financeiro com mensalidades e pagamentos.</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hover:shadow-md transition-shadow cursor-pointer" onclick="gerarRelatorio('desempenho')">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-purple-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800">Relatório de Desempenho</h3>
        </div>
        <p class="text-gray-600 text-sm">Análise de desempenho dos alunos por turma e disciplina.</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hover:shadow-md transition-shadow cursor-pointer" onclick="gerarRelatorio('frequencia')">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clipboard-check text-red-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800">Relatório de Frequência</h3>
        </div>
        <p class="text-gray-600 text-sm">Controle de frequência dos alunos por período.</p>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 hover:shadow-md transition-shadow cursor-pointer" onclick="gerarRelatorio('patrimonio')">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-box text-indigo-600"></i>
            </div>
            <h3 class="font-semibold text-gray-800">Relatório de Patrimônio</h3>
        </div>
        <p class="text-gray-600 text-sm">Inventário completo de equipamentos e materiais.</p>
    </div>
</div>

<!-- Tabela de Usuários por Tipo -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mt-6">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Resumo por Tipo de Usuário</h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <?php foreach ($stats['usuarios_por_tipo'] ?? [] as $tipo): ?>
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-sm text-gray-500"><?php echo ucfirst($tipo['tipo_usuario']); ?></p>
                    <p class="text-xl font-bold text-gray-800"><?php echo $tipo['total']; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    function gerarRelatorio(tipo) {
        window.location.href = '?action=exportar&tipo=' + tipo;
    }
    
    function exportarRelatorio() {
        window.location.href = '?action=exportar&tipo=geral';
    }
</script>

<?php
require_once '../../config.php';

requireAdmin();

$error = '';
$success = '';

// Obter alunos sem matrícula ativa
$alunos_sem_matricula = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.* FROM usuarios u 
        WHERE u.tipo_usuario = 'aluno' AND u.ativo = 1 
        AND u.id NOT IN (SELECT aluno_id FROM matriculas WHERE status = 'ativa' AND ano_letivo = ?)
        ORDER BY u.nome_completo
    ");
    $stmt->execute([date('Y')]);
    $alunos_sem_matricula = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}

// Obter turmas ativas
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM turmas WHERE ano_letivo = ? ORDER BY nome, serie");
    $stmt->execute([date('Y')]);
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter matrículas ativas
$matriculas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT m.*, u.nome_completo as aluno_nome, t.nome as turma_nome, t.serie 
        FROM matriculas m 
        JOIN usuarios u ON m.aluno_id = u.id 
        JOIN turmas t ON m.turma_id = t.id 
        WHERE m.status = 'ativa' AND m.ano_letivo = ?
        ORDER BY u.nome_completo
    ");
    $stmt->execute([date('Y')]);
    $matriculas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter matrículas: " . $e->getMessage());
}

// Processar nova matrícula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nova_matricula') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $ano_letivo = intval($_POST['ano_letivo'] ?? date('Y'));
    $data_matricula = $_POST['data_matricula'] ?? date('Y-m-d');
    $observacao = sanitizeInput($_POST['observacao'] ?? '');
    
    if (empty($aluno_id) || empty($turma_id)) {
        $error = 'Por favor, selecione o aluno e a turma.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Atualizar turma do aluno
            $stmt = $pdo->prepare("UPDATE usuarios SET turma_id = ? WHERE id = ?");
            $stmt->execute([$turma_id, $aluno_id]);
            
            // Criar matrícula
            $stmt = $pdo->prepare("INSERT INTO matriculas (aluno_id, turma_id, ano_letivo, data_matricula, observacao) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$aluno_id, $turma_id, $ano_letivo, $data_matricula, $observacao]);
            
            $success = 'Matrícula realizada com sucesso!';
            
            // Recarregar dados
            $stmt = $pdo->prepare("
                SELECT u.* FROM usuarios u 
                WHERE u.tipo_usuario = 'aluno' AND u.ativo = 1 
                AND u.id NOT IN (SELECT aluno_id FROM matriculas WHERE status = 'ativa' AND ano_letivo = ?)
                ORDER BY u.nome_completo
            ");
            $stmt->execute([date('Y')]);
            $alunos_sem_matricula = $stmt->fetchAll();
            
            $stmt = $pdo->prepare("SELECT * FROM turmas WHERE ano_letivo = ? ORDER BY nome, serie");
            $stmt->execute([date('Y')]);
            $turmas = $stmt->fetchAll();
            
            $stmt = $pdo->prepare("
                SELECT m.*, u.nome_completo as aluno_nome, t.nome as turma_nome, t.serie 
                FROM matriculas m 
                JOIN usuarios u ON m.aluno_id = u.id 
                JOIN turmas t ON m.turma_id = t.id 
                WHERE m.status = 'ativa' AND m.ano_letivo = ?
                ORDER BY u.nome_completo
            ");
            $stmt->execute([date('Y')]);
            $matriculas = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao realizar matrícula: " . $e->getMessage());
            $error = 'Erro ao realizar matrícula. Verifique se o aluno já está matriculado nesta turma.';
        }
    }
}

// Cancelar matrícula
if (isset($_GET['action']) && $_GET['action'] === 'cancelar' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE matriculas SET status = 'cancelada' WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: ../index.php?page=matriculas');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao cancelar matrícula: " . $e->getMessage());
    }
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Gestão de Matrículas</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Nova Matrícula
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<!-- Estatísticas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Matrículas Ativas</p>
                <p class="text-4xl font-bold text-green-600"><?php echo count($matriculas); ?></p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-check text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Alunos Pendentes</p>
                <p class="text-4xl font-bold text-orange-600"><?php echo count($alunos_sem_matricula); ?></p>
            </div>
            <div class="w-14 h-14 bg-orange-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-user-clock text-orange-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Turmas Disponíveis</p>
                <p class="text-4xl font-bold text-primary-600"><?php echo count($turmas); ?></p>
            </div>
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-chalkboard text-primary-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Matrículas -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Matrículas Ativas - <?php echo date('Y'); ?></h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Série</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Data Matrícula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($matriculas as $matricula): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 font-semibold text-sm">
                                    <?php echo strtoupper(substr($matricula['aluno_nome'], 0, 1)); ?>
                                </div>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($matricula['aluno_nome']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo htmlspecialchars($matricula['turma_nome']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo htmlspecialchars($matricula['serie']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 hidden md:table-cell"><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-600">Ativa</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="?action=cancelar&id=<?php echo $matricula['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja cancelar esta matrícula?');">
                                <i class="fas fa-times"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Nova Matrícula -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Nova Matrícula</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="nova_matricula">
                
                <div class="mb-4">
                    <label for="aluno_id" class="block text-sm font-medium text-gray-700 mb-2">Aluno *</label>
                    <select id="aluno_id" name="aluno_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <?php foreach ($alunos_sem_matricula as $aluno): ?>
                            <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-2">Turma *</label>
                    <select id="turma_id" name="turma_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <?php foreach ($turmas as $turma): ?>
                            <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="ano_letivo" class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo *</label>
                        <input type="number" id="ano_letivo" name="ano_letivo" required value="<?php echo date('Y'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label for="data_matricula" class="block text-sm font-medium text-gray-700 mb-2">Data Matrícula *</label>
                        <input type="date" id="data_matricula" name="data_matricula" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="observacao" class="block text-sm font-medium text-gray-700 mb-2">Observação</label>
                    <textarea id="observacao" name="observacao" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Observações sobre a matrícula"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Realizar Matrícula
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal() {
        const modal = document.getElementById('modal');
        modal.classList.toggle('hidden');
    }
</script>

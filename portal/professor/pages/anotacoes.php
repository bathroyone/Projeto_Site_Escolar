<?php
require_once '../../config.php';

requireLogin();

if (!isProfessor()) {
    header('Location: ../../dashboard.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];

$success = '';
$error = '';

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

// Obter alunos para seleção
$alunos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.* 
        FROM usuarios u 
        JOIN matriculas m ON u.id = m.aluno_id 
        JOIN grade_aulas ga ON m.turma_id = ga.turma_id 
        WHERE ga.professor_id = ? AND u.tipo_usuario = 'aluno' AND m.status = 'ativa'
        ORDER BY u.nome_completo
    ");
    $stmt->execute([$professor_id]);
    $alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}

// Criar anotação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_anotacao') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $conteudo = sanitizeInput($_POST['conteudo'] ?? '');
    
    if (empty($aluno_id) || empty($tipo) || empty($conteudo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO anotacoes_alunos (professor_id, aluno_id, tipo, conteudo, data_criacao) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$professor_id, $aluno_id, $tipo, $conteudo]);
            
            $success = 'Anotação criada com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao criar anotação: " . $e->getMessage());
            $error = 'Erro ao criar anotação.';
        }
    }
}

// Obter anotações
$anotacoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT a.*, u.nome_completo as aluno_nome 
        FROM anotacoes_alunos a 
        JOIN usuarios u ON a.aluno_id = u.id 
        WHERE a.professor_id = ?
        ORDER BY a.data_criacao DESC
    ");
    $stmt->execute([$professor_id]);
    $anotacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter anotações: " . $e->getMessage());
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Anotações sobre Alunos</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Nova Anotação
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

<!-- Lista de Anotações -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Anotações Registradas</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conteúdo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($anotacoes as $anotacao): ?>
                    <?php 
                    $tipo_cor = match($anotacao['tipo']) {
                        'positiva' => 'bg-green-100 text-green-600',
                        'negativa' => 'bg-red-100 text-red-600',
                        'neutra' => 'bg-gray-100 text-gray-600',
                        default => 'bg-blue-100 text-blue-600'
                    };
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($anotacao['aluno_nome']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $tipo_cor; ?>"><?php echo ucfirst($anotacao['tipo']); ?></span>
                        </td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars(substr($anotacao['conteudo'], 0, 50) . '...'); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($anotacao['data_criacao'])); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <a href="?action=excluir&id=<?php echo $anotacao['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir esta anotação?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($anotacoes)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-sticky-note text-4xl mb-4"></i>
            <p>Nenhuma anotação registrada.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Anotação -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Nova Anotação</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="criar_anotacao">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Aluno *</label>
                    <select name="aluno_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <?php foreach ($alunos as $aluno): ?>
                            <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                    <select name="tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="positiva">Positiva</option>
                        <option value="negativa">Negativa</option>
                        <option value="neutra">Neutra</option>
                        <option value="comportamento">Comportamento</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Conteúdo *</label>
                    <textarea name="conteudo" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Salvar Anotação
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

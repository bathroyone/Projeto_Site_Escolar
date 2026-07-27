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

// Criar planejamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_planejamento') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    $data_aula = sanitizeInput($_POST['data_aula'] ?? '');
    $conteudo = sanitizeInput($_POST['conteudo'] ?? '');
    $objetivos = sanitizeInput($_POST['objetivos'] ?? '');
    
    if (empty($turma_id) || empty($disciplina) || empty($data_aula) || empty($conteudo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO planejamento_aulas (professor_id, turma_id, disciplina, data_aula, conteudo, objetivos, data_criacao) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$professor_id, $turma_id, $disciplina, $data_aula, $conteudo, $objetivos]);
            
            $success = 'Planejamento criado com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao criar planejamento: " . $e->getMessage());
            $error = 'Erro ao criar planejamento.';
        }
    }
}

// Obter planejamentos do professor
$planejamentos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT p.*, t.nome as turma_nome, t.serie 
        FROM planejamento_aulas p 
        JOIN turmas t ON p.turma_id = t.id 
        WHERE p.professor_id = ?
        ORDER BY p.data_aula DESC
    ");
    $stmt->execute([$professor_id]);
    $planejamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter planejamentos: " . $e->getMessage());
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Planejamento de Aulas</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Novo Planejamento
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

<!-- Lista de Planejamentos -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Planejamentos Cadastrados</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Aula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Conteúdo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($planejamentos as $planejamento): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($planejamento['disciplina']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($planejamento['turma_nome'] . ' - ' . $planejamento['serie']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($planejamento['data_aula'])); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars(substr($planejamento['conteudo'], 0, 50) . '...'); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <a href="?action=excluir&id=<?php echo $planejamento['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir este planejamento?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($planejamentos)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-calendar-alt text-4xl mb-4"></i>
            <p>Nenhum planejamento cadastrado.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Novo Planejamento -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Novo Planejamento de Aula</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="criar_planejamento">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Turma *</label>
                    <select name="turma_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <?php foreach ($turmas as $turma): ?>
                            <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Disciplina *</label>
                    <input type="text" name="disciplina" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data da Aula *</label>
                    <input type="date" name="data_aula" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Conteúdo *</label>
                    <textarea name="conteudo" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Objetivos</label>
                    <textarea name="objetivos" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Salvar Planejamento
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

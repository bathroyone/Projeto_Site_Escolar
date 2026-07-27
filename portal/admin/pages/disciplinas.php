<?php
require_once '../config.php';

requireAdmin();

$success = '';
$error = '';

// Criar disciplina
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_disciplina') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $codigo = sanitizeInput($_POST['codigo'] ?? '');
    $carga_horaria = intval($_POST['carga_horaria'] ?? 0);
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    
    if (empty($nome) || empty($codigo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO disciplinas (nome, codigo, carga_horaria, descricao, data_criacao) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$nome, $codigo, $carga_horaria, $descricao]);
            
            $success = 'Disciplina criada com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao criar disciplina: " . $e->getMessage());
            $error = 'Erro ao criar disciplina.';
        }
    }
}

// Obter disciplinas
$disciplinas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM disciplinas ORDER BY nome");
    $disciplinas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter disciplinas: " . $e->getMessage());
}

// Excluir disciplina
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM disciplinas WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        $success = 'Disciplina excluída com sucesso!';
    } catch (PDOException $e) {
        error_log("Erro ao excluir disciplina: " . $e->getMessage());
        $error = 'Erro ao excluir disciplina.';
    }
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Gerenciar Disciplinas</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Nova Disciplina
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

<!-- Lista de Disciplinas -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Disciplinas Cadastradas</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Carga Horária</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($disciplinas as $disciplina): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($disciplina['nome']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($disciplina['codigo']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo $disciplina['carga_horaria']; ?> h</td>
                        <td class="px-6 py-4 text-sm">
                            <a href="?action=excluir&id=<?php echo $disciplina['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir esta disciplina?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($disciplinas)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-book text-4xl mb-4"></i>
            <p>Nenhuma disciplina encontrada.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Disciplina -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Nova Disciplina</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="criar_disciplina">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome *</label>
                    <input type="text" name="nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Código *</label>
                    <input type="text" name="codigo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Carga Horária *</label>
                    <input type="number" name="carga_horaria" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea name="descricao" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Criar Disciplina
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

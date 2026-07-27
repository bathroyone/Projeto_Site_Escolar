<?php
require_once '../../config.php';

requireLogin();

if (!isProfessor()) {
    header('Location: ../../dashboard.php');
    exit();
}

$professor_id = $_SESSION['usuario_id'];

// Criar tabela de diário de classe se não existir
try {
    $pdo = getDBConnection();
    $pdo->query("CREATE TABLE IF NOT EXISTS diario_classe (
        id INT AUTO_INCREMENT PRIMARY KEY,
        professor_id INT NOT NULL,
        turma_id INT NOT NULL,
        data_aula DATE NOT NULL,
        disciplina VARCHAR(100) NOT NULL,
        conteudo TEXT NOT NULL,
        atividades TEXT,
        observacoes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (professor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    error_log("Erro ao criar tabela diario_classe: " . $e->getMessage());
}

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

// Adicionar entrada no diário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $data_aula = $_POST['data_aula'] ?? date('Y-m-d');
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    $conteudo = sanitizeInput($_POST['conteudo'] ?? '');
    $atividades = sanitizeInput($_POST['atividades'] ?? '');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($turma_id) || empty($disciplina) || empty($conteudo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO diario_classe (professor_id, turma_id, data_aula, disciplina, conteudo, atividades, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$professor_id, $turma_id, $data_aula, $disciplina, $conteudo, $atividades, $observacoes]);
            $success = 'Entrada adicionada ao diário com sucesso!';
            
            // Recarregar diário
            $stmt = $pdo->prepare("
                SELECT dc.*, t.nome as turma_nome, t.serie 
                FROM diario_classe dc 
                JOIN turmas t ON dc.turma_id = t.id 
                WHERE dc.professor_id = ?
                ORDER BY dc.data_aula DESC, dc.created_at DESC
            ");
            $stmt->execute([$professor_id]);
            $diario = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao adicionar entrada: " . $e->getMessage());
            $error = 'Erro ao adicionar entrada.';
        }
    }
}

// Excluir entrada
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM diario_classe WHERE id = ? AND professor_id = ?");
        $stmt->execute([intval($_GET['id']), $professor_id]);
        header('Location: ../index.php?page=diario');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir entrada: " . $e->getMessage());
    }
}

// Obter entradas do diário
$diario = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT dc.*, t.nome as turma_nome, t.serie 
        FROM diario_classe dc 
        JOIN turmas t ON dc.turma_id = t.id 
        WHERE dc.professor_id = ?
        ORDER BY dc.data_aula DESC, dc.created_at DESC
    ");
    $stmt->execute([$professor_id]);
    $diario = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter diário: " . $e->getMessage());
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Diário de Classe</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Nova Entrada
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

<!-- Diário de Classe -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Histórico de Aulas</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Disciplina</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Conteúdo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">Atividades</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($diario as $entrada): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-calendar text-primary-600 text-sm"></i>
                                </div>
                                <span class="font-medium text-gray-800 text-sm"><?php echo date('d/m/Y', strtotime($entrada['data_aula'])); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm"><?php echo htmlspecialchars($entrada['turma_nome'] . ' - ' . $entrada['serie']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-600">
                                <?php echo htmlspecialchars($entrada['disciplina']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars(substr($entrada['conteudo'], 0, 50)) . '...'; ?></td>
                        <td class="px-6 py-4 text-gray-600 text-sm hidden lg:table-cell"><?php echo htmlspecialchars($entrada['atividades'] ?? '-'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="?action=excluir&id=<?php echo $entrada['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir esta entrada?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($diario)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-book-open text-4xl mb-2"></i>
            <p>Nenhuma entrada registrada ainda.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Entrada -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Nova Entrada no Diário</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="adicionar">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="turma_id" class="block text-sm font-medium text-gray-700 mb-2">Turma *</label>
                        <select id="turma_id" name="turma_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">Selecione</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="data_aula" class="block text-sm font-medium text-gray-700 mb-2">Data da Aula *</label>
                        <input type="date" id="data_aula" name="data_aula" required value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="disciplina" class="block text-sm font-medium text-gray-700 mb-2">Disciplina *</label>
                    <input type="text" id="disciplina" name="disciplina" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Ex: Matemática, Português, História">
                </div>
                
                <div class="mb-4">
                    <label for="conteudo" class="block text-sm font-medium text-gray-700 mb-2">Conteúdo *</label>
                    <textarea id="conteudo" name="conteudo" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Descreva o conteúdo abordado na aula"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="atividades" class="block text-sm font-medium text-gray-700 mb-2">Atividades Realizadas</label>
                    <textarea id="atividades" name="atividades" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Descreva as atividades realizadas"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="observacoes" class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Observações adicionais"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Salvar Entrada
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

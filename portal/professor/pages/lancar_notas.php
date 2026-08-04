<?php
require_once '../config.php';

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

// Salvar notas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_notas') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $bimestre = intval($_POST['bimestre'] ?? 1);
    $disciplina = sanitizeInput($_POST['disciplina'] ?? '');
    
    if (empty($turma_id) || empty($disciplina)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            
            foreach ($_POST['notas'] as $aluno_id => $nota) {
                $nota = floatval($nota);
                $stmt = $pdo->prepare("
                    INSERT INTO notas (aluno_id, professor_id, turma_id, disciplina, bimestre, nota) 
                    VALUES (?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE nota = ?
                ");
                $stmt->execute([$aluno_id, $professor_id, $turma_id, $disciplina, $bimestre, $nota, $nota]);
            }
            
            $success = 'Notas salvas com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao salvar notas: " . $e->getMessage());
            $error = 'Erro ao salvar notas.';
        }
    }
}

// Obter alunos de uma turma específica
$alunos = [];
$turma_id = isset($_GET['turma_id']) ? intval($_GET['turma_id']) : 0;
if ($turma_id) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT u.id, u.nome_completo 
            FROM usuarios u 
            JOIN matriculas m ON u.id = m.aluno_id 
            WHERE m.turma_id = ? AND m.status = 'ativa' AND u.tipo_usuario = 'aluno'
            ORDER BY u.nome_completo
        ");
        $stmt->execute([$turma_id]);
        $alunos = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao obter alunos: " . $e->getMessage());
    }
}
?>

<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Lançar Notas</h2>
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

<!-- Seleção de Turma e Disciplina -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
    <form method="GET" action="">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                <select name="turma_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Selecione</option>
                    <?php foreach ($turmas as $turma): ?>
                        <option value="<?php echo $turma['id']; ?>" <?php echo $turma_id == $turma['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($turma['nome'] . ' - ' . $turma['serie']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Bimestre</label>
                <select name="bimestre" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="1">1º Bimestre</option>
                    <option value="2">2º Bimestre</option>
                    <option value="3">3º Bimestre</option>
                    <option value="4">4º Bimestre</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-search mr-2"></i>Carregar Alunos
                </button>
            </div>
        </div>
    </form>
</div>

<?php if ($turma_id && !empty($alunos)): ?>
    <!-- Formulário de Lançamento de Notas -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-800">Lançamento de Notas</h3>
        </div>
        <form method="POST" action="" class="p-6">
            <input type="hidden" name="action" value="salvar_notas">
            <input type="hidden" name="turma_id" value="<?php echo $turma_id; ?>">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Disciplina *</label>
                <input type="text" name="disciplina" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Ex: Matemática, Português, História">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Bimestre *</label>
                <select name="bimestre" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="1">1º Bimestre</option>
                    <option value="2">2º Bimestre</option>
                    <option value="3">3º Bimestre</option>
                    <option value="4">4º Bimestre</option>
                </select>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nota (0-10)</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($alunos as $aluno): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($aluno['nome_completo']); ?></td>
                                <td class="px-6 py-4">
                                    <input type="number" name="notas[<?php echo $aluno['id']; ?>]" min="0" max="10" step="0.1" class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <button type="submit" class="mt-6 w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-save mr-2"></i>Salvar Notas
            </button>
        </form>
    </div>
<?php endif; ?>

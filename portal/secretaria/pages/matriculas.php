<?php
require_once '../../config.php';

requireLogin();

if (!isSecretaria()) {
    header('Location: ../../dashboard.php');
    exit();
}

$success = '';
$error = '';

// Criar matrícula
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_matricula') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $ano_letivo = sanitizeInput($_POST['ano_letivo'] ?? date('Y'));
    $data_matricula = sanitizeInput($_POST['data_matricula'] ?? date('Y-m-d'));
    $status = sanitizeInput($_POST['status'] ?? 'ativa');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($aluno_id) || empty($turma_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("SELECT id FROM matriculas WHERE aluno_id = ? AND ano_letivo = ?");
            $stmt->execute([$aluno_id, $ano_letivo]);
            if ($stmt->fetch()) {
                $error = 'Este aluno já possui matrícula para este ano letivo.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO matriculas (aluno_id, turma_id, ano_letivo, data_matricula, status, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$aluno_id, $turma_id, $ano_letivo, $data_matricula, $status, $observacoes]);
                
                $success = 'Matrícula criada com sucesso!';
                
                // Recarregar dados
                $stmt = $pdo->query("
                    SELECT u.* 
                    FROM usuarios u 
                    WHERE u.tipo_usuario = 'aluno' 
                    AND u.ativo = 1
                    AND u.id NOT IN (
                        SELECT m.aluno_id 
                        FROM matriculas m 
                        WHERE m.ano_letivo = " . date('Y') . "
                        AND m.status != 'cancelada'
                    )
                    ORDER BY u.nome_completo
                ");
                $alunos_sem_matricula = $stmt->fetchAll();
                
                $stmt = $pdo->query("SELECT id, nome, serie, vagas, ano_letivo FROM turmas WHERE ano_letivo = YEAR(CURDATE()) AND vagas > 0 ORDER BY nome");
                $turmas = $stmt->fetchAll();
                
                $stmt = $pdo->query("
                    SELECT m.*, u.nome_completo as aluno_nome, u.cpf, t.nome as turma_nome, t.serie
                    FROM matriculas m
                    JOIN usuarios u ON m.aluno_id = u.id
                    LEFT JOIN turmas t ON m.turma_id = t.id
                    ORDER BY m.data_matricula DESC
                ");
                $matriculas = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            error_log("Erro ao criar matrícula: " . $e->getMessage());
            $error = 'Erro ao criar matrícula.';
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

// Obter alunos sem matrícula no ano atual
$alunos_sem_matricula = [];
try {
    $pdo = getDBConnection();
    $ano_atual = date('Y');
    $stmt = $pdo->query("
        SELECT u.* 
        FROM usuarios u 
        WHERE u.tipo_usuario = 'aluno' 
        AND u.ativo = 1
        AND u.id NOT IN (
            SELECT m.aluno_id 
            FROM matriculas m 
            WHERE m.ano_letivo = $ano_atual
            AND m.status != 'cancelada'
        )
        ORDER BY u.nome_completo
    ");
    $alunos_sem_matricula = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}

// Obter turmas
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome, serie, vagas, ano_letivo FROM turmas WHERE ano_letivo = YEAR(CURDATE()) AND vagas > 0 ORDER BY nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter matrículas
$matriculas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT m.*, u.nome_completo as aluno_nome, u.cpf, t.nome as turma_nome, t.serie
        FROM matriculas m
        JOIN usuarios u ON m.aluno_id = u.id
        LEFT JOIN turmas t ON m.turma_id = t.id
        ORDER BY m.data_matricula DESC
    ");
    $matriculas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter matrículas: " . $e->getMessage());
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

<!-- Lista de Matrículas -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Matrículas Cadastradas</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Série</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano Letivo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Matrícula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($matriculas as $matricula): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($matricula['aluno_nome']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($matricula['cpf']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($matricula['turma_nome'] ?? '-'); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($matricula['serie'] ?? '-'); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($matricula['ano_letivo']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($matricula['data_matricula'])); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium 
                                <?php 
                                $cor_status = match($matricula['status']) {
                                    'ativa' => 'bg-green-100 text-green-600',
                                    'cancelada' => 'bg-red-100 text-red-600',
                                    'pendente' => 'bg-yellow-100 text-yellow-600',
                                    default => 'bg-gray-100 text-gray-600'
                                };
                                echo $cor_status;
                                ?>">
                                <?php echo ucfirst($matricula['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <?php if ($matricula['status'] === 'ativa'): ?>
                                <a href="?action=cancelar&id=<?php echo $matricula['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Deseja realmente cancelar esta matrícula?');">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($matriculas)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-user-graduate text-4xl mb-4"></i>
            <p>Nenhuma matrícula cadastrada.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Matrícula -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Nova Matrícula</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="criar_matricula">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Aluno</label>
                        <select name="aluno_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">Selecione o aluno</option>
                            <?php foreach ($alunos_sem_matricula as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Turma</label>
                        <select name="turma_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                            <option value="">Selecione a turma</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?php echo $turma['id']; ?>"><?php echo htmlspecialchars($turma['nome']); ?> - <?php echo htmlspecialchars($turma['serie']); ?> (Vagas: <?php echo $turma['vagas']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo</label>
                        <input type="number" name="ano_letivo" value="<?php echo date('Y'); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data Matrícula</label>
                        <input type="date" name="data_matricula" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="ativa">Ativa</option>
                        <option value="pendente">Pendente</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Criar Matrícula
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

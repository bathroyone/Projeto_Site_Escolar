<?php
require_once '../config.php';

requireAdmin();

$error = '';
$success = '';

// Verificar mensagens de sucesso/erro via GET
if (isset($_GET['success'])) {
    switch($_GET['success']) {
        case 'adicionada':
            $success = 'Turma adicionada com sucesso!';
            break;
        case 'editada':
            $success = 'Turma atualizada com sucesso!';
            break;
        case 'excluida':
            $success = 'Turma excluída com sucesso!';
            break;
    }
}

if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'erro_excluir':
            $error = 'Erro ao excluir turma.';
            break;
        default:
            $error = 'Erro ao processar operação.';
    }
}

// Obter todas as turmas
$turmas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT t.*, u.nome_completo as professor_nome FROM turmas t LEFT JOIN usuarios u ON t.professor_id = u.id ORDER BY t.serie, t.nome");
    $turmas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter turmas: " . $e->getMessage());
}

// Obter professores disponíveis
$professores = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'professor' AND ativo = TRUE ORDER BY nome_completo");
    $professores = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter professores: " . $e->getMessage());
}

// Adicionar nova turma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar') {
    error_log("POST recebido para adicionar turma: " . print_r($_POST, true));
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $ano_letivo = intval($_POST['ano_letivo'] ?? date('Y'));
    $professor_id = intval($_POST['professor_id'] ?? 0);
    
    error_log("Dados processados: nome=$nome, serie=$serie, ano=$ano_letivo, prof=$professor_id");
    
    if (empty($nome) || empty($serie)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
        error_log("Erro de validação: campos vazios");
    } else {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("SELECT id FROM turmas WHERE nome = ? AND serie = ? AND ano_letivo = ?");
            $stmt->execute([$nome, $serie, $ano_letivo]);
            
            if ($stmt->fetch()) {
                $error = 'Esta turma já existe para este ano letivo.';
                error_log("Erro: turma já existe");
            } else {
                $stmt = $pdo->prepare("INSERT INTO turmas (nome, serie, ano_letivo, professor_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $serie, $ano_letivo, $professor_id > 0 ? $professor_id : null]);
                
                error_log("Turma inserida com sucesso, redirecionando...");
                header('Location: ../index.php?page=turmas&success=adicionada');
                exit();
            }
        } catch (PDOException $e) {
            error_log("Erro ao adicionar turma: " . $e->getMessage());
            $error = 'Erro ao adicionar turma: ' . $e->getMessage();
        }
    }
}

// Excluir turma
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM turmas WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: ../index.php?page=turmas&success=excluida');
        exit();
    } catch (PDOException $e) {
        error_log("Erro ao excluir turma: " . $e->getMessage());
        header('Location: ../index.php?page=turmas&error=erro_excluir');
        exit();
    }
}

// Editar turma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $turma_id = intval($_POST['turma_id'] ?? 0);
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $ano_letivo = intval($_POST['ano_letivo'] ?? date('Y'));
    $professor_id = intval($_POST['professor_id'] ?? 0);
    
    if (empty($nome) || empty($serie) || empty($turma_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("UPDATE turmas SET nome = ?, serie = ?, ano_letivo = ?, professor_id = ? WHERE id = ?");
            $stmt->execute([$nome, $serie, $ano_letivo, $professor_id > 0 ? $professor_id : null, $turma_id]);
            
            header('Location: ../index.php?page=turmas&success=editada');
            exit();
        } catch (PDOException $e) {
            error_log("Erro ao editar turma: " . $e->getMessage());
            $error = 'Erro ao editar turma.';
        }
    }
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Gerenciar Turmas</h2>
    </div>
</div>

<!-- Formulário Adicionar/Editar Turma -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4" id="form-title">Adicionar Nova Turma</h3>
    <form method="POST" action="turmas.php" class="space-y-4">
        <input type="hidden" name="action" id="form-action" value="adicionar">
        <input type="hidden" name="turma_id" id="turma-id" value="">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="nome" class="block text-sm font-medium text-gray-700 mb-2">Nome da Turma *</label>
                <input type="text" id="nome" name="nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Ex: Turma A">
            </div>
            
            <div>
                <label for="serie" class="block text-sm font-medium text-gray-700 mb-2">Série *</label>
                <select id="serie" name="serie" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Selecione</option>
                    <option value="1º Ano">1º Ano</option>
                    <option value="2º Ano">2º Ano</option>
                    <option value="3º Ano">3º Ano</option>
                    <option value="4º Ano">4º Ano</option>
                    <option value="5º Ano">5º Ano</option>
                    <option value="6º Ano">6º Ano</option>
                    <option value="7º Ano">7º Ano</option>
                    <option value="8º Ano">8º Ano</option>
                    <option value="9º Ano">9º Ano</option>
                </select>
            </div>
            
            <div>
                <label for="ano_letivo" class="block text-sm font-medium text-gray-700 mb-2">Ano Letivo *</label>
                <input type="number" id="ano_letivo" name="ano_letivo" required value="<?php echo date('Y'); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
            </div>
            
            <div>
                <label for="professor_id" class="block text-sm font-medium text-gray-700 mb-2">Professor Responsável</label>
                <select id="professor_id" name="professor_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">Sem professor responsável</option>
                    <?php foreach ($professores as $prof): ?>
                        <option value="<?php echo $prof['id']; ?>"><?php echo htmlspecialchars($prof['nome_completo']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="flex gap-2">
            <button type="submit" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
                <i class="fas fa-save mr-2"></i>Salvar Turma
            </button>
            <button type="button" onclick="resetForm()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors">
                <i class="fas fa-times mr-2"></i>Cancelar
            </button>
        </div>
    </form>
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

<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Série</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ano Letivo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professor Responsável</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($turmas as $turma): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center text-green-600">
                                    <i class="fas fa-users"></i>
                                </div>
                                <span class="font-medium text-gray-800"><?php echo htmlspecialchars($turma['nome']); ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo htmlspecialchars($turma['serie']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600"><?php echo $turma['ano_letivo']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if ($turma['professor_nome']): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-600 rounded-full text-xs font-medium">
                                    <?php echo htmlspecialchars($turma['professor_nome']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-gray-400 text-sm">Não atribuído</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button onclick="editTurma(<?php echo $turma['id']; ?>, '<?php echo htmlspecialchars($turma['nome']); ?>', '<?php echo htmlspecialchars($turma['serie']); ?>', <?php echo $turma['ano_letivo']; ?>, <?php echo $turma['professor_id'] ?? 0; ?>)" class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="turmas.php?action=excluir&id=<?php echo $turma['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir esta turma?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function editTurma(id, nome, serie, anoLetivo, professorId) {
        document.getElementById('form-action').value = 'editar';
        document.getElementById('turma-id').value = id;
        document.getElementById('form-title').textContent = 'Editar Turma';
        document.getElementById('nome').value = nome;
        document.getElementById('serie').value = serie;
        document.getElementById('ano_letivo').value = anoLetivo;
        document.getElementById('professor_id').value = professorId;
        
        // Scroll to form
        document.querySelector('.bg-white.rounded-xl.border').scrollIntoView({ behavior: 'smooth' });
    }
    
    function resetForm() {
        document.getElementById('form-action').value = 'adicionar';
        document.getElementById('turma-id').value = '';
        document.getElementById('form-title').textContent = 'Adicionar Nova Turma';
        document.getElementById('nome').value = '';
        document.getElementById('serie').value = '';
        document.getElementById('ano_letivo').value = '<?php echo date('Y'); ?>';
        document.getElementById('professor_id').value = '';
    }
</script>

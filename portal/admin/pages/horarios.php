<?php
require_once '../config.php';

requireAdmin();

$success = '';
$error = '';

// Obter usuários para escala
$usuarios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo, tipo_usuario FROM usuarios WHERE tipo_usuario IN ('professor', 'secretaria') ORDER BY nome_completo");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter usuários: " . $e->getMessage());
}

// Criar escala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_escala') {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $dia_semana = sanitizeInput($_POST['dia_semana'] ?? '');
    $hora_inicio = sanitizeInput($_POST['hora_inicio'] ?? '');
    $hora_fim = sanitizeInput($_POST['hora_fim'] ?? '');
    $local = sanitizeInput($_POST['local'] ?? '');
    
    if (empty($usuario_id) || empty($dia_semana) || empty($hora_inicio) || empty($hora_fim)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO escala_horarios (usuario_id, dia_semana, hora_inicio, hora_fim, local, data_criacao) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$usuario_id, $dia_semana, $hora_inicio, $hora_fim, $local]);
            
            $success = 'Escala criada com sucesso!';
            
            // Recarregar escalas
            $stmt = $pdo->query("
                SELECT e.*, u.nome_completo, u.tipo_usuario 
                FROM escala_horarios e 
                JOIN usuarios u ON e.usuario_id = u.id 
                ORDER BY FIELD(e.dia_semana, 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'), e.hora_inicio
            ");
            $escalas = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao criar escala: " . $e->getMessage());
            $error = 'Erro ao criar escala.';
        }
    }
}

// Editar escala
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_escala') {
    $escala_id = intval($_POST['escala_id'] ?? 0);
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $dia_semana = sanitizeInput($_POST['dia_semana'] ?? '');
    $hora_inicio = sanitizeInput($_POST['hora_inicio'] ?? '');
    $hora_fim = sanitizeInput($_POST['hora_fim'] ?? '');
    $local = sanitizeInput($_POST['local'] ?? '');
    
    if (empty($usuario_id) || empty($dia_semana) || empty($hora_inicio) || empty($hora_fim) || empty($escala_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                UPDATE escala_horarios SET usuario_id = ?, dia_semana = ?, hora_inicio = ?, hora_fim = ?, local = ? WHERE id = ?
            ");
            $stmt->execute([$usuario_id, $dia_semana, $hora_inicio, $hora_fim, $local, $escala_id]);
            
            $success = 'Escala atualizada com sucesso!';
            
            // Recarregar escalas
            $stmt = $pdo->query("
                SELECT e.*, u.nome_completo, u.tipo_usuario 
                FROM escala_horarios e 
                JOIN usuarios u ON e.usuario_id = u.id 
                ORDER BY FIELD(e.dia_semana, 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'), e.hora_inicio
            ");
            $escalas = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao editar escala: " . $e->getMessage());
            $error = 'Erro ao editar escala.';
        }
    }
}

// Obter escalas
$escalas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT e.*, u.nome_completo, u.tipo_usuario 
        FROM escala_horarios e 
        JOIN usuarios u ON e.usuario_id = u.id 
        ORDER BY FIELD(e.dia_semana, 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'), e.hora_inicio
    ");
    $escalas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter escalas: " . $e->getMessage());
}

// Excluir escala
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM escala_horarios WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        $success = 'Escala excluída com sucesso!';
    } catch (PDOException $e) {
        error_log("Erro ao excluir escala: " . $e->getMessage());
        $error = 'Erro ao excluir escala.';
    }
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Horários</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Nova Escala
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

<!-- Lista de Escalas -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Escalas de Horários</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuário</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dia</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Horário</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($escalas as $escala): ?>
                    <?php 
                    $tipo_cor = $escala['tipo_usuario'] === 'professor' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($escala['nome_completo']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $tipo_cor; ?>">
                                <?php echo ucfirst($escala['tipo_usuario']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600"><?php echo ucfirst($escala['dia_semana']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo substr($escala['hora_inicio'], 0, 5) . ' - ' . substr($escala['hora_fim'], 0, 5); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($escala['local'] ?? '-'); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <button onclick="editEscala(<?php echo $escala['id']; ?>, <?php echo $escala['usuario_id']; ?>, '<?php echo $escala['dia_semana']; ?>', '<?php echo substr($escala['hora_inicio'], 0, 5); ?>', '<?php echo substr($escala['hora_fim'], 0, 5); ?>', '<?php echo htmlspecialchars($escala['local'] ?? '', ENT_QUOTES); ?>')" class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="../index.php?page=horarios&action=excluir&id=<?php echo $escala['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir esta escala?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($escalas)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-clock text-4xl mb-4"></i>
            <p>Nenhuma escala encontrada.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Escala -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800" id="modal-title">Nova Escala de Horário</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="../index.php?page=horarios" class="p-6">
                <input type="hidden" name="action" id="form-action" value="criar_escala">
                <input type="hidden" name="escala_id" id="escala-id" value="">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usuário *</label>
                    <select name="usuario_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nome_completo'] . ' - ' . ucfirst($usuario['tipo_usuario'])); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dia da Semana *</label>
                    <select name="dia_semana" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="segunda">Segunda-feira</option>
                        <option value="terca">Terça-feira</option>
                        <option value="quarta">Quarta-feira</option>
                        <option value="quinta">Quinta-feira</option>
                        <option value="sexta">Sexta-feira</option>
                        <option value="sabado">Sábado</option>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hora Início *</label>
                        <input type="time" name="hora_inicio" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Hora Fim *</label>
                        <input type="time" name="hora_fim" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Local</label>
                    <input type="text" name="local" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i><span id="submit-text">Criar Escala</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal() {
        const modal = document.getElementById('modal');
        modal.classList.toggle('hidden');
        if (modal.classList.contains('hidden')) {
            resetForm();
        }
    }
    
    function editEscala(id, usuarioId, diaSemana, horaInicio, horaFim, local) {
        document.getElementById('form-action').value = 'editar_escala';
        document.getElementById('escala-id').value = id;
        document.getElementById('modal-title').textContent = 'Editar Escala de Horário';
        document.getElementById('submit-text').textContent = 'Salvar Alterações';
        document.getElementById('usuario_id').value = usuarioId;
        document.getElementById('dia_semana').value = diaSemana;
        document.getElementById('hora_inicio').value = horaInicio;
        document.getElementById('hora_fim').value = horaFim;
        document.getElementById('local').value = local;
        
        toggleModal();
    }
    
    function resetForm() {
        document.getElementById('form-action').value = 'criar_escala';
        document.getElementById('escala-id').value = '';
        document.getElementById('modal-title').textContent = 'Nova Escala de Horário';
        document.getElementById('submit-text').textContent = 'Criar Escala';
        document.getElementById('usuario_id').value = '';
        document.getElementById('dia_semana').value = 'segunda';
        document.getElementById('hora_inicio').value = '';
        document.getElementById('hora_fim').value = '';
        document.getElementById('local').value = '';
    }
</script>

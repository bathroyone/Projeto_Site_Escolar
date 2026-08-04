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

// Obter outros professores
$professores = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM usuarios WHERE tipo_usuario = 'professor' AND id != ? ORDER BY nome_completo
    ");
    $stmt->execute([$professor_id]);
    $professores = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter professores: " . $e->getMessage());
}

// Criar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar_mensagem') {
    $destinatario_id = intval($_POST['destinatario_id'] ?? 0);
    $assunto = sanitizeInput($_POST['assunto'] ?? '');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    
    if (empty($destinatario_id) || empty($assunto) || empty($mensagem)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO comunicacao_professores (remetente_id, destinatario_id, assunto, mensagem, data_envio) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$professor_id, $destinatario_id, $assunto, $mensagem]);
            
            $success = 'Mensagem enviada com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao enviar mensagem: " . $e->getMessage());
            $error = 'Erro ao enviar mensagem.';
        }
    }
}

// Obter mensagens enviadas e recebidas
$mensagens = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT c.*, 
               u_remetente.nome_completo as remetente_nome,
               u_destinatario.nome_completo as destinatario_nome
        FROM comunicacao_professores c 
        JOIN usuarios u_remetente ON c.remetente_id = u_remetente.id 
        JOIN usuarios u_destinatario ON c.destinatario_id = u_destinatario.id 
        WHERE c.remetente_id = ? OR c.destinatario_id = ?
        ORDER BY c.data_envio DESC
    ");
    $stmt->execute([$professor_id, $professor_id]);
    $mensagens = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter mensagens: " . $e->getMessage());
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Comunicação com Professores</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Nova Mensagem
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

<!-- Lista de Mensagens -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Histórico de Mensagens</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">De</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Para</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assunto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($mensagens as $msg): ?>
                    <?php 
                    $is_enviada = $msg['remetente_id'] == $professor_id;
                    $tipo_cor = $is_enviada ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600';
                    $tipo_texto = $is_enviada ? 'Enviada' : 'Recebida';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($msg['remetente_nome']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($msg['destinatario_nome']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($msg['assunto']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y H:i', strtotime($msg['data_envio'])); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $tipo_cor; ?> mr-2"><?php echo $tipo_texto; ?></span>
                            <a href="?action=excluir&id=<?php echo $msg['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir esta mensagem?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($mensagens)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-envelope text-4xl mb-4"></i>
            <p>Nenhuma mensagem encontrada.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Mensagem -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Nova Mensagem</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="enviar_mensagem">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Destinatário *</label>
                    <select name="destinatario_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <?php foreach ($professores as $prof): ?>
                            <option value="<?php echo $prof['id']; ?>"><?php echo htmlspecialchars($prof['nome_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assunto *</label>
                    <input type="text" name="assunto" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mensagem *</label>
                    <textarea name="mensagem" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>Enviar Mensagem
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

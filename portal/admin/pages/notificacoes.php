<?php
require_once '../config.php';

requireAdmin();

$success = '';
$error = '';

// Criar notificação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_notificacao') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? 'info');
    $destinatario = sanitizeInput($_POST['destinatario'] ?? 'todos');
    
    if (empty($titulo) || empty($mensagem)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO notificacoes (titulo, mensagem, tipo, destinatario, data_criacao) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$titulo, $mensagem, $tipo, $destinatario]);
            
            $success = 'Notificação criada com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao criar notificação: " . $e->getMessage());
            $error = 'Erro ao criar notificação.';
        }
    }
}

// Obter notificações
$notificacoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM notificacoes ORDER BY data_criacao DESC");
    $notificacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter notificações: " . $e->getMessage());
}

// Excluir notificação
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM notificacoes WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        $success = 'Notificação excluída com sucesso!';
    } catch (PDOException $e) {
        error_log("Erro ao excluir notificação: " . $e->getMessage());
        $error = 'Erro ao excluir notificação.';
    }
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Notificações</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Nova Notificação
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

<!-- Lista de Notificações -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Notificações do Sistema</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destinatário</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($notificacoes as $notificacao): ?>
                    <?php 
                    $tipo_cor = match($notificacao['tipo']) {
                        'info' => 'bg-blue-100 text-blue-600',
                        'warning' => 'bg-yellow-100 text-yellow-600',
                        'error' => 'bg-red-100 text-red-600',
                        'success' => 'bg-green-100 text-green-600',
                        default => 'bg-gray-100 text-gray-600'
                    };
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($notificacao['titulo']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $tipo_cor; ?>">
                                <?php echo ucfirst($notificacao['tipo']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600"><?php echo ucfirst($notificacao['destinatario']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <a href="../index.php?page=notificacoes&action=excluir&id=<?php echo $notificacao['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir esta notificação?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($notificacoes)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-bell text-4xl mb-4"></i>
            <p>Nenhuma notificação encontrada.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Notificação -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Nova Notificação</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="../index.php?page=notificacoes" class="p-6">
                <input type="hidden" name="action" value="criar_notificacao">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
                    <input type="text" name="titulo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo</label>
                    <select name="tipo" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="info">Informação</option>
                        <option value="warning">Aviso</option>
                        <option value="error">Erro</option>
                        <option value="success">Sucesso</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Destinatário</label>
                    <select name="destinatario" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="todos">Todos</option>
                        <option value="alunos">Alunos</option>
                        <option value="professores">Professores</option>
                        <option value="secretaria">Secretaria</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Mensagem *</label>
                    <textarea name="mensagem" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>Enviar Notificação
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

<?php
require_once '../config.php';

requireAdmin();

$success = '';
$error = '';

// Criar integração
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_integracao') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $api_key = sanitizeInput($_POST['api_key'] ?? '');
    $endpoint = sanitizeInput($_POST['endpoint'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    
    if (empty($nome) || empty($tipo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO integracoes (nome, tipo, api_key, endpoint, descricao, data_criacao) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$nome, $tipo, $api_key, $endpoint, $descricao]);
            
            $success = 'Integração criada com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao criar integração: " . $e->getMessage());
            $error = 'Erro ao criar integração.';
        }
    }
}

// Obter integrações
$integracoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM integracoes ORDER BY data_criacao DESC");
    $integracoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter integrações: " . $e->getMessage());
}

// Excluir integração
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM integracoes WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        $success = 'Integração excluída com sucesso!';
    } catch (PDOException $e) {
        error_log("Erro ao excluir integração: " . $e->getMessage());
        $error = 'Erro ao excluir integração.';
    }
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Integrações</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Nova Integração
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

<!-- Lista de Integrações -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Integrações Externas</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Endpoint</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($integracoes as $integracao): ?>
                    <?php 
                    $status_cor = $integracao['status'] === 'ativo' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($integracao['nome']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($integracao['tipo']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($integracao['endpoint'] ?? '-'); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_cor; ?>">
                                <?php echo ucfirst($integracao['status'] ?? 'Inativo'); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <a href="?action=excluir&id=<?php echo $integracao['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir esta integração?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($integracoes)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-plug text-4xl mb-4"></i>
            <p>Nenhuma integração encontrada.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Integração -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Nova Integração</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="criar_integracao">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome *</label>
                    <input type="text" name="nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                    <select name="tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="api">API</option>
                        <option value="webhook">Webhook</option>
                        <option value="oauth">OAuth</option>
                        <option value="ftp">FTP</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                    <input type="text" name="api_key" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Endpoint</label>
                    <input type="url" name="endpoint" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea name="descricao" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Criar Integração
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

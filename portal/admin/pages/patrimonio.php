<?php
require_once '../config.php';

requireAdmin();

$success = '';
$error = '';

// Criar item de patrimônio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_item') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $local = sanitizeInput($_POST['local'] ?? '');
    $quantidade = intval($_POST['quantidade'] ?? 1);
    $valor = floatval($_POST['valor'] ?? 0);
    
    if (empty($nome) || empty($tipo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO patrimonio (nome, tipo, descricao, local, quantidade, valor, data_cadastro) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$nome, $tipo, $descricao, $local, $quantidade, $valor]);
            
            $success = 'Item de patrimônio cadastrado com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao criar item: " . $e->getMessage());
            $error = 'Erro ao cadastrar item.';
        }
    }
}

// Obter itens de patrimônio
$itens = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM patrimonio ORDER BY data_cadastro DESC");
    $itens = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter itens: " . $e->getMessage());
}

// Excluir item
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM patrimonio WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        
        $success = 'Item excluído com sucesso!';
    } catch (PDOException $e) {
        error_log("Erro ao excluir item: " . $e->getMessage());
        $error = 'Erro ao excluir item.';
    }
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Patrimônio</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Novo Item
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

<!-- Lista de Itens -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Itens do Patrimônio</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Local</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantidade</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($itens as $item): ?>
                    <?php 
                    $tipo_cor = match($item['tipo']) {
                        'equipamento' => 'bg-blue-100 text-blue-600',
                        'mobilio' => 'bg-green-100 text-green-600',
                        'material' => 'bg-yellow-100 text-yellow-600',
                        'software' => 'bg-purple-100 text-purple-600',
                        default => 'bg-gray-100 text-gray-600'
                    };
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($item['nome']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $tipo_cor; ?>">
                                <?php echo ucfirst($item['tipo']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($item['local'] ?? '-'); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo $item['quantidade']; ?></td>
                        <td class="px-6 py-4 text-gray-600">R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($item['data_cadastro'])); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <a href="?action=excluir&id=<?php echo $item['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir este item?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($itens)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-box text-4xl mb-4"></i>
            <p>Nenhum item encontrado.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Novo Item -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Novo Item de Patrimônio</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="criar_item">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome *</label>
                    <input type="text" name="nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                    <select name="tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="equipamento">Equipamento</option>
                        <option value="mobilio">Mobiliário</option>
                        <option value="material">Material</option>
                        <option value="software">Software</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Local</label>
                    <input type="text" name="local" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantidade *</label>
                        <input type="number" name="quantidade" required min="1" value="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Valor (R$)</label>
                        <input type="number" name="valor" step="0.01" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea name="descricao" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Cadastrar Item
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

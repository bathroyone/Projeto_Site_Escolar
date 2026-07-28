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
            
            // Recarregar itens
            $stmt = $pdo->query("SELECT * FROM patrimonio ORDER BY data_cadastro DESC");
            $itens = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao criar item: " . $e->getMessage());
            $error = 'Erro ao cadastrar item.';
        }
    }
}

// Editar item de patrimônio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar_item') {
    $item_id = intval($_POST['item_id'] ?? 0);
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $local = sanitizeInput($_POST['local'] ?? '');
    $quantidade = intval($_POST['quantidade'] ?? 1);
    $valor = floatval($_POST['valor'] ?? 0);
    
    if (empty($nome) || empty($tipo) || empty($item_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                UPDATE patrimonio SET nome = ?, tipo = ?, descricao = ?, local = ?, quantidade = ?, valor = ? WHERE id = ?
            ");
            $stmt->execute([$nome, $tipo, $descricao, $local, $quantidade, $valor, $item_id]);
            
            $success = 'Item de patrimônio atualizado com sucesso!';
            
            // Recarregar itens
            $stmt = $pdo->query("SELECT * FROM patrimonio ORDER BY data_cadastro DESC");
            $itens = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao editar item: " . $e->getMessage());
            $error = 'Erro ao editar item.';
        }
    }
}

// Criar novo tipo de equipamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_tipo') {
    $novo_tipo = sanitizeInput($_POST['novo_tipo'] ?? '');
    
    if (empty($novo_tipo)) {
        $error = 'Por favor, informe o nome do novo tipo.';
    } else {
        try {
            $pdo = getDBConnection();
            // Verificar se o tipo já existe
            $stmt = $pdo->prepare("SELECT tipo FROM patrimonio WHERE tipo = ? LIMIT 1");
            $stmt->execute([$novo_tipo]);
            
            if ($stmt->fetch()) {
                $error = 'Este tipo de equipamento já existe.';
            } else {
                // Adicionar o novo tipo criando um item temporário
                $stmt = $pdo->prepare("
                    INSERT INTO patrimonio (nome, tipo, descricao, local, quantidade, valor, data_cadastro) 
                    VALUES ('Temporário - ' . ? . ', ?, 'Tipo criado para cadastro', 'Temporário', 0, 0, NOW())
                ");
                $stmt->execute([$novo_tipo, $novo_tipo]);
                
                // Excluir o item temporário
                $stmt = $pdo->prepare("DELETE FROM patrimonio WHERE nome LIKE 'Temporário - %' AND tipo = ?");
                $stmt->execute([$novo_tipo]);
                
                $success = 'Tipo de equipamento cadastrado com sucesso!';
            }
        } catch (PDOException $e) {
            error_log("Erro ao criar tipo: " . $e->getMessage());
            $error = 'Erro ao cadastrar tipo.';
        }
    }
}

// Obter itens de patrimônio
$itens = [];
$tipos_existentes = ['equipamento', 'mobilio', 'material', 'software'];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM patrimonio ORDER BY data_cadastro DESC");
    $itens = $stmt->fetchAll();
    
    // Obter tipos únicos existentes
    $stmt = $pdo->query("SELECT DISTINCT tipo FROM patrimonio WHERE tipo IS NOT NULL ORDER BY tipo");
    $tipos_db = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tipos_db as $tipo) {
        if (!in_array($tipo, $tipos_existentes)) {
            $tipos_existentes[] = $tipo;
        }
    }
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
                            <button onclick="editItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nome'], ENT_QUOTES); ?>', '<?php echo $item['tipo']; ?>', '<?php echo htmlspecialchars($item['local'] ?? '', ENT_QUOTES); ?>', <?php echo $item['quantidade']; ?>, <?php echo $item['valor']; ?>, '<?php echo htmlspecialchars($item['descricao'] ?? '', ENT_QUOTES); ?>')" class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="../index.php?page=patrimonio&action=excluir&id=<?php echo $item['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Tem certeza que deseja excluir este item?');">
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
                <h3 class="text-lg font-semibold text-gray-800" id="modal-title">Novo Item de Patrimônio</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="../index.php?page=patrimonio" class="p-6">
                <input type="hidden" name="action" id="form-action" value="criar_item">
                <input type="hidden" name="item_id" id="item-id" value="">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome *</label>
                    <input type="text" name="nome" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo *</label>
                    <div class="flex gap-2">
                        <select name="tipo" required class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" id="tipo-select">
                            <?php foreach ($tipos_existentes as $tipo): ?>
                                <option value="<?php echo $tipo; ?>"><?php echo ucfirst($tipo); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="toggleTipoModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
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
                    <i class="fas fa-save mr-2"></i><span id="submit-text">Cadastrar Item</span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Novo Tipo -->
<div id="tipo-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleTipoModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Novo Tipo de Equipamento</h3>
                <button onclick="toggleTipoModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="../index.php?page=patrimonio" class="p-6">
                <input type="hidden" name="action" value="criar_tipo">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome do Tipo *</label>
                    <input type="text" name="novo_tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Ex: Projetor, Impressora, etc.">
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Cadastrar Tipo
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
    
    function toggleTipoModal() {
        const modal = document.getElementById('tipo-modal');
        modal.classList.toggle('hidden');
    }
    
    function editItem(id, nome, tipo, local, quantidade, valor, descricao) {
        document.getElementById('form-action').value = 'editar_item';
        document.getElementById('item-id').value = id;
        document.getElementById('modal-title').textContent = 'Editar Item de Patrimônio';
        document.getElementById('submit-text').textContent = 'Salvar Alterações';
        document.getElementById('nome').value = nome;
        document.getElementById('tipo-select').value = tipo;
        document.getElementById('local').value = local;
        document.getElementById('quantidade').value = quantidade;
        document.getElementById('valor').value = valor;
        document.getElementById('descricao').value = descricao;
        
        toggleModal();
    }
    
    function resetForm() {
        document.getElementById('form-action').value = 'criar_item';
        document.getElementById('item-id').value = '';
        document.getElementById('modal-title').textContent = 'Novo Item de Patrimônio';
        document.getElementById('submit-text').textContent = 'Cadastrar Item';
        document.getElementById('nome').value = '';
        document.getElementById('tipo-select').value = 'equipamento';
        document.getElementById('local').value = '';
        document.getElementById('quantidade').value = '1';
        document.getElementById('valor').value = '';
        document.getElementById('descricao').value = '';
    }
</script>

<?php
require_once '../config.php';

requireAdmin();

$success = '';
$error = '';

// Criar ticket de suporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_ticket') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $prioridade = sanitizeInput($_POST['prioridade'] ?? 'normal');
    $categoria = sanitizeInput($_POST['categoria'] ?? '');
    
    if (empty($titulo) || empty($descricao)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("
                INSERT INTO suporte_tickets (titulo, descricao, prioridade, categoria, status, data_criacao) 
                VALUES (?, ?, ?, ?, 'aberto', NOW())
            ");
            $stmt->execute([$titulo, $descricao, $prioridade, $categoria]);
            
            $success = 'Ticket criado com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao criar ticket: " . $e->getMessage());
            $error = 'Erro ao criar ticket.';
        }
    }
}

// Obter tickets
$tickets = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM suporte_tickets ORDER BY data_criacao DESC");
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter tickets: " . $e->getMessage());
}

// Atualizar status do ticket
if (isset($_GET['action']) && $_GET['action'] === 'atualizar_status' && isset($_GET['id']) && isset($_GET['status'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE suporte_tickets SET status = ? WHERE id = ?");
        $stmt->execute([$_GET['status'], intval($_GET['id'])]);
        
        $success = 'Status atualizado com sucesso!';
    } catch (PDOException $e) {
        error_log("Erro ao atualizar status: " . $e->getMessage());
        $error = 'Erro ao atualizar status.';
    }
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Suporte</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Novo Ticket
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

<!-- Lista de Tickets -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Tickets de Suporte</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prioridade</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($tickets as $ticket): ?>
                    <?php 
                    $prioridade_cor = match($ticket['prioridade']) {
                        'baixa' => 'bg-green-100 text-green-600',
                        'normal' => 'bg-yellow-100 text-yellow-600',
                        'alta' => 'bg-orange-100 text-orange-600',
                        'urgente' => 'bg-red-100 text-red-600',
                        default => 'bg-gray-100 text-gray-600'
                    };
                    
                    $status_cor = match($ticket['status']) {
                        'aberto' => 'bg-blue-100 text-blue-600',
                        'em_andamento' => 'bg-yellow-100 text-yellow-600',
                        'resolvido' => 'bg-green-100 text-green-600',
                        'fechado' => 'bg-gray-100 text-gray-600',
                        default => 'bg-gray-100 text-gray-600'
                    };
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($ticket['titulo']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($ticket['categoria'] ?? '-'); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $prioridade_cor; ?>">
                                <?php echo ucfirst($ticket['prioridade']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $status_cor; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y H:i', strtotime($ticket['data_criacao'])); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <select onchange="window.location.href='../index.php?page=suporte&action=atualizar_status&id=<?php echo $ticket['id']; ?>&status=' + this.value" class="text-xs border rounded px-2 py-1">
                                <option value="">Status</option>
                                <option value="em_andamento">Em Andamento</option>
                                <option value="resolvido">Resolvido</option>
                                <option value="fechado">Fechado</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($tickets)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-headset text-4xl mb-4"></i>
            <p>Nenhum ticket encontrado.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Novo Ticket -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Novo Ticket de Suporte</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="../index.php?page=suporte" class="p-6">
                <input type="hidden" name="action" value="criar_ticket">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Título *</label>
                    <input type="text" name="titulo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                    <select name="categoria" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <option value="tecnico">Técnico</option>
                        <option value="financeiro">Financeiro</option>
                        <option value="academico">Acadêmico</option>
                        <option value="administrativo">Administrativo</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Prioridade</label>
                    <select name="prioridade" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="baixa">Baixa</option>
                        <option value="normal" selected>Normal</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Descrição *</label>
                    <textarea name="descricao" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-paper-plane mr-2"></i>Criar Ticket
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

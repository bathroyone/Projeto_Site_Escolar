<?php
require_once '../../config.php';

requireAdmin();

$error = '';
$success = '';

// Processar formulário de movimento financeiro
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_movimento') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $categoria = sanitizeInput($_POST['categoria'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $valor = floatval($_POST['valor'] ?? 0);
    $data_movimento = $_POST['data_movimento'] ?? date('Y-m-d');
    $forma_pagamento = sanitizeInput($_POST['forma_pagamento'] ?? '');
    
    if (empty($tipo) || empty($categoria) || $valor <= 0) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO financeiro_historico (aluno_id, tipo_movimento, categoria, descricao, valor, data_movimento, forma_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$aluno_id, $tipo, $categoria, $descricao, $valor, $data_movimento, $forma_pagamento]);
            $success = 'Movimento financeiro registrado com sucesso!';
        } catch (PDOException $e) {
            error_log("Erro ao registrar movimento: " . $e->getMessage());
            $error = 'Erro ao registrar movimento financeiro.';
        }
    }
}

// Obter estatísticas financeiras
$total_receitas = 0;
$total_despesas = 0;
$saldo = 0;

try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT SUM(valor) as total FROM financeiro_historico WHERE tipo_movimento = 'receita'");
    $result = $stmt->fetch();
    $total_receitas = $result['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT SUM(valor) as total FROM financeiro_historico WHERE tipo_movimento = 'despesa'");
    $result = $stmt->fetch();
    $total_despesas = $result['total'] ?? 0;
    
    $saldo = $total_receitas - $total_despesas;
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}

// Obter histórico financeiro
$historico = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT fh.*, u.nome_completo as aluno_nome FROM financeiro_historico fh LEFT JOIN usuarios u ON fh.aluno_id = u.id ORDER BY fh.data_movimento DESC LIMIT 50");
    $historico = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter histórico: " . $e->getMessage());
}

// Obter lista de alunos
$alunos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = 1 ORDER BY nome_completo");
    $alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}
?>

<div class="mb-6">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">Gestão Financeira</h2>
        <button onclick="toggleModal()" class="bg-primary-600 text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-plus mr-2"></i>Novo Movimento
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

<!-- Cards de Estatísticas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Total Receitas</p>
                <p class="text-3xl font-bold text-green-600">R$ <?php echo number_format($total_receitas, 2, ',', '.'); ?></p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-arrow-up text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Total Despesas</p>
                <p class="text-3xl font-bold text-red-600">R$ <?php echo number_format($total_despesas, 2, ',', '.'); ?></p>
            </div>
            <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-arrow-down text-red-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Saldo Atual</p>
                <p class="text-3xl font-bold <?php echo $saldo >= 0 ? 'text-primary-600' : 'text-red-600'; ?>">R$ <?php echo number_format($saldo, 2, ',', '.'); ?></p>
            </div>
            <div class="w-14 h-14 <?php echo $saldo >= 0 ? 'bg-primary-100' : 'bg-red-100'; ?> rounded-xl flex items-center justify-center">
                <i class="fas fa-wallet <?php echo $saldo >= 0 ? 'text-primary-600' : 'text-red-600'; ?> text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Histórico Financeiro -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200">
        <h3 class="font-semibold text-gray-800">Histórico de Movimentos</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categoria</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Descrição</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Aluno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Forma Pagamento</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($historico as $movimento): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($movimento['data_movimento'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $movimento['tipo_movimento'] === 'receita' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                <?php echo ucfirst($movimento['tipo_movimento']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm"><?php echo htmlspecialchars($movimento['categoria']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm hidden sm:table-cell"><?php echo htmlspecialchars($movimento['descricao'] ?? '-'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($movimento['aluno_nome'] ?? '-'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap font-semibold <?php echo $movimento['tipo_movimento'] === 'receita' ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $movimento['tipo_movimento'] === 'receita' ? '+' : '-'; ?>R$ <?php echo number_format($movimento['valor'], 2, ',', '.'); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-sm hidden sm:table-cell"><?php echo htmlspecialchars($movimento['forma_pagamento'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Adicionar Movimento -->
<div id="modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Novo Movimento Financeiro</h3>
                <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-times text-gray-400"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="adicionar_movimento">
                
                <div class="mb-4">
                    <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2">Tipo de Movimento *</label>
                    <select id="tipo" name="tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <option value="receita">Receita</option>
                        <option value="despesa">Despesa</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="categoria" class="block text-sm font-medium text-gray-700 mb-2">Categoria *</label>
                    <select id="categoria" name="categoria" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <option value="mensalidade">Mensalidade</option>
                        <option value="matricula">Matrícula</option>
                        <option value="material">Material Escolar</option>
                        <option value="alimentacao">Alimentação</option>
                        <option value="transporte">Transporte</option>
                        <option value="outros">Outros</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="aluno_id" class="block text-sm font-medium text-gray-700 mb-2">Aluno (opcional)</label>
                    <select id="aluno_id" name="aluno_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <?php foreach ($alunos as $aluno): ?>
                            <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="valor" class="block text-sm font-medium text-gray-700 mb-2">Valor (R$) *</label>
                    <input type="number" id="valor" name="valor" step="0.01" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="0,00">
                </div>
                
                <div class="mb-4">
                    <label for="data_movimento" class="block text-sm font-medium text-gray-700 mb-2">Data do Movimento *</label>
                    <input type="date" id="data_movimento" name="data_movimento" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" value="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="mb-4">
                    <label for="forma_pagamento" class="block text-sm font-medium text-gray-700 mb-2">Forma de Pagamento</label>
                    <select id="forma_pagamento" name="forma_pagamento" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">Selecione</option>
                        <option value="dinheiro">Dinheiro</option>
                        <option value="cartao_credito">Cartão de Crédito</option>
                        <option value="cartao_debito">Cartão de Débito</option>
                        <option value="pix">PIX</option>
                        <option value="boleto">Boleto</option>
                        <option value="transferencia">Transferência</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="descricao" class="block text-sm font-medium text-gray-700 mb-2">Descrição</label>
                    <textarea id="descricao" name="descricao" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="Descrição do movimento"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-primary-600 text-white font-medium py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Salvar Movimento
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

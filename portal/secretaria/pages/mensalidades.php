<?php
require_once '../../config.php';

requireLogin();

if (!isSecretaria()) {
    header('Location: ../../dashboard.php');
    exit();
}

$success = '';
$error = '';

// Registrar pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_pagamento') {
    $mensalidade_id = intval($_POST['mensalidade_id'] ?? 0);
    $data_pagamento = sanitizeInput($_POST['data_pagamento'] ?? date('Y-m-d'));
    $valor_pago = floatval($_POST['valor_pago'] ?? 0);
    $forma_pagamento = sanitizeInput($_POST['forma_pagamento'] ?? '');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($mensalidade_id) || empty($valor_pago)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("UPDATE mensalidades SET status = 'pago', data_pagamento = ?, valor_pago = ?, forma_pagamento = ?, observacoes = ? WHERE id = ?");
            $stmt->execute([$data_pagamento, $valor_pago, $forma_pagamento, $observacoes, $mensalidade_id]);
            
            $success = 'Pagamento registrado com sucesso!';
            
            // Recarregar dados
            $stmt = $pdo->query("
                SELECT m.*, 
                       u.nome_completo as aluno_nome,
                       t.nome as turma_nome,
                       c.valor_mensalidade as valor_esperado
                FROM mensalidades m
                JOIN matriculas mat ON m.matricula_id = mat.id
                JOIN usuarios u ON mat.aluno_id = u.id
                LEFT JOIN turmas t ON mat.turma_id = t.id
                LEFT JOIN contratos_responsaveis c ON mat.aluno_id = c.aluno_id AND c.status = 'ativo'
                WHERE m.status = 'pendente' AND m.data_vencimento < CURDATE()
                ORDER BY m.data_vencimento ASC
            ");
            $mensalidades_atraso = $stmt->fetchAll();
            
            $stmt = $pdo->query("
                SELECT m.*, 
                       u.nome_completo as aluno_nome,
                       t.nome as turma_nome,
                       c.valor_mensalidade as valor_esperado
                FROM mensalidades m
                JOIN matriculas mat ON m.matricula_id = mat.id
                JOIN usuarios u ON mat.aluno_id = u.id
                LEFT JOIN turmas t ON mat.turma_id = t.id
                LEFT JOIN contratos_responsaveis c ON mat.aluno_id = c.aluno_id AND c.status = 'ativo'
                WHERE m.status = 'pendente' AND m.data_vencimento >= CURDATE()
                ORDER BY m.data_vencimento ASC
            ");
            $mensalidades_pendentes = $stmt->fetchAll();
            
            $stmt = $pdo->query("
                SELECT 
                    COUNT(CASE WHEN status = 'pendente' AND data_vencimento < CURDATE() THEN 1 END) as total_atraso,
                    COUNT(CASE WHEN status = 'pendente' AND data_vencimento >= CURDATE() THEN 1 END) as total_pendente,
                    COUNT(CASE WHEN status = 'pago' THEN 1 END) as total_pago,
                    SUM(CASE WHEN status = 'pendente' AND data_vencimento < CURDATE() THEN valor END) as valor_atraso
                FROM mensalidades
            ");
            $estatisticas = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao registrar pagamento: " . $e->getMessage());
            $error = 'Erro ao registrar pagamento.';
        }
    }
}

// Obter mensalidades em atraso
$mensalidades_atraso = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT m.*, 
               u.nome_completo as aluno_nome,
               t.nome as turma_nome,
               c.valor_mensalidade as valor_esperado
        FROM mensalidades m
        JOIN matriculas mat ON m.matricula_id = mat.id
        JOIN usuarios u ON mat.aluno_id = u.id
        LEFT JOIN turmas t ON mat.turma_id = t.id
        LEFT JOIN contratos_responsaveis c ON mat.aluno_id = c.aluno_id AND c.status = 'ativo'
        WHERE m.status = 'pendente' AND m.data_vencimento < CURDATE()
        ORDER BY m.data_vencimento ASC
    ");
    $mensalidades_atraso = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter mensalidades em atraso: " . $e->getMessage());
}

// Obter mensalidades pendentes (não atrasadas)
$mensalidades_pendentes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT m.*, 
               u.nome_completo as aluno_nome,
               t.nome as turma_nome,
               c.valor_mensalidade as valor_esperado
        FROM mensalidades m
        JOIN matriculas mat ON m.matricula_id = mat.id
        JOIN usuarios u ON mat.aluno_id = u.id
        LEFT JOIN turmas t ON mat.turma_id = t.id
        LEFT JOIN contratos_responsaveis c ON mat.aluno_id = c.aluno_id AND c.status = 'ativo'
        WHERE m.status = 'pendente' AND m.data_vencimento >= CURDATE()
        ORDER BY m.data_vencimento ASC
    ");
    $mensalidades_pendentes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter mensalidades pendentes: " . $e->getMessage());
}

// Obter estatísticas
$estatisticas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT 
            COUNT(CASE WHEN status = 'pendente' AND data_vencimento < CURDATE() THEN 1 END) as total_atraso,
            COUNT(CASE WHEN status = 'pendente' AND data_vencimento >= CURDATE() THEN 1 END) as total_pendente,
            COUNT(CASE WHEN status = 'pago' THEN 1 END) as total_pago,
            SUM(CASE WHEN status = 'pendente' AND data_vencimento < CURDATE() THEN valor END) as valor_atraso
        FROM mensalidades
    ");
    $estatisticas = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Erro ao obter estatísticas: " . $e->getMessage());
}
?>

<div class="mb-6">
    <h2 class="text-xl font-semibold text-gray-800">Controle de Mensalidades</h2>
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

<!-- Estatísticas -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Em Atraso</p>
                <p class="text-3xl font-bold text-red-600"><?php echo $estatisticas['total_atraso'] ?? 0; ?></p>
            </div>
            <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Pendentes</p>
                <p class="text-3xl font-bold text-yellow-600"><?php echo $estatisticas['total_pendente'] ?? 0; ?></p>
            </div>
            <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-clock text-yellow-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Pagas</p>
                <p class="text-3xl font-bold text-green-600"><?php echo $estatisticas['total_pago'] ?? 0; ?></p>
            </div>
            <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm mb-1">Valor Atraso</p>
                <p class="text-3xl font-bold text-primary-600">R$ <?php echo number_format($estatisticas['valor_atraso'] ?? 0, 2, ',', '.'); ?></p>
            </div>
            <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center">
                <i class="fas fa-dollar-sign text-primary-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Mensalidades em Atraso -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-8">
    <div class="p-4 border-b border-gray-200 bg-red-50">
        <h3 class="font-semibold text-red-800">
            <i class="fas fa-exclamation-triangle mr-2"></i>Mensalidades em Atraso
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referência</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dias Atraso</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($mensalidades_atraso as $mensalidade): ?>
                    <?php 
                    $dias_atraso = (strtotime(date('Y-m-d')) - strtotime($mensalidade['data_vencimento'])) / 86400;
                    ?>
                    <tr class="hover:bg-red-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($mensalidade['turma_nome'] ?? '-'); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('m/Y', strtotime($mensalidade['referencia'])); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?></td>
                        <td class="px-6 py-4 text-gray-600">R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-600">
                                <?php echo floor($dias_atraso); ?> dias
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <button onclick="registrarPagamento(<?php echo $mensalidade['id']; ?>, <?php echo $mensalidade['valor']; ?>)" class="bg-green-600 text-white px-3 py-1 rounded-lg hover:bg-green-700 transition-colors text-xs font-medium">
                                <i class="fas fa-check mr-1"></i>Pagar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($mensalidades_atraso)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-check-circle text-4xl mb-4 text-green-500"></i>
            <p>Nenhuma mensalidade em atraso.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Mensalidades Pendentes -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm">
    <div class="p-4 border-b border-gray-200 bg-yellow-50">
        <h3 class="font-semibold text-yellow-800">
            <i class="fas fa-clock mr-2"></i>Mensalidades Pendentes (Vencimento Futuro)
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aluno</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Turma</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referência</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($mensalidades_pendentes as $mensalidade): ?>
                    <tr class="hover:bg-yellow-50">
                        <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($mensalidade['turma_nome'] ?? '-'); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('m/Y', strtotime($mensalidade['referencia'])); ?></td>
                        <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?></td>
                        <td class="px-6 py-4 text-gray-600">R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?></td>
                        <td class="px-6 py-4 text-sm">
                            <button onclick="registrarPagamento(<?php echo $mensalidade['id']; ?>, <?php echo $mensalidade['valor']; ?>)" class="bg-green-600 text-white px-3 py-1 rounded-lg hover:bg-green-700 transition-colors text-xs font-medium">
                                <i class="fas fa-check mr-1"></i>Pagar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($mensalidades_pendentes)): ?>
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-clock text-4xl mb-4"></i>
            <p>Nenhuma mensalidade pendente.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Registrar Pagamento -->
<div id="modal-pagamento" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6 border-b border-gray-200 bg-green-600 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">Registrar Pagamento</h3>
                <button onclick="closeModal()" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="registrar_pagamento">
                <input type="hidden" name="mensalidade_id" id="mensalidade_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Valor Pago</label>
                    <input type="number" name="valor_pago" id="valor_pago" step="0.01" min="0" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data Pagamento</label>
                    <input type="date" name="data_pagamento" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Forma de Pagamento</label>
                    <select name="forma_pagamento" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        <option value="dinheiro">Dinheiro</option>
                        <option value="cartao_credito">Cartão de Crédito</option>
                        <option value="cartao_debito">Cartão de Débito</option>
                        <option value="pix">PIX</option>
                        <option value="boleto">Boleto</option>
                        <option value="transferencia">Transferência</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                
                <button type="submit" class="w-full bg-green-600 text-white font-medium py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Registrar Pagamento
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function registrarPagamento(mensalidadeId, valor) {
        document.getElementById('mensalidade_id').value = mensalidadeId;
        document.getElementById('valor_pago').value = valor;
        document.getElementById('modal-pagamento').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('modal-pagamento').classList.add('hidden');
    }
</script>

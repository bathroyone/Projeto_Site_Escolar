<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é secretaria
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'secretaria') {
    header('Location: ../login.php');
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
            $stmt = $pdo->prepare("UPDATE mensalidades SET status = 'pago', data_pagamento = ?, valor_pago = ?, forma_pagamento = ?, observacoes = ?, atualizado_por = ? WHERE id = ?");
            $stmt->execute([$data_pagamento, $valor_pago, $forma_pagamento, $observacoes, $_SESSION['usuario_id'], $mensalidade_id]);
            
            logAudit('MENSALIDADE_PAGAMENTO', 'mensalidades', $mensalidade_id, null, ['valor_pago' => $valor_pago, 'forma_pagamento' => $forma_pagamento]);
            
            $success = 'Pagamento registrado com sucesso!';
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
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Mensalidades | Portal da Secretaria</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        azul: {
                            principal: '#063b7a',
                            escuro: '#082b54',
                            claro: '#0b4a8c'
                        },
                        amarelo: {
                            destaque: '#ffd000',
                            claro: '#ffe033'
                        },
                        verde: {
                            complementar: '#13843b',
                            claro: '#15a048'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Poppins', 'system-ui', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #063b7a 0%, #0b4a8c 50%, #13843b 100%);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg shadow-lg sticky top-0 z-40">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2 sm:gap-3 group">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm group-hover:bg-white/30 transition-all">
                            <i class="fas fa-arrow-left text-white text-lg sm:text-xl"></i>
                        </div>
                        <div class="hidden sm:block">
                            <span class="text-white font-bold text-xs sm:text-sm tracking-wide">CONTROLE DE</span>
                            <span class="block text-amarelo-destaque font-extrabold text-xs sm:text-sm">MENSALIDADES</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-2 p-2 rounded-xl hover:bg-white/10 transition-all">
                            <div class="w-9 h-9 sm:w-11 sm:h-11 bg-gradient-to-br from-amarelo-destaque to-amarelo-claro rounded-xl flex items-center justify-center text-azul-escuro font-bold shadow-lg">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <div class="hidden sm:block text-left">
                                <span class="text-white text-xs sm:text-sm font-medium block"><?php echo htmlspecialchars(substr($_SESSION['nome'], 0, 15)); ?></span>
                                <span class="text-white/70 text-xs">Secretaria</span>
                            </div>
                            <i class="fas fa-chevron-down text-white/70 text-xs sm:text-sm"></i>
                        </button>

                        <div id="user-menu" class="hidden absolute right-0 mt-2 sm:mt-3 w-48 sm:w-56 glass-card rounded-2xl shadow-2xl overflow-hidden">
                            <div class="p-4 sm:p-5 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                                <p class="font-semibold text-white text-sm"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-xs sm:text-sm text-white/80">Secretaria</p>
                            </div>
                            <div class="p-2">
                                <a href="index.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-home"></i>
                                    <span>Painel Secretaria</span>
                                </a>
                                <a href="../dashboard.php" class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 rounded-xl transition-all">
                                    <i class="fas fa-tachometer-alt"></i>
                                    <span>Dashboard</span>
                                </a>
                                <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 text-red-600 hover:bg-red-50 rounded-xl transition-all">
                                    <i class="fas fa-sign-out-alt"></i>
                                    <span>Sair</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 sm:gap-6 mb-8">
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-red-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Em Atraso</p>
                        <p class="text-3xl font-bold text-red-500"><?php echo $estatisticas['total_atraso'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-400 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-clock text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Pendentes</p>
                        <p class="text-3xl font-bold text-yellow-500"><?php echo $estatisticas['total_pendente'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-verde-complementar to-verde-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-check-circle text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Pagas</p>
                        <p class="text-3xl font-bold text-verde-complementar"><?php echo $estatisticas['total_pago'] ?? 0; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="glass-card rounded-3xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-azul-claro rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-dollar-sign text-white text-xl"></i>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-500 text-sm">Valor Atraso</p>
                        <p class="text-3xl font-bold text-azul-principal">R$ <?php echo number_format($estatisticas['valor_atraso'] ?? 0, 2, ',', '.'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-azul-principal mb-8">Controle de Mensalidades</h2>

        <!-- Mensalidades em Atraso -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-red-500 to-red-400">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Mensalidades em Atraso
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($mensalidades_atraso) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">Turma</th>
                                    <th class="px-4 sm:px-6 py-4">Referência</th>
                                    <th class="px-4 sm:px-6 py-4">Vencimento</th>
                                    <th class="px-4 sm:px-6 py-4">Valor</th>
                                    <th class="px-4 sm:px-6 py-4">Dias Atraso</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mensalidades_atraso as $mensalidade): ?>
                                    <?php 
                                    $dias_atraso = (strtotime(date('Y-m-d')) - strtotime($mensalidade['data_vencimento'])) / 86400;
                                    ?>
                                    <tr class="border-b border-gray-50 hover:bg-red-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($mensalidade['turma_nome'] ?? '-'); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('m/Y', strtotime($mensalidade['referencia'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600">R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-600">
                                                <?php echo floor($dias_atraso); ?> dias
                                            </span>
                                        </td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <button onclick="registrarPagamento(<?php echo $mensalidade['id']; ?>, <?php echo $mensalidade['valor']; ?>)" class="bg-gradient-to-r from-verde-complementar to-verde-claro text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all text-sm font-semibold">
                                                <i class="fas fa-check mr-1"></i>Pagar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-check-circle text-4xl mb-4 text-green-500"></i>
                        <p>Nenhuma mensalidade em atraso.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Mensalidades Pendentes -->
        <div class="glass-card rounded-3xl shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-yellow-500 to-yellow-400">
                <h3 class="text-xl font-display font-bold text-white">
                    <i class="fas fa-clock mr-2"></i>Mensalidades Pendentes (Vencimento Futuro)
                </h3>
            </div>
            <div class="p-6">
                <?php if (count($mensalidades_pendentes) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                    <th class="px-4 sm:px-6 py-4">Aluno</th>
                                    <th class="px-4 sm:px-6 py-4">Turma</th>
                                    <th class="px-4 sm:px-6 py-4">Referência</th>
                                    <th class="px-4 sm:px-6 py-4">Vencimento</th>
                                    <th class="px-4 sm:px-6 py-4">Valor</th>
                                    <th class="px-4 sm:px-6 py-4">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mensalidades_pendentes as $mensalidade): ?>
                                    <tr class="border-b border-gray-50 hover:bg-yellow-50">
                                        <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($mensalidade['aluno_nome']); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo htmlspecialchars($mensalidade['turma_nome'] ?? '-'); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('m/Y', strtotime($mensalidade['referencia'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600"><?php echo date('d/m/Y', strtotime($mensalidade['data_vencimento'])); ?></td>
                                        <td class="px-4 sm:px-6 py-4 text-gray-600">R$ <?php echo number_format($mensalidade['valor'], 2, ',', '.'); ?></td>
                                        <td class="px-4 sm:px-6 py-4">
                                            <button onclick="registrarPagamento(<?php echo $mensalidade['id']; ?>, <?php echo $mensalidade['valor']; ?>)" class="bg-gradient-to-r from-verde-complementar to-verde-claro text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all text-sm font-semibold">
                                                <i class="fas fa-check mr-1"></i>Pagar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-clock text-4xl mb-4"></i>
                        <p>Nenhuma mensalidade pendente.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Registrar Pagamento -->
    <div id="modal-pagamento" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
        <div class="glass-card rounded-3xl shadow-2xl w-full max-w-md">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-verde-complementar to-verde-claro flex items-center justify-between">
                <h3 class="text-xl font-display font-bold text-white">Registrar Pagamento</h3>
                <button onclick="document.getElementById('modal-pagamento').classList.add('hidden')" class="text-white hover:text-white/80 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" class="p-6">
                <input type="hidden" name="action" value="registrar_pagamento">
                <input type="hidden" name="mensalidade_id" id="mensalidade_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Valor Pago</label>
                    <input type="number" name="valor_pago" id="valor_pago" step="0.01" min="0" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Data Pagamento</label>
                    <input type="date" name="data_pagamento" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Forma de Pagamento</label>
                    <select name="forma_pagamento" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent">
                        <option value="dinheiro">Dinheiro</option>
                        <option value="cartao_credito">Cartão de Crédito</option>
                        <option value="cartao_debito">Cartão de Débito</option>
                        <option value="pix">PIX</option>
                        <option value="boleto">Boleto</option>
                        <option value="transferencia">Transferência</option>
                    </select>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                    <textarea name="observacoes" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-verde-complementar focus:border-transparent"></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('modal-pagamento').classList.add('hidden')" class="px-6 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-verde-complementar to-verde-claro text-white rounded-xl hover:shadow-lg transition-all font-semibold">
                        Registrar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function registrarPagamento(mensalidadeId, valor) {
            document.getElementById('mensalidade_id').value = mensalidadeId;
            document.getElementById('valor_pago').value = valor;
            document.getElementById('modal-pagamento').classList.remove('hidden');
        }

        document.addEventListener('click', function(event) {
            const menu = document.getElementById('user-menu');
            const button = event.target.closest('button');
            if (!button && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

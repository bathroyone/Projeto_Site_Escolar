<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin ou secretaria
if (!isset($_SESSION['usuario_id']) || ($_SESSION['tipo'] !== 'admin' && $_SESSION['tipo'] !== 'secretaria')) {
    header('Location: ../login.php');
    exit();
}

// Conectar ao banco de dados
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$success = '';
$error = '';

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
            $stmt = $conn->prepare("INSERT INTO financeiro_historico (aluno_id, tipo_movimento, categoria, descricao, valor, data_movimento, forma_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$aluno_id, $tipo, $categoria, $descricao, $valor, $data_movimento, $forma_pagamento]);
            $success = 'Movimento financeiro registrado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao registrar movimento financeiro.';
        }
    }
}

// Obter estatísticas financeiras
$total_receitas = 0;
$total_despesas = 0;
$saldo = 0;

$query_receitas = "SELECT SUM(valor) as total FROM financeiro_historico WHERE tipo_movimento = 'receita'";
$result_receitas = $conn->query($query_receitas);
$total_receitas = $result_receitas->fetch_assoc()['total'] ?? 0;

$query_despesas = "SELECT SUM(valor) as total FROM financeiro_historico WHERE tipo_movimento = 'despesa'";
$result_despesas = $conn->query($query_despesas);
$total_despesas = $result_despesas->fetch_assoc()['total'] ?? 0;

$saldo = $total_receitas - $total_despesas;

// Obter histórico financeiro
$historico = [];
$query_historico = "SELECT fh.*, u.nome_completo as aluno_nome FROM financeiro_historico fh LEFT JOIN usuarios u ON fh.aluno_id = u.id ORDER BY fh.data_movimento DESC LIMIT 50";
$result_historico = $conn->query($query_historico);
while ($row = $result_historico->fetch_assoc()) {
    $historico[] = $row;
}

// Obter lista de alunos
$alunos = [];
$query_alunos = "SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = 1 ORDER BY nome_completo";
$result_alunos = $conn->query($query_alunos);
while ($row = $result_alunos->fetch_assoc()) {
    $alunos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Financeira | Portal de Gestão Escolar</title>
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
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <a href="index.php" class="flex items-center gap-2">
                        <img src="../img/logo.jpg" alt="Logo" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">[Inserir nome da escola aqui]</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">[Inserir nome da escola aqui]</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="../dashboard_secretaria.php" class="px-4 py-2 text-gray-600 hover:text-azul-principal transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Voltar
                    </a>
                    
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-2 p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <span class="hidden md:block text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-sm text-gray-500 capitalize"><?php echo htmlspecialchars($_SESSION['tipo']); ?></p>
                            </div>
                            <div class="p-2">
                                <a href="../logout.php" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Sair
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gestão Financeira</h1>
                <p class="text-gray-600 mt-2">Controle financeiro completo da escola</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Novo Movimento
            </button>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-4">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total Receitas</p>
                        <p class="text-3xl font-bold text-green-600">R$ <?php echo number_format($total_receitas, 2, ',', '.'); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-arrow-up text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total Despesas</p>
                        <p class="text-3xl font-bold text-red-600">R$ <?php echo number_format($total_despesas, 2, ',', '.'); ?></p>
                    </div>
                    <div class="w-14 h-14 bg-red-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-arrow-down text-red-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Saldo Atual</p>
                        <p class="text-3xl font-bold <?php echo $saldo >= 0 ? 'text-azul-principal' : 'text-red-600'; ?>">R$ <?php echo number_format($saldo, 2, ',', '.'); ?></p>
                    </div>
                    <div class="w-14 h-14 <?php echo $saldo >= 0 ? 'bg-azul-principal/10' : 'bg-red-100'; ?> rounded-2xl flex items-center justify-center">
                        <i class="fas fa-wallet <?php echo $saldo >= 0 ? 'text-azul-principal' : 'text-red-600'; ?> text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Histórico Financeiro -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Histórico de Movimentos</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead>
                        <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-4 sm:px-6 py-4">Data</th>
                            <th class="px-4 sm:px-6 py-4">Tipo</th>
                            <th class="px-4 sm:px-6 py-4">Categoria</th>
                            <th class="px-4 sm:px-6 py-4 hidden sm:table-cell">Descrição</th>
                            <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Aluno</th>
                            <th class="px-4 sm:px-6 py-4">Valor</th>
                            <th class="px-4 sm:px-6 py-4 hidden sm:table-cell">Forma Pagamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $movimento): ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50">
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($movimento['data_movimento'])); ?></td>
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        <?php echo $movimento['tipo_movimento'] === 'receita' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                        <?php echo ucfirst($movimento['tipo_movimento']); ?>
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($movimento['categoria']); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden sm:table-cell"><?php echo htmlspecialchars($movimento['descricao'] ?? '-'); ?></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($movimento['aluno_nome'] ?? '-'); ?></td>
                                <td class="px-4 sm:px-6 py-4 font-semibold 
                                    <?php echo $movimento['tipo_movimento'] === 'receita' ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $movimento['tipo_movimento'] === 'receita' ? '+' : '-'; ?>R$ <?php echo number_format($movimento['valor'], 2, ',', '.'); ?>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden sm:table-cell"><?php echo htmlspecialchars($movimento['forma_pagamento'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Adicionar Movimento -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Movimento Financeiro</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="adicionar_movimento">
                    
                    <div class="mb-4">
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Movimento *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="receita">Receita</option>
                            <option value="despesa">Despesa</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="categoria" class="block text-sm font-semibold text-gray-700 mb-2">Categoria *</label>
                        <select id="categoria" name="categoria" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
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
                        <label for="aluno_id" class="block text-sm font-semibold text-gray-700 mb-2">Aluno (opcional)</label>
                        <select id="aluno_id" name="aluno_id"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="valor" class="block text-sm font-semibold text-gray-700 mb-2">Valor (R$) *</label>
                        <input type="number" id="valor" name="valor" step="0.01" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="0,00">
                    </div>
                    
                    <div class="mb-4">
                        <label for="data_movimento" class="block text-sm font-semibold text-gray-700 mb-2">Data do Movimento *</label>
                        <input type="date" id="data_movimento" name="data_movimento" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="mb-4">
                        <label for="forma_pagamento" class="block text-sm font-semibold text-gray-700 mb-2">Forma de Pagamento</label>
                        <select id="forma_pagamento" name="forma_pagamento"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
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
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição do movimento"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Movimento
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function toggleModal() {
            const modal = document.getElementById('modal');
            modal.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('user-menu');
            if (!e.target.closest('[onclick="toggleMenu()"]') && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

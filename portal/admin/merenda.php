<?php
session_start();
require_once '../config.php';

// Verificar se o usuário está logado e é admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['tipo_usuario'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$success = '';
$error = '';

// Criar cardápio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_cardapio') {
    $data = sanitizeInput($_POST['data'] ?? '');
    $tipo_refeicao = sanitizeInput($_POST['tipo_refeicao'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $calorias = intval($_POST['calorias'] ?? 0);
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($data) || empty($tipo_refeicao) || empty($descricao)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO merenda_cardapios (data, tipo_refeicao, descricao, calorias, observacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$data, $tipo_refeicao, $descricao, $calorias, $observacoes, $_SESSION['usuario_id']]);
            
            logAudit('CARDPIO_CREATE', 'merenda_cardapios', $pdo->lastInsertId(), null, ['data' => $data, 'tipo' => $tipo_refeicao]);
            
            $success = 'Cardápio criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar cardápio.';
        }
    }
}

// Registrar consumo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_consumo') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $data_consumo = sanitizeInput($_POST['data_consumo'] ?? date('Y-m-d'));
    $tipo_refeicao = sanitizeInput($_POST['tipo_refeicao'] ?? 'almoco');
    $consumiu = isset($_POST['consumiu']) ? 1 : 0;
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($aluno_id)) {
        $error = 'Por favor, selecione o aluno.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO merenda_consumo (aluno_id, data_consumo, tipo_refeicao, consumiu, observacoes, registrado_por) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE consumiu = ?, observacoes = ?");
            $stmt->execute([$aluno_id, $data_consumo, $tipo_refeicao, $consumiu, $observacoes, $_SESSION['usuario_id'], $consumiu, $observacoes]);
            
            logAudit('MERENDA_CONSUMO', 'merenda_consumo', null, null, ['aluno_id' => $aluno_id, 'consumiu' => $consumiu]);
            
            $success = 'Consumo registrado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao registrar consumo.';
        }
    }
}

// Obter cardápios
$cardapios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM merenda_cardapios ORDER BY data DESC, tipo_refeicao");
    $cardapios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter cardápios: " . $e->getMessage());
}

// Obter consumo
$consumo = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT mc.*, u.nome_completo as aluno_nome 
        FROM merenda_consumo mc 
        JOIN usuarios u ON mc.aluno_id = u.id 
        ORDER BY mc.data_consumo DESC, mc.tipo_refeicao 
        LIMIT 50
    ");
    $consumo = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter consumo: " . $e->getMessage());
}

// Obter alunos
$alunos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE tipo_usuario = 'aluno' AND ativo = 1 ORDER BY nome_completo");
    $alunos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Alimentação | Portal de Gestão Escolar</title>
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
                    <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-azul-principal transition-colors">
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
                                <p class="text-sm text-gray-500">Administrador</p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Controle de Alimentação</h1>
                <p class="text-gray-600 mt-2">Gestão de merenda escolar</p>
            </div>
            <button onclick="toggleModalCardapio()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Novo Cardápio
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

        <!-- Tabs -->
        <div class="flex gap-2 mb-6 border-b border-gray-200">
            <button onclick="showTab('cardapios')" id="tab-cardapios" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Cardápios</button>
            <button onclick="showTab('consumo')" id="tab-consumo" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Consumo</button>
        </div>

        <!-- Tab Cardápios -->
        <div id="content-cardapios" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Data</th>
                                <th class="px-4 sm:px-6 py-4">Refeição</th>
                                <th class="px-4 sm:px-6 py-4">Descrição</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Calorias</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cardapios as $cardapio): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($cardapio['data'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-600">
                                            <?php echo ucfirst(str_replace('_', ' ', $cardapio['tipo_refeicao'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($cardapio['descricao']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $cardapio['calorias'] ? $cardapio['calorias'] . ' kcal' : '-'; ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_cardapio&id=<?php echo $cardapio['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir este cardápio?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($cardapios)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-utensils text-4xl mb-2"></i>
                        <p>Nenhum cardápio cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Consumo -->
        <div id="content-consumo" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Registrar Consumo</h3>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="action" value="registrar_consumo">
                    
                    <div>
                        <label for="aluno_id" class="block text-sm font-semibold text-gray-700 mb-2">Aluno</label>
                        <select id="aluno_id" name="aluno_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo $aluno['id']; ?>"><?php echo htmlspecialchars($aluno['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="data_consumo" class="block text-sm font-semibold text-gray-700 mb-2">Data</label>
                        <input type="date" id="data_consumo" name="data_consumo" value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="tipo_refeicao" class="block text-sm font-semibold text-gray-700 mb-2">Refeição</label>
                        <select id="tipo_refeicao" name="tipo_refeicao" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="cafe_manha">Café da Manhã</option>
                            <option value="almoco">Almoço</option>
                            <option value="lanche">Lanche</option>
                            <option value="jantar">Jantar</option>
                        </select>
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-save mr-2"></i>Registrar
                        </button>
                    </div>
                    
                    <div class="md:col-span-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="consumiu" checked class="w-5 h-5 text-azul-principal">
                            <span class="text-sm text-gray-700">Aluno consumiu a refeição</span>
                        </label>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-azul-principal">Histórico de Consumo</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Aluno</th>
                                <th class="px-4 sm:px-6 py-4">Data</th>
                                <th class="px-4 sm:px-6 py-4">Refeição</th>
                                <th class="px-4 sm:px-6 py-4">Consumiu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consumo as $c): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($c['aluno_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($c['data_consumo'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-600">
                                            <?php echo ucfirst(str_replace('_', ' ', $c['tipo_refeicao'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $c['consumiu'] ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                            <?php echo $c['consumiu'] ? 'Sim' : 'Não'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($consumo)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-utensils text-4xl mb-2"></i>
                        <p>Nenhum consumo registrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Cardápio -->
    <div id="modal-cardapio" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModalCardapio()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Cardápio</h2>
                    <button onclick="toggleModalCardapio()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_cardapio">
                    
                    <div class="mb-4">
                        <label for="data" class="block text-sm font-semibold text-gray-700 mb-2">Data *</label>
                        <input type="date" id="data" name="data" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo_refeicao" class="block text-sm font-semibold text-gray-700 mb-2">Refeição *</label>
                        <select id="tipo_refeicao" name="tipo_refeicao" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="cafe_manha">Café da Manhã</option>
                            <option value="almoco">Almoço</option>
                            <option value="lanche">Lanche</option>
                            <option value="jantar">Jantar</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição *</label>
                        <textarea id="descricao" name="detalhes" rows="3" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição do cardápio"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="calorias" class="block text-sm font-semibold text-gray-700 mb-2">Calorias (kcal)</label>
                        <input type="number" id="calorias" name="calorias"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: 500">
                    </div>
                    
                    <div class="mb-4">
                        <label for="observacoes" class="block text-sm font-semibold text-gray-700 mb-2">Observações</label>
                        <textarea id="observacoes" name="observacoes" rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Observações adicionais"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Cardápio
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

        function toggleModalCardapio() {
            const modal = document.getElementById('modal-cardapio');
            modal.classList.toggle('hidden');
        }

        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('[id^="tab-"]').forEach(el => {
                el.classList.remove('text-azul-principal', 'border-b-2', 'border-azul-principal');
                el.classList.add('text-gray-500');
            });
            
            document.getElementById('content-' + tab).classList.remove('hidden');
            
            const tabElement = document.getElementById('tab-' + tab);
            tabElement.classList.add('text-azul-principal', 'border-b-2', 'border-azul-principal');
            tabElement.classList.remove('text-gray-500');
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

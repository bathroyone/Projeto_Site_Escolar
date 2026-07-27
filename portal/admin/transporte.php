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

// Criar veículo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_veiculo') {
    $placa = sanitizeInput($_POST['placa'] ?? '');
    $modelo = sanitizeInput($_POST['modelo'] ?? '');
    $ano = intval($_POST['ano'] ?? 0);
    $capacidade = intval($_POST['capacidade'] ?? 40);
    $tipo = sanitizeInput($_POST['tipo'] ?? 'onibus');
    $motorista = sanitizeInput($_POST['motorista'] ?? '');
    $telefone_motorista = sanitizeInput($_POST['telefone_motorista'] ?? '');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($placa) || empty($modelo) || empty($tipo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO transportes_veiculos (placa, modelo, ano, capacidade, tipo, motorista, telefone_motorista, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$placa, $modelo, $ano, $capacidade, $tipo, $motorista, $telefone_motorista, $observacoes]);
            
            logAudit('VEICULO_CREATE', 'transportes_veiculos', $pdo->lastInsertId(), null, ['placa' => $placa, 'modelo' => $modelo]);
            
            $success = 'Veículo cadastrado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao cadastrar veículo.';
        }
    }
}

// Adicionar aluno ao transporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_aluno') {
    $aluno_id = intval($_POST['aluno_id'] ?? 0);
    $veiculo_id = intval($_POST['veiculo_id'] ?? 0);
    $rota_id = intval($_POST['rota_id'] ?? 0);
    $tipo = sanitizeInput($_POST['tipo'] ?? 'ida');
    $ponto_embarque = sanitizeInput($_POST['ponto_embarque'] ?? '');
    $horario_embarque = sanitizeInput($_POST['horario_embarque'] ?? '');
    $horario_chegada = sanitizeInput($_POST['horario_chegada'] ?? '');
    $responsavel_busca = sanitizeInput($_POST['responsavel_busca'] ?? '');
    $telefone_responsavel = sanitizeInput($_POST['telefone_responsavel'] ?? '');
    
    if (empty($aluno_id) || empty($veiculo_id) || empty($rota_id)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO transportes_alunos (aluno_id, veiculo_id, rota_id, tipo, ponto_embarque, horario_embarque, horario_chegada, responsavel_busca, telefone_responsavel) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$aluno_id, $veiculo_id, $rota_id, $tipo, $ponto_embarque, $horario_embarque, $horario_chegada, $responsavel_busca, $telefone_responsavel]);
            
            logAudit('TRANSPORTE_ALUNO_ADD', 'transportes_alunos', $pdo->lastInsertId(), null, ['aluno_id' => $aluno_id, 'veiculo_id' => $veiculo_id]);
            
            $success = 'Aluno adicionado ao transporte com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao adicionar aluno ao transporte.';
        }
    }
}

// Excluir veículo
if (isset($_GET['action']) && $_GET['action'] === 'excluir_veiculo' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM transportes_veiculos WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: transporte.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir veículo.';
    }
}

// Obter veículos
$veiculos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM transportes_veiculos ORDER BY status, modelo");
    $veiculos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter veículos: " . $e->getMessage());
}

// Obter rotas
$rotas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM transportes_rotas WHERE ativo = 1 ORDER BY nome");
    $rotas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter rotas: " . $e->getMessage());
}

// Obter alunos no transporte
$alunos_transporte = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT ta.*, u.nome_completo as aluno_nome, v.placa as veiculo_placa, r.nome as rota_nome 
        FROM transportes_alunos ta 
        JOIN usuarios u ON ta.aluno_id = u.id 
        JOIN transportes_veiculos v ON ta.veiculo_id = v.id 
        JOIN transportes_rotas r ON ta.rota_id = r.id 
        WHERE ta.ativo = 1 
        ORDER BY v.placa, r.nome, u.nome_completo
    ");
    $alunos_transporte = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter alunos no transporte: " . $e->getMessage());
}

// Obter alunos disponíveis
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
    <title>Gestão de Transporte Escolar | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gestão de Transporte Escolar</h1>
                <p class="text-gray-600 mt-2">Veículos, rotas e alunos</p>
            </div>
            <button onclick="toggleModalVeiculo()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Novo Veículo
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
            <button onclick="showTab('veiculos')" id="tab-veiculos" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Veículos</button>
            <button onclick="showTab('alunos')" id="tab-alunos" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Alunos no Transporte</button>
        </div>

        <!-- Tab Veículos -->
        <div id="content-veiculos" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Placa</th>
                                <th class="px-4 sm:px-6 py-4">Modelo</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Capacidade</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Motorista</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="font-mono font-bold text-gray-800"><?php echo htmlspecialchars($veiculo['placa']); ?></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($veiculo['modelo']); ?> (<?php echo $veiculo['ano']; ?>)</td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                            <?php echo ucfirst($veiculo['tipo']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $veiculo['capacidade']; ?> alunos</td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($veiculo['motorista'] ?? '-'); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($veiculo['status']) {
                                                'ativo' => 'bg-green-100 text-green-600',
                                                'manutencao' => 'bg-yellow-100 text-yellow-600',
                                                'inativo' => 'bg-red-100 text-red-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst($veiculo['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_veiculo&id=<?php echo $veiculo['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir este veículo?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($veiculos)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-bus text-4xl mb-2"></i>
                        <p>Nenhum veículo cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Alunos -->
        <div id="content-alunos" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Adicionar Aluno ao Transporte</h3>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="action" value="adicionar_aluno">
                    
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
                        <label for="veiculo_id" class="block text-sm font-semibold text-gray-700 mb-2">Veículo</label>
                        <select id="veiculo_id" name="veiculo_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($veiculos as $veiculo): ?>
                                <option value="<?php echo $veiculo['id']; ?>"><?php echo htmlspecialchars($veiculo['placa'] . ' - ' . $veiculo['modelo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="rota_id" class="block text-sm font-semibold text-gray-700 mb-2">Rota</label>
                        <select id="rota_id" name="rota_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($rotas as $rota): ?>
                                <option value="<?php echo $rota['id']; ?>"><?php echo htmlspecialchars($rota['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="ida">Ida</option>
                            <option value="volta">Volta</option>
                            <option value="ida_volta">Ida e Volta</option>
                        </select>
                    </div>
                    
                    <div class="md:col-span-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="ponto_embarque" class="block text-sm font-semibold text-gray-700 mb-2">Ponto de Embarque</label>
                            <input type="text" id="ponto_embarque" name="ponto_embarque"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="horario_embarque" class="block text-sm font-semibold text-gray-700 mb-2">Horário Embarque</label>
                            <input type="time" id="horario_embarque" name="horario_embarque"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="responsavel_busca" class="block text-sm font-semibold text-gray-700 mb-2">Responsável</label>
                            <input type="text" id="responsavel_busca" name="responsavel_busca"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                                <i class="fas fa-plus mr-2"></i>Adicionar
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Aluno</th>
                                <th class="px-4 sm:px-6 py-4">Veículo</th>
                                <th class="px-4 sm:px-6 py-4">Rota</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Ponto Embarque</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Horário</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alunos_transporte as $aluno): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($aluno['aluno_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($aluno['veiculo_placa']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($aluno['rota_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                            <?php echo ucfirst(str_replace('_', ' ', $aluno['tipo'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($aluno['ponto_embarque'] ?? '-'); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $aluno['horario_embarque'] ?? '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($alunos_transporte)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-user-graduate text-4xl mb-2"></i>
                        <p>Nenhum aluno no transporte ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Veículo -->
    <div id="modal-veiculo" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModalVeiculo()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Veículo</h2>
                    <button onclick="toggleModalVeiculo()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_veiculo">
                    
                    <div class="mb-4">
                        <label for="placa" class="block text-sm font-semibold text-gray-700 mb-2">Placa *</label>
                        <input type="text" id="placa" name="placa" required maxlength="20"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: ABC-1234">
                    </div>
                    
                    <div class="mb-4">
                        <label for="modelo" class="block text-sm font-semibold text-gray-700 mb-2">Modelo *</label>
                        <input type="text" id="modelo" name="modelo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: Mercedes-Benz OF-1722">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="ano" class="block text-sm font-semibold text-gray-700 mb-2">Ano</label>
                            <input type="number" id="ano" name="ano"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="2024">
                        </div>
                        
                        <div>
                            <label for="capacidade" class="block text-sm font-semibold text-gray-700 mb-2">Capacidade</label>
                            <input type="number" id="capacidade" name="capacidade" value="40"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="onibus">Ônibus</option>
                            <option value="van">Van</option>
                            <option value="microonibus">Micro-ônibus</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="motorista" class="block text-sm font-semibold text-gray-700 mb-2">Motorista</label>
                        <input type="text" id="motorista" name="motorista"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Nome do motorista">
                    </div>
                    
                    <div class="mb-4">
                        <label for="telefone_motorista" class="block text-sm font-semibold text-gray-700 mb-2">Telefone Motorista</label>
                        <input type="text" id="telefone_motorista" name="telefone_motorista"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="(00) 00000-0000">
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
                        Salvar Veículo
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

        function toggleModalVeiculo() {
            const modal = document.getElementById('modal-veiculo');
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

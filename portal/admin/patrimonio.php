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

// Criar equipamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_equipamento') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? 'informatica');
    $numero_patrimonio = sanitizeInput($_POST['numero_patrimonio'] ?? '');
    $marca = sanitizeInput($_POST['marca'] ?? '');
    $modelo = sanitizeInput($_POST['modelo'] ?? '');
    $numero_serie = sanitizeInput($_POST['numero_serie'] ?? '');
    $data_aquisicao = sanitizeInput($_POST['data_aquisicao'] ?? '');
    $valor = floatval($_POST['valor'] ?? 0);
    $localizacao = sanitizeInput($_POST['localizacao'] ?? '');
    $estado_conservacao = sanitizeInput($_POST['estado_conservacao'] ?? 'bom');
    $status = sanitizeInput($_POST['status'] ?? 'ativo');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($nome) || empty($tipo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO equipamentos (nome, tipo, numero_patrimonio, marca, modelo, numero_serie, data_aquisicao, valor, localizacao, estado_conservacao, status, observacoes, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $tipo, $numero_patrimonio, $marca, $modelo, $numero_serie, $data_aquisicao, $valor, $localizacao, $estado_conservacao, $status, $observacoes, $_SESSION['usuario_id']]);
            
            logAudit('EQUIPAMENTO_CREATE', 'equipamentos', $pdo->lastInsertId(), null, ['nome' => $nome, 'tipo' => $tipo]);
            
            $success = 'Equipamento cadastrado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao cadastrar equipamento.';
        }
    }
}

// Registrar movimentação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'registrar_movimentacao') {
    $equipamento_id = intval($_POST['equipamento_id'] ?? 0);
    $tipo_movimentacao = sanitizeInput($_POST['tipo_movimentacao'] ?? 'entrada');
    $data_movimentacao = sanitizeInput($_POST['data_movimentacao'] ?? date('Y-m-d'));
    $origem = sanitizeInput($_POST['origem'] ?? '');
    $destino = sanitizeInput($_POST['destino'] ?? '');
    $responsavel = sanitizeInput($_POST['responsavel'] ?? '');
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    
    if (empty($equipamento_id) || empty($tipo_movimentacao) || empty($data_movimentacao)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO equipamentos_movimentacoes (equipamento_id, tipo_movimentacao, data_movimentacao, origem, destino, responsavel, motivo, registrado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$equipamento_id, $tipo_movimentacao, $data_movimentacao, $origem, $destino, $responsavel, $motivo, $_SESSION['usuario_id']]);
            
            logAudit('PATRIMONIO_MOVIMENTACAO', 'equipamentos_movimentacoes', $pdo->lastInsertId(), null, ['equipamento_id' => $equipamento_id, 'tipo' => $tipo_movimentacao]);
            
            $success = 'Movimentação registrada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao registrar movimentação.';
        }
    }
}

// Excluir equipamento
if (isset($_GET['action']) && $_GET['action'] === 'excluir_equipamento' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM equipamentos WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: patrimonio.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir equipamento.';
    }
}

// Obter equipamentos
$equipamentos = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM equipamentos ORDER BY nome");
    $equipamentos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter equipamentos: " . $e->getMessage());
}

// Obter movimentações
$movimentacoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT em.*, e.nome as equipamento_nome 
        FROM equipamentos_movimentacoes em 
        JOIN equipamentos e ON em.equipamento_id = e.id 
        ORDER BY em.data_movimentacao DESC
    ");
    $movimentacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter movimentações: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patrimônio | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gestão de Patrimônio</h1>
                <p class="text-gray-600 mt-2">Controle de equipamentos e patrimônio</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Novo Equipamento
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
            <button onclick="showTab('equipamentos')" id="tab-equipamentos" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Equipamentos</button>
            <button onclick="showTab('movimentacoes')" id="tab-movimentacoes" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Movimentações</button>
        </div>

        <!-- Tab Equipamentos -->
        <div id="content-equipamentos" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Nome</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Nº Patrimônio</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Marca/Modelo</th>
                                <th class="px-4 sm:px-6 py-4">Estado</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipamentos as $equip): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($equip['nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                            <?php echo ucfirst($equip['tipo']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($equip['numero_patrimonio'] ?? '-'); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($equip['marca'] ?? '-'); ?> <?php echo htmlspecialchars($equip['modelo'] ?? ''); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_estado = match($equip['estado_conservacao']) {
                                                'novo' => 'bg-green-100 text-green-600',
                                                'bom' => 'bg-blue-100 text-blue-600',
                                                'regular' => 'bg-yellow-100 text-yellow-600',
                                                'ruim' => 'bg-orange-100 text-orange-600',
                                                'danificado' => 'bg-red-100 text-red-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_estado;
                                            ?>">
                                            <?php echo ucfirst($equip['estado_conservacao']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $equip['status'] === 'ativo' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo ucfirst($equip['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_equipamento&id=<?php echo $equip['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir este equipamento?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($equipamentos)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-laptop text-4xl mb-2"></i>
                        <p>Nenhum equipamento cadastrado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Movimentações -->
        <div id="content-movimentacoes" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Nova Movimentação</h3>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="registrar_movimentacao">
                    
                    <div>
                        <label for="equipamento_id" class="block text-sm font-semibold text-gray-700 mb-2">Equipamento</label>
                        <select id="equipamento_id" name="equipamento_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($equipamentos as $equip): ?>
                                <option value="<?php echo $equip['id']; ?>"><?php echo htmlspecialchars($equip['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="tipo_movimentacao" class="block text-sm font-semibold text-gray-700 mb-2">Tipo</label>
                        <select id="tipo_movimentacao" name="tipo_movimentacao" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                            <option value="transferencia">Transferência</option>
                            <option value="baixa">Baixa</option>
                            <option value="manutencao">Manutenção</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="data_movimentacao" class="block text-sm font-semibold text-gray-700 mb-2">Data</label>
                        <input type="date" id="data_movimentacao" name="data_movimentacao" required value="<?php echo date('Y-m-d'); ?>"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="origem" class="block text-sm font-semibold text-gray-700 mb-2">Origem</label>
                        <input type="text" id="origem" name="origem"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="destino" class="block text-sm font-semibold text-gray-700 mb-2">Destino</label>
                        <input type="text" id="destino" name="destino"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="responsavel" class="block text-sm font-semibold text-gray-700 mb-2">Responsável</label>
                        <input type="text" id="responsavel" name="responsavel"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="md:col-span-3">
                        <label for="motivo" class="block text-sm font-semibold text-gray-700 mb-2">Motivo</label>
                        <input type="text" id="motivo" name="motivo"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="md:col-span-3">
                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-plus mr-2"></i>Registrar Movimentação
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-azul-principal">Histórico de Movimentações</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Equipamento</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4">Data</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Origem/Destino</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Responsável</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($mov['equipamento_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-600">
                                            <?php echo ucfirst($mov['tipo_movimentacao']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y', strtotime($mov['data_movimentacao'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell">
                                        <?php echo htmlspecialchars($mov['origem'] ?? ''); ?> 
                                        <?php echo $mov['destino'] ? ' → ' . htmlspecialchars($mov['destino']) : ''; ?>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($mov['responsavel'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($movimentacoes)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-exchange-alt text-4xl mb-2"></i>
                        <p>Nenhuma movimentação registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Equipamento -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Equipamento</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_equipamento">
                    
                    <div class="mb-4">
                        <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome *</label>
                        <input type="text" id="nome" name="nome" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Nome do equipamento">
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="informatica">Informática</option>
                            <option value="mobiliario">Mobiliário</option>
                            <option value="laboratorio">Laboratório</option>
                            <option value="esporte">Esporte</option>
                            <option value="audiovisual">Audiovisual</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="numero_patrimonio" class="block text-sm font-semibold text-gray-700 mb-2">Nº Patrimônio</label>
                            <input type="text" id="numero_patrimonio" name="numero_patrimonio"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="marca" class="block text-sm font-semibold text-gray-700 mb-2">Marca</label>
                            <input type="text" id="marca" name="marca"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="modelo" class="block text-sm font-semibold text-gray-700 mb-2">Modelo</label>
                            <input type="text" id="modelo" name="modelo"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="numero_serie" class="block text-sm font-semibold text-gray-700 mb-2">Número de Série</label>
                            <input type="text" id="numero_serie" name="numero_serie"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="data_aquisicao" class="block text-sm font-semibold text-gray-700 mb-2">Data Aquisição</label>
                            <input type="date" id="data_aquisicao" name="data_aquisicao"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="valor" class="block text-sm font-semibold text-gray-700 mb-2">Valor (R$)</label>
                            <input type="number" id="valor" name="valor" step="0.01"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="localizacao" class="block text-sm font-semibold text-gray-700 mb-2">Localização</label>
                            <input type="text" id="localizacao" name="localizacao"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="estado_conservacao" class="block text-sm font-semibold text-gray-700 mb-2">Estado</label>
                            <select id="estado_conservacao" name="estado_conservacao"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                                <option value="novo">Novo</option>
                                <option value="bom" selected>Bom</option>
                                <option value="regular">Regular</option>
                                <option value="ruim">Ruim</option>
                                <option value="danificado">Danificado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                        <select id="status" name="status"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="ativo" selected>Ativo</option>
                            <option value="inativo">Inativo</option>
                            <option value="manutencao">Manutenção</option>
                            <option value="baixado">Baixado</option>
                        </select>
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
                        Salvar Equipamento
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

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

// Processar formulário de valor de mensalidade por série
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_valor') {
    $serie = sanitizeInput($_POST['serie'] ?? '');
    $valor = floatval($_POST['valor'] ?? 0);
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    
    if (empty($serie) || $valor <= 0) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO mensalidade_valores (serie, valor_base, descricao) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor_base = ?, descricao = ?");
            $stmt->execute([$serie, $valor, $descricao, $valor, $descricao]);
            $success = 'Valor de mensalidade salvo com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao salvar valor de mensalidade.';
        }
    }
}

// Processar formulário de vencimento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_vencimento') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $dia = intval($_POST['dia'] ?? 0);
    $valor_adicional = floatval($_POST['valor_adicional'] ?? 0);
    $obrigatorio = isset($_POST['obrigatorio']) ? 1 : 0;
    
    if (empty($nome) || $dia < 1 || $dia > 31) {
        $error = 'Por favor, preencha todos os campos corretamente.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO mensalidade_vencimentos (nome, dia_vencimento, valor_adicional, obrigatorio) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $dia, $valor_adicional, $obrigatorio]);
            $success = 'Data de vencimento salva com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao salvar data de vencimento.';
        }
    }
}

// Processar formulário de desconto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'salvar_desconto') {
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $valor_percentual = floatval($_POST['valor_percentual'] ?? 0);
    $valor_fixo = floatval($_POST['valor_fixo'] ?? 0);
    $condicao = sanitizeInput($_POST['condicao'] ?? '');
    
    if (empty($tipo) || empty($nome)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO mensalidade_descontos (tipo, nome, valor_percentual, valor_fixo, condicao) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$tipo, $nome, $valor_percentual, $valor_fixo, $condicao]);
            $success = 'Desconto salvo com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao salvar desconto.';
        }
    }
}

// Obter valores de mensalidade por série
$valores_serie = [];
$query_valores = "SELECT * FROM mensalidade_valores WHERE ativo = 1 ORDER BY serie";
$result_valores = $conn->query($query_valores);
while ($row = $result_valores->fetch_assoc()) {
    $valores_serie[] = $row;
}

// Obter datas de vencimento
$vencimentos = [];
$query_vencimentos = "SELECT * FROM mensalidade_vencimentos WHERE ativo = 1 ORDER BY ordem";
$result_vencimentos = $conn->query($query_vencimentos);
while ($row = $result_vencimentos->fetch_assoc()) {
    $vencimentos[] = $row;
}

// Obter descontos
$descontos = [];
$query_descontos = "SELECT * FROM mensalidade_descontos WHERE ativo = 1 ORDER BY tipo, nome";
$result_descontos = $conn->query($query_descontos);
while ($row = $result_descontos->fetch_assoc()) {
    $descontos[] = $row;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Mensalidades | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Gerenciar Mensalidades</h1>
                <p class="text-gray-600 mt-2">Configurar valores, vencimentos e descontos</p>
            </div>
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
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6">
            <div class="flex border-b border-gray-100">
                <button onclick="showTab('valores')" id="tab-valores" class="px-6 py-4 font-semibold text-azul-principal border-b-2 border-azul-principal">
                    <i class="fas fa-dollar-sign mr-2"></i>Valores por Série
                </button>
                <button onclick="showTab('vencimentos')" id="tab-vencimentos" class="px-6 py-4 font-semibold text-gray-500 hover:text-azul-principal transition-colors">
                    <i class="fas fa-calendar-alt mr-2"></i>Datas de Vencimento
                </button>
                <button onclick="showTab('descontos')" id="tab-descontos" class="px-6 py-4 font-semibold text-gray-500 hover:text-azul-principal transition-colors">
                    <i class="fas fa-percent mr-2"></i>Descontos
                </button>
            </div>

            <!-- Tab Valores -->
            <div id="content-valores" class="p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Adicionar/Editar Valor por Série</h3>
                    <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="hidden" name="action" value="salvar_valor">
                        <input type="text" name="serie" placeholder="Série (ex: 1º Ano)" required
                            class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <input type="number" name="valor" placeholder="Valor (R$)" step="0.01" required
                            class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <input type="text" name="descricao" placeholder="Descrição"
                            class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-save mr-2"></i>Salvar
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-6 py-4">Série</th>
                                <th class="px-6 py-4">Valor Base</th>
                                <th class="px-6 py-4">Descrição</th>
                                <th class="px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($valores_serie as $valor): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($valor['serie']); ?></td>
                                    <td class="px-6 py-4 text-gray-600">R$ <?php echo number_format($valor['valor_base'], 2, ',', '.'); ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($valor['descricao'] ?? '-'); ?></td>
                                    <td class="px-6 py-4">
                                        <button class="p-2 rounded-lg hover:bg-red-100 text-red-600" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Vencimentos -->
            <div id="content-vencimentos" class="p-6 hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Adicionar Data de Vencimento</h3>
                    <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <input type="hidden" name="action" value="salvar_vencimento">
                        <input type="text" name="nome" placeholder="Nome (ex: Vencimento Padrão)" required
                            class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <input type="number" name="dia" placeholder="Dia (1-31)" min="1" max="31" required
                            class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <input type="number" name="valor_adicional" placeholder="Valor Adicional (R$)" step="0.01"
                            class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <label class="flex items-center gap-2 cursor-pointer px-4 py-3">
                            <input type="checkbox" name="obrigatorio" class="w-5 h-5 text-azul-principal">
                            <span class="text-sm text-gray-700">Obrigatório</span>
                        </label>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-save mr-2"></i>Salvar
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-6 py-4">Nome</th>
                                <th class="px-6 py-4">Dia Vencimento</th>
                                <th class="px-6 py-4">Valor Adicional</th>
                                <th class="px-6 py-4">Obrigatório</th>
                                <th class="px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vencimentos as $vencimento): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($vencimento['nome']); ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo $vencimento['dia_vencimento']; ?>º dia</td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <?php if ($vencimento['valor_adicional'] > 0): ?>
                                            <span class="text-green-600">+R$ <?php echo number_format($vencimento['valor_adicional'], 2, ',', '.'); ?></span>
                                        <?php elseif ($vencimento['valor_adicional'] < 0): ?>
                                            <span class="text-blue-600">R$ <?php echo number_format($vencimento['valor_adicional'], 2, ',', '.'); ?></span>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($vencimento['obrigatorio']): ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-600">Sim</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-600">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <button class="p-2 rounded-lg hover:bg-red-100 text-red-600" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Descontos -->
            <div id="content-descontos" class="p-6 hidden">
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Adicionar Desconto</h3>
                    <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <input type="hidden" name="action" value="salvar_desconto">
                        <select name="tipo" required class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                            <option value="">Tipo</option>
                            <option value="irmao">Irmão</option>
                            <option value="antes_vencimento">Antes do Vencimento</option>
                        </select>
                        <input type="text" name="nome" placeholder="Nome" required
                            class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <input type="number" name="valor_percentual" placeholder="% Desconto" step="0.01"
                            class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <input type="number" name="valor_fixo" placeholder="Valor Fixo (R$)" step="0.01"
                            class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <input type="text" name="condicao" placeholder="Condição"
                            class="px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-save mr-2"></i>Salvar
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-6 py-4">Tipo</th>
                                <th class="px-6 py-4">Nome</th>
                                <th class="px-6 py-4">% Desconto</th>
                                <th class="px-6 py-4">Valor Fixo</th>
                                <th class="px-6 py-4">Condição</th>
                                <th class="px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($descontos as $desconto): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php echo $desconto['tipo'] === 'irmao' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'; ?>">
                                            <?php echo ucfirst($desconto['tipo']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($desconto['nome']); ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo $desconto['valor_percentual']; ?>%</td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo $desconto['valor_fixo'] > 0 ? 'R$ ' . number_format($desconto['valor_fixo'], 2, ',', '.') : '-'; ?></td>
                                    <td class="px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($desconto['condicao'] ?? '-'); ?></td>
                                    <td class="px-6 py-4">
                                        <button class="p-2 rounded-lg hover:bg-red-100 text-red-600" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        function showTab(tabName) {
            // Hide all content
            document.getElementById('content-valores').classList.add('hidden');
            document.getElementById('content-vencimentos').classList.add('hidden');
            document.getElementById('content-descontos').classList.add('hidden');
            
            // Reset all tabs
            document.getElementById('tab-valores').classList.remove('text-azul-principal', 'border-b-2', 'border-azul-principal');
            document.getElementById('tab-valores').classList.add('text-gray-500');
            document.getElementById('tab-vencimentos').classList.remove('text-azul-principal', 'border-b-2', 'border-azul-principal');
            document.getElementById('tab-vencimentos').classList.add('text-gray-500');
            document.getElementById('tab-descontos').classList.remove('text-azul-principal', 'border-b-2', 'border-azul-principal');
            document.getElementById('tab-descontos').classList.add('text-gray-500');
            
            // Show selected tab
            document.getElementById('content-' + tabName).classList.remove('hidden');
            document.getElementById('tab-' + tabName).classList.add('text-azul-principal', 'border-b-2', 'border-azul-principal');
            document.getElementById('tab-' + tabName).classList.remove('text-gray-500');
        }

        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
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

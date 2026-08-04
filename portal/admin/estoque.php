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

// Criar material
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_material') {
    $codigo = sanitizeInput($_POST['codigo'] ?? '');
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $categoria = sanitizeInput($_POST['categoria'] ?? '');
    $unidade_medida = sanitizeInput($_POST['unidade_medida'] ?? 'un');
    $estoque_minimo = intval($_POST['estoque_minimo'] ?? 10);
    $estoque_atual = intval($_POST['estoque_atual'] ?? 0);
    $localizacao = sanitizeInput($_POST['localizacao'] ?? '');
    
    if (empty($codigo) || empty($nome) || empty($categoria)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO materiais (codigo, nome, descricao, categoria, unidade_medida, estoque_minimo, estoque_atual, localizacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $nome, $descricao, $categoria, $unidade_medida, $estoque_minimo, $estoque_atual, $localizacao]);
            
            logAudit('MATERIAL_CREATE', 'materiais', $pdo->lastInsertId(), null, ['codigo' => $codigo, 'nome' => $nome]);
            
            $success = 'Material criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar material.';
        }
    }
}

// Movimentação de estoque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'movimentar_estoque') {
    $material_id = intval($_POST['material_id'] ?? 0);
    $tipo = sanitizeInput($_POST['tipo'] ?? 'entrada');
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $motivo = sanitizeInput($_POST['motivo'] ?? '');
    $observacoes = sanitizeInput($_POST['observacoes'] ?? '');
    
    if (empty($material_id) || empty($quantidade)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Atualizar estoque
            if ($tipo === 'entrada' || $tipo === 'devolucao') {
                $stmt = $pdo->prepare("UPDATE materiais SET estoque_atual = estoque_atual + ? WHERE id = ?");
                $stmt->execute([$quantidade, $material_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE materiais SET estoque_atual = estoque_atual - ? WHERE id = ?");
                $stmt->execute([$quantidade, $material_id]);
            }
            
            // Registrar movimentação
            $stmt = $pdo->prepare("INSERT INTO estoque_movimentacoes (material_id, tipo, quantidade, motivo, responsavel_id, observacoes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$material_id, $tipo, $quantidade, $motivo, $_SESSION['usuario_id'], $observacoes]);
            
            logAudit('ESTOQUE_MOVIMENTACAO', 'estoque_movimentacoes', $pdo->lastInsertId(), null, ['material_id' => $material_id, 'tipo' => $tipo, 'quantidade' => $quantidade]);
            
            $success = 'Movimentação registrada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao registrar movimentação.';
        }
    }
}

// Excluir material
if (isset($_GET['action']) && $_GET['action'] === 'excluir_material' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM materiais WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: estoque.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir material.';
    }
}

// Obter materiais
$materiais = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM materiais WHERE ativo = 1 ORDER BY categoria, nome");
    $materiais = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter materiais: " . $e->getMessage());
}

// Obter movimentações recentes
$movimentacoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT em.*, m.nome as material_nome, u.nome_completo as responsavel_nome 
        FROM estoque_movimentacoes em 
        JOIN materiais m ON em.material_id = m.id 
        JOIN usuarios u ON em.responsavel_id = u.id 
        ORDER BY em.created_at DESC LIMIT 20
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
    <title>Controle de Estoque | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Controle de Estoque</h1>
                <p class="text-gray-600 mt-2">Gestão de materiais e estoque</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Novo Material
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
            <button onclick="showTab('materiais')" id="tab-materiais" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Materiais</button>
            <button onclick="showTab('movimentacoes')" id="tab-movimentacoes" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Movimentações</button>
        </div>

        <!-- Tab Materiais -->
        <div id="content-materiais" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Código</th>
                                <th class="px-4 sm:px-6 py-4">Nome</th>
                                <th class="px-4 sm:px-6 py-4">Categoria</th>
                                <th class="px-4 sm:px-6 py-4">Estoque Atual</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Mínimo</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materiais as $material): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="font-mono font-bold text-gray-800"><?php echo htmlspecialchars($material['codigo']); ?></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($material['nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                            <?php echo ucfirst($material['categoria']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="text-2xl font-bold <?php echo $material['estoque_atual'] <= $material['estoque_minimo'] ? 'text-red-600' : 'text-green-600'; ?>">
                                            <?php echo $material['estoque_atual']; ?> <?php echo htmlspecialchars($material['unidade_medida']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $material['estoque_minimo']; ?> <?php echo htmlspecialchars($material['unidade_medida']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $material['estoque_atual'] <= $material['estoque_minimo'] ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'; ?>">
                                            <?php echo $material['estoque_atual'] <= $material['estoque_minimo'] ? 'Estoque Baixo' : 'OK'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_material&id=<?php echo $material['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir este material?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab Movimentações -->
        <div id="content-movimentacoes" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Nova Movimentação</h3>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <input type="hidden" name="action" value="movimentar_estoque">
                    
                    <div>
                        <label for="material_id" class="block text-sm font-semibold text-gray-700 mb-2">Material</label>
                        <select id="material_id" name="material_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($materiais as $material): ?>
                                <option value="<?php echo $material['id']; ?>"><?php echo htmlspecialchars($material['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                            <option value="ajuste">Ajuste</option>
                            <option value="devolucao">Devolução</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="quantidade" class="block text-sm font-semibold text-gray-700 mb-2">Quantidade</label>
                        <input type="number" id="quantidade" name="quantidade" required min="1"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="motivo" class="block text-sm font-semibold text-gray-700 mb-2">Motivo</label>
                        <input type="text" id="motivo" name="motivo"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-save mr-2"></i>Registrar
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
                                <th class="px-4 sm:px-6 py-4">Material</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4">Quantidade</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Responsável</th>
                                <th class="px-4 sm:px-6 py-4">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimentacoes as $mov): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($mov['material_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_tipo = match($mov['tipo']) {
                                                'entrada' => 'bg-green-100 text-green-600',
                                                'saida' => 'bg-red-100 text-red-600',
                                                'ajuste' => 'bg-yellow-100 text-yellow-600',
                                                'devolucao' => 'bg-blue-100 text-blue-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_tipo;
                                            ?>">
                                            <?php echo ucfirst($mov['tipo']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo $mov['quantidade']; ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($mov['responsavel_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y H:i', strtotime($mov['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Novo Material -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Material</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_material">
                    
                    <div class="mb-4">
                        <label for="codigo" class="block text-sm font-semibold text-gray-700 mb-2">Código *</label>
                        <input type="text" id="codigo" name="codigo" required maxlength="50"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: PAP001">
                    </div>
                    
                    <div class="mb-4">
                        <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome *</label>
                        <input type="text" id="nome" name="nome" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Nome do material">
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição do material"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="categoria" class="block text-sm font-semibold text-gray-700 mb-2">Categoria *</label>
                        <select id="categoria" name="categoria" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="papelaria">Papelaria</option>
                            <option value="limpeza">Limpeza</option>
                            <option value="informatica">Informática</option>
                            <option value="mobiliario">Mobiliário</option>
                            <option value="livros">Livros</option>
                            <option value="esportes">Esportes</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="unidade_medida" class="block text-sm font-semibold text-gray-700 mb-2">Unidade</label>
                            <input type="text" id="unidade_medida" name="unidade_medida" value="un"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="estoque_minimo" class="block text-sm font-semibold text-gray-700 mb-2">Estoque Mínimo</label>
                            <input type="number" id="estoque_minimo" name="estoque_minimo" value="10"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="estoque_atual" class="block text-sm font-semibold text-gray-700 mb-2">Estoque Atual</label>
                            <input type="number" id="estoque_atual" name="estoque_atual" value="0"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="localizacao" class="block text-sm font-semibold text-gray-700 mb-2">Localização</label>
                        <input type="text" id="localizacao" name="localizacao"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Ex: Armário 1, Prateleira A">
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Material
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

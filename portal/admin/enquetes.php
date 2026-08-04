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

// Criar enquete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_enquete') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? 'enquete');
    $data_inicio = sanitizeInput($_POST['data_inicio'] ?? date('Y-m-d'));
    $data_fim = sanitizeInput($_POST['data_fim'] ?? '');
    $anonima = isset($_POST['anonima']) ? 1 : 0;
    
    if (empty($titulo) || empty($tipo) || empty($data_inicio)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO enquetes (titulo, descricao, tipo, data_inicio, data_fim, anonima, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$titulo, $descricao, $tipo, $data_inicio, $data_fim, $anonima, $_SESSION['usuario_id']]);
            
            logAudit('ENQUETE_CREATE', 'enquetes', $pdo->lastInsertId(), null, ['titulo' => $titulo, 'tipo' => $tipo]);
            
            $success = 'Enquete criada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar enquete.';
        }
    }
}

// Adicionar pergunta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_pergunta') {
    $enquete_id = intval($_POST['enquete_id'] ?? 0);
    $pergunta = sanitizeInput($_POST['pergunta'] ?? '');
    $tipo_pergunta = sanitizeInput($_POST['tipo_pergunta'] ?? 'texto');
    $obrigatoria = isset($_POST['obrigatoria']) ? 1 : 0;
    
    if (empty($enquete_id) || empty($pergunta)) {
        $error = 'Por favor, selecione a enquete e preencha a pergunta.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO enquete_perguntas (enquete_id, pergunta, tipo_pergunta, obrigatoria) VALUES (?, ?, ?, ?)");
            $stmt->execute([$enquete_id, $pergunta, $tipo_pergunta, $obrigatoria]);
            
            logAudit('PERGUNTA_CREATE', 'enquete_perguntas', $pdo->lastInsertId(), null, ['enquete_id' => $enquete_id]);
            
            $success = 'Pergunta adicionada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao adicionar pergunta.';
        }
    }
}

// Excluir enquete
if (isset($_GET['action']) && $_GET['action'] === 'excluir_enquete' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM enquetes WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: enquetes.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir enquete.';
    }
}

// Obter enquetes
$enquetes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM enquetes ORDER BY created_at DESC");
    $enquetes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter enquetes: " . $e->getMessage());
}

// Obter perguntas
$perguntas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT ep.*, e.titulo as enquete_titulo 
        FROM enquete_perguntas ep 
        JOIN enquetes e ON ep.enquete_id = e.id 
        ORDER BY e.created_at DESC, ep.ordem
    ");
    $perguntas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter perguntas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquetes e Pesquisas | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Enquetes e Pesquisas</h1>
                <p class="text-gray-600 mt-2">Gestão de pesquisas e enquetes</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Nova Enquete
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
            <button onclick="showTab('enquetes')" id="tab-enquetes" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Enquetes</button>
            <button onclick="showTab('perguntas')" id="tab-perguntas" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Perguntas</button>
        </div>

        <!-- Tab Enquetes -->
        <div id="content-enquetes" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Título</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data Início</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data Fim</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enquetes as $enquete): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($enquete['titulo']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-600">
                                            <?php echo ucfirst($enquete['tipo']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo date('d/m/Y', strtotime($enquete['data_inicio'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo $enquete['data_fim'] ? date('d/m/Y', strtotime($enquete['data_fim'])) : '-'; ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $enquete['ativa'] ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo $enquete['ativa'] ? 'Ativa' : 'Inativa'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <a href="?action=excluir_enquete&id=<?php echo $enquete['id']; ?>" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir esta enquete?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($enquetes)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-poll text-4xl mb-2"></i>
                        <p>Nenhuma enquete cadastrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Perguntas -->
        <div id="content-perguntas" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Nova Pergunta</h3>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="action" value="adicionar_pergunta">
                    
                    <div>
                        <label for="enquete_id" class="block text-sm font-semibold text-gray-700 mb-2">Enquete</label>
                        <select id="enquete_id" name="enquete_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($enquetes as $enquete): ?>
                                <option value="<?php echo $enquete['id']; ?>"><?php echo htmlspecialchars($enquete['titulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="tipo_pergunta" class="block text-sm font-semibold text-gray-700 mb-2">Tipo</label>
                        <select id="tipo_pergunta" name="tipo_pergunta" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="texto">Texto</option>
                            <option value="opcao_unica">Opção Única</option>
                            <option value="opcao_multipla">Opção Múltipla</option>
                            <option value="escala">Escala</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="pergunta" class="block text-sm font-semibold text-gray-700 mb-2">Pergunta</label>
                        <input type="text" id="pergunta" name="pergunta" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-plus mr-2"></i>Adicionar
                        </button>
                    </div>
                    
                    <div class="md:col-span-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="obrigatoria" checked class="w-5 h-5 text-azul-principal">
                            <span class="text-sm text-gray-700">Pergunta obrigatória</span>
                        </label>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-azul-principal">Perguntas Cadastradas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Enquete</th>
                                <th class="px-4 sm:px-6 py-4">Pergunta</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4">Obrigatória</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($perguntas as $pergunta): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($pergunta['enquete_titulo']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($pergunta['pergunta']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                            <?php echo ucfirst(str_replace('_', ' ', $pergunta['tipo_pergunta'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $pergunta['obrigatoria'] ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'; ?>">
                                            <?php echo $pergunta['obrigatoria'] ? 'Sim' : 'Não'; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($perguntas)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-question-circle text-4xl mb-2"></i>
                        <p>Nenhuma pergunta cadastrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Nova Enquete -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Nova Enquete</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_enquete">
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Título da enquete">
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="pesquisa">Pesquisa</option>
                            <option value="enquete">Enquete</option>
                            <option value="avaliacao">Avaliação</option>
                            <option value="satisfacao">Satisfação</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="3"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição da enquete"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="data_inicio" class="block text-sm font-semibold text-gray-700 mb-2">Data Início *</label>
                            <input type="date" id="data_inicio" name="data_inicio" required value="<?php echo date('Y-m-d'); ?>"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="data_fim" class="block text-sm font-semibold text-gray-700 mb-2">Data Fim</label>
                            <input type="date" id="data_fim" name="data_fim"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="anonima" class="w-5 h-5 text-azul-principal">
                            <span class="text-sm text-gray-700">Enquete anônima</span>
                        </label>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Salvar Enquete
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

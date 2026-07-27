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

// Criar relatório personalizado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_relatorio') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $tipo = sanitizeInput($_POST['tipo'] ?? '');
    $configuracao = $_POST['configuracao'] ?? '{}';
    
    if (empty($nome) || empty($tipo)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO relatorios_personalizados (nome, descricao, tipo, configuracao, criado_por) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $descricao, $tipo, $configuracao, $_SESSION['usuario_id']]);
            
            logAudit('RELATORIO_CREATE', 'relatorios_personalizados', $pdo->lastInsertId(), null, json_decode($configuracao, true));
            
            $success = 'Relatório criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar relatório.';
        }
    }
}

// Excluir relatório
if (isset($_GET['action']) && $_GET['action'] === 'excluir' && isset($_GET['id'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM relatorios_personalizados WHERE id = ?");
        $stmt->execute([intval($_GET['id'])]);
        header('Location: relatorios.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao excluir relatório.';
    }
}

// Obter relatórios personalizados
$relatorios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT r.*, u.nome_completo as criador_nome FROM relatorios_personalizados r LEFT JOIN usuarios u ON r.criado_por = u.id ORDER BY r.created_at DESC");
    $relatorios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter relatórios: " . $e->getMessage());
}

// Obter histórico de exportações
$exportacoes = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT e.*, r.nome as relatorio_nome, u.nome_completo as usuario_nome 
        FROM relatorios_exportacoes e 
        LEFT JOIN relatorios_personalizados r ON e.relatorio_id = r.id 
        LEFT JOIN usuarios u ON e.usuario_id = u.id 
        ORDER BY e.created_at DESC LIMIT 20
    ");
    $exportacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter exportações: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios Personalizados | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Relatórios Personalizados</h1>
                <p class="text-gray-600 mt-2">Gerenciar e exportar relatórios</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Novo Relatório
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
            <button onclick="showTab('relatorios')" id="tab-relatorios" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Relatórios</button>
            <button onclick="showTab('historico')" id="tab-historico" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Histórico de Exportações</button>
        </div>

        <!-- Tab Relatórios -->
        <div id="content-relatorios" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Nome</th>
                                <th class="px-4 sm:px-6 py-4">Tipo</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Criado Por</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data Criação</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relatorios as $relatorio): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-azul-principal/10 rounded-lg flex items-center justify-center">
                                                <i class="fas fa-file-alt text-azul-principal"></i>
                                            </div>
                                            <div>
                                                <span class="font-medium text-gray-800 text-sm"><?php echo htmlspecialchars($relatorio['nome']); ?></span>
                                                <span class="block text-xs text-gray-500"><?php echo htmlspecialchars($relatorio['descricao'] ?? ''); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_tipo = match($relatorio['tipo']) {
                                                'usuarios' => 'bg-blue-100 text-blue-600',
                                                'notas' => 'bg-green-100 text-green-600',
                                                'frequencia' => 'bg-yellow-100 text-yellow-600',
                                                'financeiro' => 'bg-purple-100 text-purple-600',
                                                'turmas' => 'bg-orange-100 text-orange-600',
                                                'matriculas' => 'bg-pink-100 text-pink-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_tipo;
                                            ?>">
                                            <?php echo ucfirst($relatorio['tipo']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($relatorio['criador_nome'] ?? '-'); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo date('d/m/Y', strtotime($relatorio['created_at'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <button onclick="exportarRelatorio(<?php echo $relatorio['id']; ?>, 'pdf')" class="p-2 rounded-lg hover:bg-red-100 text-red-600 transition-colors" title="Exportar PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                            <button onclick="exportarRelatorio(<?php echo $relatorio['id']; ?>, 'excel')" class="p-2 rounded-lg hover:bg-green-100 text-green-600 transition-colors" title="Exportar Excel">
                                                <i class="fas fa-file-excel"></i>
                                            </button>
                                            <a href="?action=excluir&id=<?php echo $relatorio['id']; ?>" class="p-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors" onclick="return confirm('Tem certeza que deseja excluir este relatório?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($relatorios)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-file-alt text-4xl mb-2"></i>
                        <p>Nenhum relatório personalizado criado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Histórico -->
        <div id="content-historico" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Relatório</th>
                                <th class="px-4 sm:px-6 py-4">Usuário</th>
                                <th class="px-4 sm:px-6 py-4">Formato</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Data</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exportacoes as $exportacao): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 text-gray-800 text-sm"><?php echo htmlspecialchars($exportacao['relatorio_nome'] ?? '-'); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars($exportacao['usuario_nome'] ?? '-'); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_formato = match($exportacao['formato']) {
                                                'pdf' => 'bg-red-100 text-red-600',
                                                'excel' => 'bg-green-100 text-green-600',
                                                'csv' => 'bg-blue-100 text-blue-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_formato;
                                            ?>">
                                            <?php echo strtoupper($exportacao['formato']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo date('d/m/Y H:i', strtotime($exportacao['created_at'])); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($exportacao['status']) {
                                                'concluido' => 'bg-green-100 text-green-600',
                                                'processando' => 'bg-yellow-100 text-yellow-600',
                                                'erro' => 'bg-red-100 text-red-600',
                                                'pendente' => 'bg-gray-100 text-gray-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst($exportacao['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($exportacoes)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-history text-4xl mb-2"></i>
                        <p>Nenhuma exportação registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Relatório -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Relatório</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_relatorio">
                    
                    <div class="mb-4">
                        <label for="nome" class="block text-sm font-semibold text-gray-700 mb-2">Nome *</label>
                        <input type="text" id="nome" name="nome" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Nome do relatório">
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição do relatório"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="tipo" class="block text-sm font-semibold text-gray-700 mb-2">Tipo *</label>
                        <select id="tipo" name="tipo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="usuarios">Usuários</option>
                            <option value="notas">Notas</option>
                            <option value="frequencia">Frequência</option>
                            <option value="financeiro">Financeiro</option>
                            <option value="turmas">Turmas</option>
                            <option value="matriculas">Matrículas</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="configuracao" class="block text-sm font-semibold text-gray-700 mb-2">Configuração (JSON)</label>
                        <textarea id="configuracao" name="configuracao" rows="4"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent font-mono text-sm"
                            placeholder='{"filtros": {}, "colunas": [], "ordenacao": ""}'>{"filtros": {}, "colunas": [], "ordenacao": ""}</textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Criar Relatório
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

        function exportarRelatorio(relatorioId, formato) {
            // Simulação de exportação
            alert(`Exportando relatório ${relatorioId} no formato ${formato}...`);
            
            // Em produção, isso chamaria um endpoint para gerar o arquivo
            // window.location.href = `exportar_relatorio.php?id=${relatorioId}&formato=${formato}`;
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

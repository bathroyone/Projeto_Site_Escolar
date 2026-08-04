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

// Criar ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'criar_ticket') {
    $titulo = sanitizeInput($_POST['titulo'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $categoria = sanitizeInput($_POST['categoria'] ?? 'duvida');
    $prioridade = sanitizeInput($_POST['prioridade'] ?? 'normal');
    $solicitante_id = intval($_POST['solicitante_id'] ?? $_SESSION['usuario_id']);
    
    if (empty($titulo) || empty($descricao) || empty($categoria)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $numero = 'TKT-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO suporte_tickets (numero, titulo, descricao, categoria, prioridade, solicitante_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$numero, $titulo, $descricao, $categoria, $prioridade, $solicitante_id]);
            
            logAudit('SUPPORT_TICKET_CREATE', 'suporte_tickets', $pdo->lastInsertId(), null, ['numero' => $numero, 'titulo' => $titulo]);
            
            $success = 'Ticket criado com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao criar ticket.';
        }
    }
}

// Adicionar resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar_resposta') {
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $mensagem = sanitizeInput($_POST['mensagem'] ?? '');
    $interno = isset($_POST['interno']) ? 1 : 0;
    
    if (empty($ticket_id) || empty($mensagem)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO suporte_respostas (ticket_id, usuario_id, mensagem, interno) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ticket_id, $_SESSION['usuario_id'], $mensagem, $interno]);
            
            logAudit('SUPPORT_RESPONSE_ADD', 'suporte_respostas', $pdo->lastInsertId(), null, ['ticket_id' => $ticket_id]);
            
            $success = 'Resposta adicionada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao adicionar resposta.';
        }
    }
}

// Atualizar status do ticket
if (isset($_GET['action']) && $_GET['action'] === 'atualizar_status' && isset($_GET['id']) && isset($_GET['status'])) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE suporte_tickets SET status = ? WHERE id = ?");
        $stmt->execute([sanitizeInput($_GET['status']), intval($_GET['id'])]);
        header('Location: suporte.php');
        exit();
    } catch (PDOException $e) {
        $error = 'Erro ao atualizar status.';
    }
}

// Obter tickets
$tickets = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT st.*, u1.nome_completo as solicitante_nome, u2.nome_completo as atribuido_nome 
        FROM suporte_tickets st 
        LEFT JOIN usuarios u1 ON st.solicitante_id = u1.id 
        LEFT JOIN usuarios u2 ON st.atribuido_a = u2.id 
        ORDER BY st.data_abertura DESC
    ");
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter tickets: " . $e->getMessage());
}

// Obter respostas
$respostas = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT sr.*, u.nome_completo as usuario_nome 
        FROM suporte_respostas sr 
        JOIN usuarios u ON sr.usuario_id = u.id 
        ORDER BY sr.created_at DESC
    ");
    $respostas = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter respostas: " . $e->getMessage());
}

// Obter usuários
$usuarios = [];
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, nome_completo FROM usuarios WHERE ativo = 1 ORDER BY nome_completo");
    $usuarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter usuários: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte e Help Desk | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Suporte e Help Desk</h1>
                <p class="text-gray-600 mt-2">Gestão de tickets de suporte</p>
            </div>
            <button onclick="toggleModal()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-plus mr-2"></i>Novo Ticket
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
            <button onclick="showTab('tickets')" id="tab-tickets" class="px-6 py-3 font-semibold text-azul-principal border-b-2 border-azul-principal">Tickets</button>
            <button onclick="showTab('respostas')" id="tab-respostas" class="px-6 py-3 font-semibold text-gray-500 hover:text-azul-principal">Respostas</button>
        </div>

        <!-- Tab Tickets -->
        <div id="content-tickets" class="tab-content">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Número</th>
                                <th class="px-4 sm:px-6 py-4">Título</th>
                                <th class="px-4 sm:px-6 py-4">Categoria</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Solicitante</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Prioridade</th>
                                <th class="px-4 sm:px-6 py-4">Status</th>
                                <th class="px-4 sm:px-6 py-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="font-mono font-bold text-azul-principal"><?php echo htmlspecialchars($ticket['numero']); ?></span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($ticket['titulo']); ?></td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-600">
                                            <?php echo ucfirst($ticket['categoria']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell"><?php echo htmlspecialchars($ticket['solicitante_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 hidden md:table-cell">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_prioridade = match($ticket['prioridade']) {
                                                'baixa' => 'bg-gray-100 text-gray-600',
                                                'normal' => 'bg-blue-100 text-blue-600',
                                                'alta' => 'bg-orange-100 text-orange-600',
                                                'urgente' => 'bg-red-100 text-red-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_prioridade;
                                            ?>">
                                            <?php echo ucfirst($ticket['prioridade']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_status = match($ticket['status']) {
                                                'aberto' => 'bg-green-100 text-green-600',
                                                'em_analise' => 'bg-yellow-100 text-yellow-600',
                                                'resolvido' => 'bg-blue-100 text-blue-600',
                                                'fechado' => 'bg-gray-100 text-gray-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_status;
                                            ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4">
                                        <div class="flex gap-1">
                                            <?php if ($ticket['status'] !== 'fechado'): ?>
                                                <a href="?action=atualizar_status&id=<?php echo $ticket['id']; ?>&status=resolvido" class="p-2 rounded-lg hover:bg-green-100 text-green-600 transition-colors" title="Resolver">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="?action=atualizar_status&id=<?php echo $ticket['id']; ?>&status=fechado" class="p-2 rounded-lg hover:bg-gray-100 text-gray-600 transition-colors" title="Fechar">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($tickets)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-headset text-4xl mb-2"></i>
                        <p>Nenhum ticket criado ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Respostas -->
        <div id="content-respostas" class="tab-content hidden">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="text-lg font-bold text-azul-principal mb-4">Nova Resposta</h3>
                <form method="POST" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="action" value="adicionar_resposta">
                    
                    <div>
                        <label for="ticket_id" class="block text-sm font-semibold text-gray-700 mb-2">Ticket</label>
                        <select id="ticket_id" name="ticket_id" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($tickets as $ticket): ?>
                                <option value="<?php echo $ticket['id']; ?>"><?php echo htmlspecialchars($ticket['numero'] . ' - ' . $ticket['titulo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="md:col-span-3">
                        <label for="mensagem" class="block text-sm font-semibold text-gray-700 mb-2">Mensagem</label>
                        <input type="text" id="mensagem" name="mensagem" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                    </div>
                    
                    <div class="md:col-span-4 flex items-end gap-2">
                        <button type="submit" class="flex-1 px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all">
                            <i class="fas fa-paper-plane mr-2"></i>Enviar Resposta
                        </button>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="interno" class="w-5 h-5 text-azul-principal">
                            <span class="text-sm text-gray-700">Interno</span>
                        </label>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-azul-principal">Histórico de Respostas</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs sm:text-sm text-gray-500 border-b border-gray-100 bg-gray-50">
                                <th class="px-4 sm:px-6 py-4">Usuário</th>
                                <th class="px-4 sm:px-6 py-4">Mensagem</th>
                                <th class="px-4 sm:px-6 py-4 hidden md:table-cell">Ticket</th>
                                <th class="px-4 sm:px-6 py-4">Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($respostas as $resposta): ?>
                                <tr class="border-b border-gray-50 hover:bg-gray-50">
                                    <td class="px-4 sm:px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($resposta['usuario_nome']); ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo htmlspecialchars(substr($resposta['mensagem'], 0, 50)) . '...'; ?></td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm hidden md:table-cell">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $resposta['interno'] ? 'bg-orange-100 text-orange-600' : 'bg-green-100 text-green-600'; ?>">
                                            <?php echo $resposta['interno'] ? 'Interno' : 'Público'; ?>
                                        </span>
                                    </td>
                                    <td class="px-4 sm:px-6 py-4 text-gray-600 text-sm"><?php echo date('d/m/Y H:i', strtotime($resposta['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($respostas)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-comments text-4xl mb-2"></i>
                        <p>Nenhuma resposta registrada ainda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Modal Novo Ticket -->
    <div id="modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="toggleModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-xl font-display font-bold text-azul-principal">Novo Ticket</h2>
                    <button onclick="toggleModal()" class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-times text-gray-400"></i>
                    </button>
                </div>
                <form method="POST" action="" class="p-6">
                    <input type="hidden" name="action" value="criar_ticket">
                    
                    <div class="mb-4">
                        <label for="titulo" class="block text-sm font-semibold text-gray-700 mb-2">Título *</label>
                        <input type="text" id="titulo" name="titulo" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Título do ticket">
                    </div>
                    
                    <div class="mb-4">
                        <label for="categoria" class="block text-sm font-semibold text-gray-700 mb-2">Categoria *</label>
                        <select id="categoria" name="categoria" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <option value="tecnico">Técnico</option>
                            <option value="acesso">Acesso</option>
                            <option value="duvida">Dúvida</option>
                            <option value="sugestao">Sugestão</option>
                            <option value="bug">Bug</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="prioridade" class="block text-sm font-semibold text-gray-700 mb-2">Prioridade</label>
                        <select id="prioridade" name="prioridade"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="baixa">Baixa</option>
                            <option value="normal" selected>Normal</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="solicitante_id" class="block text-sm font-semibold text-gray-700 mb-2">Solicitante</label>
                        <select id="solicitante_id" name="solicitante_id"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent appearance-none bg-white">
                            <option value="">Selecione</option>
                            <?php foreach ($usuarios as $usuario): ?>
                                <option value="<?php echo $usuario['id']; ?>"><?php echo htmlspecialchars($usuario['nome_completo']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição *</label>
                        <textarea id="descricao" name="descricao" rows="4" required
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                            placeholder="Descrição detalhada do problema"></textarea>
                    </div>
                    
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                        <i class="fas fa-save mr-2"></i>
                        Criar Ticket
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

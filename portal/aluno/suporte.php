<?php
require_once '../config.php';

requireLogin();

if (!isAluno()) {
    header('Location: ../dashboard.php');
    exit();
}

$aluno_id = $_SESSION['usuario_id'];
$turma = $_SESSION['turma'];
$serie = $_SESSION['serie'];

$success = '';
$error = '';

// Enviar solicitação de suporte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enviar_solicitacao') {
    $assunto = sanitizeInput($_POST['assunto'] ?? '');
    $categoria = sanitizeInput($_POST['categoria'] ?? '');
    $descricao = sanitizeInput($_POST['descricao'] ?? '');
    $prioridade = sanitizeInput($_POST['prioridade'] ?? 'normal');
    
    if (empty($assunto) || empty($descricao)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO suporte_solicitacoes (aluno_id, assunto, categoria, descricao, prioridade, data_solicitacao, status) VALUES (?, ?, ?, ?, ?, NOW(), 'pendente')");
            $stmt->execute([$aluno_id, $assunto, $categoria, $descricao, $prioridade]);
            
            logAudit('SUPORTE_SOLICITAR', 'suporte_solicitacoes', $pdo->lastInsertId(), null, ['assunto' => $assunto, 'prioridade' => $prioridade]);
            
            $success = 'Solicitação enviada com sucesso!';
        } catch (PDOException $e) {
            $error = 'Erro ao enviar solicitação.';
        }
    }
}

// Conectar ao banco de dados
$pdo = getDBConnection();

// Obter solicitações do aluno
$solicitacoes = [];
try {
    $stmt = $pdo->query("
        SELECT 
            ss.*,
            u.nome_completo as aluno_nome
        FROM suporte_solicitacoes ss
        JOIN usuarios u ON ss.aluno_id = u.id
        WHERE ss.aluno_id = ?
        ORDER BY ss.data_solicitacao DESC
        LIMIT 50
    ");
    $stmt->execute([$aluno_id]);
    $solicitacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter solicitações: " . $e->getMessage());
}

// Calcular estatísticas
$estatisticas = [
    'total' => count($solicitacoes),
    'pendentes' => 0,
    'em_andamento' => 0,
    'resolvidas' => 0
];

foreach ($solicitacoes as $solicitacao) {
    if ($solicitacao['status'] === 'pendente') {
        $estatisticas['pendentes']++;
    } elseif ($solicitacao['status'] === 'em_andamento') {
        $estatisticas['em_andamento']++;
    } elseif ($solicitacao['status'] === 'resolvido') {
        $estatisticas['resolvidas']++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte Técnico | Portal de Gestão Escolar</title>
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
                                <p class="text-sm text-gray-500">Aluno</p>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Suporte Técnico</h1>
                <p class="text-gray-600 mt-2">Precisa de ajuda? Entre em contato conosco</p>
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

        <!-- Cards de Estatísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Total de Solicitações</p>
                        <p class="text-4xl font-bold text-azul-principal"><?php echo $estatisticas['total']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-azul-principal/10 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-headset text-azul-principal text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Pendentes</p>
                        <p class="text-4xl font-bold text-yellow-600"><?php echo $estatisticas['pendentes']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-yellow-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Em Andamento</p>
                        <p class="text-4xl font-bold text-blue-600"><?php echo $estatisticas['em_andamento']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-spinner text-blue-600 text-2xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm mb-1">Resolvidas</p>
                        <p class="text-4xl font-bold text-green-600"><?php echo $estatisticas['resolvidas']; ?></p>
                    </div>
                    <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Formulário de Solicitação -->
            <div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                        <h2 class="text-xl font-display font-bold text-white">
                            <i class="fas fa-paper-plane mr-2"></i>Nova Solicitação
                        </h2>
                    </div>
                    <form method="POST" action="" class="p-6">
                        <input type="hidden" name="action" value="enviar_solicitacao">
                        
                        <div class="mb-4">
                            <label for="assunto" class="block text-sm font-semibold text-gray-700 mb-2">Assunto *</label>
                            <input type="text" id="assunto" name="assunto" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Sobre o que é sua dúvida?">
                        </div>
                        
                        <div class="mb-4">
                            <label for="categoria" class="block text-sm font-semibold text-gray-700 mb-2">Categoria</label>
                            <select id="categoria" name="categoria"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                                <option value="">Selecione</option>
                                <option value="acesso">Acesso ao Sistema</option>
                                <option value="notas">Notas e Boletim</option>
                                <option value="frequencia">Frequência</option>
                                <option value="trabalhos">Trabalhos e Entregas</option>
                                <option value="calendario">Calendário</option>
                                <option value="outro">Outro</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label for="prioridade" class="block text-sm font-semibold text-gray-700 mb-2">Prioridade</label>
                            <select id="prioridade" name="prioridade"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent">
                                <option value="baixa">Baixa</option>
                                <option value="normal" selected>Normal</option>
                                <option value="alta">Alta</option>
                                <option value="urgente">Urgente</option>
                            </select>
                        </div>
                        
                        <div class="mb-6">
                            <label for="descricao" class="block text-sm font-semibold text-gray-700 mb-2">Descrição *</label>
                            <textarea id="descricao" name="descricao" rows="4" required
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-azul-principal focus:border-transparent"
                                placeholder="Descreva seu problema em detalhes..."></textarea>
                        </div>
                        
                        <button type="submit"
                            class="w-full bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold py-3 rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Enviar Solicitação
                        </button>
                    </form>
                </div>
            </div>

            <!-- Histórico de Solicitações -->
            <div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-display font-bold text-azul-principal">Histórico de Solicitações</h2>
                    </div>
                    <div class="p-6">
                        <?php if (!empty($solicitacoes)): ?>
                            <div class="space-y-4">
                                <?php foreach ($solicitacoes as $solicitacao): ?>
                                    <div class="p-4 rounded-xl border border-gray-100 hover:shadow-md transition-all">
                                        <div class="flex items-start justify-between mb-2">
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($solicitacao['assunto']); ?></h3>
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                $cor_status = match($solicitacao['status']) {
                                                    'pendente' => 'bg-yellow-100 text-yellow-600',
                                                    'em_andamento' => 'bg-blue-100 text-blue-600',
                                                    'resolvido' => 'bg-green-100 text-green-600',
                                                    'fechado' => 'bg-gray-100 text-gray-600',
                                                    default => 'bg-gray-100 text-gray-600'
                                                };
                                                echo $cor_status;
                                                ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $solicitacao['status'])); ?>
                                            </span>
                                        </div>
                                        <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars(substr($solicitacao['descricao'], 0, 80)) . '...'; ?></p>
                                        <div class="flex items-center justify-between text-sm text-gray-500">
                                            <span>
                                                <i class="fas fa-calendar-alt mr-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($solicitacao['data_solicitacao'])); ?>
                                            </span>
                                            <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                                <?php 
                                                $cor_prioridade = match($solicitacao['prioridade']) {
                                                    'urgente' => 'bg-red-100 text-red-600',
                                                    'alta' => 'bg-orange-100 text-orange-600',
                                                    'normal' => 'bg-blue-100 text-blue-600',
                                                    'baixa' => 'bg-gray-100 text-gray-600',
                                                    default => 'bg-gray-100 text-gray-600'
                                                };
                                                echo $cor_prioridade;
                                                ?>">
                                                <?php echo ucfirst($solicitacao['prioridade']); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2"></i>
                                <p>Nenhuma solicitação enviada ainda.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
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

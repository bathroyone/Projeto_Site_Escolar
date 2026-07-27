<?php
require_once 'config.php';

requireLogin();

$usuario_id = $_SESSION['usuario_id'];
$tipo_usuario = $_SESSION['tipo_usuario'];
$turma = $_SESSION['turma'];
$serie = $_SESSION['serie'];

// Obter arquivos disponíveis para o aluno
$arquivos = [];
if (isAluno()) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT a.*, u.nome_completo as professor_nome 
            FROM arquivos a 
            JOIN usuarios u ON a.professor_id = u.id 
            WHERE a.ativo = TRUE 
            AND (a.visibilidade = 'publico' 
                OR (a.visibilidade = 'turma' AND a.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1))
                OR (a.visibilidade = 'serie' AND a.serie = ?))
            ORDER BY a.data_upload DESC
        ");
        $stmt->execute([$turma, $serie, $serie]);
        $arquivos = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Erro ao obter arquivos: " . $e->getMessage());
    }
}

// Obter eventos do calendário
$eventos = [];
try {
    $pdo = getDBConnection();
    if (isAluno()) {
        $stmt = $pdo->prepare("
            SELECT * FROM eventos_calendario 
            WHERE (turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1) 
                OR serie = ? 
                OR turma_id IS NULL)
            AND data_inicio >= CURDATE()
            ORDER BY data_inicio ASC
            LIMIT 10
        ");
        $stmt->execute([$turma, $serie, $serie]);
    } else {
        $stmt = $pdo->query("
            SELECT * FROM eventos_calendario 
            WHERE data_inicio >= CURDATE()
            ORDER BY data_inicio ASC
            LIMIT 10
        ");
    }
    $eventos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter eventos: " . $e->getMessage());
}

// Obter avisos
$avisos = [];
try {
    $pdo = getDBConnection();
    if (isAluno()) {
        $stmt = $pdo->prepare("
            SELECT a.*, u.nome_completo as professor_nome 
            FROM avisos a 
            JOIN usuarios u ON a.professor_id = u.id 
            WHERE a.ativo = TRUE 
            AND (a.tipo_aviso = 'geral' 
                OR (a.tipo_aviso = 'turma' AND a.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1))
                OR (a.tipo_aviso = 'serie' AND a.serie = ?))
            AND (a.data_expiracao IS NULL OR a.data_expiracao > CURDATE())
            ORDER BY a.data_publicacao DESC
            LIMIT 5
        ");
        $stmt->execute([$turma, $serie, $serie]);
    } else {
        $stmt = $pdo->query("
            SELECT a.*, u.nome_completo as professor_nome 
            FROM avisos a 
            JOIN usuarios u ON a.professor_id = u.id 
            WHERE a.ativo = TRUE 
            AND (a.data_expiracao IS NULL OR a.data_expiracao > CURDATE())
            ORDER BY a.data_publicacao DESC
            LIMIT 5
        ");
    }
    $avisos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter avisos: " . $e->getMessage());
}

// Obter notificações não lidas
$notificacoes = getUnreadNotifications($usuario_id);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Portal CEAA</title>
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
                    <a href="../index.html" class="flex items-center gap-2">
                        <img src="../img/logo1.png" alt="Logo CEAA" class="h-10">
                        <div class="hidden sm:block">
                            <span class="text-azul-principal font-bold text-xs">CENTRO EDUCACIONAL</span>
                            <span class="block text-amarelo-destaque font-extrabold text-sm">ALAMEDA ARGENTINA</span>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <button onclick="toggleNotificacoes()" class="relative p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <i class="fas fa-bell text-gray-600 text-xl"></i>
                            <?php if (count($notificacoes) > 0): ?>
                                <span class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                                    <?php echo count($notificacoes); ?>
                                </span>
                            <?php endif; ?>
                        </button>
                        
                        <div id="notificacoes-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <h3 class="font-semibold text-gray-800">Notificações</h3>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <?php if (count($notificacoes) > 0): ?>
                                    <?php foreach ($notificacoes as $notif): ?>
                                        <div class="p-4 border-b border-gray-50 hover:bg-gray-50">
                                            <p class="font-semibold text-sm text-gray-800"><?php echo htmlspecialchars($notif['titulo']); ?></p>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($notif['mensagem']); ?></p>
                                            <p class="text-xs text-gray-400 mt-2"><?php echo date('d/m/Y H:i', strtotime($notif['data_criacao'])); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-4 text-center text-gray-500 text-sm">
                                        Nenhuma notificação
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="relative">
                        <button onclick="toggleMenu()" class="flex items-center gap-2 p-2 rounded-full hover:bg-gray-100 transition-colors">
                            <div class="w-10 h-10 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-full flex items-center justify-center text-white font-bold">
                                <?php echo strtoupper(substr($_SESSION['nome'], 0, 1)); ?>
                            </div>
                            <span class="hidden md:block text-sm font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['nome']); ?></span>
                            <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                        </button>
                        
                        <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
                            <div class="p-4 border-b border-gray-100">
                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($_SESSION['nome']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo ucfirst($tipo_usuario); ?></p>
                                <?php if (isAluno()): ?>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($turma); ?> - <?php echo htmlspecialchars($serie); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="p-2">
                                <a href="logout.php" class="flex items-center gap-2 px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
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
        <!-- Bem-vindo -->
        <div class="mb-8">
            <h1 class="text-3xl font-display font-bold text-azul-principal">
                Bem-vindo, <?php echo htmlspecialchars(explode(' ', $_SESSION['nome'])[0]); ?>!
            </h1>
            <p class="text-gray-600 mt-2">
                <?php if (isAluno()): ?>
                    Turma: <?php echo htmlspecialchars($turma); ?> | Série: <?php echo htmlspecialchars($serie); ?>
                <?php elseif (isProfessor()): ?>
                    Portal do Professor
                <?php else: ?>
                    Portal Administrativo
                <?php endif; ?>
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Arquivos Disponíveis</p>
                        <p class="text-3xl font-bold text-azul-principal"><?php echo count($arquivos); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-file-alt text-azul-principal text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Próximos Eventos</p>
                        <p class="text-3xl font-bold text-verde-complementar"><?php echo count($eventos); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-calendar text-verde-complementar text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Avisos</p>
                        <p class="text-3xl font-bold text-amarelo-destaque"><?php echo count($avisos); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-bullhorn text-amarelo-destaque text-xl"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Notificações</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo count($notificacoes); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                        <i class="fas fa-bell text-purple-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Arquivos Recentes -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-display font-bold text-azul-principal">Arquivos Disponíveis</h2>
                    </div>
                    <div class="p-6">
                        <?php if (count($arquivos) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach ($arquivos as $arquivo): ?>
                                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                            <i class="fas fa-file text-azul-principal text-xl"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($arquivo['titulo']); ?></h3>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($arquivo['disciplina'] ?? ''); ?> | <?php echo htmlspecialchars($arquivo['tipo_arquivo']); ?></p>
                                            <p class="text-xs text-gray-400">Por: <?php echo htmlspecialchars($arquivo['professor_nome']); ?> | <?php echo date('d/m/Y', strtotime($arquivo['data_upload'])); ?></p>
                                        </div>
                                        <a href="uploads/<?php echo htmlspecialchars($arquivo['caminho_arquivo']); ?>" target="_blank" class="px-4 py-2 bg-azul-principal text-white rounded-lg hover:bg-azul-escuro transition-colors text-sm font-semibold">
                                            <i class="fas fa-download mr-1"></i>
                                            Baixar
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-folder-open text-4xl mb-4"></i>
                                <p>Nenhum arquivo disponível no momento.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Calendário e Avisos -->
            <div class="space-y-8">
                <!-- Calendário -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-display font-bold text-azul-principal">Próximos Eventos</h2>
                    </div>
                    <div class="p-6">
                        <?php if (count($eventos) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach ($eventos as $evento): ?>
                                    <div class="p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100">
                                        <div class="flex items-start gap-3">
                                            <div class="w-12 h-12 bg-white rounded-xl flex flex-col items-center justify-center shadow-sm">
                                                <span class="text-xs text-gray-500 uppercase"><?php echo date('M', strtotime($evento['data_inicio'])); ?></span>
                                                <span class="text-lg font-bold text-azul-principal"><?php echo date('d', strtotime($evento['data_inicio'])); ?></span>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                                                <p class="text-sm text-gray-600"><?php echo date('H:i', strtotime($evento['data_inicio'])); ?></p>
                                                <span class="inline-block mt-2 px-2 py-1 bg-<?php echo $evento['tipo_evento'] === 'prova' ? 'red' : ($evento['tipo_evento'] === 'trabalho' ? 'orange' : 'blue'); ?>-100 text-<?php echo $evento['tipo_evento'] === 'prova' ? 'red' : ($evento['tipo_evento'] === 'trabalho' ? 'orange' : 'blue'); ?>-600 rounded-full text-xs font-semibold">
                                                    <?php echo ucfirst($evento['tipo_evento']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-calendar-times text-4xl mb-4"></i>
                                <p>Nenhum evento próximo.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Avisos -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h2 class="text-xl font-display font-bold text-azul-principal">Avisos Recentes</h2>
                    </div>
                    <div class="p-6">
                        <?php if (count($avisos) > 0): ?>
                            <div class="space-y-4">
                                <?php foreach ($avisos as $aviso): ?>
                                    <div class="p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl border border-yellow-100">
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm">
                                                <i class="fas fa-bullhorn text-amarelo-destaque"></i>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($aviso['titulo']); ?></h3>
                                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($aviso['conteudo'], 0, 100)) . '...'; ?></p>
                                                <p class="text-xs text-gray-400 mt-2">Por: <?php echo htmlspecialchars($aviso['professor_nome']); ?> | <?php echo date('d/m/Y', strtotime($aviso['data_publicacao'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-info-circle text-4xl mb-4"></i>
                                <p>Nenhum aviso recente.</p>
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

        function toggleNotificacoes() {
            const dropdown = document.getElementById('notificacoes-dropdown');
            dropdown.classList.toggle('hidden');
        }

        // Fechar menus ao clicar fora
        document.addEventListener('click', function(e) {
            const userMenu = document.getElementById('user-menu');
            const notifDropdown = document.getElementById('notificacoes-dropdown');
            
            if (!e.target.closest('[onclick="toggleMenu()"]') && !userMenu.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
            
            if (!e.target.closest('[onclick="toggleNotificacoes()"]') && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.add('hidden');
            }
        });
    </script>
</body>
</html>

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

// Conectar ao banco de dados
$pdo = getDBConnection();

// Marcar notificações como lidas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'marcar_lida') {
    $notificacao_id = intval($_POST['notificacao_id'] ?? 0);
    try {
        $stmt = $pdo->prepare("UPDATE notificacoes SET lida = TRUE WHERE id = ? AND usuario_id = ?");
        $stmt->execute([$notificacao_id, $aluno_id]);
    } catch (PDOException $e) {
        error_log("Erro ao marcar notificação como lida: " . $e->getMessage());
    }
}

// Obter notificações
$notificacoes = [];
try {
    $stmt = $pdo->query("
        SELECT 
            n.*,
            CASE 
                WHEN n.tipo = 'trabalho' THEN (SELECT titulo FROM controle_entregas WHERE id = n.referencia_id LIMIT 1)
                WHEN n.tipo = 'prova' THEN (SELECT titulo FROM avaliacoes_formativas WHERE id = n.referencia_id LIMIT 1)
                WHEN n.tipo = 'comunicado' THEN (SELECT titulo FROM comunicados WHERE id = n.referencia_id LIMIT 1)
                ELSE n.titulo
            END as titulo_referencia
        FROM notificacoes n
        WHERE n.usuario_id = ?
        ORDER BY n.data_criacao DESC
        LIMIT 50
    ");
    $stmt->execute([$aluno_id]);
    $notificacoes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter notificações: " . $e->getMessage());
}

// Contar notificações não lidas
$nao_lidas = 0;
foreach ($notificacoes as $notificacao) {
    if (!$notificacao['lida']) {
        $nao_lidas++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificações | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Notificações</h1>
                <p class="text-gray-600 mt-2">Prazos e avisos importantes</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="px-4 py-2 bg-azul-principal text-white rounded-xl font-semibold">
                    <i class="fas fa-bell mr-2"></i>
                    <?php echo $nao_lidas; ?> não lidas
                </span>
                <button onclick="marcarTodasLidas()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl font-semibold hover:bg-gray-200 transition-all">
                    <i class="fas fa-check-double mr-2"></i>
                    Marcar todas como lidas
                </button>
            </div>
        </div>

        <!-- Lista de Notificações -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Todas as Notificações</h2>
            </div>
            <div class="divide-y divide-gray-100">
                <?php foreach ($notificacoes as $notificacao): ?>
                    <div class="p-6 hover:bg-gray-50 transition-colors <?php echo !$notificacao['lida'] ? 'bg-blue-50' : ''; ?>">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                                <?php 
                                $cor_tipo = match($notificacao['prioridade']) {
                                    'alta' => 'bg-red-100',
                                    'normal' => 'bg-blue-100',
                                    'baixa' => 'bg-gray-100',
                                    default => 'bg-gray-100'
                                };
                                echo $cor_tipo;
                                ?>">
                                <i class="fas 
                                    <?php 
                                    $icone_tipo = match($notificacao['tipo']) {
                                        'trabalho' => 'fa-tasks',
                                        'prova' => 'fa-file-alt',
                                        'comunicado' => 'fa-bullhorn',
                                        'aviso' => 'fa-bell',
                                        default => 'fa-info-circle'
                                    };
                                    echo $icone_tipo;
                                    ?> 
                                    text-xl 
                                    <?php 
                                    $cor_texto = match($notificacao['prioridade']) {
                                        'alta' => 'text-red-600',
                                        'normal' => 'text-blue-600',
                                        'baixa' => 'text-gray-600',
                                        default => 'text-gray-600'
                                    };
                                    echo $cor_texto;
                                    ?>">
                                </i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h3 class="font-semibold text-gray-800 <?php echo !$notificacao['lida'] ? 'font-bold' : ''; ?>">
                                            <?php echo htmlspecialchars($notificacao['titulo']); ?>
                                        </h3>
                                        <?php if ($notificacao['titulo_referencia']): ?>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($notificacao['titulo_referencia']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <?php if (!$notificacao['lida']): ?>
                                            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                        <?php endif; ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                            <?php 
                                            $cor_badge = match($notificacao['prioridade']) {
                                                'alta' => 'bg-red-100 text-red-600',
                                                'normal' => 'bg-blue-100 text-blue-600',
                                                'baixa' => 'bg-gray-100 text-gray-600',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                            echo $cor_badge;
                                            ?>">
                                            <?php echo ucfirst($notificacao['prioridade']); ?>
                                        </span>
                                    </div>
                                </div>
                                <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                    <span>
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?>
                                    </span>
                                    <?php if ($notificacao['data_prazo']): ?>
                                        <span class="<?php echo strtotime($notificacao['data_prazo']) < time() ? 'text-red-600 font-semibold' : ''; ?>">
                                            <i class="fas fa-clock mr-1"></i>
                                            Prazo: <?php echo date('d/m/Y H:i', strtotime($notificacao['data_prazo'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!$notificacao['lida']): ?>
                                <form method="POST" action="" class="flex-shrink-0">
                                    <input type="hidden" name="action" value="marcar_lida">
                                    <input type="hidden" name="notificacao_id" value="<?php echo $notificacao['id']; ?>">
                                    <button type="submit" class="p-2 rounded-lg hover:bg-gray-200 transition-colors" title="Marcar como lida">
                                        <i class="fas fa-check text-gray-400"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (empty($notificacoes)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-4xl mb-2"></i>
                    <p>Nenhuma notificação encontrada.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function marcarTodasLidas() {
            const forms = document.querySelectorAll('form[action=""]');
            forms.forEach(form => {
                form.submit();
            });
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

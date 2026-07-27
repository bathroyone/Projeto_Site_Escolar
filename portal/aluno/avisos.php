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

// Obter comunicados para alunos
$comunicados = [];
try {
    $stmt = $pdo->query("
        SELECT 
            c.*,
            u.nome_completo as autor_nome
        FROM comunicados c
        JOIN usuarios u ON c.autor_id = u.id
        WHERE (c.tipo = 'geral' OR c.tipo = 'alunos' OR c.tipo = 'todos')
        AND c.status = 'ativo'
        AND c.data_publicacao <= CURDATE()
        AND (c.data_expiracao IS NULL OR c.data_expiracao >= CURDATE())
        ORDER BY c.data_publicacao DESC
        LIMIT 50
    ");
    $comunicados = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter comunicados: " . $e->getMessage());
}

// Agrupar por prioridade
$comunicados_por_prioridade = [
    'urgente' => [],
    'alta' => [],
    'normal' => [],
    'baixa' => []
];

foreach ($comunicados as $comunicado) {
    $prioridade = $comunicado['prioridade'] ?? 'normal';
    if (isset($comunicados_por_prioridade[$prioridade])) {
        $comunicados_por_prioridade[$prioridade][] = $comunicado;
    } else {
        $comunicados_por_prioridade['normal'][] = $comunicado;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avisos e Comunicados | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Avisos e Comunicados</h1>
                <p class="text-gray-600 mt-2">Fique por dentro das novidades da escola</p>
            </div>
        </div>

        <!-- Filtros por Prioridade -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex flex-wrap gap-4">
                <button onclick="filtrarPrioridade('todos')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-azul-principal text-white" data-prioridade="todos">
                    <i class="fas fa-list mr-2"></i>Todos
                </button>
                <button onclick="filtrarPrioridade('urgente')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-prioridade="urgente">
                    <i class="fas fa-exclamation-circle mr-2"></i>Urgente
                </button>
                <button onclick="filtrarPrioridade('alta')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-prioridade="alta">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Alta
                </button>
                <button onclick="filtrarPrioridade('normal')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-prioridade="normal">
                    <i class="fas fa-info-circle mr-2"></i>Normal
                </button>
                <button onclick="filtrarPrioridade('baixa')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-prioridade="baixa">
                    <i class="fas fa-bell mr-2"></i>Baixa
                </button>
            </div>
        </div>

        <!-- Comunicados -->
        <div class="space-y-6">
            <?php foreach ($comunicados_por_prioridade as $prioridade => $lista): ?>
                <?php if (!empty($lista)): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 
                            <?php 
                            $cor_header = match($prioridade) {
                                'urgente' => 'bg-red-500',
                                'alta' => 'bg-orange-500',
                                'normal' => 'bg-azul-principal',
                                'baixa' => 'bg-gray-500',
                                default => 'bg-azul-principal'
                            };
                            echo $cor_header;
                            ?>">
                            <h2 class="text-xl font-display font-bold text-white flex items-center gap-2">
                                <i class="fas 
                                    <?php 
                                    $icone_prioridade = match($prioridade) {
                                        'urgente' => 'fa-exclamation-circle',
                                        'alta' => 'fa-exclamation-triangle',
                                        'normal' => 'fa-info-circle',
                                        'baixa' => 'fa-bell',
                                        default => 'fa-info-circle'
                                    };
                                    echo $icone_prioridade;
                                    ?>"></i>
                                <?php echo ucfirst($prioridade); ?>
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <?php foreach ($lista as $comunicado): ?>
                                    <div class="comunicado-card p-4 rounded-xl border border-gray-100 hover:shadow-md transition-all" data-prioridade="<?php echo $prioridade; ?>">
                                        <div class="flex items-start gap-4">
                                            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                                                <?php 
                                                $cor_badge = match($prioridade) {
                                                    'urgente' => 'bg-red-100',
                                                    'alta' => 'bg-orange-100',
                                                    'normal' => 'bg-azul-principal/10',
                                                    'baixa' => 'bg-gray-100',
                                                    default => 'bg-gray-100'
                                                };
                                                echo $cor_badge;
                                                ?>">
                                                <i class="fas 
                                                    <?php 
                                                    $icone = match($prioridade) {
                                                        'urgente' => 'fa-exclamation-circle',
                                                        'alta' => 'fa-exclamation-triangle',
                                                        'normal' => 'fa-info-circle',
                                                        'baixa' => 'fa-bell',
                                                        default => 'fa-info-circle'
                                                    };
                                                    echo $icone;
                                                    ?> 
                                                    text-xl 
                                                    <?php 
                                                    $cor_texto = match($prioridade) {
                                                        'urgente' => 'text-red-600',
                                                        'alta' => 'text-orange-600',
                                                        'normal' => 'text-azul-principal',
                                                        'baixa' => 'text-gray-600',
                                                        default => 'text-gray-600'
                                                    };
                                                    echo $cor_texto;
                                                    ?>">
                                                </i>
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-start justify-between mb-2">
                                                    <h3 class="font-semibold text-gray-800 text-lg"><?php echo htmlspecialchars($comunicado['titulo']); ?></h3>
                                                    <span class="text-xs text-gray-400">
                                                        <?php echo date('d/m/Y', strtotime($comunicado['data_publicacao'])); ?>
                                                    </span>
                                                </div>
                                                <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars(substr($comunicado['conteudo'], 0, 150)) . '...'; ?></p>
                                                <div class="flex items-center gap-4 text-sm text-gray-500">
                                                    <span>
                                                        <i class="fas fa-user mr-1"></i>
                                                        <?php echo htmlspecialchars($comunicado['autor_nome']); ?>
                                                    </span>
                                                    <?php if ($comunicado['data_expiracao']): ?>
                                                        <span>
                                                            <i class="fas fa-calendar-times mr-1"></i>
                                                            Expira: <?php echo date('d/m/Y', strtotime($comunicado['data_expiracao'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if (empty($comunicados)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                    <i class="fas fa-bell-slash text-4xl mb-2"></i>
                    <p>Nenhum comunicado encontrado.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function filtrarPrioridade(prioridade) {
            const cards = document.querySelectorAll('.comunicado-card');
            const buttons = document.querySelectorAll('.filtro-btn');
            
            // Atualizar botões
            buttons.forEach(btn => {
                if (btn.dataset.prioridade === prioridade) {
                    btn.classList.remove('bg-gray-100', 'text-gray-700');
                    btn.classList.add('bg-azul-principal', 'text-white');
                } else {
                    btn.classList.remove('bg-azul-principal', 'text-white');
                    btn.classList.add('bg-gray-100', 'text-gray-700');
                }
            });
            
            // Filtrar cards
            cards.forEach(card => {
                if (prioridade === 'todos' || card.dataset.prioridade === prioridade) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
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

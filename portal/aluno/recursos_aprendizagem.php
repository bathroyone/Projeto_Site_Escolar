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

// Obter recursos de aprendizagem
$recursos = [];
try {
    $stmt = $pdo->query("
        SELECT 
            ra.*,
            d.nome as disciplina_nome,
            u.nome_completo as professor_nome
        FROM recursos_aprendizagem ra
        LEFT JOIN disciplinas d ON ra.disciplina_id = d.id
        LEFT JOIN usuarios u ON ra.professor_id = u.id
        WHERE (ra.publico_alvo = 'todos' OR ra.publico_alvo = 'alunos')
        AND ra.status = 'ativo'
        AND ra.data_publicacao <= CURDATE()
        ORDER BY ra.data_publicacao DESC
        LIMIT 50
    ");
    $recursos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter recursos: " . $e->getMessage());
}

// Agrupar por tipo
$recursos_por_tipo = [
    'video' => [],
    'artigo' => [],
    'exercicio' => [],
    'quiz' => [],
    'outro' => []
];

foreach ($recursos as $recurso) {
    $tipo = $recurso['tipo'] ?? 'outro';
    if (isset($recursos_por_tipo[$tipo])) {
        $recursos_por_tipo[$tipo][] = $recurso;
    } else {
        $recursos_por_tipo['outro'][] = $recurso;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recursos de Aprendizagem | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Recursos de Aprendizagem</h1>
                <p class="text-gray-600 mt-2">Materiais extras para aprimorar seus estudos</p>
            </div>
        </div>

        <!-- Filtros por Tipo -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex flex-wrap gap-4">
                <button onclick="filtrarTipo('todos')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-azul-principal text-white" data-tipo="todos">
                    <i class="fas fa-th-large mr-2"></i>Todos
                </button>
                <button onclick="filtrarTipo('video')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="video">
                    <i class="fas fa-video mr-2"></i>Vídeos
                </button>
                <button onclick="filtrarTipo('artigo')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="artigo">
                    <i class="fas fa-file-alt mr-2"></i>Artigos
                </button>
                <button onclick="filtrarTipo('exercicio')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="exercicio">
                    <i class="fas fa-pencil-alt mr-2"></i>Exercícios
                </button>
                <button onclick="filtrarTipo('quiz')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="quiz">
                    <i class="fas fa-question-circle mr-2"></i>Quizzes
                </button>
            </div>
        </div>

        <!-- Recursos -->
        <div class="space-y-6">
            <?php foreach ($recursos_por_tipo as $tipo => $lista): ?>
                <?php if (!empty($lista)): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100 
                            <?php 
                            $cor_header = match($tipo) {
                                'video' => 'bg-red-500',
                                'artigo' => 'bg-blue-500',
                                'exercicio' => 'bg-green-500',
                                'quiz' => 'bg-purple-500',
                                'outro' => 'bg-gray-500',
                                default => 'bg-azul-principal'
                            };
                            echo $cor_header;
                            ?>">
                            <h2 class="text-xl font-display font-bold text-white flex items-center gap-2">
                                <i class="fas 
                                    <?php 
                                    $icone_tipo = match($tipo) {
                                        'video' => 'fa-video',
                                        'artigo' => 'fa-file-alt',
                                        'exercicio' => 'fa-pencil-alt',
                                        'quiz' => 'fa-question-circle',
                                        'outro' => 'fa-folder',
                                        default => 'fa-file'
                                    };
                                    echo $icone_tipo;
                                    ?>"></i>
                                <?php echo ucfirst($tipo); ?>
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php foreach ($lista as $recurso): ?>
                                    <div class="recurso-card p-4 rounded-xl border border-gray-100 hover:shadow-md transition-all" data-tipo="<?php echo $tipo; ?>">
                                        <div class="flex items-start gap-4 mb-3">
                                            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                                                <?php 
                                                $cor_badge = match($tipo) {
                                                    'video' => 'bg-red-100',
                                                    'artigo' => 'bg-blue-100',
                                                    'exercicio' => 'bg-green-100',
                                                    'quiz' => 'bg-purple-100',
                                                    'outro' => 'bg-gray-100',
                                                    default => 'bg-gray-100'
                                                };
                                                echo $cor_badge;
                                                ?>">
                                                <i class="fas 
                                                    <?php 
                                                    $icone = match($tipo) {
                                                        'video' => 'fa-video',
                                                        'artigo' => 'fa-file-alt',
                                                        'exercicio' => 'fa-pencil-alt',
                                                        'quiz' => 'fa-question-circle',
                                                        'outro' => 'fa-folder',
                                                        default => 'fa-file'
                                                    };
                                                    echo $icone;
                                                    ?> 
                                                    text-xl 
                                                    <?php 
                                                    $cor_texto = match($tipo) {
                                                        'video' => 'text-red-600',
                                                        'artigo' => 'text-blue-600',
                                                        'exercicio' => 'text-green-600',
                                                        'quiz' => 'text-purple-600',
                                                        'outro' => 'text-gray-600',
                                                        default => 'text-gray-600'
                                                    };
                                                    echo $cor_texto;
                                                    ?>">
                                                </i>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-semibold text-gray-800 text-lg mb-1"><?php echo htmlspecialchars($recurso['titulo']); ?></h3>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($recurso['disciplina_nome'] ?? 'Geral'); ?></p>
                                            </div>
                                        </div>
                                        <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars(substr($recurso['descricao'] ?? '', 0, 80)) . '...'; ?></p>
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-400">
                                                <?php echo date('d/m/Y', strtotime($recurso['data_publicacao'])); ?>
                                            </span>
                                            <a href="<?php echo htmlspecialchars($recurso['url'] ?? '#'); ?>" target="_blank" class="px-4 py-2 bg-azul-principal text-white rounded-lg text-sm font-semibold hover:bg-azul-escuro transition-colors">
                                                <i class="fas fa-external-link-alt mr-1"></i>
                                                Acessar
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if (empty($recursos)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                    <i class="fas fa-book-open text-4xl mb-2"></i>
                    <p>Nenhum recurso de aprendizagem disponível.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('user-menu');
            menu.classList.toggle('hidden');
        }

        function filtrarTipo(tipo) {
            const cards = document.querySelectorAll('.recurso-card');
            const buttons = document.querySelectorAll('.filtro-btn');
            
            // Atualizar botões
            buttons.forEach(btn => {
                if (btn.dataset.tipo === tipo) {
                    btn.classList.remove('bg-gray-100', 'text-gray-700');
                    btn.classList.add('bg-azul-principal', 'text-white');
                } else {
                    btn.classList.remove('bg-azul-principal', 'text-white');
                    btn.classList.add('bg-gray-100', 'text-gray-700');
                }
            });
            
            // Filtrar cards
            cards.forEach(card => {
                if (tipo === 'todos' || card.dataset.tipo === tipo) {
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

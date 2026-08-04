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

// Obter eventos do calendário (provas, trabalhos, aulas)
$eventos = [];
try {
    // Provas agendadas
    $stmt = $pdo->query("
        SELECT 
            af.id,
            af.titulo as titulo,
            af.data_avaliacao as data,
            af.descricao as descricao,
            'prova' as tipo,
            d.nome as disciplina_nome,
            u.nome_completo as professor_nome
        FROM avaliacoes_formativas af
        JOIN disciplinas d ON af.disciplina_id = d.id
        JOIN usuarios u ON af.professor_id = u.id
        WHERE af.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        AND af.data_avaliacao >= CURDATE() - INTERVAL 30 DAY
        AND af.status = 'ativo'
    ");
    $stmt->execute([$turma, $serie]);
    $eventos = array_merge($eventos, $stmt->fetchAll());
    
    // Trabalhos pendentes
    $stmt = $pdo->query("
        SELECT 
            ce.id,
            ce.titulo as titulo,
            ce.data_limite as data,
            ce.descricao as descricao,
            'trabalho' as tipo,
            d.nome as disciplina_nome,
            u.nome_completo as professor_nome
        FROM controle_entregas ce
        JOIN disciplinas d ON ce.disciplina_id = d.id
        JOIN usuarios u ON ce.professor_id = u.id
        WHERE ce.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        AND ce.data_limite >= CURDATE() - INTERVAL 30 DAY
        AND ce.status = 'ativo'
    ");
    $stmt->execute([$turma, $serie]);
    $eventos = array_merge($eventos, $stmt->fetchAll());
    
    // Aulas agendadas
    $stmt = $pdo->query("
        SELECT 
            cd.id,
            cd.conteudo as titulo,
            cd.data_aula as data,
            cd.observacoes as descricao,
            'aula' as tipo,
            d.nome as disciplina_nome,
            u.nome_completo as professor_nome
        FROM chamadas_digitais cd
        JOIN disciplinas d ON cd.disciplina_id = d.id
        JOIN usuarios u ON cd.professor_id = u.id
        WHERE cd.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        AND cd.data_aula >= CURDATE() - INTERVAL 30 DAY
    ");
    $stmt->execute([$turma, $serie]);
    $eventos = array_merge($eventos, $stmt->fetchAll());
    
    // Ordenar por data
    usort($eventos, function($a, $b) {
        return strtotime($a['data']) - strtotime($b['data']);
    });
} catch (PDOException $e) {
    error_log("Erro ao obter eventos: " . $e->getMessage());
}

// Agrupar eventos por mês
$eventos_por_mes = [];
foreach ($eventos as $evento) {
    $mes = date('Y-m', strtotime($evento['data']));
    if (!isset($eventos_por_mes[$mes])) {
        $eventos_por_mes[$mes] = [];
    }
    $eventos_por_mes[$mes][] = $evento;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Calendário Escolar</h1>
                <p class="text-gray-600 mt-2">Provas, trabalhos e atividades</p>
            </div>
        </div>

        <!-- Filtros Rápidos -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex flex-wrap gap-4">
                <button onclick="filtrarTipo('todos')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-azul-principal text-white" data-tipo="todos">
                    <i class="fas fa-calendar-alt mr-2"></i>Todos
                </button>
                <button onclick="filtrarTipo('prova')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="prova">
                    <i class="fas fa-file-alt mr-2"></i>Provas
                </button>
                <button onclick="filtrarTipo('trabalho')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="trabalho">
                    <i class="fas fa-tasks mr-2"></i>Trabalhos
                </button>
                <button onclick="filtrarTipo('aula')" class="filtro-btn px-4 py-2 rounded-xl font-semibold transition-all bg-gray-100 text-gray-700 hover:bg-gray-200" data-tipo="aula">
                    <i class="fas fa-chalkboard-teacher mr-2"></i>Aulas
                </button>
            </div>
        </div>

        <!-- Eventos por Mês -->
        <div class="space-y-8">
            <?php foreach ($eventos_por_mes as $mes => $eventos): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-azul-principal to-azul-claro">
                        <h2 class="text-xl font-display font-bold text-white">
                            <i class="fas fa-calendar mr-2"></i>
                            <?php echo strftime('%B %Y', strtotime($mes . '-01')); ?>
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($eventos as $evento): ?>
                                <div class="evento-card p-4 rounded-xl hover:shadow-md transition-all border border-gray-100" data-tipo="<?php echo $evento['tipo']; ?>">
                                    <div class="flex items-start gap-4">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0
                                            <?php 
                                            $cor_tipo = match($evento['tipo']) {
                                                'prova' => 'bg-red-100',
                                                'trabalho' => 'bg-orange-100',
                                                'aula' => 'bg-blue-100',
                                                default => 'bg-gray-100'
                                            };
                                            echo $cor_tipo;
                                            ?>">
                                            <i class="fas 
                                                <?php 
                                                $icone_tipo = match($evento['tipo']) {
                                                    'prova' => 'fa-file-alt',
                                                    'trabalho' => 'fa-tasks',
                                                    'aula' => 'fa-chalkboard-teacher',
                                                    default => 'fa-calendar'
                                                };
                                                echo $icone_tipo;
                                                ?> 
                                                text-xl 
                                                <?php 
                                                $cor_texto = match($evento['tipo']) {
                                                    'prova' => 'text-red-600',
                                                    'trabalho' => 'text-orange-600',
                                                    'aula' => 'text-blue-600',
                                                    default => 'text-gray-600'
                                                };
                                                echo $cor_texto;
                                                ?>">
                                            </i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-start justify-between mb-2">
                                                <div>
                                                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                                                    <p class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($evento['disciplina_nome'] ?? ''); ?> | 
                                                        <?php echo htmlspecialchars($evento['professor_nome'] ?? ''); ?>
                                                    </p>
                                                </div>
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold 
                                                    <?php 
                                                    $cor_badge = match($evento['tipo']) {
                                                        'prova' => 'bg-red-100 text-red-600',
                                                        'trabalho' => 'bg-orange-100 text-orange-600',
                                                        'aula' => 'bg-blue-100 text-blue-600',
                                                        default => 'bg-gray-100 text-gray-600'
                                                    };
                                                    echo $cor_badge;
                                                    ?>">
                                                    <?php echo ucfirst($evento['tipo']); ?>
                                                </span>
                                            </div>
                                            <div class="flex items-center gap-4 text-sm text-gray-500 mb-2">
                                                <span>
                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                    <?php echo date('d/m/Y', strtotime($evento['data'])); ?>
                                                </span>
                                                <span>
                                                    <i class="fas fa-clock mr-1"></i>
                                                    <?php echo date('H:i', strtotime($evento['data'])); ?>
                                                </span>
                                            </div>
                                            <p class="text-gray-600 text-sm"><?php echo htmlspecialchars(substr($evento['descricao'] ?? '', 0, 100)) . '...'; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (empty($eventos_por_mes)): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center text-gray-500">
                    <i class="fas fa-calendar-times text-4xl mb-2"></i>
                    <p>Nenhum evento agendado.</p>
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
            const cards = document.querySelectorAll('.evento-card');
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

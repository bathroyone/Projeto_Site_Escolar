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

// Obter horários do aluno
$horarios = [];
try {
    $stmt = $pdo->query("
        SELECT 
            h.*,
            d.nome as disciplina_nome,
            u.nome_completo as professor_nome,
            s.nome as sala_nome
        FROM horarios h
        JOIN disciplinas d ON h.disciplina_id = d.id
        JOIN usuarios u ON h.professor_id = u.id
        LEFT JOIN salas s ON h.sala_id = s.id
        WHERE h.turma_id = (SELECT id FROM turmas WHERE nome = ? AND serie = ? LIMIT 1)
        AND h.status = 'ativo'
        ORDER BY h.dia_semana, h.horario_inicio
    ");
    $stmt->execute([$turma, $serie]);
    $horarios = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Erro ao obter horários: " . $e->getMessage());
}

// Agrupar por dia da semana
$horarios_por_dia = [];
$dias_semana = [
    1 => 'Segunda-feira',
    2 => 'Terça-feira',
    3 => 'Quarta-feira',
    4 => 'Quinta-feira',
    5 => 'Sexta-feira',
    6 => 'Sábado'
];

foreach ($horarios as $horario) {
    $dia = $horario['dia_semana'];
    if (!isset($horarios_por_dia[$dia])) {
        $horarios_por_dia[$dia] = [];
    }
    $horarios_por_dia[$dia][] = $horario;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Horários e Grade de Aulas | Portal de Gestão Escolar</title>
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
                <h1 class="text-3xl font-display font-bold text-azul-principal">Horários e Grade de Aulas</h1>
                <p class="text-gray-600 mt-2">Visualize sua grade horária semanal</p>
            </div>
            <button onclick="window.print()" class="px-6 py-3 bg-gradient-to-r from-azul-principal to-verde-complementar text-white font-bold rounded-xl hover:from-azul-escuro hover:to-verde-claro transition-all shadow-lg">
                <i class="fas fa-print mr-2"></i>Imprimir
            </button>
        </div>

        <!-- Informações da Turma -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-azul-principal/10 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-school text-azul-principal text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Turma: <?php echo htmlspecialchars($turma); ?></h2>
                    <p class="text-gray-600">Série: <?php echo htmlspecialchars($serie); ?></p>
                </div>
            </div>
        </div>

        <!-- Grade Horária -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-xl font-display font-bold text-azul-principal">Grade Horária Semanal</h2>
            </div>
            <div class="p-6">
                <?php foreach ($dias_semana as $dia_num => $dia_nome): ?>
                    <div class="mb-6 last:mb-0">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 bg-azul-principal rounded-xl flex items-center justify-center">
                                <i class="fas fa-calendar-day text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800"><?php echo $dia_nome; ?></h3>
                        </div>
                        
                        <?php if (isset($horarios_por_dia[$dia_num]) && !empty($horarios_por_dia[$dia_num])): ?>
                            <div class="space-y-3">
                                <?php foreach ($horarios_por_dia[$dia_num] as $horario): ?>
                                    <div class="p-4 bg-gray-50 rounded-xl border border-gray-100 hover:shadow-md transition-all">
                                        <div class="flex items-start justify-between">
                                            <div class="flex items-start gap-4">
                                                <div class="w-12 h-12 bg-gradient-to-br from-azul-principal to-verde-complementar rounded-xl flex items-center justify-center flex-shrink-0">
                                                    <i class="fas fa-clock text-white text-xl"></i>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-3 mb-1">
                                                        <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($horario['disciplina_nome']); ?></span>
                                                        <span class="text-sm text-gray-500">
                                                            <?php echo date('H:i', strtotime($horario['horario_inicio'])); ?> - 
                                                            <?php echo date('H:i', strtotime($horario['horario_fim'])); ?>
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-gray-600">
                                                        <i class="fas fa-user-tie mr-1"></i>
                                                        <?php echo htmlspecialchars($horario['professor_nome']); ?>
                                                    </p>
                                                    <?php if ($horario['sala_nome']): ?>
                                                        <p class="text-sm text-gray-600">
                                                            <i class="fas fa-door-open mr-1"></i>
                                                            Sala: <?php echo htmlspecialchars($horario['sala_nome']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-4 bg-gray-50 rounded-xl text-center text-gray-500">
                                <i class="fas fa-coffee mr-2"></i>
                                Sem aulas neste dia
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($horarios_por_dia)): ?>
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-calendar-times text-4xl mb-2"></i>
                        <p>Nenhum horário cadastrado para sua turma.</p>
                    </div>
                <?php endif; ?>
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
